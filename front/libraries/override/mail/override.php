<?php

use AcyMailing\Helpers\MailerHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Factory;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Mail\MailHelper;

defined('JPATH_PLATFORM') or die;

jimport('joomla.mail.helper');

class jMail_acym extends PHPMailer
{
    protected static $instances = [];
    public $CharSet = 'utf-8';

    public function __construct($exceptions = true)
    {
        parent::__construct($exceptions);

        // PHPMailer has an issue using the relative path for its language files
        $this->setLanguage('en_gb', __DIR__.'/language/');

        // Configure a callback function to handle errors when $this->edebug() is called
        $this->Debugoutput = function ($message, $level) {
            Log::add(sprintf('Error in Mail API: %s', $message), Log::ERROR, 'mail');
        };

        // If debug mode is enabled then set SMTPDebug to the maximum level
        if (defined('JDEBUG') && JDEBUG) {
            $this->SMTPDebug = 4;
        }

        // Don't disclose the PHPMailer version
        $this->XMailer = ' ';

        PHPMailer::$validator = 'html5';
    }

    public static function getInstance($id = 'Joomla', $exceptions = true)
    {
        if (empty(self::$instances[$id])) {
            self::$instances[$id] = new Mail($exceptions);
        }

        return self::$instances[$id];
    }

    public function Send()
    {
        $success = false;

        // Send the email with AcyMailing if possible
        $ds = DIRECTORY_SEPARATOR;
        if (!empty($this->to[0][0]) && include_once rtrim(JPATH_ADMINISTRATOR, $ds).$ds.'components'.$ds.'com_acym'.$ds.'helpers'.$ds.'helper.php') {
            $mailerHelper = new MailerHelper();
            $success = $mailerHelper->overrideEmail($this->Subject, $this->Body, $this->to[0][0]);
        }

        // We sent the email using AcyMailing
        if ($success) {
            return true;
        }

        if (!function_exists('mail') && $this->Mailer === 'mail') {
            acym_raiseError(500, acym_translation('JLIB_MAIL_FUNCTION_DISABLED'));
        }

        try {
            // We let the CMS send the email
            $result = parent::send();
        } catch (Exception $e) {
            $result = false;

            if ($this->SMTPAutoTLS) {
                $this->SMTPAutoTLS = false;

                try {
                    $result = parent::send();
                } catch (Exception $e) {
                    $result = false;
                }
            }
        }

        if ($result == false) {
            $result = acym_raiseError(500, acym_translation($this->ErrorInfo));
        }

        return $result;
    }

