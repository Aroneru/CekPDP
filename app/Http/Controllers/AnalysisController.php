<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Evaluation\SamplePolicies;
use App\Domain\Evaluation\SamplePolicy;
use App\Domain\Extraction\TextCleaner;
use App\Domain\Reporting\ReportBuilder;
use App\Domain\Rules\ContextProfile;
use App\Domain\Rules\Ruleset;
use App\Domain\Rules\RulesetRepository;
use App\Enums\AnalysisStage;
use App\Enums\AnalysisStatus;
use App\Enums\ContextAnswer;
use App\Enums\InputKind;
use App\Http\Support\AnalysisOwner;
use App\Http\Support\VisitorKey;
use App\Jobs\AnalyzePolicyJob;
use App\Models\Analysis;
use App\Services\AnalysisPurger;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Number;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

/**
 * The analysis flow: the form, submission, progress and results, and deletion.
 *
 * The form is a plain HTML form (not a Livewire upload), so nothing reaches the
 * server before the visitor has read the notice and submitted. When validation
 * fails the form is rendered again directly instead of redirecting, so pasted
 * policy text is never put in the session.
 */
final class AnalysisController extends Controller
{
    private const SUBMISSION_WINDOW_SECONDS = 3600;

    public function __construct(
        private readonly RulesetRepository $rulesets,
        private readonly AnalysisOwner $owner,
        private readonly SamplePolicies $samples,
    ) {}

    public function create(Request $request): Response
    {
        $ruleset = $this->rulesets->active();
        $sample = $this->samples->find($request->string('sample')->toString());

        return $this->form(
            ruleset: $ruleset,
            text: $sample->text ?? '',
            answers: $this->answers($ruleset, $sample->context ?? []),
            sample: $sample,
        );
    }

    public function store(Request $request, ValidationFactory $validation, TextCleaner $cleaner, RateLimiter $limiter, AnalysisPurger $purger): Response|RedirectResponse
    {
        $ruleset = $this->rulesets->active();
        $raw = $request->input('text');
        $text = is_string($raw) ? $cleaner->clean($raw) : '';
        $contextInput = $this->contextInput($request);

        $validator = $validation->make($request->only(['text', 'context', 'acknowledge']), [
            'text' => ['required', 'string'],
            'context' => ['nullable', 'array'],
            'context.*' => ['nullable', 'string', Rule::in(array_column(ContextAnswer::cases(), 'value'))],
            'acknowledge' => ['accepted'],
        ]);

        $validator->after(function (Validator $validator) use ($text, $ruleset, $contextInput): void {
            if (! $validator->errors()->has('text')) {
                $this->checkLength($validator, $text);
            }

            try {
                ContextProfile::fromInput($ruleset, $contextInput);
            } catch (InvalidArgumentException) {
                $validator->errors()->add('context', __('validation.custom.context.invalid'));
            }
        });

        if ($validator->fails()) {
            return $this->form($ruleset, is_string($raw) ? $raw : '', $this->answers($ruleset, $contextInput), errors: $validator->errors(), status: 422);
        }

        $key = 'analysis-submissions:'.VisitorKey::for($request);
        $max = config()->integer('cekpdp.limits.analyses_per_hour');

        if ($limiter->tooManyAttempts($key, $max)) {
            $seconds = $limiter->availableIn($key);
            $errors = new MessageBag(['form' => __('analysis.errors.rate_limited', [
                'max' => $max,
                'minutes' => max(1, (int) ceil($seconds / 60)),
            ])]);

            return $this->form($ruleset, (string) $raw, $this->answers($ruleset, $contextInput), errors: $errors, status: 429)
                ->header('Retry-After', (string) $seconds);
        }

        $limiter->hit($key, self::SUBMISSION_WINDOW_SECONDS);

        // Belt and braces: expired analyses are also deleted here, in case the
        // scheduler is not running.
        $purger->purgeExpired();

        $analysis = Analysis::query()->create([
            'owner_hash' => $this->owner->hash(),
            'status' => AnalysisStatus::Queued,
            'stage' => AnalysisStage::Queued,
            'progress' => 0,
            'input_kind' => InputKind::Text,
            'input_meta' => ['characters' => mb_strlen($text)],
            'context' => ContextProfile::fromInput($ruleset, $contextInput)->toArray(),
            'locale' => app()->getLocale(),
            'ruleset_id' => $ruleset->id,
            'ruleset_version' => $ruleset->version,
            'provider' => config()->string('cekpdp.ai.provider'),
            'source_text' => $text,
            'text_sha256' => hash('sha256', $text),
            'expires_at' => now()->addMinutes(config()->integer('cekpdp.retention.pending_minutes')),
        ]);

        AnalyzePolicyJob::dispatch($analysis->id);

        return redirect()->route('analyses.show', $analysis->id);
    }

