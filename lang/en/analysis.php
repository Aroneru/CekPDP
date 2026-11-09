<?php

declare(strict_types=1);

return [
    'status' => [
        'addressed' => 'Addressed',
        'partially_addressed' => 'Partially addressed',
        'not_found' => 'Not found',
        'needs_human_review' => 'Needs human review',
        'not_applicable' => 'Not applicable',
    ],

    'type' => [
        'legal' => 'Legal requirement',
        'best_practice' => 'Best practice',
    ],

    'interpretive' => 'Interpretation',

    'stages' => [
        'queued' => 'Waiting in the queue',
        'extracting' => 'Preparing the text',
        'structuring' => 'Detecting the language and sections',
        'evaluating' => 'Assessing each item',
        'verifying' => 'Verifying quotes',
        'scoring' => 'Calculating coverage',
        'done' => 'Done',
    ],

    'stage_state' => [
        'done' => 'done',
        'current' => 'in progress',
        'pending' => 'not started',
    ],

    'progress' => [
        'eyebrow' => 'Analysis running',
        'title' => 'Analysing the policy',
        'intro' => 'The analysis runs in the background. This page updates by itself and shows the results when they are ready.',
        'label' => 'Analysis progress',
        'current' => 'Current stage: :stage.',
        'noscript' => 'JavaScript is off, so this page reloads every 5 seconds.',
        'stalled' => 'Not starting? Analyses are run by a queue worker. If you run CekPDP yourself, make sure the queue worker is running.',
    ],

    'failed' => [
        'eyebrow' => 'Not finished',
        'title' => 'The analysis failed',
        'retry' => 'Start a new analysis',
        'expires' => 'The policy text is deleted automatically :time.',
    ],

    'errors' => [
        'invalid_provider_output' => 'The analysis provider returned output in an unexpected format, even after one retry. No partial results are shown. Please try again.',
        'ruleset_unavailable' => 'The checklist this analysis used could not be loaded.',
        'timeout' => 'The analysis took too long. Please try again.',
        'internal_error' => 'Something unexpected went wrong during the analysis. Please try again.',
        'rate_limited' => 'You have run :max analyses in the past hour. Please try again in :minutes minutes.',
    ],

    'missing' => [
        'title' => 'Analysis not found',
        'body' => 'This analysis doesn\'t exist or has been deleted. Analyses are deleted automatically :minutes minutes after they finish, and can only be opened in the browser that created them.',
    ],

    'deleted' => 'The analysis and its policy text have been deleted.',
    'delete' => 'Delete now',

    'results' => [
        'title' => 'Analysis results',
        'eyebrow' => 'Analysis report',
        'offline_stamp' => 'Offline mode',
        'offline' => 'These results come from offline mode, which uses keyword matching rather than AI. Treat them as a rough indication only.',
        'counted' => 'Counted in the score',
        'not_counted' => 'Doesn\'t affect the score',
        'strip' => ':group: status of each item',
        'retention' => 'The policy text and these results are deleted automatically :time.',
        'summary_heading' => 'Summary',
        'details' => [
            'language' => 'Detected language',
            'size' => 'Size',
            'size_value' => ':characters characters, :words words, :sections sections',
            'provider' => 'Method',
            'ruleset' => 'Checklist',
            'ruleset_value' => 'Version :version (reviewed :date)',
            'completed' => 'Completed',
        ],
        'languages' => [
            'id' => 'Indonesian',
            'en' => 'English',
            'mixed' => 'Indonesian and English',
            'unknown' => 'Could not be determined',
        ],
        'providers' => [
            'mock' => 'Offline mode (keyword matching)',
            'claude' => 'Claude (Anthropic)',
        ],
        'counts_caption' => 'Items per status',
        'counts_type' => 'Type',
        'legal_heading' => 'Legal requirements',
        'best_heading' => 'Best practices',
        'not_applicable_heading' => 'Not applicable',
        'not_applicable_intro' => 'These items don\'t apply, based on your context answers.',
        'context_heading' => 'Context answers',
        'review_heading' => 'Needs human review',
        'review_intro' => 'These items could not be assessed with confidence. Please read the related parts of the policy yourself.',
        'none' => 'None.',
    ],

    'score' => [
        'title' => 'Policy Coverage',
        'explanation' => 'How many of the applicable UU PDP legal requirements the policy discloses. It measures disclosure, not compliance.',
        'formula' => 'Formula: (Addressed + 0.5 × Partially addressed) ÷ (Addressed + Partially addressed + Not found). Every item has equal weight.',
        'arithmetic' => '(:addressed + 0.5 × :partial) ÷ :evaluated = :percent%',
        'excluded' => ':count items that need human review are not counted.',
        'withheld_label' => 'Not shown',
        'withheld' => [
            'too_many_reviews' => 'No score is shown: :count of :total legal requirements (:share%) need human review, more than the :threshold% limit. Review those items first.',
            'no_legal_requirements' => 'No score is shown because no legal requirement applies.',
        ],
        'best_practices' => 'Best practices: :addressed of :total addressed. Best practices don\'t affect the score.',
    ],

    'item' => [
        'reason' => 'Reason',
        'evidence' => 'Quotes from the policy',
        'section' => 'Section :section',
        'no_evidence' => 'No quotes.',
        'unverified' => 'This quote was not found in the policy text.',
        'section_corrected' => 'Found in section :actual (cited as :cited).',
        'downgraded' => [
            'unverified_quote' => 'Changed to Needs human review because a quote could not be verified. Original assessment: :status.',
            'missing_evidence' => 'Changed to Needs human review because no supporting quote was given. Original assessment: :status.',
        ],
        'language_check' => 'Decided by automatic language detection.',
        'look_for' => 'What we look for',
        'criteria' => 'What each status means for this item',
        'basis' => 'Legal basis',
        'related' => 'Related provisions',
        'official_text' => 'Official text (JDIH BPK, PDF p. :page)',
        'reader' => 'Read on Pasal.id (unofficial)',
        'provision_text' => 'Text of the provision',
        'unofficial_translation' => 'Unofficial translation:',
        'modified_by' => 'Read together with :reference.',
        'next_step' => 'Next step',
        'example' => 'Example wording',
        'example_label' => 'Draft, needs legal review',
        'interpretation' => 'Interpretation note',
        'override' => 'Why this item is a best practice',
        'not_applicable_because' => 'Not applicable because of your answer:',
        'answer' => ':question Answer: :answer.',
    ],
];
