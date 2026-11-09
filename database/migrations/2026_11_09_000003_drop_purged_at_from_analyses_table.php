<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expired analyses are deleted outright, together with their results, instead
 * of being kept as purged placeholders. The purge marker is no longer needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropColumn('purged_at');
        });
    }

    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->timestamp('purged_at')->nullable();
        });
    }
};
