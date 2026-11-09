<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Ruleset
    |--------------------------------------------------------------------------
    |
    | Versioned checklists live in resources/rulesets/{id}/{version}.yaml.
    | Released versions are never edited; a change means a new version file.
    | Analyses record the version they used, so old versions stay loadable.
    |
    | Validated ruleset data is cached in `cache_directory`, keyed by a hash
    | of the ruleset file and its schema, so edits are always re-validated.
    |
    */

    'ruleset' => [
        'id' => 'uu-pdp-27-2022',
        'directory' => resource_path('rulesets'),
        'schema' => resource_path('rulesets/ruleset.schema.json'),
        'active_version' => env('CEKPDP_RULESET_VERSION', '1.0.0'),
        'cache_directory' => storage_path('framework/cache/rulesets'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI provider
    |--------------------------------------------------------------------------
    |
    | "mock" runs entirely offline with deterministic keyword matching, so the
    | app and its tests work without an API key. Nothing is sent to a third
    | party. "claude" is added in Milestone 3.
    |
    | Rules are sent to the provider in batches of `batch_size`. Every answer
    | is checked against `response_schema` and requested once more if invalid.
    |
    */

    'ai' => [
        'provider' => env('CEKPDP_AI_PROVIDER', 'mock'),
        'batch_size' => (int) env('CEKPDP_AI_BATCH_SIZE', 8),
        'response_schema' => resource_path('schemas/evaluation-response.schema.json'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Input limits and abuse controls
    |--------------------------------------------------------------------------
    |
    | Text longer than the maximum is rejected with its actual size; it is
    | never truncated. The hourly limit counts accepted analyses per visitor
    | (keyed by a keyed hash of the IP address, kept for one hour).
    |
    */

    'limits' => [
        'min_input_characters' => 100,
        'max_input_characters' => (int) env('CEKPDP_MAX_INPUT_CHARS', 300000),
        'analyses_per_hour' => (int) env('CEKPDP_ANALYSES_PER_HOUR', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Policy text, sections and results are encrypted at rest and deleted
    | `minutes` after an analysis finishes (or fails). An analysis that never
    | finishes (for example, because no queue worker is running) is deleted
    | `pending_minutes` after it was submitted. The scheduler runs
    | `cekpdp:purge-expired` every minute.
    |
    */

    'retention' => [
        'minutes' => (int) env('CEKPDP_RETENTION_MINUTES', 30),
        'pending_minutes' => (int) env('CEKPDP_PENDING_MINUTES', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Evaluation dataset and samples
    |--------------------------------------------------------------------------
    |
    | Original fictional policies with the expected status of every rule. The
    | listed ones are offered as samples on the home page.
    |
    */

    'evaluation' => [
        'directory' => base_path('evaluation'),
    ],

    'samples' => [
        'id-01-belanja-lengkap',
        'id-03-fintech-sedang',
        'id-09-umkm-minim',
        'en-04-gdpr-style',
    ],

];
