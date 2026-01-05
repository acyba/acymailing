<?php

namespace AcyMailing\Helpers;

use AcyMailerPhp\OAuth;
use AcyMailing\Classes\HistoryClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\RuleClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Core\AcymObject;
use AcyMailing\Libraries\Pear\Pop3;
use AcyMailing\Types\CharsetType;

class BounceHelper extends AcymObject
{
    const HOSTS_NEEDING_OAUTH = [
        'imap.gmail.com',
        'outlook.office365.com',
    ];

    const MESSAGE_TYPE_SUCCESS = 0;
    const MESSAGE_TYPE_INFO = 1;
    const MESSAGE_TYPE_ERROR = 2;

    // Needed information for the connection
    private string $server;
    private string $username;
    private string $password;
    private string $port;
    private string $connectMethod;
    private string $secureMethod;
    private bool $selfSigned;
    private int $timeout;
    private string $oAuthToken;

    // Allowed extensions for uploaded files (attachments)
    private array $allowed_extensions = [];
    protected int $nbMessages = 0;
    public bool $report = false;
    private MailerHelper $mailer;
    private $mailbox;
    public $_message;
    private UserClass $userClass;
    private array $blockedUsers = [];
    private array $deletedUsers = [];
    private array $bounceMessages = [];
    private bool $usePear = false;
    private string $detectEmail;
    private string $detectEmail2 = '/(([a-z0-9\-]+\.)+[a-z0-9]{2,8})\/([a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*)/i';
    public array $messages = [];
    //Max execution time minus 4 seconds... we need to stop the process before this date.
    public int $stoptime = 0;
    //Check apache module "mod_security" to avoid flush issue
    protected bool $mod_security2 = false;
    //Number of ob_end_flush used in the process...
    public int $obend = 0;
    // Correspond to mailbox entity in database
    public object $action;
    public array $attachments = [];

    private array $inlineImages = [];
    private array $allCharsets;
    private bool $errorDuringActions = false;

    private RuleClass $ruleClass;
    private MailClass $mailClass;
    private HistoryClass $historyClass;
    private EncodingHelper $encodingHelper;

    private array $allLists;

    public function __construct()
    {
        parent::__construct();

        $this->mailer = new MailerHelper();
        $this->ruleClass = new RuleClass();
        $this->userClass = new UserClass();
        $this->mailClass = new MailClass();
        $this->historyClass = new HistoryClass();
        $this->encodingHelper = new EncodingHelper();

        $this->mailer->report = false;
        $this->mailer->alreadyCheckedAddresses = true;

        $charsetType = new CharsetType();
        $this->allCharsets = $charsetType->charsets;

        $this->allowed_extensions = explode(',', $this->config->get('allowed_files', ''));
        $this->detectEmail = '/'.acym_getEmailRegex(false, true).'/i';
    }

    public function init(array $config = []): bool
    {
        if (empty($config)) {
            $config = [
                'server' => $this->config->get('bounce_server'),
                'username' => $this->config->get('bounce_username'),
                'password' => $this->config->get('bounce_password'),
                'port' => $this->config->get('bounce_port'),
                'connect_method' => $this->config->get('bounce_connection'),
                'secure_method' => $this->config->get('bounce_secured'),
                'self_signed' => $this->config->get('bounce_certif', false),
                'timeout' => $this->config->get('bounce_timeout', 10),
                'bounce_access_token' => $this->config->get('bounce_access_token'),
            ];
        }

        $this->server = $config['server'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->port = $config['port'];
        $this->connectMethod = $config['connect_method'];
        $this->secureMethod = $config['secure_method'];
        $this->selfSigned = $config['self_signed'];
        $this->timeout = intval($config['timeout']);
        $this->oAuthToken = $config['bounce_access_token'];

        if ($this->connectMethod === 'pear') {
            $this->usePear = true;
            include_once ACYM_LIBRARIES.'Pear'.DS.'Pop3.php';

            return true;
        }

        require_once ACYM_LIBRARIES.'Imap2'.DS.'bootstrap.php';

        if (extension_loaded('imap') || function_exists('imap_open')) {
            return true;
        }

        if ($this->report) {
            $prefix = PHP_SHLIB_SUFFIX === 'dll' ? 'php_' : '';
            $extension = $prefix.'imap.'.PHP_SHLIB_SUFFIX;

            acym_display(
                'The extension "'.$extension.'" could not be loaded, please change your PHP configuration to enable it or use the pop3 method without imap extension',
                'error'
            );
        }

        return false;
    }

    public function connect(): bool
    {
        if ($this->usePear) {
            return $this->connectPear();
        }

        return $this->connectImap();
    }

    private function connectPear(): bool
    {
        ob_start();

        $this->mailbox = new Pop3();

        $timeout = $this->timeout;
        if (!empty($timeout)) {
            $this->mailbox->_timeout = $timeout;
        }

        $port = intval($this->port);
        $secure = $this->secureMethod;
        if (empty($port)) {
            if ($secure === 'ssl') {
                $port = '995';
            } else {
                $port = '110/pop3/notls';
            }
        }

        $serverName = trim($this->server);

        //We don't add back the ssl:// or tls:// if it's already there
        if (!empty($secure) && !strpos($serverName, '://')) {
            $serverName = $secure.'://'.$serverName;
        }

        if (!$this->mailbox->connect($serverName, $port)) {
            $warnings = ob_get_clean();
            if ($this->report) {
                $notification = [
                    'name' => 'error_connecting',
                    'removable' => 1,
                ];
                acym_enqueueMessage(acym_translationSprintf('ACYM_ERROR_CONNECTING', $this->server.' : '.$port), 'error', true, [$notification]);
            }
            if (!empty($warnings) && $this->report) {
                acym_display($warnings, 'warning');
            }

            return false;
        }

        $login = $this->mailbox->login(trim($this->username), trim($this->password), 'USER');
        if (empty($login) || isset($login->code)) {
            $warnings = ob_get_clean();
            if ($this->report) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_ERROR_LOGIN', $this->username.':'.$this->password), 'error');
            }
            if (!empty($warnings) && $this->report) {
                acym_display($warnings, 'warning');
            }

            return false;
        }

        ob_end_clean();

        return true;
    }

