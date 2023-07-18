<?php

function acym_getPagesForAcl(): array
{
    return [
        'forms' => 'ACYM_SUBSCRIPTION_FORMS',
        'users' => 'ACYM_SUBSCRIBERS',
        'fields' => 'ACYM_CUSTOM_FIELDS',
        'lists' => 'ACYM_LISTS',
        'segments' => 'ACYM_SEGMENTS',
        'campaigns' => 'ACYM_EMAILS',
        'mails' => 'ACYM_TEMPLATES',
        'override' => 'ACYM_EMAILS_OVERRIDE',
        'automation' => 'ACYM_AUTOMATION',
        'queue' => 'ACYM_QUEUE',
        'plugins' => 'ACYM_ADD_ONS',
        'bounces' => 'ACYM_MAILBOX_ACTIONS',
        'stats' => 'ACYM_STATISTICS',
        'configuration' => 'ACYM_CONFIGURATION',
    ];
}