    public function setFrom($address, $name = '', $auto = true)
    {
        try {
            if (parent::setFrom($address, $name, $auto) === false) {
                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    public function setSender($from)
    {
        if (is_array($from)) {
            // If $from is an array we assume it has an address and a name
            if (isset($from[2])) {
                // If it is an array with entries, use them
                $result = $this->SetFrom(MailHelper::cleanLine($from[0]), MailHelper::cleanLine($from[1]), (bool)$from[2]);
            } else {
                $result = $this->SetFrom(MailHelper::cleanLine($from[0]), MailHelper::cleanLine($from[1]));
            }
        } elseif (is_string($from)) {
            // If it is a string we assume it is just the address
            $result = $this->SetFrom(MailHelper::cleanLine($from));
        } else {
            // If it is neither, we throw a warning
            acym_raiseError(500, acym_translationSprintf('JLIB_MAIL_INVALID_EMAIL_SENDER', $from));
        }

        // Check for boolean false return if exception handling is disabled
        if ($result === false) {
            return false;
        }

        return $this;
    }

    public function setSubject($subject)
    {
        $this->Subject = MailHelper::cleanLine($subject);

        return $this;
    }

    public function setBody($content)
    {
        $this->Body = MailHelper::cleanText($content);

        return $this;
    }

    protected function add($recipient, $name = '', $method = 'addAddress')
    {
        $method = lcfirst($method);

        // If the recipient is an array, add each recipient... otherwise just add the one
        if (is_array($recipient)) {
            if (is_array($name)) {
                $combined = array_combine($recipient, $name);

                if ($combined === false) {
                    throw new \InvalidArgumentException("The number of elements for each array isn't equal.");
                }

                foreach ($combined as $recipientEmail => $recipientName) {
                    $recipientEmail = MailHelper::cleanLine($recipientEmail);
                    $recipientName = MailHelper::cleanLine($recipientName);

                    // Wrapped in try/catch if PHPMailer is configured to throw exceptions
                    try {
                        // Check for boolean false return if exception handling is disabled
                        if (call_user_func('parent::'.$method, $recipientEmail, $recipientName) === false) {
                            return false;
                        }
                    } catch (Exception $e) {
                        return false;
                    }
                }
            } else {
                $name = MailHelper::cleanLine($name);

                foreach ($recipient as $to) {
                    $to = MailHelper::cleanLine($to);

                    // Wrapped in try/catch if PHPMailer is configured to throw exceptions
                    try {
                        // Check for boolean false return if exception handling is disabled
                        if (call_user_func('parent::'.$method, $to, $name) === false) {
                            return false;
                        }
                    } catch (Exception $e) {
                        return false;
                    }
                }
            }
        } else {
            $recipient = MailHelper::cleanLine($recipient);

            // Wrapped in try/catch if PHPMailer is configured to throw exceptions
            try {
                // Check for boolean false return if exception handling is disabled
                if (call_user_func('parent::'.$method, $recipient, $name) === false) {
                    return false;
                }
            } catch (Exception $e) {
                return false;
            }
        }

        return $this;
    }

    public function addRecipient($recipient, $name = '')
    {
        return $this->add($recipient, $name, 'addAddress');
    }

    public function addCC($cc, $name = '')
    {
        // If the carbon copy recipient is an array, add each recipient... otherwise just add the one
        if (isset($cc)) {
            return $this->add($cc, $name, 'addCC');
        }

        return $this;
    }

    public function addBCC($bcc, $name = '')
    {
        // If the blind carbon copy recipient is an array, add each recipient... otherwise just add the one
        if (isset($bcc)) {
            return $this->add($bcc, $name, 'addBCC');
        }

        return $this;
    }

    public function addAttachment($path, $name = '', $encoding = 'base64', $type = 'application/octet-stream', $disposition = 'attachment')
    {
        // If the file attachments is an array, add each file... otherwise just add the one
        if (isset($path)) {
            // Wrapped in try/catch if PHPMailer is configured to throw exceptions
            try {
                $result = true;

                if (is_array($path)) {
                    if (!empty($name) && count($path) != count($name)) {
                        throw new InvalidArgumentException('The number of attachments must be equal with the number of name');
                    }

                    foreach ($path as $key => $file) {
                        if (!empty($name)) {
                            $result = parent::addAttachment($file, $name[$key], $encoding, $type, $disposition);
                        } else {
                            $result = parent::addAttachment($file, $name, $encoding, $type, $disposition);
                        }
                    }
                } else {
                    $result = parent::addAttachment($path, $name, $encoding, $type, $disposition);
                }

                // Check for boolean false return if exception handling is disabled
                if ($result === false) {
                    return false;
                }
            } catch (Exception $e) {
                return false;
            }
        }

        return $this;
    }

    public function clearAttachments()
    {
        parent::clearAttachments();

        return $this;
    }

    public function removeAttachment($index = 0)
    {
        if (isset($this->attachment[$index])) {
            unset($this->attachment[$index]);
        }

        return $this;
    }

    public function addReplyTo($replyto, $name = '')
    {
        return $this->add($replyto, $name, 'addReplyTo');
    }

    public function isHtml($ishtml = true)
    {
        parent::isHTML($ishtml);

        return $this;
    }

    public function isSendmail()
    {
        // Prefer the Joomla configured sendmail path and default to the configured PHP path otherwise
        $sendmail = Factory::getConfig()->get('sendmail', ini_get('sendmail_path'));

        // And if we still don't have a path, then use the system default for Linux
        if (empty($sendmail)) {
            $sendmail = '/usr/sbin/sendmail';
        }

        $this->Sendmail = $sendmail;
        $this->Mailer = 'sendmail';
    }

    public function useSendmail($sendmail = null)
    {
        $this->Sendmail = $sendmail;

        if (!empty($this->Sendmail)) {
            $this->isSendmail();

            return true;
        } else {
            $this->isMail();

            return false;
        }
    }

    public function useSmtp($auth = null, $host = null, $user = null, $pass = null, $secure = null, $port = 25)
    {
        $this->SMTPAuth = $auth;
        $this->Host = $host;
        $this->Username = $user;
        $this->Password = $pass;
        $this->Port = $port;

        if ($secure == 'ssl' || $secure == 'tls') {
            $this->SMTPSecure = $secure;
        }

        if (($this->SMTPAuth !== null && $this->Host !== null && $this->Username !== null && $this->Password !== null) || ($this->SMTPAuth === null && $this->Host !== null)) {
            $this->isSMTP();

            return true;
        } else {
            $this->isMail();

            return false;
        }
    }

    public function sendMail(
        $from, $fromName, $recipient, $subject, $body, $mode = false, $cc = null, $bcc = null, $attachment = null, $replyTo = null, $replyToName = null
    ) {
        // Create config object
        $config = Factory::getConfig();

        $this->setSubject($subject);
        $this->setBody($body);

        // Are we sending the email as HTML?
        $this->isHtml($mode);

        /*
         * Do not send the message if adding any of the below items fails
         */

        if ($this->addRecipient($recipient) === false) {
            return false;
        }

        if ($this->addCc($cc) === false) {
            return false;
        }

        if ($this->addBcc($bcc) === false) {
            return false;
        }

        if ($this->addAttachment($attachment) === false) {
            return false;
        }

        // Take care of reply email addresses
        if (is_array($replyTo)) {
            $numReplyTo = count($replyTo);

            for ($i = 0 ; $i < $numReplyTo ; $i++) {
                if ($this->addReplyTo($replyTo[$i], $replyToName[$i]) === false) {
                    return false;
                }
            }
        } elseif (isset($replyTo)) {
            if ($this->addReplyTo($replyTo, $replyToName) === false) {
                return false;
            }
        } elseif ($config->get('replyto')) {
            $this->addReplyTo($config->get('replyto'), $config->get('replytoname'));
        }

        // Add sender to replyTo only if no replyTo received
        $autoReplyTo = (empty($this->ReplyTo)) ? true : false;

        if ($this->setSender([$from, $fromName, $autoReplyTo]) === false) {
            return false;
        }

        return $this->Send();
    }

    public function sendAdminMail($adminName, $adminEmail, $email, $type, $title, $author, $url = null)
    {
        $subject = acym_translationSprintf('JLIB_MAIL_USER_SUBMITTED', $type);

        $message = sprintf(acym_translation('JLIB_MAIL_MSG_ADMIN'), $adminName, $type, $title, $author, $url, $url, 'administrator', $type);
        $message .= acym_translation('JLIB_MAIL_MSG')."\n";

        if ($this->addRecipient($adminEmail) === false) {
            return false;
        }

        $this->setSubject($subject);
        $this->setBody($message);

        return $this->Send();
    }
}
