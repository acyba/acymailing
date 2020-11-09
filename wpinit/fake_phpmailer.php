<?php

namespace AcyMailing\Init;

use AcyMailing\Helpers\MailerHelper;

class acyFakePhpMailer
{
    public function send()
    {
        return true;
    }
}
