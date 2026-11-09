<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One analysis of one policy. There are no user accounts: ownership is tied to
 * a random token in the browser session (stored here only as a hash).
 * Policy text is encrypted at rest and deleted automatically after the
 * retention window (expires_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->char('owner_hash', 64)->index();

            $table->string('status', 20)->index();
            $table->string('stage', 20);
            $table->unsignedTinyInteger('progress')->default(0);
            $table->json('progress_meta')->nullable();

            $table->string('input_kind', 10);
            $table->json('input_meta')->nullable();
            $table->json('context');
            $table->string('locale', 5);

            $table->string('ruleset_id', 60);
            $table->string('ruleset_version', 30);
            $table->string('provider', 30);
            $table->string('model', 100)->nullable();

            $table->longText('source_text')->nullable();
            $table->longText('sections')->nullable();
            $table->char('text_sha256', 64)->nullable();
            $table->json('injection_flags')->nullable();

            $table->json('score')->nullable();
            $table->string('error_code', 60)->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('purged_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
