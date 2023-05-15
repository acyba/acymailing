<?php

class SendinblueIntegration extends SendinblueClass
{
    public function getSettingsSendingMethodFromPlugin(&$data, $plugin, $method)
    {
        if ($method != plgAcymSendinblue::SENDING_METHOD_ID) return;

        //__START__wordpress_
        if (ACYM_CMS == 'wordpress' && $plugin == 'wp_mail_smtp') {
            $wpMailSmtpSetting = get_option('wp_mail_smtp', '');
            if (empty($wpMailSmtpSetting['sendinblue']['api_key'])) {
                return;
            }

            $data['sendinblue_api_key'] = $wpMailSmtpSetting['sendinblue']['api_key'];
        }
        //__END__wordpress_
    }
}
