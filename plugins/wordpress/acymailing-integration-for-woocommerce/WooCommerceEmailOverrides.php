<?php

use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\OverrideClass;
use AcyMailing\Helpers\PluginHelper;

trait WooCommerceEmailOverrides
{
    private $mailOverrideSourceName = 'woocommerce';
    private $pluginDisplayedName = 'WooCommerce';

    public function onAcymGetEmailOverrides(&$emailsOverride)
    {
        $wooOverrides = [
            [
                'name' => 'woo-new_order',
                'base_subject' => [
                    '[{site_title}]: New order #{order_number}',
                ],
                'base_body' => '',
                'new_subject' => '[{param1}]: New order #{param2}',
                'new_body' => 'You’ve received the following order from {user_billing_full_name}:
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_WOO_NEW_ORDER_EMAIL_DESC',
                'source' => $this->mailOverrideSourceName,
            ],
            [
                'name' => 'woo-customer_completed_order',
                'base_subject' => [
                    'Your {site_title} order is now complete',
                ],
                'base_body' => '',
                'new_subject' => 'Your {param1} order is now complete',
                'new_body' => 'Hi {user_billing_full_name},
			<br>
			We have finished processing your order.
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_WOO_CUSTOMER_COMPLETE_ORDER',
                'source' => $this->mailOverrideSourceName,
            ],
            [
                'name' => 'woo-customer_on_hold_order',
                'base_subject' => [
                    'Your {site_title} order has been received!',
                ],
                'base_body' => '',
                'new_subject' => 'Your {param1} order has been received!',
                'new_body' => 'Hi {user_billing_full_name},
			<br>
			Thanks for your order. It’s on-hold until we confirm that payment has been received. In the meantime, here’s a reminder of what you ordered:
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_WOO_CUSTOMER_ON_HOLD_ORDER',
                'source' => $this->mailOverrideSourceName,
            ],
            [
                'name' => 'woo-customer_invoice',
                'base_subject' => [
                    'Invoice for order #{order_number} on {site_title}',
                ],
                'base_body' => '',
                'new_subject' => 'Invoice for order #{param1} on {param2}',
                'new_body' => 'Hi {user_billing_full_name},
			<br>
			Here are the details of your order placed on {order_date_created}}:
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_WOO_CUSTOMER_INVOICE',
                'source' => $this->mailOverrideSourceName,
            ],
            [
                'name' => 'woo-customer_processing_order',
                'base_subject' => [
                    'Your {site_title} order has been received!',
                ],
                'base_body' => '',
                'new_subject' => 'Your {param1} order has been received!',
                'new_body' => 'Hi {user_billing_full_name},
			<br>
			Just to let you know &mdash; we\'ve received your order #{order_number}, and it is now being processed:
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_WOO_CUSTOMER_PROCESSING_ORDER',
                'source' => $this->mailOverrideSourceName,
            ],
            [
                'name' => 'woo-customer_refunded_order',
                'base_subject' => [
                    'Your {site_title} order #{order_number} has been refunded',
                ],
                'base_body' => '',
                'new_subject' => 'Your {param1} order #{param2} has been refunded',
                'new_body' => 'Hi {user_billing_full_name},
			<br>
			Your order on {param1} has been refunded. There are more details below for your reference:
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_WOO_CUSTOMER_REFUNDED_ORDER',
                'source' => $this->mailOverrideSourceName,
            ],
            [
                'name' => 'woo-customer_partially_refunded_order',
                'base_subject' => [
                    'Your {site_title} order #{order_number} has been partially refunded',
                ],
                'base_body' => '',
                'new_subject' => 'Your {param1} order #{param2} has been partially refunded',
                'new_body' => 'Hi {user_billing_full_name},
			<br>
			Your order on {param1} has been partially refunded. There are more details below for your reference:
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_WOO_CUSTOMER_PARTIALLY_REFUNDED_ORDER',
                'source' => $this->mailOverrideSourceName,
            ],
            [
                'name' => 'woo-failed_order',
                'base_subject' => [
                    '[{site_title}]: Order #{order_number} has failed',
                ],
                'base_body' => '',
                'new_subject' => '[{param1}]: Order #{param2} has failed',
                'new_body' => 'Payment for order {param2} from {user_billing_full_name} has failed. The order was as follows:
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_WOO_FAILED_ORDER',
                'source' => $this->mailOverrideSourceName,
            ],
            [
                'name' => 'woo-cancelled_order',
                'base_subject' => [
                    '[{site_title}]: Order #{order_number} has been cancelled',
                ],
                'base_body' => '',
                'new_subject' => '[{param1}]: Order #{param2} has been cancelled',
                'new_body' => 'Notification to let you know &mdash; order #{param2} belonging to {user_billing_full_name} has been cancelled:
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_WOO_CANCELED_ORDER',
                'source' => $this->mailOverrideSourceName,
            ],
            [
                'name' => 'woo-customer_reset_password',
                'base_subject' => [
                    'Password Reset Request for {site_title}',
                ],
                'base_body' => '',
                'new_subject' => 'Password Reset Request for {param1}',
                'new_body' => 'Hi {user_login},
            <br>
            Someone has requested a new password for the following account on {param1}:
            <br>
            Username: {user_login}
            <br>
            If you didn\'t make this request, just ignore this email. If you\'d like to proceed:
            <br>
            <a class="link" href="{link_reset_password}">Click here to reset your password</a>',
                'description' => 'ACYM_OVERRIDE_DESC_RESET_PASSWORD',
                'source' => $this->mailOverrideSourceName,
            ],
            [
                'name' => 'woo-customer_new_account',
                'base_subject' => [
                    'Your {site_title} account has been created!',
                ],
                'base_body' => '',
                'new_subject' => 'Your {param1} account has been created!',
                'new_body' => 'Hi {user_login},
            <br>
            Thanks for creating an account on {param1}. Your username is {user_login}. You can access your account area to view orders, change your password, and more at: {my_account_link}
            <br>
            Your password has been automatically generated: {user_password}',
                'description' => 'ACYM_OVERRIDE_DESC_ADMIN_CREATED',
                'source' => $this->mailOverrideSourceName,
            ],
            [
                'name' => 'woo-customer_note',
                'base_subject' => [
                    'Note added to your {site_title} order from {order_date}',
                ],
                'base_body' => '',
                'new_subject' => 'Note added to your {param1} order from {param2}',
                'new_body' => 'Hi {user_billing_full_name},
            <br>
            The following note has been added to your order:
            <br>
            <blockquote>{customer_note}</blockquote>
            <br>
            As a reminder, here are your order details:
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_OVERRIDE_DESC_CUSTOMER_NOTE',
                'source' => $this->mailOverrideSourceName,
            ],
        ];

