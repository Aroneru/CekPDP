<?php

declare(strict_types=1);

return [
    'tagline' => 'Privacy policy transparency checker for Indonesia\'s UU PDP',
    'skip_to_content' => 'Skip to main content',
    'new_analysis' => 'New analysis',

    'language' => [
        'label' => 'Interface language',
        'short' => 'Language',
        'id' => 'Bahasa Indonesia',
        'en' => 'English',
    ],

    'notice' => [
        'label' => 'Notice',
        'short' => 'Not legal advice',
        'body' => 'This tool gives an automated initial review and is not legal advice. Consult a qualified professional before making compliance decisions.',
    ],

    'errors' => [
        '404' => [
            'title' => 'Page not found',
            'body' => 'The page you are looking for doesn\'t exist.',
        ],
        '419' => [
            'title' => 'This page has expired',
            'body' => 'The form was open too long to be submitted. Reload the page, then paste and submit the policy text again.',
        ],
        '429' => [
            'title' => 'Too many requests',
            'body' => 'You sent too many requests in a short time. Please wait a moment and try again.',
        ],
    ],

    'footer' => [
        'ruleset' => 'UU PDP checklist version :version, last reviewed :date.',
        'scope' => 'CekPDP assesses only what the policy text says, not whether an organisation complies in practice.',
        'translation' => 'English legal text is an unofficial translation; the Indonesian text is authoritative.',
    ],
];
