<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AnalysisStage;
use App\Enums\AnalysisStatus;
use App\Enums\InputKind;
use Carbon\CarbonInterface;
use Database\Factories\AnalysisFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One analysis of one privacy policy.
 *
 * Policy text and sections are encrypted at rest (Laravel's encrypted casts,
 * keyed by APP_KEY). When the retention window ends (expires_at), the analysis
 * is deleted together with its results.
 */
#[Fillable([
    'owner_hash',
    'status',
    'stage',
    'progress',
    'progress_meta',
    'input_kind',
    'input_meta',
    'context',
    'locale',
    'ruleset_id',
    'ruleset_version',
    'provider',
    'model',
    'source_text',
    'sections',
    'text_sha256',
    'injection_flags',
    'score',
    'error_code',
    'started_at',
    'completed_at',
    'expires_at',
])]
class Analysis extends Model
{
    /** @use HasFactory<AnalysisFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * @return HasMany<RuleResult, $this>
     */
    public function ruleResults(): HasMany
    {
        return $this->hasMany(RuleResult::class);
    }

    public function isFinished(): bool
    {
        return $this->status->isFinished();
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ownedBy(Builder $query, string $ownerHash): void
    {
        $query->where('owner_hash', $ownerHash);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function expired(Builder $query, ?CarbonInterface $now = null): void
    {
        $query->whereNotNull('expires_at')->where('expires_at', '<=', $now ?? now());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AnalysisStatus::class,
            'stage' => AnalysisStage::class,
            'progress' => 'integer',
            'progress_meta' => 'array',
            'input_kind' => InputKind::class,
            'input_meta' => 'array',
            'context' => 'array',
            'source_text' => 'encrypted',
            'sections' => 'encrypted:array',
            'injection_flags' => 'array',
            'score' => 'array',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