        $emailsOverride = array_merge($emailsOverride, $wooOverrides);
    }

    public function onAcymGetEmailOverridesParams(&$overridesParamsAll)
    {
        $overridesParamsAll['woo-new_order'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_ORDER_NUMBER'),
                'description' => acym_translation('ACYM_ORDER_NUMBER_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-customer_completed_order'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-customer_on_hold_order'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-customer_invoice'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_ORDER_NUMBER'),
                'description' => acym_translation('ACYM_ORDER_NUMBER_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'order_date_created' => [
                'nicename' => acym_translation('ACYM_ORDER_CREATION_DATE'),
                'description' => acym_translation('ACYM_ORDER_CREATION_DATE_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-customer_processing_order'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'order_number' => [
                'nicename' => acym_translation('ACYM_ORDER_NUMBER'),
                'description' => acym_translation('ACYM_ORDER_NUMBER_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-customer_refunded_order'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_ORDER_NUMBER'),
                'description' => acym_translation('ACYM_ORDER_NUMBER_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-customer_partially_refunded_order'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_ORDER_NUMBER'),
                'description' => acym_translation('ACYM_ORDER_NUMBER_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-failed_order'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_ORDER_NUMBER'),
                'description' => acym_translation('ACYM_ORDER_NUMBER_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-cancelled_order'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_ORDER_NUMBER'),
                'description' => acym_translation('ACYM_ORDER_NUMBER_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-customer_reset_password'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'user_login' => [
                'nicename' => acym_translation('ACYM_USER_NAME'),
                'description' => acym_translation('ACYM_USER_NAME_OVERRIDE_DESC'),
            ],
            'link_reset_password' => [
                'nicename' => acym_translation('ACYM_LINK_RESET_PASSWORD'),
                'description' => acym_translation('ACYM_LINK_RESET_PASSWORD_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-customer_new_account'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'user_login' => [
                'nicename' => acym_translation('ACYM_USER_NAME'),
                'description' => acym_translation('ACYM_USER_NAME_OVERRIDE_DESC'),
            ],
            'my_account_link' => [
                'nicename' => acym_translation('ACYM_LINK_TO_ACCOUNT_FRONT'),
                'description' => acym_translation('ACYM_LINK_TO_ACCOUNT_FRONT_OVERRIDE_DESC'),
            ],
            'user_password' => [
                'nicename' => acym_translation('ACYM_PASSWORD'),
                'description' => acym_translation('ACYM_PASSWORD_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-customer_note'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_ORDER_CREATION_DATE'),
                'description' => acym_translation('ACYM_ORDER_CREATION_DATE_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'customer_note' => [
                'nicename' => acym_translation('ACYM_CUSTOMER_NOTE'),
                'description' => acym_translation('ACYM_CUSTOMER_NOTE_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];
    }

    public function onAcymGetEmailOverrideSources(&$sources)
    {
        $sources[$this->mailOverrideSourceName] = $this->pluginDisplayedName;
    }

    /**
     * @param $args
     *             0: to
     *             1: subject
     *             2: body
     *             3: headers
     *             4: attachments
     * @param $emailTypeClass
     */
    public function onWooCommerceEmailSend($args, $emailTypeClass)
    {
        $overrideClass = new OverrideClass();
        $activeOverrides = $overrideClass->getActiveOverrides('name');

        if (empty($activeOverrides)) return $args;

        if (empty($activeOverrides['woo-'.$emailTypeClass->id])) return $args;

        $override = $activeOverrides['woo-'.$emailTypeClass->id];

        $mailClass = new MailClass();
        $mail = $mailClass->getOneById($override->mail_id);

        if (empty($mail)) return $args;

        $mail = $this->onWooCommerceEmailSendReplaceTags($mail, $emailTypeClass);

        $args[2] = $mail->body;

        $dynamicSubjects = [
            'customer_invoice',
            'customer_refunded_order',
        ];
        if (in_array($emailTypeClass->id, $dynamicSubjects)) {
            $overrideNameKey = array_search($emailTypeClass->id, $dynamicSubjects);
            $overrideName = 'woo-'.$dynamicSubjects[$overrideNameKey];

            $wooOverrides = [];
            $this->onAcymGetEmailOverrides($wooOverrides);

            $key = array_search($overrideName, array_column($wooOverrides, 'name'));
            $mail->subject = $wooOverrides[$key]['base_subject'][0];
            $mail = $this->onWooCommerceEmailSendReplaceTags($mail, $emailTypeClass, 'subject');

            $args[1] = $mail->subject;
        }

        return $args;
    }

    private function onWooCommerceEmailSendReplaceTags($mail, $emailTypeClass, $column = 'body')
    {
        $order = $emailTypeClass->object;

        $dynamicText = [
            '{site_title}' => wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
        ];

        if (get_class($order) != 'WP_User') {
            $dynamicText['{order_number}'] = $order->get_order_number();
            $dynamicText['{user_billing_full_name}'] = $order->get_formatted_billing_full_name();
            $dynamicText['{order_date_created}'] = wc_format_datetime($order->get_date_created());
            $dynamicText['{checkout_payment_url}'] = '<a href="'.esc_url($order->get_checkout_payment_url()).'">';
            if (!empty($emailTypeClass->customer_note)) $dynamicText['{customer_note}'] = wpautop(wptexturize(make_clickable($emailTypeClass->customer_note)));
            //get woocommerce_email_order_details
            ob_start();
            do_action('woocommerce_email_order_details', $order, true, false, '');
            $dynamicText['{woocommerce_email_order_details}'] = ob_get_clean();

            //get woocommerce_email_order_meta
            ob_start();
            do_action('woocommerce_email_order_meta', $order, true, false, '');
            $dynamicText['{woocommerce_email_order_meta}'] = ob_get_clean();

            //get woocommerce_email_customer_details
            ob_start();
            do_action('woocommerce_email_customer_details', $order, true, false, '');
            $dynamicText['{woocommerce_email_customer_details}'] = ob_get_clean();
        } else {
            $dynamicText['{user_login}'] = $emailTypeClass->user_login;
            $dynamicText['{user_password}'] = $emailTypeClass->user_pass;
            $dynamicText['{my_account_link}'] = make_clickable(esc_url(wc_get_page_permalink('myaccount')));
            $dynamicText['{link_reset_password}'] = esc_url(
                add_query_arg(['key' => $emailTypeClass->reset_key, 'id' => $emailTypeClass->user_id], wc_get_endpoint_url('lost-password', '', wc_get_page_permalink('myaccount')))
            );
        }


        $pluginHelper = new PluginHelper();
        $mail->{$column} = $pluginHelper->replaceDText($mail->{$column}, $dynamicText);

        return $mail;
    }
}