    private function connectImap(): bool
    {
        if (empty($this->server)) {
            acym_enqueueMessage(acym_translation('ACYM_CONFIGURE_BOUNCE'), 'warning');

            return false;
        }

        ob_start();
        //First we reset the buffer or errors and warnings
        $buff = $this->callImapFunction('imap_alerts', []);
        $buff = $this->callImapFunction('imap_errors', []);

        $timeout = intval($this->timeout);
        if (!empty($timeout)) {
            $this->callImapFunction('imap_timeout', [IMAP_OPENTIMEOUT, $timeout]);
        }

        $port = intval($this->port);
        $secure = $this->secureMethod;
        $protocol = $this->connectMethod;
        $serverName = '{'.trim($this->server);
        if (empty($port)) {
            if ($protocol === 'imap') {
                $port = $secure === 'ssl' ? '993' : '143';
            } elseif ($protocol === 'pop3') {
                $port = $secure === 'ssl' ? '995' : '110';
            }
        }

        if (!empty($port)) {
            $serverName .= ':'.$port;
        }

        if ($this->server === 'outlook.office365.com') {
            $serverName .= '/imap';
        }

        //Add the secure protocol (TLS or SSL)
        if (!empty($secure)) {
            $serverName .= '/'.$secure;
        }

        // Test if string contains '\'
        $email = trim($this->username);
        if (strpos($email, '\\') !== false) {
            [$user, $authuser] = explode('\\', $email);
            [$x, $domain] = explode('@', $user);
            $serverName .= '/authuser='.$user.'/user='.$authuser.'@'.$domain;
        }

        if ($this->selfSigned) {
            $serverName .= '/novalidate-cert';
        }

        //Add the method (imap by default) ex : pop3
        if (!empty($protocol) && $this->server !== 'outlook.office365.com') {
            $serverName .= '/service='.$protocol;
        }
        $serverName .= '}';
        if (in_array($this->server, self::HOSTS_NEEDING_OAUTH)) {
            $this->refreshToken();
            if (empty($this->oAuthToken)) {
                return false;
            }
            $this->mailbox = imap2_open($serverName, trim($this->username), $this->oAuthToken, OP_XOAUTH2);
        } else {
            $this->mailbox = imap_open($serverName, trim($this->username), trim($this->password), OP_SILENT);
        }
        $warnings = ob_get_clean();

        if ($this->report) {
            if (!$this->mailbox) {
                $notification = [
                    'name' => 'error_connecting',
                    'removable' => 1,
                ];
                acym_enqueueMessage(acym_translationSprintf('ACYM_ERROR_CONNECTING', $serverName), 'error', true, [$notification]);
            }
            if (!empty($warnings)) {
                acym_enqueueMessage($warnings, 'warning');
            }
        }

        return (bool)$this->mailbox;
    }

    public function getNBMessages(): int
    {
        if ($this->usePear) {
            $this->nbMessages = intval($this->mailbox->numMsg());
        } else {
            $this->nbMessages = intval($this->callImapFunction('imap_num_msg', [$this->mailbox]));
        }

        return $this->nbMessages;
    }

    protected function getMessage(int $msgNB)
    {
        if ($this->usePear) {
            $message = new \stdClass();
            $message->headerString = $this->mailbox->getRawHeaders($msgNB);
            if (empty($message->headerString)) {
                return false;
            }
        } else {
            $message = $this->callImapFunction('imap_headerinfo', [$this->mailbox, $msgNB]);
        }

        return $message;
    }

    protected function deleteMessage($msgNB): void
    {
        if ($this->usePear) {
            $this->mailbox->deleteMsg($msgNB);
        } else {
            $this->callImapFunction('imap_delete', [$this->mailbox, $msgNB]);
            $this->callImapFunction('imap_expunge', [$this->mailbox]);
        }
    }

    public function close(): void
    {
        if ($this->usePear) {
            $this->mailbox->disconnect();
        } else {
            $this->callImapFunction('imap_close', [$this->mailbox]);
        }
    }

    public function decodeMessage(bool $attachments = false): bool
    {
        if ($this->usePear) {
            return $this->decodeMessagePear($attachments);
        } else {
            return $this->decodeMessageImap($attachments);
        }
    }

    private function decodeMessagePear(bool $attachments): bool
    {
        $this->_message->headerinfo = $this->mailbox->getParsedHeaders($this->_message->messageNB);
        if (empty($this->_message->headerinfo['subject'])) {
            if (empty($this->_message->headerinfo['Subject'])) {
                return false;
            }
            $this->_message->headerinfo['subject'] = $this->_message->headerinfo['Subject'];
        }
        $this->_message->text = '';
        $this->_message->html = $this->mailbox->getBody($this->_message->messageNB);

        if (!empty($this->_message->headerinfo['content-type']) && strpos($this->_message->headerinfo['content-type'], 'boundary') !== false) {
            $matches = [];
            preg_match('#boundary="([^"]+)"#i', $this->_message->headerinfo['content-type'], $matches);

            if (!empty($matches[1]) && strpos($this->_message->html, $matches[1]) !== false) {
                $inlineImages = [];

                $segments = explode('--'.$matches[1], $this->_message->html);
                foreach ($segments as $segment) {
                    // Find the segment containing the html and text version
                    if (strpos($segment, 'Content-Type: text/plain') !== false) {
                        // Check if there is another boundary
                        $matches = [];
                        preg_match('#boundary="([^"]+)"#i', $segment, $matches);

                        if (!empty($matches[1])) {
                            $parts = explode('--'.$matches[1], $segment);

                            foreach ($parts as $onePart) {
                                $content = trim(preg_replace('#(charset=".*?\r\n)|Content-(Type|ID|Disposition|Transfer-Encoding):.*?\r\n#is', "", $onePart));

                                if (strpos($onePart, 'Content-Transfer-Encoding') !== false) {
                                    preg_match('#Content-Transfer-Encoding: (.+)#i', $onePart, $encoding);
                                    $encoding = trim($encoding[1]);
                                    // Someone has an issue when the $encoding is equal to 'Content-Type:' only
                                    if (!empty($encoding) && $encoding !== 'Content-Type:') {
                                        $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                                    }
                                }

                                if (strpos($onePart, "Content-Type: text/plain") !== false) {
                                    $this->_message->text .= ' '.$content;
                                }
                                if (strpos($onePart, "Content-Type: text/html") !== false) {
                                    $this->_message->html .= ' '.$content;
                                }
                            }
                        } else {
                            $this->_message->html .= ' '.trim(preg_replace('#(charset=".*?\r\n)|Content-(Type|ID|Disposition|Transfer-Encoding):.*?\r\n#is', "", $segment));
                        }
                    } elseif (preg_match("#Content-Type: .*?/(png|jpg|jpeg|gif)#i", $segment) !== false) {
                        preg_match('#name="([^"]+)"#i', $segment, $filename);
                        if (empty($filename) || empty($filename[1])) {
                            continue;
                        }
                        $name = trim($filename[1]);
                        $filename = $name;
                        $extensionPos = strrpos($filename, '.');

                        if ($extensionPos === false) {
                            continue;
                        }

                        $extension = substr($filename, $extensionPos + 1);

                        // Make sure there is no double extension or space
                        $filename = preg_replace('#[^a-zA-Z0-9]#Uis', '_', substr($filename, 0, $extensionPos));

                        // Get the upload folder and create it if needed
                        $uploadFolder = str_replace(['/', '\\'], DS, acym_getFilesFolder());
                        $pathToUpload = ACYM_ROOT.trim($uploadFolder, DS).DS;

                        // Rename if needed
                        if (file_exists($pathToUpload.$filename.'.'.$extension)) {
                            $fileNumber = 1;
                            while (file_exists($pathToUpload.$filename.'_('.$fileNumber.').'.$extension)) {
                                $fileNumber++;
                            }
                            $filename = $filename.'_('.$fileNumber.')';
                        }

                        if ($attachments && preg_match("#Content-Disposition: attachment;#i", $segment) !== false) {
                            $data = trim(substr($segment, strpos($segment, "\r\n\r\n")));
                            if (strpos($segment, 'Content-Transfer-Encoding: base64') !== false) {
                                $data = base64_decode($data);
                            }

                            try {
                                if (acym_writeFile($pathToUpload.$filename.'.'.$extension, $data)) {
                                    $attachment = new \stdClass();
                                    $attachment->filename = str_replace(DS, '/', trim($uploadFolder, '\\/')).'/'.$filename.'.'.$extension;
                                    $attachment->size = filesize(ACYM_ROOT.$attachment->filename);
                                    $this->attachments[] = $attachment;
                                }
                            } catch (\Exception $e) {
                                $completeFilename = $filename.'.'.$extension;
                                $this->display(acym_translationSprintf('ACYM_ERROR_UPLOADING_ATTACHMENT_X_TO_X', $completeFilename, $e->getMessage()), self::MESSAGE_TYPE_ERROR);
                            }
                        } else {
                            preg_match('#Content-ID: <([^>]+)>#i', $segment, $contentID);
                            if (empty($contentID) || empty($contentID[1])) {
                                continue;
                            }
                            $contentID = trim($contentID[1]);

                            $data = trim(substr($segment, strpos($segment, "\r\n\r\n")));
                            if (strpos($segment, 'Content-Transfer-Encoding: base64') !== false) {
                                $data = base64_decode($data);
                            }


                            try {
                                if (acym_writeFile($pathToUpload.$filename.'.'.$extension, $data)) {
                                    $inlineImages['cid:'.$contentID] = acym_rootURI().$uploadFolder.'/'.$filename.'.'.$extension;
                                }
                            } catch (\Exception $e) {
                                $this->display(acym_translationSprintf('ACYM_ERROR_UPLOAD_ATTACHMENT', $filename.'.'.$extension, $e->getMessage()), self::MESSAGE_TYPE_ERROR);
                            }
                        }
                    }
                }

                if (!empty($inlineImages)) {
                    $this->_message->html = str_replace(array_keys($inlineImages), $inlineImages, $this->_message->html);
                }
            }
        }

        $this->_message->subject = $this->decodeHeader($this->_message->headerinfo['subject']);
        if (empty($this->_message->header)) {
            $this->_message->header = new \stdClass();
        }
        $this->_message->header->sender_email = @$this->_message->headerinfo['return-path'];
        if (is_array($this->_message->header->sender_email)) {
            $this->_message->header->sender_email = reset($this->_message->header->sender_email);
        }
        if (preg_match($this->detectEmail, $this->_message->header->sender_email, $results)) {
            $this->_message->header->sender_email = $results[0];
        }
        $this->_message->header->sender_name = strip_tags(@$this->_message->headerinfo['from']);
        $this->_message->header->reply_to_email = $this->_message->header->sender_email;
        $this->_message->header->reply_to_name = $this->_message->header->sender_name;
        $this->_message->header->from_email = $this->_message->header->sender_email;
        $this->_message->header->from_name = $this->_message->header->sender_name;

        return true;
    }

