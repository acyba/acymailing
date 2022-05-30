<?php

namespace AcyMailing\Helpers;

require_once ACYM_INC.'phpmailer'.DS.'exception.php';
require_once ACYM_INC.'phpmailer'.DS.'smtp.php';
require_once ACYM_INC.'phpmailer'.DS.'phpmailer.php';
require_once ACYM_INC.'emogrifier.php';

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\OverrideClass;
use AcyMailing\Classes\UrlClass;
use AcyMailing\Classes\UserClass;
use acyPHPMailer\Exception;
use acyPHPMailer\SMTP;
use acyPHPMailer\acyPHPMailer;
use acymEmogrifier\acymEmogrifier;

class MailerHelper extends acyPHPMailer
{
    // The DKIM fails when the X-Mailer is added and the user uses their own keys, it makes no sense D:
    public $XMailer = ' ';

    // We remove default values
    public $SMTPAutoTLS = false;

    var $encodingHelper;
    var $editorHelper;
    var $userClass;
    var $config;

    var $report = true;
    var $alreadyCheckedAddresses = false;
	var $errorNumber = 0;
    //Error number which induct a new try soon
    var $errorNewTry = [1, 6];
    var $autoAddUser = false;
    var $reportMessage = '';

    // Should we track the sending of a	message (used for welcoming message)
    var $trackEmail = false;

    //External method name
    var $externalMailer;

    // We need those attributes to be public for our tag system
    public $to = [];
    public $cc = [];
    public $bcc = [];
    public $ReplyTo = [];
    public $attachment = [];
    public $CustomHeader = [];

    // To import custom stylesheet from user
    public $stylesheet = '';
    public $settings;

    // Used to store special dynamic text content
    public $parameters = [];

    // Preset override email
    public $overrideEmailToSend = '';

    public $userLanguage = '';

    public $mailId;
    public $receiverEmail;

    public $isTest = false;
    public $isSpamTest = false;

    public function __construct()
    {
        parent::__construct();

        $this->encodingHelper = new EncodingHelper();
        $this->editorHelper = new EditorHelper();
        $this->userClass = new UserClass();
        $this->config = acym_config();
        $this->setFrom($this->getSendSettings('from_email'), $this->getSendSettings('from_name'));
        $this->Sender = $this->cleanText($this->config->get('bounce_email'));
        if (empty($this->Sender)) {
            $this->Sender = '';
        }
        $maxLineLength = $this->config->get('mailer_wordwrap', 0);
        if (!empty($maxLineLength) && self::MAX_LINE_LENGTH > $maxLineLength) {
            $this->WordWrap = $maxLineLength;
        }

        $externalSendingMethod = [];
        acym_trigger('onAcymGetSendingMethods', [&$externalSendingMethod, true]);
        $externalSendingMethod = array_keys($externalSendingMethod['sendingMethods']);

        $mailerMethodConfig = $this->config->get('mailer_method', 'phpmail');

        // Default mailer is to use PHP's mail function
        if ($mailerMethodConfig == 'smtp') {
            $this->isSMTP();
            $this->Host = trim($this->config->get('smtp_host'));
            $port = $this->config->get('smtp_port');
            // 465 is default port for SSL
            if (empty($port) && $this->config->get('smtp_secured') == 'ssl') {
                $port = 465;
            }
            if (!empty($port)) {
                $this->Host .= ':'.$port;
            }
            $this->SMTPAuth = (bool)$this->config->get('smtp_auth', true);
            $this->Username = trim($this->config->get('smtp_username'));
            $this->Password = trim($this->config->get('smtp_password'));
            //SMTP Secure to connect to Gmail for example (tls)
            $this->SMTPSecure = trim((string)$this->config->get('smtp_secured'));

            if (empty($this->Sender)) {
                $this->Sender = strpos($this->Username, '@') ? $this->Username : $this->config->get('from_email');
            }
        } elseif ($mailerMethodConfig == 'sendmail') {
            $this->isSendmail();
            $this->Sendmail = trim($this->config->get('sendmail_path'));
            if (empty($this->Sendmail)) {
                $this->Sendmail = '/usr/sbin/sendmail';
            }
        } elseif ($mailerMethodConfig == 'qmail') {
            $this->isQmail();
        } elseif ($mailerMethodConfig == 'elasticemail') {
            $port = $this->config->get('elasticemail_port', 'rest');
            if (is_numeric($port)) {
                $this->isSMTP();
                if ($port == '25') {
                    $this->Host = 'smtp25.elasticemail.com:25';
                } else {
                    $this->Host = 'smtp.elasticemail.com:2525';
                }
                $this->Username = trim($this->config->get('elasticemail_username'));
                $this->Password = trim($this->config->get('elasticemail_password'));
                $this->SMTPAuth = true;
            } else {
                //REST API!
                include_once ACYM_INC.'phpmailer'.DS.'elasticemail.php';
                $this->Mailer = 'elasticemail';
                $this->{$this->Mailer} = new \acyElasticemail();
                $this->{$this->Mailer}->Username = trim($this->config->get('elasticemail_username'));
                $this->{$this->Mailer}->Password = trim($this->config->get('elasticemail_password'));
            }
        } elseif ($mailerMethodConfig == 'amazon') {
            $this->isSMTP();
            $amazonCredentials = [];
            acym_trigger('onAcymGetCredentialsSendingMethod', [&$amazonCredentials, 'amazon'], 'plgAcymAmazon');
            $this->Host = trim($amazonCredentials['amazon_host']).':587';
            $this->Username = trim($amazonCredentials['amazon_username']);
            $this->Password = trim($amazonCredentials['amazon_password']);
            $this->SMTPAuth = true;
            $this->SMTPSecure = 'tls';
        } elseif (in_array($mailerMethodConfig, $externalSendingMethod)) {
            $this->isExternal($mailerMethodConfig);
        } else {
            $this->isMail();
        }

        //Do we have a DKIM validation?
        if ($this->config->get('dkim', 0) && $this->Mailer != 'elasticemail') {
            $this->DKIM_domain = $this->config->get('dkim_domain');
            $this->DKIM_selector = $this->config->get('dkim_selector', 'acy');
            //Just in case of...
            if (empty($this->DKIM_selector)) $this->DKIM_selector = 'acy';
            $this->DKIM_passphrase = $this->config->get('dkim_passphrase');
            $this->DKIM_identity = $this->config->get('dkim_identity');
            $this->DKIM_private = trim($this->config->get('dkim_private'));
            $this->DKIM_private_string = trim($this->config->get('dkim_private'));
        }

        //Set the Charset, by default 'utf-8'
        $this->CharSet = strtolower($this->config->get('charset'));
        if (empty($this->CharSet)) {
            $this->CharSet = 'utf-8';
        }

        $this->clearAll();

        //Set the encoding format, should be 8 bit by default.
        $this->Encoding = $this->config->get('encoding_format');
        if (empty($this->Encoding)) {
            $this->Encoding = '8bit';
        }

        @ini_set('pcre.backtrack_limit', 1000000);

        $this->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];

