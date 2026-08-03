<?php
// phpcs:disable library_core_files -- Intended to disable the WordPress emails, if the user chooses so.

namespace AcyMailing\WpInit;

defined('ABSPATH') || die('Restricted Access');

use AcyMailing\Helpers\MailerHelper;

class MailDisabler
{
    public function send()
    {
        return true;
    }

    public function IsSMTP()
    {
    }

    public function addReplyTo($replyto, $name = '')
    {
        return true;
    }

    public function setFrom($address, $name = '', $auto = true)
    {
        return true;
    }
}