    private function decodeMessageImap(bool $attachments): bool
    {
        $this->_message->structure = $this->callImapFunction('imap_fetchstructure', [$this->mailbox, $this->_message->messageNB]);

        if (empty($this->_message->structure)) {
            return false;
        }

        $this->_message->headerinfo = $this->callImapFunction('imap_fetchheader', [$this->mailbox, $this->_message->messageNB]);

        $this->_message->html = '';
        $this->_message->text = '';

        //Multipart message : type == 1
        if ($this->_message->structure->type == 1) {
            $this->_message->contentType = 2;
            if ($this->_message->structure->subtype === 'MIXED') {
                $allParts = $this->explodeBodyMixed($this->_message->structure);
            } else {
                $allParts = $this->explodeBody($this->_message->structure);
            }

            $text = '';
            foreach ($allParts as $num => $onePart) {
                $content = $this->callImapFunction('imap_fetchbody', [$this->mailbox, $this->_message->messageNB, $num]);
                $decodedContent = empty($content) ? '' : $this->decodeContent($content, $onePart);
                if ($onePart->subtype === 'HTML') {
                    $this->_message->html .= $decodedContent;
                } else {
                    if ($onePart->subtype === 'PLAIN') {
                        $text .= $decodedContent."\n";
                    }
                    $this->_message->text .= $decodedContent."\n\n- - -\n\n";
                }

                // Only for mailbox actions
                if (!empty($this->action) && $attachments) {
                    if ((isset($onePart->parameters) && is_array($onePart->parameters)) || (isset($onePart->dparameters) && is_array($onePart->dparameters))) {
                        $this->uploadAttachment($onePart, $num);
                    } elseif (isset($onePart->parts) && is_array($onePart->parts)) {
                        foreach ($onePart->parts as $num2 => $innerPart) {
                            $this->uploadAttachment($innerPart, $num2);
                        }
                    }
                }
            }

            if (!empty($this->action) && empty($this->_message->html)) {
                $this->_message->html = $text."\n";
            }
            if (!empty($this->inlineImages)) {
                $this->_message->html = str_replace(array_keys($this->inlineImages), $this->inlineImages, $this->_message->html);
            }
        } else {
            if ($this->_message->structure->subtype === 'HTML') {
                $this->_message->contentType = 1;
                $this->_message->html = $this->decodeContent($this->callImapFunction('imap_body', [$this->mailbox, $this->_message->messageNB]), $this->_message->structure);
            } else {
                $this->_message->contentType = 0;
                $this->_message->text = $this->decodeContent($this->callImapFunction('imap_body', [$this->mailbox, $this->_message->messageNB]), $this->_message->structure);
            }
        }

        //Decode the subject
        if (!empty($this->_message->subject)) {
            $this->_message->subject = $this->decodeHeader($this->_message->subject);
        } else {
            $this->_message->subject = '';
        }

        $this->decodeAddressImap('sender');
        $this->decodeAddressImap('from');
        $this->decodeAddressImap('reply_to');
        $this->decodeAddressImap('to');

        return true;
    }