    public function show(string $analysis, ReportBuilder $reports): Response
    {
        $model = $this->find($analysis);

        if ($model === null) {
            return response()->view('pages.analysis-missing', [
                'retentionMinutes' => config()->integer('cekpdp.retention.minutes'),
            ], 404);
        }

        return match ($model->status) {
            AnalysisStatus::Queued, AnalysisStatus::Processing => response()->view('pages.analysis-progress', ['analysis' => $model]),
            AnalysisStatus::Failed => response()->view('pages.analysis-failed', ['analysis' => $model]),
            AnalysisStatus::Completed => response()->view('pages.analysis-results', ['report' => $reports->build($model)]),
        };
    }

    public function destroy(string $analysis, AnalysisPurger $purger): RedirectResponse
    {
        $model = $this->find($analysis);

        if ($model !== null) {
            $purger->delete($model);
        }

        return redirect()->route('home')->with('status', __('analysis.deleted'));
    }

    private function find(string $id): ?Analysis
    {
        $ownerHash = $this->owner->existingHash();

        return $ownerHash === null ? null : Analysis::query()->ownedBy($ownerHash)->find($id);
    }

    private function checkLength(Validator $validator, string $text): void
    {
        $length = mb_strlen($text);
        $min = config()->integer('cekpdp.limits.min_input_characters');
        $max = config()->integer('cekpdp.limits.max_input_characters');
        $locale = app()->getLocale();

        if ($length < $min) {
            $validator->errors()->add('text', __('validation.custom.text.too_short', [
                'min' => Number::format($min, locale: $locale),
                'actual' => Number::format($length, locale: $locale),
            ]));
        } elseif ($length > $max) {
            $validator->errors()->add('text', __('validation.custom.text.too_long', [
                'max' => Number::format($max, locale: $locale),
                'actual' => Number::format($length, locale: $locale),
            ]));
        }
    }

    /**
     * @return array<string, string>
     */
    private function contextInput(Request $request): array
    {
        $input = $request->input('context');
        $context = [];

        foreach (is_array($input) ? $input : [] as $key => $answer) {
            if (is_string($key) && is_string($answer)) {
                $context[$key] = $answer;
            }
        }

        return $context;
    }

    /**
     * Answers to pre-select in the form; invalid input falls back to the defaults.
     *
     * @param  array<string, string>  $input
     * @return array<string, string>
     */
    private function answers(Ruleset $ruleset, array $input): array
    {
        try {
            return ContextProfile::fromInput($ruleset, $input)->toArray();
        } catch (InvalidArgumentException) {
            return ContextProfile::defaults($ruleset)->toArray();
        }
    }

    /**
     * @param  array<string, string>  $answers
     */
    private function form(Ruleset $ruleset, string $text, array $answers, ?SamplePolicy $sample = null, ?MessageBag $errors = null, int $status = 200): Response
    {
        return response()->view('pages.home', [
            'ruleset' => $ruleset,
            'samples' => $this->samples->all(),
            'sample' => $sample,
            'text' => $text,
            'answers' => $answers,
            'provider' => config()->string('cekpdp.ai.provider'),
            'minCharacters' => config()->integer('cekpdp.limits.min_input_characters'),
            'maxCharacters' => config()->integer('cekpdp.limits.max_input_characters'),
            'retentionMinutes' => config()->integer('cekpdp.retention.minutes'),
            'errors' => (new ViewErrorBag)->put('default', $errors ?? new MessageBag),
        ], $status);
    }
}
