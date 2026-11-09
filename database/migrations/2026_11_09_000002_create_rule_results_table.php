<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The result of one rule in one analysis. Reasons and evidence can contain
 * policy text, so they are encrypted at rest and deleted with the analysis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rule_results', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('analysis_id')->constrained()->cascadeOnDelete();
            $table->string('rule_id', 30);

            $table->string('requirement_type', 20);
            $table->boolean('applicable');
            $table->string('applied_override', 60)->nullable();

            $table->string('status', 30)->nullable();
            $table->string('model_status', 30)->nullable();
            $table->text('reason_id')->nullable();
            $table->text('reason_en')->nullable();
            $table->longText('evidence')->nullable();
            $table->json('verification')->nullable();

            $table->timestamps();

            $table->unique(['analysis_id', 'rule_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_results');
    }
};