    private function uploadAttachment($attachment, $num): void
    {
        // It may be a real attachment, or an inline image
        if (!isset($attachment->disposition) || (strtolower($attachment->disposition) !== 'attachment' && strtolower($attachment->disposition) !== 'inline')) {
            return;
        }

        $name = empty($attachment->dparameters[0]->value) ? $attachment->parameters[0]->value : $attachment->dparameters[0]->value;
        $filename = $this->decodeHeader($name);
        $extensionPos = strrpos($filename, '.');

        if ($extensionPos === false) {
            foreach ($attachment->parameters as $key => $oneParam) {
                if (strtolower($oneParam->attribute) === 'name') {
                    $filename = $this->decodeHeader($attachment->parameters[$key]->value);
                    $extensionPos = strrpos($filename, '.');
                    break;
                }
            }
        }

        // No extension => no upload
        if ($extensionPos === false) {
            return;
        }

        // Upload only allowed extensions in the configuration
        $extension = substr($filename, $extensionPos + 1);
        if (!in_array(strtolower($extension), $this->allowed_extensions)) {
            return;
        }

        // Make sure there is no double extension or space
        $filename = preg_replace('#[^a-zA-Z0-9]#Uis', '_', substr($filename, 0, $extensionPos));

        // Get the upload folder and create it if needed
        $uploadFolder = str_replace(['/', '\\'], DS, acym_getFilesFolder());
        $pathToUpload = ACYM_ROOT.trim($uploadFolder, DS).DS;

        // Rename if needed
        if (file_exists($pathToUpload.$filename.'.'.$extension)) {
            $fileNumber = 1;
            while (file_exists($pathToUpload.$filename.'_('.$fileNumber.').'.$extension)) {
                $fileNumber++;
            }
            $filename = $filename.'_('.$fileNumber.')';
        }

        $data = $this->decodeContent($this->callImapFunction('imap_fetchbody', [$this->mailbox, $this->_message->messageNB, $num]), $attachment);

        try {
            if (acym_writeFile($pathToUpload.$filename.'.'.$extension, $data)) {
                if (
                    strtolower($attachment->disposition) !== 'attachment'
                    && !empty($attachment->id)
                    && in_array(strtolower($attachment->subtype), acym_getImageFileExtensions())
                ) {
                    $this->inlineImages['cid:'.trim($attachment->id, '<>')] = acym_rootURI().$uploadFolder.'/'.$filename.'.'.$extension;
                } else {
                    $attachment = new \stdClass();
                    $attachment->filename = str_replace(DS, '/', trim($uploadFolder, '\\/')).'/'.$filename.'.'.$extension;
                    $attachment->size = filesize(ACYM_ROOT.$attachment->filename);
                    $this->attachments[] = $attachment;
                }
            }
        } catch (\Exception $e) {
            $completeFilename = $filename.'.'.$extension;
            $this->display(acym_translationSprintf('ACYM_ERROR_UPLOADING_ATTACHMENT_X_TO_X', $completeFilename, $e->getMessage()), self::MESSAGE_TYPE_ERROR);
        }
    }

    public function handleMessages(): void
    {
        $maxMessages = min($this->nbMessages, $this->config->get('bounce_max', 0));
        //500 messages maximum at once
        if (empty($maxMessages)) {
            $maxMessages = min($this->nbMessages, 500);
        }

        if ($this->report) {
            if (function_exists('apache_get_modules')) {
                $modules = apache_get_modules();
                $this->mod_security2 = in_array('mod_security2', $modules);
            }

            /*This is to avoid the blank page... and it apparently works! ;) */
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', 0);


            if (!headers_sent()) {
                while (ob_get_level() > 0 && $this->obend++ < 3) {
                    @ob_end_flush();
                }
            }

            //We prepare the area where we will add informations...
            $disp = "<div style='position:fixed; top:3px;left:3px;background-color : white;border : 1px solid grey; padding : 3px;font-size:14px'>";
            $disp .= acym_translation('ACYM_BOUNCE_HANDLING');
            $disp .= ':  <span id="counter">0</span> / '.$maxMessages;
            $disp .= '</div>';
            $disp .= '<script type="text/javascript" language="javascript">';
            $disp .= 'var mycounter = document.getElementById("counter");';
            $disp .= 'function setCounter(val){ mycounter.innerHTML=val;}';
            $disp .= '</script>';
            echo $disp;
            if (function_exists('ob_flush')) {
                @ob_flush();
            }
            if (!$this->mod_security2) {
                @flush();
            }
        }

        //We load all the published rules
        $rules = $this->ruleClass->getAll();
        $rules = array_filter($rules, function ($rule) {
            return $rule->active;
        });

        $msgNB = $maxMessages;
        $listClass = new ListClass();
        $this->allLists = $listClass->getAll('id');

        //Exclude some email addresses...
        $replyemail = $this->config->get('reply_email');
        $fromemail = $this->config->get('from_email');
        $bouncemail = $this->config->get('bounce_email');
        $removeEmails = '#('.str_replace(['%'], ['@'], $this->config->get('bounce_username'));
        if (!empty($bouncemail)) {
            $removeEmails .= '|'.$bouncemail;
        }
        if (!empty($fromemail)) {
            $removeEmails .= '|'.$fromemail;
        }
        if (!empty($replyemail)) {
            $removeEmails .= '|'.$replyemail;
        }
        $removeEmails .= ')#i';

        while (($msgNB > 0) && ($this->_message = $this->getMessage($msgNB))) {
            if ($this->report) {
                echo '<script type="text/javascript" language="javascript">setCounter('.($maxMessages - $msgNB + 1).')</script>';
                if (function_exists('ob_flush')) {
                    @ob_flush();
                }
                if (!$this->mod_security2) {
                    @flush();
                }
            }
            $this->_message->messageNB = $msgNB;
            $msgNB--;

            //We could not retrieve the message... we continue with the next message
            if (!$this->decodeMessage()) {
                $this->display(acym_translation('ACYM_ERROR_RETRIEVING_MESSAGE'), self::MESSAGE_TYPE_ERROR, $maxMessages - $this->_message->messageNB + 1);
                continue;
            }

            if (empty($this->_message->subject)) {
                $this->_message->subject = '';
            }

            $this->_message->analyseText = $this->_message->html.' '.$this->_message->text;
            //We add the from in the list of possible e-mail address to check
            if (!empty($this->_message->header->from_email)) {
                $this->_message->analyseText .= ' '.$this->_message->header->from_email;
            }
            $this->display(
                '<b>'.acym_translation('ACYM_EMAIL_SUBJECT').' : '.strip_tags($this->_message->subject).'</b>',
                self::MESSAGE_TYPE_SUCCESS,
                $maxMessages - $this->_message->messageNB + 1
            );

            //Identify the user and the e-mail...
            preg_match('#AC([0-9]+)Y([0-9]+)BA#i', $this->_message->analyseText, $resultsVars);
            if (!empty($resultsVars[1])) {
                $this->_message->userid = $resultsVars[1];
            }
            if (!empty($resultsVars[2])) {
                $this->_message->mailid = $resultsVars[2];
            }

            if (empty($this->_message->userid)) {
                //We will need the e-mail itself in that case... :p
                preg_match_all($this->detectEmail, $this->_message->analyseText, $results);

                //Still no result? We try to find others
                if (empty($results[0])) {
                    preg_match_all($this->detectEmail2, $this->_message->analyseText, $results2);
                    for ($i = 0; $i < count($results2[0]); $i++) {
                        $results[0][] = $results2[3][$i].'@'.$results2[1][$i];
                    }
                }

                if (!empty($results[0])) {
                    $alreadyChecked = [];
                    foreach ($results[0] as $oneEmail) {
                        //We will find the e-mail if it's not in the list of incorrect e-mail addresses
                        if (!preg_match($removeEmails, $oneEmail)) {
                            //We will keep this one, so we make sure it's strtolower
                            $this->_message->subemail = strtolower($oneEmail);
                            //We already checked this e-mail address... no need to try it a second time
                            if (!empty($alreadyChecked[$this->_message->subemail])) {
                                continue;
                            }

                            $user = $this->userClass->getOneByEmail($this->_message->subemail);
                            if (!empty($user)) $this->_message->userid = $user->id;

                            if (empty($this->_message->userid) && preg_match('#(&|\?)id=([0-9]+)&#ims', $this->_message->subemail, $subid)) {
                                $user = $this->userClass->getOneById($subid[2]);
                                $this->_message->userid = $user->id;
                                $this->_message->subemail = $user->email;
                            }

                            $alreadyChecked[$this->_message->subemail] = true;
                            if (!empty($this->_message->userid)) {
                                break;
                            }
                        }
                    }
                }
            }

            if (empty($this->_message->mailid) && !empty($this->_message->userid)) {
                //We can check if we have a user and only one e-mail sent for this user, it's obviously the e-mail we just sent!!
                $this->_message->mailid = acym_loadResult(
                    'SELECT `mail_id` FROM #__acym_user_stat WHERE `user_id` = '.intval($this->_message->userid).' ORDER BY `send_date` DESC'
                );
            }

            foreach ($rules as $oneRule) {
                //We stop as soon as we find a good rule...
                if ($this->handleRule($oneRule)) {
                    break;
                }
            }

            if ($msgNB % 50 == 0) {
                $this->userActions();
            }

            //We don't have time to finish the process? Ok, we stop it now!
            if (!empty($this->stoptime) && time() > $this->stoptime) {
                break;
            }
        }

        $this->userActions();
        $this->close();
    }

