<?php

function acym_getEmailOverrides()
{
    $emailOverrides = [
        [
            'name' => 'joomla-resetPwd',
            'base_subject' => ['COM_USERS_EMAIL_PASSWORD_RESET_SUBJECT'],
            'base_body' => ['COM_USERS_EMAIL_PASSWORD_RESET_BODY'],
            'new_subject' => '{trans:COM_USERS_EMAIL_PASSWORD_RESET_SUBJECT|param1}',
            'new_body' => '{trans:COM_USERS_EMAIL_PASSWORD_RESET_BODY|param2|param3|param4}',
            'description' => 'ACYM_OVERRIDE_DESC_RESET_PASSWORD',
            'source' => 'joomla',
        ],
        [
            'name' => 'joomla-usernameReminder',
            'base_subject' => ['COM_USERS_EMAIL_USERNAME_REMINDER_SUBJECT'],
            'base_body' => ['COM_USERS_EMAIL_USERNAME_REMINDER_BODY'],
            'new_subject' => '{trans:COM_USERS_EMAIL_USERNAME_REMINDER_SUBJECT|param1}',
            'new_body' => '{trans:COM_USERS_EMAIL_USERNAME_REMINDER_BODY|param2|param3|param4}',
            'description' => 'ACYM_OVERRIDE_DESC_REMIND_USERNAME',
            'source' => 'joomla',
        ],
        [
            'name' => 'joomla-directRegNoPwd',
            'base_subject' => ['COM_USERS_EMAIL_ACCOUNT_DETAILS'],
            'base_body' => ['COM_USERS_EMAIL_REGISTERED_BODY_NOPW'],
            'new_subject' => '{trans:COM_USERS_EMAIL_ACCOUNT_DETAILS|param1|param2}',
            'new_body' => '{trans:COM_USERS_EMAIL_REGISTERED_BODY_NOPW|param3|param4|param5}',
            'description' => 'ACYM_OVERRIDE_DESC_DIRECT_REG_NO_PWD',
            'source' => 'joomla',
        ],
        [
            'name' => 'joomla-directReg',
            'base_subject' => ['COM_USERS_EMAIL_ACCOUNT_DETAILS'],
            'base_body' => ['COM_USERS_EMAIL_REGISTERED_BODY'],
            'new_subject' => '{trans:COM_USERS_EMAIL_ACCOUNT_DETAILS|param1|param2}',
            'new_body' => '{trans:COM_USERS_EMAIL_REGISTERED_BODY|param3|param4|param5|param6|param7}',
            'description' => 'ACYM_OVERRIDE_DESC_DIRECT_REG',
            'source' => 'joomla',
        ],
        [
            'name' => 'joomla-ownActivReg',
            'base_subject' => ['COM_USERS_EMAIL_ACCOUNT_DETAILS'],
            'base_body' => ['COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY'],
            'new_subject' => '{trans:COM_USERS_EMAIL_ACCOUNT_DETAILS|param1|param2}',
            'new_body' => '{trans:COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY|param3|param4|param5|param6|param7|param8}',
            'description' => 'ACYM_OVERRIDE_DESC_REG_ACTIVATION',
            'source' => 'joomla',
        ],
        [
            'name' => 'joomla-ownActivRegNoPwd',
            'base_subject' => ['COM_USERS_EMAIL_ACCOUNT_DETAILS'],
            'base_body' => ['COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY_NOPW'],
            'new_subject' => '{trans:COM_USERS_EMAIL_ACCOUNT_DETAILS|param1|param2}',
            'new_body' => '{trans:COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY_NOPW|param3|param4|param5|param6|param7}',
            'description' => 'ACYM_OVERRIDE_DESC_REG_ACTIVATION_NO_PWD',
            'source' => 'joomla',
        ],
        [
            'name' => 'joomla-adminActivReg',
            'base_subject' => ['COM_USERS_EMAIL_ACCOUNT_DETAILS'],
            'base_body' => ['COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY'],
            'new_subject' => '{trans:COM_USERS_EMAIL_ACCOUNT_DETAILS|param1|param2}',
            'new_body' => '{trans:COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY|param3|param4|param5|param6|param7|param8}',
            'description' => 'ACYM_OVERRIDE_DESC_REG_ADMIN_ACTIVATION',
            'source' => 'joomla',
        ],
        [
            'name' => 'joomla-adminActivRegNoPwd',
            'base_subject' => ['COM_USERS_EMAIL_ACCOUNT_DETAILS'],
            'base_body' => ['COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY_NOPW'],
            'new_subject' => '{trans:COM_USERS_EMAIL_ACCOUNT_DETAILS|param1|param2}',
            'new_body' => '{trans:COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY_NOPW|param3|param4|param5|param6|param7}',
            'description' => 'ACYM_OVERRIDE_DESC_REG_ADMIN_ACTIVATION_NO_PWD',
            'source' => 'joomla',
        ],
        [
            'name' => 'joomla-confirmActiv',
            'base_subject' => ['COM_USERS_EMAIL_ACTIVATED_BY_ADMIN_ACTIVATION_SUBJECT'],
            'base_body' => ['COM_USERS_EMAIL_ACTIVATED_BY_ADMIN_ACTIVATION_BODY'],
            'new_subject' => '{trans:COM_USERS_EMAIL_ACTIVATED_BY_ADMIN_ACTIVATION_SUBJECT|param1|param2}',
            'new_body' => '{trans:COM_USERS_EMAIL_ACTIVATED_BY_ADMIN_ACTIVATION_BODY|param3|param4|param5}',
            'description' => 'ACYM_OVERRIDE_DESC_REG_ADMIN_ACTIVATED',
            'source' => 'joomla',
        ],
        [
            'name' => 'joomla-regByAdmin',
            'base_subject' => ['PLG_USER_JOOMLA_NEW_USER_EMAIL_SUBJECT'],
            'base_body' => ['PLG_USER_JOOMLA_NEW_USER_EMAIL_BODY'],
            'new_subject' => '{trans:PLG_USER_JOOMLA_NEW_USER_EMAIL_SUBJECT}',
            'new_body' => '{trans:PLG_USER_JOOMLA_NEW_USER_EMAIL_BODY|param1|param2|param3|param4|param5}',
            'description' => 'ACYM_OVERRIDE_DESC_ADMIN_CREATED',
            'source' => 'joomla',
        ],
        [
            'name' => 'joomla-regNotifAdmin',
            'base_subject' => ['COM_USERS_EMAIL_ACCOUNT_DETAILS'],
            'base_body' => ['COM_USERS_EMAIL_REGISTERED_NOTIFICATION_TO_ADMIN_BODY'],
            'new_subject' => '{trans:COM_USERS_EMAIL_ACCOUNT_DETAILS|param1|param2}',
            'new_body' => '{trans:COM_USERS_EMAIL_REGISTERED_NOTIFICATION_TO_ADMIN_BODY|param3|param4|param5}',
            'description' => 'ACYM_OVERRIDE_DESC_REG_ADMIN_NOTIFICATION',
            'source' => 'joomla',
        ],
        [
            'name' => 'joomla-regNotifAdminActiv',
            'base_subject' => ['COM_USERS_EMAIL_ACTIVATE_WITH_ADMIN_ACTIVATION_SUBJECT'],
            'base_body' => ['COM_USERS_EMAIL_ACTIVATE_WITH_ADMIN_ACTIVATION_BODY'],
            'new_subject' => '{trans:COM_USERS_EMAIL_ACTIVATE_WITH_ADMIN_ACTIVATION_SUBJECT|param1|param2}',
            'new_body' => '{trans:COM_USERS_EMAIL_ACTIVATE_WITH_ADMIN_ACTIVATION_BODY|param3|param4|param5|param6|param7}',
            'description' => 'ACYM_OVERRIDE_DESC_ADMIN_ACTIVATION_NOTIFICATION',
            'source' => 'joomla',
        ],
        [
            'name' => 'joomla-frontsendarticle',
            'base_subject' => '',
            'base_body' => ['COM_MAILTO_EMAIL_MSG'],
            'new_subject' => '{senderSubject}',
            'new_body' => '{trans:COM_MAILTO_EMAIL_MSG|param1|param2|param3|param4}',
            'description' => 'ACYM_OVERRIDE_DESC_ARTICLE_SHARE',
            'source' => 'joomla',
        ],
    ];

    acym_trigger('onAcymGetEmailOverrides', [&$emailOverrides]);

    return $emailOverrides;
}

