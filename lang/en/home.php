<?php

declare(strict_types=1);

return [
    'eyebrow' => 'Privacy policy checker · UU No. 27 Tahun 2022',
    'title' => 'Check a privacy policy against the UU PDP',
    'intro' => 'CekPDP compares a privacy policy with :count transparency and disclosure items under Indonesia\'s Personal Data Protection Law (UU No. 27 Tahun 2022). For each item you get a status, a reason, quotes from the policy and the legal basis.',
    'scope' => 'Only the policy text is assessed, not whether the organisation actually complies.',

    'text' => [
        'label' => 'Privacy policy text',
        'hint' => 'Paste the full policy in Indonesian or English, at least :min and at most :max characters. Longer text is never cut short; you will be told its size instead.',
        'count' => ':count characters',
        'placeholder' => 'Paste the privacy policy here…',
    ],

    'facts' => [
        'version' => 'Checklist',
        'reviewed' => 'Reviewed',
        'items' => 'Items',
        'items_value' => ':legal legal · :best practice',
        'method' => 'Assessed by',
        'method_mock' => 'Offline, keywords',
        'method_ai' => 'Claude (Anthropic)',
    ],

    'samples' => [
        'label' => 'Try a fictional sample:',
        'loaded' => 'Sample loaded: :title. The context answers match the sample.',
        'id-01-belanja-lengkap' => 'Online shop, complete',
        'id-03-fintech-sedang' => 'Fintech app, average',
        'id-09-umkm-minim' => 'Small bakery, minimal',
        'en-04-gdpr-style' => 'GDPR-style service',
    ],

    'context' => [
        'heading' => 'Context',
        'optional' => 'optional',
        'intro' => 'Your answers decide which items apply. If in doubt, choose "Not sure": the related items are still checked.',
    ],

    'answers' => [
        'yes' => 'Yes',
        'no' => 'No',
        'unsure' => 'Not sure',
    ],

    'disclosure' => [
        'heading' => 'Before you submit',
        'mock' => 'Offline mode is on: the text is analysed on the CekPDP server with keyword matching and is not sent to a third-party AI provider. Results in this mode are much less accurate than an AI analysis.',
        'ai' => 'The policy text will be sent to :provider, a third-party AI provider, for analysis.',
        'confidential' => 'Don\'t submit confidential information or personal data. Privacy policies that are or will be published are generally fine to analyse.',
        'retention' => 'The text is stored encrypted and deleted automatically :minutes minutes after the analysis finishes. You can also delete it sooner.',
        'acknowledge' => 'I have read the notice above and am not submitting confidential information.',
    ],

    'submit' => 'Analyse policy',
    'submitting' => 'Submitting…',
    'submit_hint' => 'The analysis runs in the background; you will see its progress after submitting.',
    'errors_heading' => 'Please check the following:',

    'about' => [
        'heading' => 'About the checklist',
        'ruleset' => 'Version :version, last reviewed :date: :legal legal requirements and :best best practices. Every article reference was verified against the official copy of UU No. 27 Tahun 2022.',
        'regulations' => 'Implementing regulations',
        'not_used' => 'Not used in this checklist.',
        'status' => [
            'in_force' => 'In force',
            'promulgated_not_in_force' => 'Not yet in force',
            'not_enacted' => 'Not enacted',
        ],
    ],
];