    /**
     * Execute actions on the subscriber... and record the statistics
     * We group them to not have performance issues
     */
    private function userActions(): void
    {
        if (!empty($this->deletedUsers)) {
            acym_arrayToInteger($this->deletedUsers);
            $this->userClass->delete($this->deletedUsers);
            $this->deletedUsers = [];
        }

        if (!empty($this->blockedUsers)) {
            acym_arrayToInteger($this->blockedUsers);
            $allUsersId = implode(',', $this->blockedUsers);
            acym_query('UPDATE `#__acym_user` SET `active` = 0 WHERE `id` IN ('.$allUsersId.')');
            //We delete any other e-mail from the queue as well
            acym_query('DELETE FROM `#__acym_queue` WHERE `user_id` IN ('.$allUsersId.')');
            $this->blockedUsers = [];
        }

        if (!empty($this->bounceMessages)) {
            foreach ($this->bounceMessages as $mailid => $bouncedata) {
                //Do we have some bounce details to update?
                //If so, we will load the current bouncedetails and update it properly.
                //bouncedetails is an array of bounceRule => nbTimes used

                $updateBounceDetails = '';
                if (!empty($bouncedata['bouncedetails'])) {
                    $bouncedetails = acym_loadResult('SELECT `bounce_details` FROM #__acym_mail_stat WHERE mail_id = '.intval($mailid));
                    if (!empty($bouncedetails)) {
                        $bouncedetails = unserialize($bouncedetails);
                    } else {
                        $bouncedetails = [];
                    }

                    foreach ($bouncedata['bouncedetails'] as $ruleName => $nbTimes) {
                        if (empty($bouncedetails[$ruleName])) {
                            $bouncedetails[$ruleName] = 0;
                        }
                        $bouncedetails[$ruleName] += $nbTimes;
                    }

                    $updateBounceDetails = ' , `bounce_details` = '.acym_escapeDB(serialize($bouncedetails));
                }

                if (!empty($bouncedata['userids'])) {
                    $valueInsert = [];
                    foreach ($bouncedata['ruletriggered'] as $userid => $rulename) {
                        $valueInsert[] = '('.intval($mailid).','.intval($userid).',1,'.acym_escapeDB($rulename).')';
                    }
                    acym_query(
                        'INSERT INTO #__acym_user_stat (mail_id,user_id,bounce,bounce_rule) VALUES '.implode(
                            ',',
                            $valueInsert
                        ).' ON DUPLICATE KEY UPDATE `bounce` = `bounce` + 1, bounce_rule=VALUES(bounce_rule)'
                    );
                    //We updated some profiles... let's make sure we really handle only unique bounces then and don't count it twice
                    acym_arrayToInteger($bouncedata['userids']);
                    $realUniqueBounces = acym_loadResult(
                        'SELECT COUNT(*) 
                        FROM #__acym_user_stat 
                        WHERE `bounce` = 1 
                            AND `user_id` IN ('.implode(',', $bouncedata['userids']).') 
                            AND `mail_id` = '.intval($mailid)
                    );
                    $bouncedata['nbbounces'] = $bouncedata['nbbounces'] - count($bouncedata['userids']) + $realUniqueBounces;
                }

                acym_query(
                    'UPDATE #__acym_mail_stat SET `bounce_unique` = `bounce_unique` + '.intval($bouncedata['nbbounces']).$updateBounceDetails.' 
                    WHERE `mail_id` = '.intval($mailid).' 
                    LIMIT 1'
                );
            }
            $this->bounceMessages = [];
        }
    }

    private function handleRule(object &$oneRule): bool
    {
        $regex = $oneRule->regex;
        if (empty($regex)) {
            return false;
        }

        //Do it based on the config of the rule...

        $analyseText = '';
        if (in_array('senderInfo', $oneRule->executed_on)) {
            $analyseText .= ' ';
            if (isset($this->_message->header->sender_name)) {
                $analyseText .= $this->_message->header->sender_name;
            }
            if (isset($this->_message->header->sender_email)) {
                $analyseText .= $this->_message->header->sender_email;
            }
        }

        if (in_array('subject', $oneRule->executed_on)) {
            $analyseText .= ' '.$this->_message->subject;
        }

        if (in_array('body', $oneRule->executed_on)) {
            if (!empty($this->_message->html)) {
                $analyseText .= ' '.$this->_message->html;
            }
            if (!empty($this->_message->text)) {
                $analyseText .= ' '.$this->_message->text;
            }
        }

        //Because it's easier to handle it that way... for multilines.
        $analyseText = str_replace(["\n", "\r", "\t"], ' ', $analyseText);

        if (!preg_match('#'.$regex.'#ims', $analyseText)) {
            return false;
        }

        $message = acym_translation('ACYM_BOUNCE_RULE').' ['.acym_translation('ACYM_ID').' '.$oneRule->id.'] '.acym_translation($oneRule->name).' : ';
        $this->errorDuringActions = false;
        $message .= $this->actionUser($oneRule);
        $message .= $this->actionMessage($oneRule);

        $type = self::MESSAGE_TYPE_SUCCESS;
        if ($this->errorDuringActions) {
            $type = self::MESSAGE_TYPE_ERROR;
        }

        $this->display($message, $type);

        return true;
    }