function acym_getOverrideParamsByName($name)
{
    $overridesParamsAll = [
        'joomla-resetPwd' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param3' => [
                'nicename' => acym_translation('ACYM_TOKEN'),
                'description' => acym_translation('ACYM_TOKEN_OVERRIDE_DESC'),
            ],
            'param4' => [
                'nicename' => acym_translation('ACYM_LINK_TEXT'),
                'description' => acym_translation('ACYM_LINK_TEXT_OVERRIDE_DESC'),
            ],
        ],
        'joomla-usernameReminder' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_DESC'),
            ],
            'param3' => [
                'nicename' => acym_translation('ACYM_USERNAME'),
                'description' => acym_translation('ACYM_USERNAME_OVERRIDE_DESC'),
            ],
            'param4' => [
                'nicename' => acym_translation('ACYM_LINK_TEXT'),
                'description' => acym_translation('ACYM_LINK_TEXT_OVERRIDE_DESC'),
            ],
        ],
        'joomla-directRegNoPwd' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_USER_NAME'),
                'description' => acym_translation('ACYM_USER_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param5' => [
                'nicename' => acym_translation('ACYM_SITE_URL'),
                'description' => acym_translation('ACYM_SITE_URL_OVERRIDE_DESC'),
            ],
        ],
        'joomla-directReg' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_USER_NAME'),
                'description' => acym_translation('ACYM_USER_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param5' => [
                'nicename' => acym_translation('ACYM_SITE_URL'),
                'description' => acym_translation('ACYM_SITE_URL_OVERRIDE_DESC'),
            ],
            'param6' => [
                'nicename' => acym_translation('ACYM_USERNAME'),
                'description' => acym_translation('ACYM_USERNAME_OVERRIDE_DESC'),
            ],
            'param7' => [
                'nicename' => acym_translation('ACYM_PASSWORD'),
                'description' => acym_translation('ACYM_PASSWORD_OVERRIDE_DESC'),
            ],
        ],
        'joomla-ownActivReg' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_USER_NAME'),
                'description' => acym_translation('ACYM_USER_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param5' => [
                'nicename' => acym_translation('ACYM_ACTIVATION_LINK'),
                'description' => acym_translation('ACYM_ACTIVATION_LINK_OVERRIDE_DESC'),
            ],
            'param6' => [
                'nicename' => acym_translation('ACYM_SITE_URL'),
                'description' => acym_translation('ACYM_SITE_URL_OVERRIDE_DESC'),
            ],
            'param7' => [
                'nicename' => acym_translation('ACYM_USERNAME'),
                'description' => acym_translation('ACYM_USERNAME_OVERRIDE_DESC'),
            ],
            'param8' => [
                'nicename' => acym_translation('ACYM_PASSWORD'),
                'description' => acym_translation('ACYM_PASSWORD_OVERRIDE_DESC'),
            ],
        ],
        'joomla-ownActivRegNoPwd' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_USER_NAME'),
                'description' => acym_translation('ACYM_USER_NAME_OVERRIDE_DESC'),
            ],
            'param4' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param5' => [
                'nicename' => acym_translation('ACYM_ACTIVATION_LINK'),
                'description' => acym_translation('ACYM_ACTIVATION_LINK_OVERRIDE_DESC'),
            ],
            'param6' => [
                'nicename' => acym_translation('ACYM_SITE_URL'),
                'description' => acym_translation('ACYM_SITE_URL_OVERRIDE_DESC'),
            ],
            'param7' => [
                'nicename' => acym_translation('ACYM_USERNAME'),
                'description' => acym_translation('ACYM_USERNAME_OVERRIDE_DESC'),
            ],
        ],
        'joomla-adminActivReg' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_USER_NAME'),
                'description' => acym_translation('ACYM_USER_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param5' => [
                'nicename' => acym_translation('ACYM_ACTIVATION_LINK'),
                'description' => acym_translation('ACYM_ACTIVATION_LINK_OVERRIDE_DESC'),
            ],
            'param6' => [
                'nicename' => acym_translation('ACYM_SITE_URL'),
                'description' => acym_translation('ACYM_SITE_URL_OVERRIDE_DESC'),
            ],
            'param7' => [
                'nicename' => acym_translation('ACYM_USERNAME'),
                'description' => acym_translation('ACYM_USERNAME_OVERRIDE_DESC'),
            ],
            'param8' => [
                'nicename' => acym_translation('ACYM_PASSWORD'),
                'description' => acym_translation('ACYM_PASSWORD_OVERRIDE_DESC'),
            ],
        ],
        'joomla-adminActivRegNoPwd' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_USER_NAME'),
                'description' => acym_translation('ACYM_USER_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param5' => [
                'nicename' => acym_translation('ACYM_ACTIVATION_LINK'),
                'description' => acym_translation('ACYM_ACTIVATION_LINK_OVERRIDE_DESC'),
            ],
            'param6' => [
                'nicename' => acym_translation('ACYM_SITE_URL'),
                'description' => acym_translation('ACYM_SITE_URL_OVERRIDE_DESC'),
            ],
            'param7' => [
                'nicename' => acym_translation('ACYM_USERNAME'),
                'description' => acym_translation('ACYM_USERNAME_OVERRIDE_DESC'),
            ],
        ],
        'joomla-confirmActiv' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_USER_NAME'),
                'description' => acym_translation('ACYM_USER_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param4' => [
                'nicename' => acym_translation('ACYM_SITE_URL'),
                'description' => acym_translation('ACYM_SITE_URL_OVERRIDE_DESC'),
            ],
            'param5' => [
                'nicename' => acym_translation('ACYM_USERNAME'),
                'description' => acym_translation('ACYM_USERNAME_OVERRIDE_DESC'),
            ],
        ],
        'joomla-regByAdmin' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_USER_NAME'),
                'description' => acym_translation('ACYM_USER_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param3' => [
                'nicename' => acym_translation('ACYM_SITE_URL'),
                'description' => acym_translation('ACYM_SITE_URL_OVERRIDE_DESC'),
            ],
            'param4' => [
                'nicename' => acym_translation('ACYM_USERNAME'),
                'description' => acym_translation('ACYM_USERNAME_OVERRIDE_DESC'),
            ],
            'param5' => [
                'nicename' => acym_translation('ACYM_PASSWORD'),
                'description' => acym_translation('ACYM_PASSWORD_OVERRIDE_DESC'),
            ],
        ],
        'joomla-regNotifAdmin' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_USER_NAME'),
                'description' => acym_translation('ACYM_USER_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param4' => [
                'nicename' => acym_translation('ACYM_USERNAME'),
                'description' => acym_translation('ACYM_USERNAME_OVERRIDE_DESC'),
            ],
            'param5' => [
                'nicename' => acym_translation('ACYM_SITE_URL'),
                'description' => acym_translation('ACYM_SITE_URL_OVERRIDE_DESC'),
            ],
        ],
        'joomla-regNotifAdminActiv' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_USER_NAME'),
                'description' => acym_translation('ACYM_USER_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param5' => [
                'nicename' => acym_translation('ACYM_USER_EMAIL'),
                'description' => acym_translation('ACYM_USER_EMAIL_OVERRIDE_DESC'),
            ],
            'param6' => [
                'nicename' => acym_translation('ACYM_USERNAME'),
                'description' => acym_translation('ACYM_USERNAME_OVERRIDE_DESC'),
            ],
            'param7' => [
                'nicename' => acym_translation('ACYM_ACTIVATION_LINK'),
                'description' => acym_translation('ACYM_ACTIVATION_LINK_OVERRIDE_DESC'),
            ],
        ],
        'joomla-frontsendarticle' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_SENDER_NAME'),
                'description' => acym_translation('ACYM_SENDER_NAME_OVERRIDE_DESC'),
            ],
            'param3' => [
                'nicename' => acym_translation('ACYM_SENDER_EMAIL'),
                'description' => acym_translation('ACYM_SENDER_EMAIL_OVERRIDE_DESC'),
            ],
            'param4' => [
                'nicename' => acym_translation('ACYM_LINK'),
                'description' => acym_translation('ACYM_LINK_OVERRIDE_DESC'),
            ],
        ],
    ];

    if (empty($overridesParamsAll[$name])) return [];

    return $overridesParamsAll[$name];
}
