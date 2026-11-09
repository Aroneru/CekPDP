<?php

declare(strict_types=1);

/*
 * CekPDP-specific messages. Everything else falls back to Laravel's English
 * validation messages.
 */
return [
    'custom' => [
        'text' => [
            'required' => 'Paste the privacy policy text first.',
            'too_short' => 'The text is too short: :actual characters. Paste the full policy, at least :min characters.',
            'too_long' => 'The text is too long: :actual characters, and the limit is :max characters. It is never cut short automatically. Remove parts that aren\'t the privacy policy, or analyse the policy in several parts.',
        ],
        'acknowledge' => [
            'accepted' => 'Tick the box to confirm you have read the notice and are not submitting confidential information.',
        ],
        'context' => [
            'invalid' => 'A context answer is not valid. Choose Yes, No or Not sure.',
        ],
    ],

    'attributes' => [
        'text' => 'policy text',
        'acknowledge' => 'confirmation',
        'context' => 'context answers',
    ],
];