    private function actionUser(object &$oneRule): string
    {
        $commonListsMailUser = [];
        $message = '';

        if (empty($this->_message->userid)) {
            $message .= 'user not identified';
            if (!empty($this->_message->subemail)) {
                $message .= ' ( '.$this->_message->subemail.' ) ';
            }

            return $message;
        }

        //To display nice error messages...
        if (
            in_array('delete_user_subscription', $oneRule->action_user)
            || in_array('unsubscribe_user', $oneRule->action_user)
            || in_array('subscribe_user', $oneRule->action_user)
        ) {
            if (empty($this->_message->subemail)) {
                $currentUser = $this->userClass->getOneById($this->_message->userid);
                if (!empty($currentUser->email)) {
                    $this->_message->subemail = $currentUser->email;
                }
            }

            $userSubscription = $this->userClass->getSubscriptionStatus($this->_message->userid);

            $bounceContent = '';
            if (!empty($this->_message->html)) {
                $bounceContent .= ' '.$this->_message->html;
            }
            if (!empty($this->_message->text)) {
                $bounceContent .= ' '.$this->_message->text;
            }

            $emailLists = [];
            preg_match('#Please unsubscribe user ID \d+ from list\(s\) ([^.]+)\.#Uis', $bounceContent, $emailLists);
            if (!empty($emailLists[1])) {
                $currentEmailLists = explode(',', $emailLists[1]);
                foreach ($currentEmailLists as $oneListId) {
                    if (!empty($userSubscription[$oneListId]) && $userSubscription[$oneListId]->status == 1) {
                        $commonListsMailUser[] = $oneListId;
                    }
                }
            }

            if (empty($commonListsMailUser)) {
                $commonListsMailUser = array_keys($userSubscription);
            }
        }

        if (empty($this->_message->subemail)) {
            $this->_message->subemail = $this->_message->userid;
        }


        //handle this rule in the stats
        if (!empty($this->_message->mailid)) {
            $mail = $this->mailClass->getOneById($this->_message->mailid);
        } else {
            $mail = null;
        }

        if ($oneRule->increment_stats && !empty($this->_message->mailid) && !empty($mail)) {
            //Init the stats...
            if (empty($this->bounceMessages[$this->_message->mailid])) {
                $this->bounceMessages[$this->_message->mailid] = [];
                $this->bounceMessages[$this->_message->mailid]['nbbounces'] = 0;
                $this->bounceMessages[$this->_message->mailid]['userids'] = [];
                $this->bounceMessages[$this->_message->mailid]['bouncedetails'] = [];
                $this->bounceMessages[$this->_message->mailid]['ruletriggered'] = [];
            }

            //Increment the global stats...
            $this->bounceMessages[$this->_message->mailid]['nbbounces']++;

            //Increment the detailed stats...
            $ruleName = $oneRule->name.' [ID '.$oneRule->id.'] ';
            //We add a @ just in case the rule was not already defined...
            if (empty($this->bounceMessages[$this->_message->mailid]['bouncedetails'][$ruleName])) {
                $this->bounceMessages[$this->_message->mailid]['bouncedetails'][$ruleName] = 1;
            } else {
                $this->bounceMessages[$this->_message->mailid]['bouncedetails'][$ruleName]++;
            }

            $user = $this->userClass->getOneById($this->_message->userid);
            if (!empty($this->_message->userid) && !in_array('delete_user', $oneRule->action_user) && !empty($user)) {
                //Increment the bounce number in the user stat table but only if we don't delete the subscriber
                $this->bounceMessages[$this->_message->mailid]['userids'][] = intval($this->_message->userid);
                $this->bounceMessages[$this->_message->mailid]['ruletriggered'][intval($this->_message->userid)] = $oneRule->name.' ['.acym_translation(
                        'ACYM_ID'
                    ).' '.$oneRule->id.']';
            }
        }

        //Make sure we have enough messages to really execute this
        if (!empty($oneRule->execute_action_after) && $oneRule->execute_action_after > 1) {
            //Let's load the number of bounces the user has and then exit or not...
            if (empty($this->_message->mailid)) {
                $this->_message->mailid = 0;
            }
            $nb = intval(
                    acym_loadResult(
                        'SELECT COUNT(mail_id) FROM #__acym_user_stat WHERE bounce > 0 AND user_id = '.intval($this->_message->userid).' AND mail_id != '.intval(
                            $this->_message->mailid
                        )
                    )
                ) + 1;

            if ($nb < $oneRule->execute_action_after) {
                $message .= ' | '.acym_translationSprintf('ACYM_BOUNCE_RECEIVED', $nb, $this->_message->subemail).' | '.acym_translationSprintf(
                        'ACYM_BOUNCE_MIN_EXEC',
                        $oneRule->execute_action_after
                    );

                return $message;
            }
        }

        //If we delete the subscriber, it's the last action we execute
        if (in_array('delete_user', $oneRule->action_user)) {
            $message .= ' | '.acym_translationSprintf('ACYM_USER_X_DELETED', $this->_message->subemail);
            $this->deletedUsers[] = intval($this->_message->userid);

            return $message;
        }

        //We will need a default listid after...
        $listId = 0;
        if (in_array('subscribe_user', $oneRule->action_user) && !empty($oneRule->action_user['subscribe_user_list'])) {
            $listId = $oneRule->action_user['subscribe_user_list'];

            $listName = empty($this->allLists[$listId]->name) ? $listId : $this->allLists[$listId]->name;
            $message .= ' | ';
            if (isset($userSubscription[$listId])) {
                $message .= acym_translationSprintf('ACYM_USER_X_NOT_SUBSCRIBED_TO', $this->_message->subemail, $listName);
                if ($userSubscription[$listId]->status == 1) {
                    $message .= acym_translation('ACYM_USER_ALREADY_SUBSCRIBED');
                } elseif ($userSubscription[$listId]->status == 0) {
                    $message .= acym_translation('ACYM_USER_ALREADY_UNSUBSCRIBED');
                }
            } else {
                $this->userClass->subscribe([$this->_message->userid], [$listId]);
                $message .= acym_translationSprintf('ACYM_USER_X_SUBSCRIBED_TO', $this->_message->subemail, $listName);
            }
        }

        if (in_array('delete_user_subscription', $oneRule->action_user)) {
            $removeLists = array_diff($commonListsMailUser, [$listId]);
            if (!empty($removeLists)) {
                $listNames = [];
                foreach ($removeLists as $oneListId) {
                    if (!empty($this->allLists[$oneListId]->name)) {
                        $listNames[] = $this->allLists[$oneListId]->name;
                    } else {
                        $listNames[] = $oneListId;
                    }
                }
                $message .= ' | '.acym_translationSprintf('ACYM_USER_X_REMOVED_FROM', $this->_message->subemail, implode(', ', $listNames));
                $this->userClass->removeSubscription([$this->_message->userid], $removeLists);
            } else {
                $message .= ' | '.acym_translationSprintf('ACYM_USER_X_NOT_SUBSCRIBED', $this->_message->subemail);
            }
        }

        if (in_array('unsubscribe_user', $oneRule->action_user)) {
            $unsubLists = array_diff($commonListsMailUser, [$listId]);
            if (!empty($unsubLists)) {
                $listNames = [];
                foreach ($unsubLists as $oneListId) {
                    if (!empty($this->allLists[$oneListId]->name)) {
                        $listNames[] = $this->allLists[$oneListId]->name;
                    } else {
                        $listNames[] = $oneListId;
                    }
                }
                $this->userClass->unsubscribe([$this->_message->userid], $unsubLists);
                $message .= ' | '.acym_translationSprintf('ACYM_USER_X_UNSUBSCRIBED_FROM', $this->_message->subemail, implode(', ', $listNames));
            } else {
                $message .= ' | '.acym_translationSprintf('ACYM_USER_X_NOT_SUBSCRIBED', $this->_message->subemail);
            }
        }

        if (in_array('block_user', $oneRule->action_user)) {
            $message .= ' | '.acym_translationSprintf('ACYM_USER_X_BLOCKED', $this->_message->subemail.' ( '.acym_translation('ACYM_ID').' '.intval($this->_message->userid).' )');
            $this->blockedUsers[] = intval($this->_message->userid);
        }

        if (in_array('empty_queue_user', $oneRule->action_user)) {
            $affected = acym_query('DELETE FROM #__acym_queue WHERE user_id = '.intval($this->_message->userid));
            $message .= ' | '.acym_translationSprintf(
                    'ACYM_USER_X_QUEUE',
                    $this->_message->subemail.' ( '.acym_translation('ACYM_ID').' '.intval($this->_message->userid).' )',
                    acym_translationSprintf('ACYM_SUCC_DELETE_ELEMENTS', $affected)
                );
        }

        return $message;
    }