        // Dynamic text for debug purposes
        $this->addParamInfo();
    }

    /**
     * Send messages using SMTP.
     */
    public function isExternal($method)
    {
        $this->Mailer = 'external';
        $this->externalMailer = $method;
    }

    protected function elasticemailSend($MIMEHeader, $MIMEBody)
    {
        $result = $this->elasticemail->sendMail($this);
        if (!$result) {
            $this->setError($this->elasticemail->error);
        }

        return $result;
    }

    protected function externalSend($MIMEHeader, $MIMEBody)
    {
        $reply_to = array_shift($this->ReplyTo);

        $response = [];

        $fromName = empty($this->FromName) ? $this->config->get('from_name', '') : $this->FromName;

        $bcc = !empty($this->bcc) ? $this->bcc : [];

        $attachments = [];
        if (!empty($this->attachment) && $this->config->get('embed_files')) {
            foreach ($this->attachment as $i => $oneAttach) {
                $encodedContent = $this->encodeFile($oneAttach[0], $oneAttach[3]);
                $this->attachment[$i]['contentEncoded'] = $encodedContent;
            }
            $attachments = $this->attachment;
        }

        $data = [
            &$response,
            $this->externalMailer,
            ['email' => $this->to[0][0], 'name' => $this->to[0][1]],
            $this->Subject,
            ['email' => $this->From, 'name' => $fromName],
            ['email' => $reply_to[0], 'name' => $reply_to[1]],
            $this->Body,
            $bcc,
            $attachments,
            empty($this->id) ? null : $this->id,
        ];
        acym_trigger('onAcymSendEmail', $data);

        if ($response['error']) {
            $this->setError($response['message']);

            return false;
        }

        return true;
    }

    public function send()
    {
        if (!file_exists(ACYM_INC.'phpmailer'.DS.'phpmailer.php')) {
            $this->reportMessage = acym_translationSprintf('ACYM_X_FILE_MISSING', 'phpmailer', ACYM_INC.'phpmailer'.DS);
            if ($this->report) {
                acym_enqueueMessage($this->reportMessage, 'error');
            }

            return false;
        }

        if (empty($this->Subject) || empty($this->Body)) {
            if ($this->isTest && empty($this->Subject)) {
                $this->Subject = acym_translation('ACYM_EMAIL_SUBJECT');
            } else {
                $this->reportMessage = acym_translation('ACYM_SEND_EMPTY');
                $this->errorNumber = 8;
                if ($this->report) {
                    acym_enqueueMessage($this->reportMessage, 'error');
                }

                return false;
            }
        }

        //Check if there is at least one reply to otherwise add the default one.
        if (empty($this->ReplyTo) && empty($this->ReplyToQueue)) {
            if (!empty($this->replyemail)) {
                $replyToEmail = $this->replyemail;
            } elseif ($this->config->get('from_as_replyto', 1) == 1) {
                $replyToEmail = $this->getSendSettings('from_email');
            } else {
                $replyToEmail = $this->getSendSettings('replyto_email');
            }

            if (!empty($this->replyname)) {
                $replyToName = $this->replyname;
            } elseif ($this->config->get('from_as_replyto', 1) == 1) {
                $replyToName = $this->getSendSettings('from_name');
            } else {
                $replyToName = $this->getSendSettings('replyto_name');
            }

            $this->_addReplyTo($replyToEmail, $replyToName);
        }

        //Embed images if there is images to embed...
        if ((bool)$this->config->get('embed_images', 0) && $this->Mailer != 'elasticemail') {
            $this->embedImages();
        }

        if (!$this->alreadyCheckedAddresses) {
            $this->alreadyCheckedAddresses = true;

            $replyToTmp = '';
            if (!empty($this->ReplyTo)) {
                $replyToTmp = reset($this->ReplyTo);
                $replyToTmp = $replyToTmp[0];
            } elseif (!empty($this->ReplyToQueue)) {
                $replyToTmp = reset($this->ReplyToQueue);
                $replyToTmp = $replyToTmp[1];
            }

            if (empty($replyToTmp) || !acym_isValidEmail($replyToTmp)) {
                $this->reportMessage = acym_translation('ACYM_VALID_EMAIL').' ( '.acym_translation('ACYM_REPLYTO_EMAIL').' : '.(empty($this->ReplyTo) ? '' : $replyToTmp).' ) ';
                $this->errorNumber = 9;
                if ($this->report) {
                    acym_enqueueMessage($this->reportMessage, 'error');
                }

                return false;
            }

            //Check the from address
            if (empty($this->From) || !acym_isValidEmail($this->From)) {
                $this->reportMessage = acym_translation('ACYM_VALID_EMAIL').' ( '.acym_translation('ACYM_FROM_EMAIL').' : '.$this->From.' ) ';
                $this->errorNumber = 9;
                if ($this->report) {
                    acym_enqueueMessage($this->reportMessage, 'error');
                }

                return false;
            }

            if (!empty($this->Sender) && !acym_isValidEmail($this->Sender)) {
                $this->reportMessage = acym_translation('ACYM_VALID_EMAIL').' ( '.acym_translation('ACYM_BOUNCE_EMAIL').' : '.$this->Sender.' ) ';
                $this->errorNumber = 9;
                if ($this->report) {
                    acym_enqueueMessage($this->reportMessage, 'error');
                }

                return false;
            }
        }

        //We will change the encoding format in case of its needed...
        //We always come from utf-8 to transform to something else!
        if (function_exists('mb_convert_encoding')) {
            $this->Body = mb_convert_encoding($this->Body, 'HTML-ENTITIES', 'UTF-8');
            //Fix The Bat issues for special encoding as &sigmaf; was interpreted as &sigma;f;...
            $this->Body = str_replace(['&amp;', '&sigmaf;'], ['&', 'ς'], $this->Body);
        }

        if ($this->CharSet != 'utf-8') {
            $this->Body = $this->encodingHelper->change($this->Body, 'UTF-8', $this->CharSet);
            $this->Subject = $this->encodingHelper->change($this->Subject, 'UTF-8', $this->CharSet);
        }

        //Let's do some referal if we send using elasticemail
        if (strpos($this->Host, 'elasticemail')) {
            $this->addCustomHeader('referral', '2f0447bb-173a-459d-ab1a-ab8cbebb9aab');
        }

        // These characters can break the send process, let's remove them from the subject
        $this->Subject = str_replace(
            ['’', '“', '”', '–'],
            ["'", '"', '"', '-'],
            $this->Subject
        );

        // BE CAREFUL! This space is not a space, it's a ALT0160 chr(194) by char(32) which means almost &nbsp;
        $this->Body = str_replace(" ", ' ', $this->Body);

        $externalSending = false;

        $mailClass = new MailClass();
        $isTransactional = $this->isTest || $this->isSpamTest || (!empty($this->defaultMail[$this->id]) && $mailClass->isTransactionalMail($this->defaultMail[$this->id]));

        acym_trigger('onAcymProcessQueueExternalSendingCampaign', [&$externalSending, $isTransactional]);

        $warnings = '';

        //__START__production_
        if (ACYM_PRODUCTION) {
            if ($externalSending) {
                $result = true;
                acym_trigger('onAcymRegisterReceiverContentAndList', [&$result, $this->Subject, $this->Body, $this->receiverEmail, $this->id, &$warnings]);
            } else {
                ob_start();
                $result = parent::send();
                $warnings = ob_get_clean();
            }
        }
        //__END__production_

        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $result = true;
        }
        //__END__demo_

        //display error if bloque is displayed... for free.fr especially
        if (!empty($warnings) && strpos($warnings, 'bloque')) {
            $result = false;
        }

        $this->mailHeader = '';

        $receivers = [];
        foreach ($this->to as $oneReceiver) {
            $receivers[] = $oneReceiver[0];
        }
        if (!$result) {
            $this->reportMessage = acym_translationSprintf('ACYM_SEND_ERROR', '<b>'.$this->Subject.'</b>', '<b>'.implode(' , ', $receivers).'</b>');
            if (!empty($this->ErrorInfo)) {
                $this->reportMessage .= " \n\n ".$this->ErrorInfo;
            }
            if (!empty($warnings)) {
                $this->reportMessage .= " \n\n ".$warnings;
            }
            $this->errorNumber = 1;
            if ($this->report) {
                //We display the report here... we add a link to our doc for the "could not instantiate mail function".
                $this->reportMessage = str_replace(
                    'Could not instantiate mail function',
                    '<a target="_blank" href="'.ACYM_REDIRECT.'could-not-instantiate-mail-function">'.acym_translation('ACYM_COUND_NOT_INSTANCIATE_MAIL_FUCNTION').'</a>',
                    $this->reportMessage
                );
                acym_enqueueMessage(nl2br($this->reportMessage), 'error');
            }
        } else {
            $this->reportMessage = acym_translationSprintf('ACYM_SEND_SUCCESS', '<b>'.$this->Subject.'</b>', '<b>'.implode(' , ', $receivers).'</b>');
            if (!empty($warnings)) {
                $this->reportMessage .= " \n\n ".$warnings;
            }
            if ($this->report) {
                if (acym_isAdmin()) {
                    acym_enqueueMessage(preg_replace('#(<br( ?/)?>){2}#', '<br />', nl2br($this->reportMessage)), 'info');
                }
            }
        }

        return $result;
    }

    public function clearAll()
    {
        $this->Subject = '';
        $this->Body = '';
        $this->AltBody = '';
        $this->ClearAllRecipients();
        $this->ClearAttachments();
        $this->ClearCustomHeaders();
        $this->ClearReplyTos();
        $this->errorNumber = 0;
        $this->MessageID = '';
        $this->ErrorInfo = '';
        $this->setFrom($this->getSendSettings('from_email'), $this->getSendSettings('from_name'));
    }

    private function loadUrlAndStyle($mailId)
    {
        // Replace the urls into absolute urls
        $this->defaultMail[$mailId]->body = acym_absoluteURL($this->defaultMail[$mailId]->body);

        $style = $this->getEmailStylesheet($this->defaultMail[$mailId]);
        $this->prepareEmailContent($this->defaultMail[$mailId], $style);
    }

    public function load($mailId, $user = null)
    {
        $mailClass = new MailClass();
        if (!empty($this->overrideEmailToSend)) {
            $this->defaultMail[$mailId] = $this->overrideEmailToSend;
        } else {
            $this->defaultMail[$mailId] = $mailClass->getOneById($mailId, true);
        }

        global $acymLanguages;
        if (!acym_isMultilingual()) {
            if (empty($this->defaultMail[$mailId])) {
                $this->defaultMail[$mailId] = $mailClass->getOneByName($mailId, true);

                if (!empty($this->defaultMail[$mailId]->id)) {
                    $this->defaultMail[$this->defaultMail[$mailId]->id] = $this->defaultMail[$mailId];
                }
            }
        } elseif (empty($this->overrideEmailToSend)) {
            $defaultLanguage = $this->config->get('multilingual_default', ACYM_DEFAULT_LANGUAGE);
            $mails = $mailClass->getMultilingualMails($mailId);
            if (empty($mails)) {
                $mails = $mailClass->getMultilingualMailsByName($mailId);
            }

            $this->userLanguage = $user != null && !empty($user->language) ? $user->language : $defaultLanguage;

            if (!empty($mails)) {
                $languages = array_keys($mails);
                if (count($languages) == 1) {
                    $key = $languages[0];
                } elseif (empty($mails[$this->userLanguage])) {
                    $key = $defaultLanguage;
                } else {
                    $key = $this->userLanguage;
                }
                if (isset($mails[$key])) $this->defaultMail[$mailId] = $mails[$key];
            } else {
                unset($this->defaultMail[$mailId]);

                return false;
            }

            $acymLanguages['userLanguage'] = $this->userLanguage;
            $this->setFrom($this->getSendSettings('from_email'), $this->getSendSettings('from_name'));
        }

        //We could not load the email...
        if (empty($this->defaultMail[$mailId]->id)) {
            unset($this->defaultMail[$mailId]);

            return false;
        }

        if (!empty($this->defaultMail[$mailId]->attachments)) {
            $this->defaultMail[$mailId]->attach = [];

            $attachments = json_decode($this->defaultMail[$mailId]->attachments);
            foreach ($attachments as $oneAttach) {
                $attach = new \stdClass();
                $attach->name = basename($oneAttach->filename);
                $attach->filename = str_replace(['/', '\\'], DS, ACYM_ROOT).$oneAttach->filename;
                $attach->url = ACYM_LIVE.$oneAttach->filename;
                $this->defaultMail[$mailId]->attach[] = $attach;
            }
        }

        acym_trigger('replaceContent', [&$this->defaultMail[$mailId], true]);

        $this->loadUrlAndStyle($mailId);

        $this->id = $mailId;

        return $this->defaultMail[$mailId];
    }

    private function getEmailStylesheet(&$mail)
    {
        static $foundationCSS = null;
        $style = [];
        if (empty($foundationCSS)) {
            $foundationCSS = acym_fileGetContent(ACYM_MEDIA.'css'.DS.'libraries'.DS.'foundation_email.min.css');
            // Remove the #acym__wysid__template prefix, not needed in sent emails
            $foundationCSS = str_replace('#acym__wysid__template ', '', $foundationCSS);
        }

        // If this is a drag and drop mail we add foundation css for email
        if (strpos($mail->body, 'acym__wysid__template') !== false) $style['foundation'] = $foundationCSS;

        static $emailFixes = null;
        if (empty($emailFixes)) $emailFixes = acym_getEmailCssFixes();
        $style[] = $emailFixes;

        if (!empty($mail->stylesheet)) $style[] = $mail->stylesheet;

        $settingsStyles = $this->editorHelper->getSettingsStyle($mail->settings);
        if (!empty($settingsStyles)) $style[] = $settingsStyles;

        preg_match('@<[^>"t]*body[^>]*>@', $mail->body, $matches);
        if (empty($matches[0])) $mail->body = '<body yahoo="fix">'.$mail->body.'</body>';

        //We get all the content of the tag styles in the body
        $styleFoundInBody = preg_match_all('/<\s*style[^>]*>(.*?)<\s*\/\s*style>/s', $mail->body, $matches);
        if ($styleFoundInBody) {
            foreach ($matches[1] as $match) {
                $style[] = $match;
            }
        }

        return $style;
    }

    private function prepareEmailContent(&$mail, $style)
    {
        // We inline all the styles we previously get
        // Emogrifer deletes all the <style> tags in the body
        $emogrifier = new acymEmogrifier($mail->body, implode('', $style));
        $mail->body = $emogrifier->emogrifyBodyContent();

        //We get all the media queries from all the CSS
        $style[] = $emogrifier->mediaCSS;

        preg_match('@<[^>"t]*/body[^>]*>@', $mail->body, $matches);
        if (empty($matches[0])) $mail->body = $mail->body.'</body>';

        // We remove the foundation library because it's already inlined and we just need the media queries
        //By the way there are more than 23 000 char in foundation library
        unset($style['foundation']);

        $finalContent = '<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office"><head>';
        $finalContent .= '<!--[if gte mso 9]><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml><![endif]-->';
        $finalContent .= '<meta http-equiv="Content-Type" content="text/html; charset='.strtolower($this->config->get('charset')).'" />'."\n";
        $finalContent .= '<meta name="viewport" content="width=device-width, initial-scale=1.0" />'."\n";
        $finalContent .= '<title>'.$mail->subject.'</title>'."\n";
        //We add the CSS like that for gmail because it delete the tag style over 8000 char
        $finalContent .= '<style type="text/css">'.implode('</style><style type="text/css">', $style).'</style>';
        $finalContent .= '<!--[if mso]><style type="text/css">#acym__wysid__template center > table { width: 580px; }</style><![endif]-->';
        $finalContent .= '<!--[if !mso]><style type="text/css">#acym__wysid__template center > table { width: 100%; }</style><![endif]-->';
        if (!empty($mail->headers)) $finalContent .= $mail->headers;
        $finalContent .= '</head>'.$mail->body.'</html>';

        $mail->body = $finalContent;
    }

    private function canTrack($mailId, $user)
    {
        if (empty($mailId) || empty($user) || !isset($user->tracking) || $user->tracking != 1) return false;

        $mailClass = new MailClass();

        $mail = $mailClass->getOneById($mailId);
        if (!empty($mail) && $mail->tracking != 1) return false;

        $lists = $mailClass->getAllListsByMailIdAndUserId($mailId, $user->id);

        foreach ($lists as $list) {
            if ($list->tracking != 1) return false;
        }

        return true;
    }

    private function loadUser($user)
    {
        if (is_string($user) && strpos($user, '@')) {
            $receiver = $this->userClass->getOneByEmail($user);

            //If we send notifications or tests, we will automatically add the user in order to have the links working fine
            if (empty($receiver) && $this->autoAddUser && acym_isValidEmail($user)) {
                //We directly add the user and send and load him.
                $newUser = new \stdClass();
                $newUser->email = $user;
                $this->userClass->checkVisitor = false;
                $this->userClass->sendConf = false;
                acym_setVar('acy_source', 'When sending a test');
                $userId = $this->userClass->save($newUser);
                $receiver = $this->userClass->getOneById($userId);
            }
        } elseif (is_object($user)) {
            $receiver = $user;
        } else {
            $receiver = $this->userClass->getOneById($user);
        }

        $this->userLanguage = empty($receiver->language) ? acym_getLanguageTag() : $receiver->language;

        $this->receiverEmail = $receiver->email;

        return $receiver;
    }

    /**
     * @param $mailId   Int the Id of the acym_mail row
     * @param $user     Mixed Can be the user Id, an email address or the user object
     * @param $isTest   Boolean If we send a test
     * @param $testNote String Message added at the top of the sent test
     * @param $clear    Boolean If we want to clear the mailer parameters
     *
     * @return bool
     * @throws Exception
     */
    public function sendOne($mailId, $user, $isTest = false, $testNote = '', $clear = true)
    {
        if ($clear) {
            $this->clearAll();
        }

        $receiver = $this->loadUser($user);
        $this->isTest = $isTest;

        //Load the mail if it's not already loaded
        if (!isset($this->defaultMail[$mailId]) && !$this->load($mailId, $receiver)) {
            $this->reportMessage = 'Can not load the e-mail : '.acym_escape($mailId);
            if ($this->report) {
                acym_enqueueMessage($this->reportMessage, 'error');
            }
            $this->errorNumber = 2;

            return false;
        }


        if (empty($receiver->email)) {
            $this->reportMessage = acym_translationSprintf('ACYM_SEND_ERROR_USER', '<b><i>'.acym_escape($user).'</i></b>');
            if ($this->report) {
                acym_enqueueMessage($this->reportMessage, 'error');
            }
            //Error : user not found
            $this->errorNumber = 4;

            return false;
        }

        //Lets try to do something even cooler and specify a messageID instead which will be kept by most mail clients and in the feedback loop...
        $this->MessageID = "<".preg_replace(
                "|[^a-z0-9+_]|i",
                '',
                base64_encode(rand(0, 9999999))."AC".$receiver->id."Y".$this->defaultMail[$mailId]->id."BA".base64_encode(time().rand(0, 99999))
            )."@".$this->serverHostname().">";

        // Set receiver name
        $addedName = '';
        if ($this->config->get('add_names', true)) {
            $addedName = $this->cleanText($receiver->name);
            //We do not set a name if the name is the same as the email address, it prevents the email from being sent with some mail servers
            if ($addedName == $this->cleanText($receiver->email)) {
                $addedName = '';
            }
        }
        $this->addAddress($this->cleanText($receiver->email), $addedName);

        $this->isHTML(true);

        $this->Subject = $this->defaultMail[$mailId]->subject;
        $this->Body = $this->defaultMail[$mailId]->body;
        if ($this->isTest && $testNote != '') {
            $this->Body = '<div style="text-align: center; padding: 25px; font-family: Poppins; font-size: 20px">'.$testNote.'</div>'.$this->Body;
        }
        $this->Preheader = $this->defaultMail[$mailId]->preheader;

        if (!empty($this->defaultMail[$mailId]->stylesheet)) {
            $this->stylesheet = $this->defaultMail[$mailId]->stylesheet;
        }
        $this->settings = json_decode($this->defaultMail[$mailId]->settings, true);

        if (!empty($this->defaultMail[$mailId]->headers)) {
            $this->mailHeader = $this->defaultMail[$mailId]->headers;
        }

        $this->setFrom($this->getSendSettings('from_email', $mailId), $this->getSendSettings('from_name', $mailId));
        $this->_addReplyTo($this->defaultMail[$mailId]->reply_to_email, $this->defaultMail[$mailId]->reply_to_name);

        if (!empty($this->defaultMail[$mailId]->bcc)) {
            $bcc = trim(str_replace([',', ' '], ';', $this->defaultMail[$mailId]->bcc));
            $allBcc = explode(';', $bcc);
            foreach ($allBcc as $oneBcc) {
                if (empty($oneBcc)) continue;
                $this->AddBCC($oneBcc);
            }
        }

        //Add the attachments here...
        if (!empty($this->defaultMail[$mailId]->attach)) {
            if ($this->config->get('embed_files')) {
                foreach ($this->defaultMail[$mailId]->attach as $attachment) {
                    $this->addAttachment($attachment->filename);
                }
            } else {
                $attachStringHTML = '<br /><fieldset><legend>'.acym_translation('ACYM_ATTACHMENTS').'</legend><table>';
                foreach ($this->defaultMail[$mailId]->attach as $attachment) {
                    $attachStringHTML .= '<tr><td><a href="'.$attachment->url.'" target="_blank">'.$attachment->name.'</a></td></tr>';
                }
                $attachStringHTML .= '</table></fieldset>';

                $this->Body .= $attachStringHTML;
            }
        }

        //We add the intro text at the top of the body
        if (!empty($this->introtext)) {
            $this->Body = $this->introtext.$this->Body;
        }

        $preheader = '';
        if (!empty($this->Preheader)) {
            $spacing = '';

            for ($x = 0 ; $x < 100 ; $x++) {
                $spacing .= '&nbsp;&zwnj;';
            }
            $preheader = '<!--[if !mso 9]><!--><div style="visibility:hidden;mso-hide:all;font-size:0;color:transparent;height:0;line-height:0;max-height:0;max-width:0;opacity:0;overflow:hidden;">'.$this->Preheader.$spacing.'</div><!--<![endif]-->';
        }

        if (!empty($preheader)) {
            //We want to insert the preview at the start of the body so we match the start of the mail
            preg_match('#(<(.*)<body(.*)>)#Uis', $this->Body, $matches);
            if (empty($matches) || empty($matches[1])) {
                $this->Body = $preheader.$this->Body;
            } else {
                $this->Body = $matches[1].$preheader.str_replace($matches[1], '', $this->Body);
            }
        }

        //We replace the user tags here and for that, we will create a new object like if it was an e-mail from the database
        //So that we can simplify the tag system and it replaces always the same thing!

        $this->replaceParams();

        //We give the whole object as parameter in reference so we can add things the way we want... attachments, bcc... whatever...
        $this->body = &$this->Body;
        $this->altbody = &$this->AltBody;
        $this->subject = &$this->Subject;
        $this->from = &$this->From;
        $this->fromName = &$this->FromName;
        $this->replyto = &$this->ReplyTo;
        $this->replyname = $this->defaultMail[$mailId]->reply_to_name;
        $this->replyemail = $this->defaultMail[$mailId]->reply_to_email;
        $this->id = $this->defaultMail[$mailId]->id;
        $this->creator_id = $this->defaultMail[$mailId]->creator_id;
        $this->type = $this->defaultMail[$mailId]->type;
        $this->stylesheet = &$this->stylesheet;
        $this->links_language = $this->defaultMail[$mailId]->links_language;

        if (!$this->isTest && $this->canTrack($mailId, $receiver)) {
            $this->statPicture($this->id, $receiver->id);
            $this->body = acym_absoluteURL($this->body);
            $this->statClick($this->id, $receiver->id);
            if (acym_isTrackingSalesActive()) $this->trackingSales($this->id, $receiver->id);
        }

        $this->replaceParams();

        // Sending a spam-test, use the current user instead
        if (strpos($receiver->email, '@mailtester.acyba.com') !== false) {
            $currentUser = $this->userClass->getOneByEmail(acym_currentUserEmail());
            if (empty($currentUser)) {
                $currentUser = $receiver;
            }
            $result = acym_trigger('replaceUserInformation', [&$this, &$currentUser, true]);
        } else {
            $result = acym_trigger('replaceUserInformation', [&$this, &$receiver, true]);
            foreach ($result as $oneResult) {
                if (!empty($oneResult) && !$oneResult['send']) {
                    $this->reportMessage = $oneResult['message'];

                    return -1;
                }
            }
        }
        if (!empty($acymLanguages['userLanguage'])) unset($acymLanguages['userLanguage']);

        if ($this->config->get('multiple_part', false)) {
            $this->altbody = $this->textVersion($this->Body);
        }

        $this->replaceParams();

        foreach ($result as $oneResult) {
            if (!empty($oneResult) && $oneResult['emogrifier']) {
                $this->loadUrlAndStyle($mailId);
                break;
            }
        }

        $status = $this->send();
        if ($this->trackEmail) {
            $helperQueue = new QueueHelper();
            $statsAdd = [];
            $statsAdd[$this->id][$status][] = $receiver->id;
            $helperQueue->statsAdd($statsAdd);
            $this->trackEmail = false;
        }

        return $status;
    }

    private function trackingSales($mailId, $userId)
    {
        preg_match_all('#href[ ]*=[ ]*"(?!mailto:|\#|ymsgr:|callto:|file:|ftp:|webcal:|skype:|tel:)([^"]+)"#Ui', $this->body, $results);
        if (empty($results)) return;

        foreach ($results[1] as $key => $url) {
            $simplifiedUrl = str_replace(['https://', 'http://', 'www.'], '', $url);
            $simplifiedWebsite = str_replace(['https://', 'http://', 'www.'], '', ACYM_LIVE);
            if (strpos($simplifiedUrl, rtrim($simplifiedWebsite, '/')) === false || strpos($url, 'task=unsub')) continue;

            $toAddUrl = (strpos($url, '?') === false ? '?' : '&').'linkReferal='.$mailId.'-'.$userId;

            $posHash = strpos($url, '#');
            if ($posHash !== false) {
                $newURL = substr($url, 0, $posHash).$toAddUrl.substr($url, $posHash);
            } else {
                $newURL = $url.$toAddUrl;
            }

            $this->body = preg_replace('#href="('.preg_quote($url, '#').')"#Uis', 'href="'.$newURL.'"', $this->body);
        }
    }

    public function statPicture($mailId, $userId)
    {
        $pictureLink = acym_frontendLink('frontstats&task=openStats&id='.$mailId.'&userid='.$userId, true, false);

        //we will add the stat picture...
        //We use some parameters so that we can define another height/width and even change the blank image into something else...
        //Why not generate the header this way??
        $widthsize = 50;
        $heightsize = 1;
        $width = empty($widthsize) ? '' : ' width="'.$widthsize.'" ';
        $height = empty($heightsize) ? '' : ' height="'.$heightsize.'" ';

        $statPicture = '<img class="spict" alt="Statistics image" src="'.$pictureLink.'"  border="0" '.$height.$width.'/>';

        if (strpos($this->body, '</body>')) {
            $this->body = str_replace('</body>', $statPicture.'</body>', $this->body);
        } else {
            $this->body .= $statPicture;
        }
    }

    public function statClick($mailId, $userid, $fromStat = false)
    {
        $mailClass = new MailClass();
        if (!$fromStat && !in_array($this->type, $mailClass::TYPES_WITH_STATS)) return;

        $urlClass = new UrlClass();
        $urls = [];

        $trackingSystemExternalWebsite = $this->config->get('trackingsystemexternalwebsite', 1);
        $trackingSystem = $this->config->get('trackingsystem', 'acymailing');
        if (false === strpos($trackingSystem, 'acymailing') && false === strpos($trackingSystem, 'google')) return;

        if (strpos($trackingSystem, 'google') !== false) {
            $mailClass = new MailClass();
            $mail = $mailClass->getOneById($mailId);
            $campaignClass = new CampaignClass();
            $campaign = $campaignClass->getOneCampaignByMailId($mailId);

            $utmCampaign = substr(acym_getAlias($mail->subject), 0, 30);
        }

        preg_match_all('#<[^>]* href[ ]*=[ ]*"(?!mailto:|\#|ymsgr:|callto:|file:|ftp:|webcal:|skype:|tel:)([^"]+)"#Ui', $this->body, $results);
        if (empty($results)) return;

        $countLinks = array_count_values($results[1]);
        if (array_product($countLinks) != 1) {
            $previousLinkHandled = '';
            foreach ($results[1] as $key => $url) {
                if ($countLinks[$url] === 1) continue;

                // Handle the Outlook buttons that double the link, we need to consider it as the same link that's on the main button element
                $previousIsOutlook = false;
                if (strpos($results[0][$key], '<v:roundrect') === 0) {
                    $previousLinkHandled = $results[0][$key];
                    if ($countLinks[$url] === 2) {
                        $countLinks[$url] = 1;
                        continue;
                    }
                } elseif (strpos($previousLinkHandled, '<v:roundrect') === 0) {
                    $previousIsOutlook = true;
                }
                $previousLinkHandled = $results[0][$key];

                if (!$previousIsOutlook) {
                    $countLinks[$url]--;
                }

                $toAddUrl = (strpos($url, '?') === false ? '?' : '&').'idU='.$countLinks[$url];

                if ($previousIsOutlook) {
                    $countLinks[$url]--;
                }

                $posHash = strpos($url, '#');
                if ($posHash !== false) {
                    $newURL = substr($url, 0, $posHash).$toAddUrl.substr($url, $posHash);
                } else {
                    $newURL = $url.$toAddUrl;
                }

                $this->body = preg_replace('#href="('.preg_quote($url, '#').')"#Uis', 'href="'.$newURL.'"', $this->body, 1);

                $results[0][$key] = 'href="'.$newURL.'"';
                $results[1][$key] = $newURL;
            }
        }

        foreach ($results[1] as $i => $url) {
            //We don't track unsubscribe link
            if (isset($urls[$results[0][$i]]) || strpos($url, 'task=unsub')) {
                continue;
            }

            //We often need to check if the url is within the website... but we don't care about http or https
            $simplifiedUrl = str_replace(['https://', 'http://', 'www.'], '', $url);
            $simplifiedWebsite = str_replace(['https://', 'http://', 'www.'], '', ACYM_LIVE);
            $internalUrl = strpos($simplifiedUrl, rtrim($simplifiedWebsite, '/')) === 0;

            // If this is an internal url
            //$subfolder : Record if the subfolder exists or not in which case it will be an external link
            $subfolder = false;
            if ($internalUrl) {
                $urlWithoutBase = str_replace($simplifiedWebsite, '', $simplifiedUrl);
                // If there is a /, it means there could be a sub-folder
                //It can be separated with a ? as well like administrator?option=com_content
                if (strpos($urlWithoutBase, '/') || strpos($urlWithoutBase, '?')) {
                    // Get the supposed sub-folder name
                    $folderName = substr($urlWithoutBase, 0, strpos($urlWithoutBase, '/') == false ? strpos($urlWithoutBase, '?') : strpos($urlWithoutBase, '/'));
                    //There is no dot in a folder!
                    if (strpos($folderName, '.') === false) {
                        $subfolder = @is_dir(ACYM_ROOT.$folderName);
                    }
                }
            }

            if ((!$internalUrl || $subfolder) && $trackingSystemExternalWebsite != 1) {
                continue;
            }

            if (strpos($url, 'utm_source') === false && strpos($trackingSystem, 'google') !== false) {
                $idToUse = empty($campaign) ? $mailId : $campaign->id;
                $args = [];
                $args[] = 'utm_source=newsletter_'.$idToUse;
                $args[] = 'utm_medium=email';
                $args[] = 'utm_campaign='.$utmCampaign;
                //If we have an anchor we need to remove it and add it to the end of the url
                $anchor = '';
                if (strpos($url, '#') !== false) {
                    $anchor = substr($url, strpos($url, '#'));
                    $url = substr($url, 0, strpos($url, '#'));
                }

                if (strpos($url, '?')) {
                    $mytracker = $url.'&'.implode('&', $args);
                } else {
                    $mytracker = $url.'?'.implode('&', $args);
                }
                //We add back the anchor if we had one
                $mytracker .= $anchor;
                $urls[$results[0][$i]] = str_replace($results[1][$i], $mytracker, $results[0][$i]);

                //Set the url variable so that we can use it later on...
                $url = $mytracker;
            }

            if (strpos($trackingSystem, 'acymailing') !== false) {
                // We don't replace an url which contains subid because we could loop or we could create quick links for modifying subscriptions which could be really dangerous
                // We don't track something with a tag in the link
                if (preg_match('#subid|passw|modify|\{|%7B#i', $url)) continue;

                if (!$fromStat) $mytracker = $urlClass->getUrl($url, $mailId, $userid);
                if (empty($mytracker)) continue;

                $urls[$results[0][$i]] = str_replace($results[1][$i], $mytracker, $results[0][$i]);
            }
        }

        $this->body = str_replace(array_keys($urls), $urls, $this->body);
    }

    public function textVersion($html, $fullConvert = true)
    {
        //Replace relative links into absolute before replacing the text version so that we keep correct urls
        $html = acym_absoluteURL($html);

        //If we come from a text version, we don't want to replace the spaces.
        //We will only do that if we come from an HTML Version to avoid breaking the user code
        if ($fullConvert) {
            //As in HTML multiple spaces are interpreted as only one space, we remove the multiple spaces for the text version
            $html = preg_replace('# +#', ' ', $html);
            //Same thing, return chars don't exist in html so we can simply remove them, neither \t
            $html = str_replace(["\n", "\r", "\t"], '', $html);
        }

        $removepictureslinks = "#< *a[^>]*> *< *img[^>]*> *< *\/ *a *>#isU";
        $removeScript = "#< *script(?:(?!< */ *script *>).)*< */ *script *>#isU";
        $removeStyle = "#< *style(?:(?!< */ *style *>).)*< */ *style *>#isU";
        $removeStrikeTags = '#< *strike(?:(?!< */ *strike *>).)*< */ *strike *>#iU';
        $replaceByTwoReturnChar = '#< *(h1|h2)[^>]*>#Ui';
        $replaceByStars = '#< *li[^>]*>#Ui';
        $replaceByReturnChar1 = '#< */ *(li|td|dt|tr|div|p)[^>]*> *< *(li|td|dt|tr|div|p)[^>]*>#Ui';
        $replaceByReturnChar = '#< */? *(br|p|h1|h2|legend|h3|li|ul|dd|dt|h4|h5|h6|tr|td|div)[^>]*>#Ui';
        $replaceLinks = '/< *a[^>]*href *= *"([^#][^"]*)"[^>]*>(.+)< *\/ *a *>/Uis';

        $text = preg_replace(
            [
                $removepictureslinks,
                $removeScript,
                $removeStyle,
                $removeStrikeTags,
                $replaceByTwoReturnChar,
                $replaceByStars,
                $replaceByReturnChar1,
                $replaceByReturnChar,
                $replaceLinks,
            ],
            ['', '', '', '', "\n\n", "\n* ", "\n", "\n", '${2} ( ${1} )'],
            $html
        );

        //The striptags function may not do the job properly in some cases...
        $text = preg_replace('#(&lt;|&\#60;)([^ \n\r\t])#i', '&lt; ${2}', $text);

        //BE CAREFUL!!!! This space is not a space, it's a ALT0160!! which means &nbsp;
        $text = str_replace([" ", "&nbsp;"], ' ', strip_tags($text));
        //BE CAREFUL!! That is magic code :) :) :)

        //@ is added on the call of html_entity_decode because on PHP 4, warnings are displayed using this function with utf-8 characters.
        $text = trim(@html_entity_decode($text, ENT_QUOTES, 'UTF-8'));

        if ($fullConvert) {
            //We do that one more time as some extra spaces may have appeared
            $text = preg_replace('# +#', ' ', $text);
            $text = preg_replace('#\n *\n\s+#', "\n\n", $text);
        }

        return $text;
    }

    /**
     * @throws Exception
     */
    protected function embedImages()
    {
        preg_match_all('/(src|background)=[\'|"]([^"\']*)[\'|"]/Ui', $this->Body, $images);
        $result = true;

        if (empty($images[2])) {
            return $result;
        }

        $mimetypes = [
            'bmp' => 'image/bmp',
            'gif' => 'image/gif',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'jpe' => 'image/jpeg',
            'png' => 'image/png',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
        ];

        $allimages = [];

        foreach ($images[2] as $i => $url) {
            //We don't add twice the same images otherwise there is a bug
            //and the picture is really attached but not in hidden base64
            if (isset($allimages[$url])) {
                continue;
            }

            // Don't embed images with a controller, most likely the stats picture
            if (strpos($url, 'ctrl=') !== false) {
                continue;
            }

            $allimages[$url] = 1;

            // We convert the url into local directory
            $path = $url;
            $base = str_replace(['http://www.', 'https://www.', 'http://', 'https://'], '', ACYM_LIVE);
            $replacements = ['https://www.'.$base, 'http://www.'.$base, 'https://'.$base, 'http://'.$base];
            foreach ($replacements as $oneReplacement) {
                if (strpos($url, $oneReplacement) === false) {
                    continue;
                }
                $path = str_replace([$oneReplacement, '/'], [ACYM_ROOT, DS], urldecode($url));
                break;
            }
            $path = $this->removeAdditionalParams($path);

            $filename = str_replace(['%', ' '], '_', basename($url));
            $filename = $this->removeAdditionalParams($filename);

            $md5 = md5($filename);
            $cid = 'cid:'.$md5;
            $fileParts = explode(".", $filename);
            if (empty($fileParts[1])) {
                continue;
            }
            $ext = strtolower($fileParts[1]);
            // We only embed image files
            if (!isset($mimetypes[$ext])) {
                continue;
            }

            // We only change the url if we were able to embed the image.
            if ($this->addEmbeddedImage($path, $md5, $filename, 'base64', $mimetypes[$ext])) {
                $this->Body = preg_replace('/'.preg_quote($images[0][$i], '/').'/Ui', $images[1][$i].'="'.$cid.'"', $this->Body);
            } else {
                $result = false;
            }
        }

        return $result;
    }

    private function removeAdditionalParams($url)
    {
        $additionalParamsPos = strpos($url, '?');
        if (!empty($additionalParamsPos)) {
            $url = substr($url, 0, $additionalParamsPos);
        }

        return $url;
    }

    public function cleanText($text)
    {
        return trim(preg_replace('/(%0A|%0D|\n+|\r+)/i', '', (string)$text));
    }

    protected function _addReplyTo($email, $name)
    {
        if (empty($email)) {
            return;
        }
        $replyToName = $this->config->get('add_names', true) ? $this->cleanText(trim($name)) : '';
        $replyToEmail = trim($email);
        if (substr_count($replyToEmail, '@') > 1) {
            //We have more than one reply to...
            $replyToEmailArray = explode(';', str_replace([';', ','], ';', $replyToEmail));
            $replyToNameArray = explode(';', str_replace([';', ','], ';', $replyToName));
            foreach ($replyToEmailArray as $i => $oneReplyTo) {
                $this->addReplyTo($this->cleanText($oneReplyTo), @$replyToNameArray[$i]);
            }
        } else {
            $this->addReplyTo($this->cleanText($replyToEmail), $replyToName);
        }
    }

    private function replaceParams()
    {
        if (empty($this->parameters)) return;

        $helperPlugin = new PluginHelper();

        //We create an extra tag which contains all possible parameters...
        $this->generateAllParams();

        $vars = [
            'Subject',
            'Body',
            'From',
            'FromName',
            'replyname',
            'replyemail',
        ];

        foreach ($vars as $oneVar) {
            if (!empty($this->$oneVar)) {
                $this->$oneVar = $helperPlugin->replaceDText($this->$oneVar, $this->parameters);
            }
        }

        if (!empty($this->ReplyTo)) {
            foreach ($this->ReplyTo as $i => $replyto) {
                foreach ($replyto as $a => $oneval) {
                    $this->ReplyTo[$i][$a] = $helperPlugin->replaceDText($this->ReplyTo[$i][$a], $this->parameters);
                }
            }
        }
    }

    /**
     * Create a new parameter called {alltags} which will include all the others
     */
    private function generateAllParams()
    {
        $result = '<table style="border:1px solid;border-collapse:collapse;" border="1" cellpadding="10"><tr><td>Tag</td><td>Value</td></tr>';
        foreach ($this->parameters as $name => $value) {
            //Just in case of...
            if (!is_string($value)) continue;

            $result .= '<tr><td>'.trim($name, '{}').'</td><td>'.$value.'</td></tr>';
        }
        $result .= '</table>';
        $this->addParam('allshortcodes', $result);
    }

    public function addParamInfo()
    {
        if (!empty($_SERVER)) {
            $serverinfo = [];
            foreach ($_SERVER as $oneKey => $oneInfo) {
                $serverinfo[] = $oneKey.' => '.strip_tags(print_r($oneInfo, true));
            }
            $this->addParam('serverinfo', implode('<br />', $serverinfo));
        }

        if (!empty($_REQUEST)) {
            $postinfo = [];
            foreach ($_REQUEST as $oneKey => $oneInfo) {
                $postinfo[] = $oneKey.' => '.strip_tags(print_r($oneInfo, true));
            }
            $this->addParam('postinfo', implode('<br />', $postinfo));
        }
    }

    /**
     * Function to add params which will be sent to the tag system
     */
    public function addParam($name, $value)
    {
        $tagName = '{'.$name.'}';
        $this->parameters[$tagName] = $value;
    }

    public function overrideEmail($subject, $body, $to)
    {
        // 1 - Get the override
        $overrideClass = new OverrideClass();
        $override = $overrideClass->getMailByBaseContent($subject, $body);

        // There is no override for this email, or the override is disabled, let Joomla send the email
        if (empty($override)) {
            return false;
        }

        // 2 - Prepare the email and params
        $this->trackEmail = true;
        $this->autoAddUser = true;

        for ($i = 1 ; $i < count($override->parameters) ; $i++) {
            $oneParam = $override->parameters[$i];

            // Joomla emails have links as text, convert them
            $unmodified = $oneParam;
            $oneParam = preg_replace(
                '/(http|https):\/\/(.*)/',
                '<a href="$1://$2" target="_blank">$1://$2</a>',
                $oneParam,
                -1,
                $count
            );
            if ($count > 0) $this->addParam('link'.$i, $unmodified);
            $this->addParam('param'.$i, $oneParam);
        }

        $this->addParam('subject', $subject);

        // 3 - Send the email
        $this->overrideEmailToSend = $override;
        $statusSend = $this->sendOne($override->id, $to);
        if (!$statusSend && !empty($this->reportMessage)) {
            // Something went wrong when trying to send the override, log the information in the cron logs file
            $cronHelper = new CronHelper();
            $cronHelper->messages[] = $this->reportMessage;
            $cronHelper->saveReport();
        }

        return $statusSend;
    }

    private function getSendSettings($type, $mailId = 0)
    {
        if (!in_array($type, ['from_name', 'from_email', 'replyto_name', 'replyto_email'])) return false;

        $mailType = strpos($type, 'replyto') !== false ? str_replace('replyto', 'reply_to', $type) : $type;

        if (!empty($mailId) && !empty($this->defaultMail[$mailId]) && !empty($this->defaultMail[$mailId]->$mailType)) return $this->defaultMail[$mailId]->$mailType;

        $lang = empty($this->userLanguage) ? acym_getLanguageTag() : $this->userLanguage;

        $setting = $this->config->get($type);

        $translation = $this->config->get('sender_info_translation');

        if (!empty($translation)) {
            $translation = json_decode($translation, true);

            if (!empty($translation[$lang])) {
                $setting = $translation[$lang][$type];
            }
        }

        return $setting;
    }

    /* * * * * * * * * * * * * * * * *
     *
     * Override PHPMailer's methods
     *
     * * * * * * * * * * * * * * * * */

    public function setFrom($email, $name = '', $auto = false)
    {
        if (!empty($email)) {
            $this->From = $this->cleanText($email);
        }
        if (!empty($name) && $this->config->get('add_names', true)) {
            $this->FromName = $this->cleanText($name);
        }
    }

    /**
     * Outputs debugging info via user-defined method
     *
     * @param string $str
     */
    protected function edebug($str)
    {
        if (strpos($this->ErrorInfo, $str) === false) {
            $this->ErrorInfo .= ' '.$str;
        }
    }

    public function getMailMIME()
    {
        $result = parent::getMailMIME();

        //Added by Adrien on 11.02.2011 and then on 06 April 2011 otherwise we have 3 return char on phpMail or other functions
        $result = rtrim($result, static::$LE);

        if ($this->Mailer != 'mail') {
            $result .= static::$LE.static::$LE;
        }

        return $result;
    }

    public static function validateAddress($address, $patternselect = null)
    {
        return true;
    }
}
