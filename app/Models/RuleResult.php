<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RequirementType;
use App\Enums\RuleStatus;
use Database\Factories\RuleResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The result of one rule within an analysis.
 *
 * `status` is the final status after evidence verification; `model_status` is
 * what the AI (or mock) provider returned, kept for transparency when a result
 * was downgraded to needs human review.
 */
#[Fillable([
    'analysis_id',
    'rule_id',
    'requirement_type',
    'applicable',
    'applied_override',
    'status',
    'model_status',
    'reason_id',
    'reason_en',
    'evidence',
    'verification',
])]
class RuleResult extends Model
{
    /** @use HasFactory<RuleResultFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Analysis, $this>
     */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requirement_type' => RequirementType::class,
            'applicable' => 'boolean',
            'status' => RuleStatus::class,
            'model_status' => RuleStatus::class,
            'reason_id' => 'encrypted',
            'reason_en' => 'encrypted',
            'evidence' => 'encrypted:array',
            'verification' => 'array',
        ];
    }
}
