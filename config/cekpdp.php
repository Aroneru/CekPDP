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
    */

    'ruleset' => [
        'id' => 'uu-pdp-27-2022',
        'directory' => resource_path('rulesets'),
        'schema' => resource_path('rulesets/ruleset.schema.json'),
        'active_version' => env('CEKPDP_RULESET_VERSION', '1.0.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI provider
    |--------------------------------------------------------------------------
    |
    | "mock" runs entirely offline with deterministic keyword matching, so the
    | app and its tests work without an API key. "claude" is added in
    | Milestone 3.
    |
    */

    'ai' => [
        'provider' => env('CEKPDP_AI_PROVIDER', 'mock'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Evaluation dataset
    |--------------------------------------------------------------------------
    |
    | Original fictional policies with the expected status of every rule.
    |
    */

    'evaluation' => [
        'directory' => base_path('evaluation'),
    ],

];
