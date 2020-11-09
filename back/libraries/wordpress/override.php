<?php

function acym_getEmailOverrides()
{
    $emailOverrides = [
        [
            'name' => 'wp-adminNewUserAdmin',
            'base_subject' => [
                '[%s] New User Registration',
            ],
            'base_body' => [
                'New user registration on your site %s:',
                "\r\n\r\n",
                'Username: %s',
                "\r\n\r\n",
                'Email: %s',
                "\r\n",
            ],
            'new_subject' => '[{param1}] New User Registration',
            'new_body' => 'New user registration on your site {param2}:
<br>
<br>
Username: {param3}
<br>
<br>Email: {param4}',
            'description' => 'ACYM_OVERRIDE_DESC_REG_ADMIN_NOTIFICATION',
            'source' => 'wordpress',
        ],
        [
            'name' => 'wp-newUser',
            'base_subject' => [
                '[%s] Login Details',
            ],
            'base_body' => [
                'Username: %s',
                "\r\n\r\n",
                'To set your password, visit the following address:',
                "\r\n\r\n",
                '%s',
            ],
            'new_subject' => '[{param1}] Login Details',
            'new_body' => 'Username: {param2}
<br>
<br>To set your password, visit the following address:
<br>
<br>{param3}',
            'description' => 'ACYM_OVERRIDE_DESC_ADMIN_CREATED',
            'source' => 'wordpress',
        ],
        [
            'name' => 'wp-passwordChangeNotif',
            'base_subject' => [
                '[%s] Password Changed',
            ],
            'base_body' => [
                'Hi ###USERNAME###,

This notice confirms that your password was changed on ###SITENAME###.

If you did not change your password, please contact the Site Administrator at
###ADMIN_EMAIL###

This email has been sent to ###EMAIL###

Regards,
All at ###SITENAME###
###SITEURL###',
            ],
            'new_subject' => '[{param1}] Password Changed',
            'new_body' => 'Hi {param2},
<br>
<br>
This notice confirms that your password was changed on {param3}.
<br>
<br>
If you did not change your password, please contact the Site Administrator at
{param4}
<br>
<br>
This email has been sent to {param5}
<br>
<br>
Regards,
All at {param6}
{param7}',
            'description' => 'ACYM_OVERRIDE_DESC_CONFIRMATION_CHANGE_PASSWORD',
            'source' => 'wordpress',
        ],
        [
            'name' => 'wp-emailChangeNotif',
            'base_subject' => [
                '[%s] Email Changed',
            ],
            'base_body' => [
                'Hi ###USERNAME###,

This notice confirms that your email address on ###SITENAME### was changed to ###NEW_EMAIL###.

If you did not change your email, please contact the Site Administrator at
###ADMIN_EMAIL###

This email has been sent to ###EMAIL###

Regards,
All at ###SITENAME###
###SITEURL###',
            ],
            'new_subject' => '[{param1}] Email Changed',
            'new_body' => 'Hi {param2},
<br>
<br>
This notice confirms that your email address on {param3} was changed to {param4}.
<br>
<br>
If you did not change your email, please contact the Site Administrator at
{param5}
<br>
<br>
This email has been sent to {param6}
<br>
<br>
Regards,
All at {param7}
{param8}',
            'description' => 'ACYM_OVERRIDE_DESC_CONFIRMATION_CHANGE_EMAIL',
            'source' => 'wordpress',
        ],
        [
            'name' => 'wp-passwordUserChangeNotifAdmin',
            'base_subject' => [
                '[%s] Password Changed',
            ],
            'base_body' => [
                'Password changed for user: %s',
                "\r\n",
            ],
            'new_subject' => '[{param1}] Password Changed',
            'new_body' => 'Password changed for user: {param2}',
            'description' => 'ACYM_OVERRIDE_DESC_ADMIN_NOTIFICATION_CHANGE_PASSWORD',
            'source' => 'wordpress',
        ],
        [
            'name' => 'wp-adminNotifUserRequestConfirm',
            'base_subject' => [
                '[%1$s] Action Confirmed: %2$s',
            ],
            'base_body' => [
                'Howdy,

A user data privacy request has been confirmed on ###SITENAME###:

User: ###USER_EMAIL###
Request: ###DESCRIPTION###

You can view and manage these data privacy requests here:

###MANAGE_URL###

Regards,
All at ###SITENAME###
###SITEURL###',
            ],
            'new_subject' => '[{param1}] Action Confirmed: {param2}',
            'new_body' => 'Howdy,
<br>
<br>
A user data privacy request has been confirmed on {param3}:
<br>
<br>
User: {param4}
Request: {param5}
<br>
<br>
You can view and manage these data privacy requests here:
<br>
<br>
{param6}
<br>
<br>
Regards,
All at {param7}
{param8}',
            'description' => 'ACYM_OVERRIDE_DESC_ADMIN_NOTIFICATION_DATA_REQUEST',
            'source' => 'wordpress',
        ],
        [
            'name' => 'wp-userErasureRequestConfirm1',
            'base_subject' => [
                '[%s] Erasure Request Fulfilled',
            ],
            'base_body' => [
                'Howdy,

Your request to erase your personal data on ###SITENAME### has been completed.

If you have any follow-up questions or concerns, please contact the site administrator.

Regards,
All at ###SITENAME###
###SITEURL###',
            ],
            'new_subject' => '[{param1}] Erasure Request Fulfilled',
            'new_body' => 'Howdy,
<br>
<br>
Your request to erase your personal data on {param2} has been completed.
<br>
<br>
If you have any follow-up questions or concerns, please contact the site administrator.
<br>
<br>
Regards,
All at {param3}
{param4}',
            'description' => 'ACYM_OVERRIDE_DESC_DATA_REMOVAL_CONFIRMATION',
            'source' => 'wordpress',
        ],
        [
            'name' => 'wp-UserErasureRequestConfirm2',
            'base_subject' => [
                '[%s] Erasure Request Fulfilled',
            ],
            'base_body' => [
                'Howdy,

Your request to erase your personal data on ###SITENAME### has been completed.

If you have any follow-up questions or concerns, please contact the site administrator.

For more information, you can also read our privacy policy: ###PRIVACY_POLICY_URL###

Regards,
All at ###SITENAME###
###SITEURL###',
            ],
            'new_subject' => '[{param1}] Erasure Request Fulfilled',
            'new_body' => 'Howdy,
<br>
<br>
Your request to erase your personal data on {param2} has been completed.
<br>
<br>
If you have any follow-up questions or concerns, please contact the site administrator.
<br>
<br>
For more information, you can also read our privacy policy: {param3}
<br>
<br>
Regards,
All at {param4}
{param5}',
            'description' => 'ACYM_OVERRIDE_DESC_DATA_REMOVAL_CONFIRMATION_PRIVACY',
            'source' => 'wordpress',
        ],
        [
            'name' => 'wp-userPasswordRetrieveRequest',
            'base_subject' => [
                '[%s] Password Reset',
            ],
            'base_body' => [
                'Someone has requested a password reset for the following account:',
                "\r\n\r\n",
                'Site Name: %s',
                "\r\n\r\n",
                'Username: %s',
                "\r\n\r\n",
                'If this was a mistake, just ignore this email and nothing will happen.',
                "\r\n\r\n",
                'To reset your password, visit the following address:',
                "\r\n\r\n",
                '%s',
                "\r\n",
            ],
            'new_subject' => '[{param1}] Password Reset',
            'new_body' => 'Someone has requested a password reset for the following account:
<br>
<br>Site Name: {param2}
<br>
<br>Username: {param3}
<br>
<br>If this was a mistake, just ignore this email and nothing will happen.
<br>
<br>To reset your password, visit the following address:
<br>
<br>{param4}',
            'description' => 'ACYM_OVERRIDE_DESC_RESET_PASSWORD',
            'source' => 'wordpress',
        ],
        [
            'name' => 'wp-userResetEmailRequest',
            'base_subject' => [
                '[%s] Email Change Request',
            ],
            'base_body' => [
                'Howdy ###USERNAME###,

You recently requested to have the email address on your account changed.

If this is correct, please click on the following link to change it:
###ADMIN_URL###

You can safely ignore and delete this email if you do not want to
take this action.

This email has been sent to ###EMAIL###

Regards,
All at ###SITENAME###
###SITEURL###',
            ],
            'new_subject' => '[{param1}] Email Change Request',
            'new_body' => 'Howdy {param2},
<br>
<br>
You recently requested to have the email address on your account changed.
<br>
<br>
If this is correct, please click on the following link to change it:
{param3}
<br>
<br>
You can safely ignore and delete this email if you do not want to
take this action.
<br>
<br>
This email has been sent to {param4}
<br>
<br>
Regards,
All at {param5}
{param6}',
            'description' => 'ACYM_OVERRIDE_DESC_CHANGE_EMAIL',
            'source' => 'wordpress',
        ],
        [
            'name' => 'wp-userRequestConfirmAction',
            'base_subject' => [
                '[%1$s] Confirm Action: %2$s',
            ],
            'base_body' => [
                'Howdy,

A request has been made to perform the following action on your account:

     ###DESCRIPTION###

To confirm this, please click on the following link:
###CONFIRM_URL###

You can safely ignore and delete this email if you do not want to
take this action.

Regards,
All at ###SITENAME###
###SITEURL###',
            ],
            'new_subject' => '[{param1}] Confirm Action: {param2}',
            'new_body' => 'Howdy,
<br>
<br>
A request has been made to perform the following action on your account:
<br>
<br>
     {param3}
<br>
<br>
To confirm this, please click on the following link:
{param4}
<br>
<br>
You can safely ignore and delete this email if you do not want to
take this action.
<br>
<br>
Regards,
All at {param5}
{param6}',
            'description' => 'ACYM_OVERRIDE_DESC_ACTION_CONFIRMATION',
            'source' => 'wordpress',
        ],
        [
            'name' => 'wp-personalDataExport',
            'base_subject' => [
                '[%s] Personal Data Export',
            ],
            'base_body' => [
                'Howdy,

Your request for an export of personal data has been completed. You may
download your personal data by clicking on the link below. For privacy
and security, we will automatically delete the file on ###EXPIRATION###,
so please download it before then.

###LINK###

Regards,
All at ###SITENAME###
###SITEURL###',
            ],
            'new_subject' => '[{param1}] Personal Data Export',
            'new_body' => 'Howdy,
<br>
<br>
Your request for an export of personal data has been completed. You may
download your personal data by clicking on the link below. For privacy
and security, we will automatically delete the file on {param2},
so please download it before then.
<br>
<br>
{param3}
<br>
<br>
Regards,
All at {param4}
{param5}',
            'description' => 'ACYM_OVERRIDE_DESC_DATA_EXPORT',
            'source' => 'wordpress',
        ],
    ];

    acym_trigger('onAcymGetEmailOverrides', [&$emailOverrides]);

    return $emailOverrides;
}

