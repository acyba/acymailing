<?php

use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\UserStatClass;

trait WooCommerceTracking
{
    public function onAcymIsTrackingWoocommerce(&$trackingWoocommerce)
    {
        $trackingWoocommerce = $this->getParam('track', 0) == 1;
    }

    public function getCurrency(&$currency)
    {
        if (empty($currency)) $currency = get_woocommerce_currency();
        $woocommerceCurrencies = get_woocommerce_currency_symbols();
        $currency = $woocommerceCurrencies[$currency];
    }

    public function acym_displayTrackingMessage(&$message)
    {
        $remindme = json_decode($this->config->get('remindme', '[]'), true);

        if ($this->getParam('track', 0) != 1 && acym_isExtensionActive('woocommerce/woocommerce.php') && acym_isAdmin() && ACYM_CMS == 'wordpress' && !in_array(
                'woocommerce_tracking',
                $remindme
            )) {
            $message = acym_translation('ACYM_WOOCOMMERCE_TRACKING_INFO');
            $message .= ' <a target="_blank" href="https://docs.acymailing.com/addons/wordpress-add-ons/woocommerce#tracking">'.acym_translation('ACYM_READ_MORE').'</a>';
            $message .= ' <a href="#" class="acym__do__not__remindme acym__do__not__remindme__info" title="woocommerce_tracking">'.acym_translation('ACYM_DO_NOT_REMIND_ME').'</a>';
            acym_display($message, 'info', false);
        } elseif (!in_array('woocommerce_tracking', $remindme)) {
            $remindme[] = 'woocommerce_tracking';
            $this->config->save(['remindme' => json_encode($remindme)]);
        }
    }

    public function trackingWoocommerceAddCookie()
    {
        $trackingWoo = acym_getVar('string', 'linkReferal', '');
        if (empty($trackingWoo)) return;

        $trackingWoo = explode('-', $trackingWoo);

        $hours = $this->getParam('cookie_expire', 1);

        $time = time() + (3600 * $hours);

        setcookie('acym_track_woocommerce', 'mailid-'.$trackingWoo[0].'_userid-'.$trackingWoo[1], $time, COOKIEPATH, COOKIE_DOMAIN);
    }

    public function trackingWoocommerce($result, $order_id)
    {
        $cookie = acym_getVar('string', 'acym_track_woocommerce', '', 'COOKIE');
        if (empty($cookie)) return $result;

        $formattedCookie = [];

		$this->formatCookie($cookie, $formattedCookie);

        if (empty($formattedCookie['userid']) || empty($formattedCookie['mailid'])) return $result;

        $order = wc_get_order($order_id);

        $currency = $order->get_currency();
        if (empty($currency)) return $result;

        $total = (float)$order->get_total() - $order->get_total_shipping();

        $removeVat = (int)($this->getParam('remove_VAT', 0));
        if ($removeVat) {
            $total -= (float)$order->get_total_tax();
        }

        $this->saveTrackingWoocommerceMailStat($formattedCookie, $total, $currency);
        $this->saveTrackingWoocommerceUserStat($formattedCookie, $total, $currency);

        return $result;
    }

    private function formatCookie(&$cookie, &$formattedCookie)
    {
        if (strpos($cookie, '_') === false) return;

        $cookie = explode('_', $cookie);

        foreach ($cookie as $value) {
            if (strpos($value, '-') === false) continue;

            $value = explode('-', $value, 2);
            $formattedCookie[$value[0]] = $value[1];
        }
    }

    private function saveTrackingWoocommerceMailStat($formattedCookie, $total, $currency)
    {
        $mailStatClass = new MailStatClass();
        $mailStat = $mailStatClass->getOneById($formattedCookie['mailid']);

        if (empty($mailStat)) return;

        $newMailStat = [
            'mail_id' => $mailStat->mail_id,
            'tracking_sale' => empty($mailStat->tracking_sale) ? $total : $mailStat->tracking_sale + $total,
            'currency' => $currency,
        ];

        $mailStatClass->save($newMailStat);
    }

    private function saveTrackingWoocommerceUserStat($formattedCookie, $total, $currency)
    {
        $userStatClass = new UserStatClass();
        $userStat = $userStatClass->getOneByMailAndUserId($formattedCookie['mailid'], $formattedCookie['userid']);
        if (empty($userStat)) return;
        unset($userStat->statusSending);
        unset($userStat->open);
        unset($userStat->open_date);

        $userStat->tracking_sale = empty($userStat->tracking_sale) ? $total : $userStat->tracking_sale + $total;
        $userStat->currency = $currency;

        $userStatClass->save($userStat);
    }
}