    private function actionMessage(object &$oneRule): string
    {
        $message = '';

        //Fix the rule if needed... when the forwarded user is the same as the bounce e-mail address...
        if (!empty($oneRule->action_message['forward_to'])) {
            if (
                strtolower($oneRule->action_message['forward_to']) === strtolower($this->config->get('bounce_username'))
                || strtolower($oneRule->action_message['forward_to']) === strtolower($this->config->get('bounce_email'))
            ) {
                //We don't forward it
                $oneRule->action_message['forward_to'] = '';
                //We don't delete it
                unset($oneRule->action_message['delete_message']);
                $message .= ' | '.acym_translation('ACYM_BOUNCE_NOT_FORWARD');
            }
        }

        //Handle actions on the message itself

        // We only delete the message if the forward didn't fail and the save in history succeeded
        $donotdelete = false;

        if (in_array('save_message', $oneRule->action_message) && !empty($this->_message->userid) && !in_array('delete_user', $oneRule->action_user)) {
            //We have a userid, should we save the message in the database?
            $data = [];
            $data[] = 'SUBJECT::'.@htmlentities($this->_message->subject, ENT_COMPAT, 'UTF-8');
            $data[] = 'ACY_RULE::'.$oneRule->id.' '.$oneRule->name;
            $data[] = 'REPLYTO_ADDRESS::'.$this->_message->header->reply_to_name.' ( '.$this->_message->header->reply_to_email.' )';
            $data[] = 'FROM_ADDRESS::'.$this->_message->header->from_name.' ( '.$this->_message->header->from_email.' )';
            $data[] = @htmlentities(print_r($this->_message->headerinfo, true), ENT_COMPAT, 'UTF-8');
            if (empty($this->_message->mailid)) {
                $this->_message->mailid = 0;
            }
            try {
                $this->historyClass->insert($this->_message->userid, 'bounce', $data, $this->_message->mailid);
                $message .= ' | '.acym_translationSprintf('ACYM_BOUNCE_MESSAGE_SAVED', $this->_message->userid);
            } catch (\Exception $e) {
                $message .= ' | '.acym_translationSprintf(
                        'ACYM_BOUNCE_SAVE_ERROR_USER_MAIL_RULE',
                        $this->_message->userid,
                        $this->_message->mailid,
                        $oneRule->id,
                        $e->getMessage()
                    );
                $donotdelete = true;
            }
        }


        //We don't forward the message if it's the same mailbox!
        if (!empty($oneRule->action_message['forward_to'])) {
            //Get the forward address :
            $this->mailer->clearAll();
            $this->mailer->Subject = 'BOUNCE FORWARD : '.$this->_message->subject;

            if (substr_count($oneRule->action_message['forward_to'], '@') > 1) {
                $forwardAddresses = explode(';', str_replace([';', ','], ';', $oneRule->action_message['forward_to']));
            } else {
                $forwardAddresses = [$oneRule->action_message['forward_to']];
            }

            foreach ($forwardAddresses as $oneForwardAddress) {
                $this->mailer->AddAddress($this->mailer->cleanText($oneForwardAddress));
            }

            //Add the rule at the top so we know why the message has been forwarded.
            $info = acym_translation('ACYM_BOUNCE_RULE').' ['.acym_translation('ACYM_ID').' '.$oneRule->id.'] '.acym_translation($oneRule->name);
            if (!empty($this->_message->html)) {
                $this->mailer->isHTML();
                if (!empty($this->_message->userid)) {
                    $info .= ' ('.acym_translation('ACYM_USER_ID').': '.$this->_message->userid.')';
                } elseif (!empty($this->_message->subemail)) {
                    $info .= ' ('.acym_translation('ACYM_USER_EMAIL').': '.$this->_message->subemail.')';
                }

                $this->mailer->Body = $info.'<br />'.$this->_message->html;
                if (!empty($this->_message->text)) {
                    $this->mailer->Body .= '<br /><br />-------<br />'.nl2br($this->_message->text);
                }
            } else {
                $this->mailer->isHTML(false);
                $this->mailer->Body = $info."\n".$this->_message->text;
            }

            //We add all other extra information just in case of we could use them...
            //original-rcpt-to ?   http://tools.ietf.org/html/rfc5965
            $this->mailer->Body .= print_r($this->_message->headerinfo, true);
            $replyAddress = trim(@$this->_message->header->reply_to_email, '<> ');
            if (!empty($replyAddress)) {
                if ($this->config->get('mailer_method') === \plgAcymAcymailer::SENDING_METHOD_ID) {
                    $this->mailer->AddReplyTo($this->config->get('replyto_email'), $this->config->get('replyto_name'));
                } else {
                    $this->mailer->AddReplyTo(trim($this->_message->header->reply_to_email, '<> '), $this->_message->header->reply_to_name);
                }
            }

            $this->mailer->isForward = true;
            if ($this->mailer->send()) {
                $message .= ' | '.acym_translationSprintf('ACYM_FORWARDED_TO_X', $oneRule->action_message['forward_to']);
            } else {
                $message .= ' | '.acym_translationSprintf('ACYM_NOT_FORWARDED_TO_X', $oneRule->action_message['forward_to'], $this->mailer->reportMessage);
                $this->errorDuringActions = true;
            }
        }

        if (in_array('delete_message', $oneRule->action_message) && !$this->errorDuringActions) {
            $message .= ' | '.acym_translation('ACYM_MESSAGE_DELETED');
            $this->deleteMessage($this->_message->messageNB);
        }

        return $message;
    }

    private function decodeAddressImap(string $type): void
    {
        $address = $type.'address';
        $name = $type.'_name';
        $email = $type.'_email';
        if (empty($this->_message->$type)) {
            return;
        }

        $var = $this->_message->$type;

        if (empty($this->_message->header)) {
            $this->_message->header = new \stdClass();
        }

        if (!empty($this->_message->$address)) {
            $this->_message->header->$name = $this->_message->$address;
        } else {
            $this->_message->header->$name = $var[0]->personal;
        }

        $this->_message->header->$email = $var[0]->mailbox.'@'.@$var[0]->host;
    }