function acym_getOverrideParamsByName($name)
{
    $overridesParamsAll = [
        'wp-adminNewUserAdmin' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param3' => [
                'nicename' => acym_translation('ACYM_USERNAME'),
                'description' => acym_translation('ACYM_USERNAME_OVERRIDE_DESC'),
            ],
            'param4' => [
                'nicename' => acym_translation('ACYM_EMAIL'),
                'description' => acym_translation('ACYM_EMAIL_OVERRIDE_DESC'),
            ],
        ],
        'wp-newUser' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_USERNAME'),
                'description' => acym_translation('ACYM_USERNAME_OVERRIDE_DESC'),
            ],
            'param3' => [
                'nicename' => acym_translation('ACYM_LOGIN_URL'),
                'description' => acym_translation('ACYM_LOGIN_URL_OVERRIDE_DESC'),
            ],
        ],
        'wp-passwordChangeNotif' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_USERNAME'),
                'description' => acym_translation('ACYM_USERNAME_OVERRIDE_DESC'),
            ],
            'param4' => [
                'nicename' => acym_translation('ACYM_ADMIN_EMAIL'),
                'description' => acym_translation('ACYM_ADMIN_EMAIL_OVERRIDE_DESC'),
            ],
            'param5' => [
                'nicename' => acym_translation('ACYM_USER_EMAIL'),
                'description' => acym_translation('ACYM_USER_EMAIL_OVERRIDE_DESC'),
            ],
            'param7' => [
                'nicename' => acym_translation('ACYM_SITE_URL'),
                'description' => acym_translation('ACYM_SITE_URL_OVERRIDE_DESC'),
            ],
        ],
        'wp-emailChangeNotif' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_USERNAME'),
                'description' => acym_translation('ACYM_USERNAME_OVERRIDE_DESC'),
            ],
            'param4' => [
                'nicename' => acym_translation('ACYM_USER_NEW_EMAIL'),
                'description' => acym_translation('ACYM_USER_NEW_EMAIL_OVERRIDE_DESC'),
            ],
            'param5' => [
                'nicename' => acym_translation('ACYM_ADMIN_EMAIL'),
                'description' => acym_translation('ACYM_ADMIN_EMAIL_OVERRIDE_DESC'),
            ],
            'param6' => [
                'nicename' => acym_translation('ACYM_USER_EMAIL'),
                'description' => acym_translation('ACYM_USER_EMAIL_OVERRIDE_DESC'),
            ],
            'param7' => [
                'nicename' => acym_translation('ACYM_SITE_URL'),
                'description' => acym_translation('ACYM_SITE_URL_OVERRIDE_DESC'),
            ],
        ],
        'wp-passwordUserChangeNotifAdmin' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_USERNAME'),
                'description' => acym_translation('ACYM_USERNAME_OVERRIDE_DESC'),
            ],
        ],
        'wp-adminNotifUserRequestConfirm' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_ACTION'),
                'description' => acym_translation('ACYM_ACTION_OVERRIDE_DESC'),
            ],
            'param4' => [
                'nicename' => acym_translation('ACYM_USER_EMAIL'),
                'description' => acym_translation('ACYM_USER_EMAIL_OVERRIDE_DESC'),
            ],
            'param6' => [
                'nicename' => acym_translation('ACYM_MANAGE_URL'),
                'description' => acym_translation('ACYM_MANAGE_URL_OVERRIDE_DESC'),
            ],
            'param8' => [
                'nicename' => acym_translation('ACYM_SITE_URL'),
                'description' => acym_translation('ACYM_SITE_URL_OVERRIDE_DESC'),
            ],
        ],
        'wp-userErasureRequestConfirm1' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param4' => [
                'nicename' => acym_translation('ACYM_SITE_URL'),
                'description' => acym_translation('ACYM_SITE_URL_OVERRIDE_DESC'),
            ],
        ],
        'wp-UserErasureRequestConfirm2' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param3' => [
                'nicename' => acym_translation('ACYM_PRIVACY_POLICY_URL'),
                'description' => acym_translation('ACYM_PRIVACY_POLICY_URL_OVERRIDE_DESC'),
            ],
            'param5' => [
                'nicename' => acym_translation('ACYM_SITE_URL'),
                'description' => acym_translation('ACYM_SITE_URL_OVERRIDE_DESC'),
            ],
        ],
        'wp-userPasswordRetrieveRequest' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param3' => [
                'nicename' => acym_translation('ACYM_USERNAME'),
                'description' => acym_translation('ACYM_USERNAME_OVERRIDE_DESC'),
            ],
            'param4' => [
                'nicename' => acym_translation('ACYM_LINK_RESET_PASSWORD'),
                'description' => acym_translation('ACYM_LINK_RESET_PASSWORD_OVERRIDE_DESC'),
            ],
        ],
        'wp-userResetEmailRequest' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_USERNAME'),
                'description' => acym_translation('ACYM_USERNAME_OVERRIDE_DESC'),
            ],
            'param3' => [
                'nicename' => acym_translation('ACYM_ADMIN_URL'),
                'description' => acym_translation('ACYM_ADMIN_URL_OVERRIDE_DESC'),
            ],
            'param4' => [
                'nicename' => acym_translation('ACYM_USER_EMAIL'),
                'description' => acym_translation('ACYM_USER_EMAIL_OVERRIDE_DESC'),
            ],
            'param6' => [
                'nicename' => acym_translation('ACYM_SITE_URL'),
                'description' => acym_translation('ACYM_SITE_URL_OVERRIDE_DESC'),
            ],
        ],
        'wp-userRequestConfirmAction' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_ACTION'),
                'description' => acym_translation('ACYM_ACTION_OVERRIDE_DESC'),
            ],
            'param4' => [
                'nicename' => acym_translation('ACYM_ACTION_CONFIRM_URL'),
                'description' => acym_translation('ACYM_ACTION_CONFIRM_URL_OVERRIDE_DESC'),
            ],
            'param6' => [
                'nicename' => acym_translation('ACYM_SITE_URL'),
                'description' => acym_translation('ACYM_SITE_URL_OVERRIDE_DESC'),
            ],
        ],
        'wp-personalDataExport' => [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_EXPIRATION_DATE'),
                'description' => acym_translation('ACYM_EXPIRATION_DATE_OVERRIDE_DESC'),
            ],
            'param3' => [
                'nicename' => acym_translation('ACYM_LINK_EXPORT_FILE'),
                'description' => acym_translation('ACYM_LINK_EXPORT_FILE_OVERRIDE_DESC'),
            ],
            'param5' => [
                'nicename' => acym_translation('ACYM_SITE_URL'),
                'description' => acym_translation('ACYM_SITE_URL_OVERRIDE_DESC'),
            ],
        ],
    ];

    if (empty($overridesParamsAll[$name])) return [];

    return $overridesParamsAll[$name];
}