    /**
     * If num is empty then it's a message otherwise it's a send status
     */
    protected function display(string $message, int $type = self::MESSAGE_TYPE_SUCCESS, string $num = ''): void
    {
        $this->messages[] = $message;

        if (!$this->report) return;

        if ($type === self::MESSAGE_TYPE_SUCCESS) {
            $color = 'green';
        } elseif ($type === self::MESSAGE_TYPE_INFO) {
            $color = 'blue';
        } else {
            $color = 'red';
        }

        if (!empty($num)) {
            echo '<br />'.$num.' : ';
        } else {
            echo '<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        }

        echo '<span style="color: '.$color.'">'.$message.'</span>';

        if (function_exists('ob_flush')) {
            @ob_flush();
        }
        if (!$this->mod_security2) {
            @flush();
        }
    }

    public function decodeHeader(string $input): string
    {
        // Remove white space between encoded-words
        $input = preg_replace('/(=\?[^?]+\?(q|b)\?[^?]*\?=)(\s)+=\?/i', '\1=?', $input);
        $currentCharset = false;

        // For each encoded-word...
        while (preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)/i', $input, $matches)) {
            $encoded = $matches[1];
            $charset = $matches[2];
            $encoding = $matches[3];
            $text = $matches[4];

            switch (strtolower($encoding)) {
                case 'b':
                    $text = base64_decode($text);
                    break;

                case 'q':
                    $text = str_replace('_', ' ', $text);
                    preg_match_all('/=([a-f0-9]{2})/i', $text, $matches);
                    foreach ($matches[1] as $value) {
                        $text = str_replace('='.$value, chr(hexdec($value)), $text);
                    }
                    break;
            }
            $currentCharset = $charset;
            $input = str_replace($encoded, $text, $input);
        }

        //If we have a charset and we can handle it...
        if (!empty($currentCharset) && in_array($currentCharset, $this->allCharsets)) {
            $input = $this->encodingHelper->change($input, $currentCharset, 'UTF-8');
        }

        return $input;
    }

    private function explodeBodyMixed(object $struct, string $path = '1'): array
    {
        $allParts = [];

        if (empty($struct->parts)) {
            return $allParts;
        }

        $pathPrefix = ($struct->subtype === 'MIXED' && $path === '1') ? '' : $path.'.';
        foreach ($struct->parts as $i => $part) {
            $partPath = $pathPrefix.($i + 1);
            if ($part->type == 1) {
                $allParts = array_merge($this->explodeBodyMixed($part, $partPath), $allParts);
            } else {
                $allParts[$partPath] = $part;
            }
        }

        return $allParts;
    }

    private function explodeBody(object $struct, string $path = '0', bool $inline = false): array
    {
        $allParts = [];

        if (empty($struct->parts)) {
            return $allParts;
        }

        //counts real content
        $c = 0;
        foreach ($struct->parts as $part) {
            if ($part->type == 1) {
                //There are more parts....:
                if ($part->subtype === 'MIXED') {
                    // refreshing current path
                    $path = $this->incPath($path);
                    //create a new path-id (ex.:2.0)
                    $newpath = $path.'.0';
                    //fetch new parts
                    $allParts = array_merge($this->explodeBody($part, $newpath), $allParts);
                } else {
                    //Alternativ / rfc / signed
                    $newpath = $this->incPath($path);
                    $path = $this->incPath($path);
                    $allParts = array_merge($this->explodeBody($part, $newpath, true), $allParts);
                }
            } else {
                $c++;
                //creating new tree if this is part of a alternativ or rfc message:
                if ($c == 1 && $inline) {
                    $path = $path.'.0';
                }
                //saving content:
                $path = $this->incPath($path);
                //print "<br>  Content ".$path."<br>";        //debug information
                $allParts[$path] = $part;
            }
        }

        return $allParts;
    }

    //Increases the Path to the parts:
    private function incPath(string $path): string
    {
        $newPath = '';
        $path_elements = explode('.', $path);
        $limit = count($path_elements);
        for ($i = 0; $i < $limit; $i++) {
            //last element
            if ($i == $limit - 1) {
                // new Part-Number
                $newPath .= $path_elements[$i] + 1;
            } else {
                //rebuild "1.2.2"-Chronology
                $newPath .= $path_elements[$i].'.';
            }
        }

        return $newPath;
    }

    private function decodeContent(string $content, object $structure): string
    {
        $encoding = $structure->encoding;

        //First we decode the content properly
        if ($encoding == 2) {
            $content = imap_binary($content);
        } elseif ($encoding == 3) {
            $content = base64_decode($content, true);
        } elseif ($encoding == 4) {
            $content = imap_qprint($content);
        }

        // Now we convert into utf-8! only for distribution lists
        if (!empty($this->action)) {
            $charset = $this->getMailParam($structure);
            if (!empty($charset) && strtoupper($charset) !== 'UTF-8') {
                $content = $this->encodingHelper->change($content, $charset, 'UTF-8');
            }

            return $content;
        }

        //It can't be more than 100 000 characters, it's already plenty enough for bounce handling... we avoid embedded pictures or big attachments we could not catch before.
        return substr($content, 0, 100000);
    }

    private function getMailParam(object $params): string
    {
        $searchIn = [];

        if ($params->ifparameters) {
            $searchIn = array_merge($searchIn, $params->parameters);
        }
        if ($params->ifdparameters) {
            $searchIn = array_merge($searchIn, $params->dparameters);
        }

        if (empty($searchIn)) {
            return '';
        }

        foreach ($searchIn as $values) {
            if (strtolower($values->attribute) === 'charset') {
                return $values->value;
            }
        }

        return '';
    }

    public function getErrors(): array
    {
        $return = [];
        if (!$this->usePear) {
            if (!function_exists('imap_alerts')) {
                $return[] = 'The IMAP extension could not be loaded, please change your PHP configuration to enable it or use the pop3 method without imap extension';

                return $return;
            }
            $alerts = $this->callImapFunction('imap_alerts');
            $errors = $this->callImapFunction('imap_errors');
            if (!empty($alerts)) {
                $return = array_merge($return, $alerts);
            }
            if (!empty($errors)) {
                $return = array_merge($return, $errors);
            }
        }

        return $return;
    }

    private function refreshToken(): void
    {
        if ($this->config->get('bounce_server') === 'imap.gmail.com') {
            $url = 'https://oauth2.googleapis.com/token';
        } else {
            $tenant = $this->config->get('bounce_tenant', 'consumers');
            $url = 'https://login.microsoftonline.com/'.$tenant.'/oauth2/v2.0/token';
        }

        $oauth = new OAuth(
            [
                'sendingMethod' => 'bounce',
                'tokenGenerationUrl' => $url,
                'userName' => trim($this->config->get('bounce_username')),
                'clientId' => trim($this->config->get('bounce_client_id')),
                'clientSecret' => trim($this->config->get('bounce_client_secret')),
                'oauthToken' => trim($this->config->get('bounce_access_token')),
                'oauthTokenExpiration' => trim($this->config->get('bounce_access_token_expiration')),
                'refreshToken' => trim($this->config->get('bounce_refresh_token')),
            ]
        );

        $this->oAuthToken = $oauth->getToken();
    }

    private function callImapFunction(string $functionName, array $params = [])
    {
        if (in_array($this->server, self::HOSTS_NEEDING_OAUTH)) {
            $functionName = str_replace('imap_', 'imap2_', $functionName);
        }

        if (empty($params)) {
            return $functionName();
        }

        return $functionName(...$params);
    }
}
