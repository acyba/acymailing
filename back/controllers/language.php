<?php

namespace AcyMailing\Controllers;

use AcyMailing\Helpers\MailerHelper;
use AcyMailing\Helpers\UpdateHelper;
use AcyMailing\Libraries\acymController;

class LanguageController extends acymController
{
    public function saveLanguage($fromShare = false)
    {
        acym_checkToken();

        $code = acym_getVar('cmd', 'code');
        acym_setVar('code', $code);

        // Get content that can be modified when loading the latest version from our site
        $content = acym_getVar('string', 'content', '', '', ACYM_ALLOWRAW);
        $content = str_replace('</textarea>', '', $content);

        if (empty($code) || empty($content)) {
            return $this->displayLanguage();
        }

        // Get the custom translations
        $customcontent = acym_getVar('string', 'customcontent', '', '', ACYM_ALLOWRAW);
        $customcontent = str_replace('</textarea>', '', $customcontent);

        // We have a code, we have a content... so we can simply save the file!
        $path = acym_getLanguagePath(ACYM_ROOT, $code).DS.$code.'.com_acym.ini';
        $result = acym_writeFile($path, $content);
        if ($result) {
            acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');
            //We update the picture from "add" to "edit"
            acym_addScript(
                true,
                'let langIcon = window.top.document.getElementById("image'.$code.'"); langIcon.className = langIcon.className.replace("acymicon-add", "") + " acymicon-edit"'
            );

            //Now we will also create a menu language file and save it
            $updateHelper = new UpdateHelper();
            $updateHelper->installBackLanguages($code);
        } else {
            acym_enqueueMessage(acym_translationSprintf('ACYM_FAIL_SAVE_FILE', $path), 'error');
        }

        // Let's save the custom language file now...
        $custompath = acym_getLanguagePath(ACYM_ROOT, $code).DS.$code.'.com_acym_custom.ini';
        $customresult = acym_writeFile($custompath, $customcontent);
        if (!$customresult) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_FAIL_SAVE_FILE', $custompath), 'error');
        }

        if ($code == acym_getLanguageTag()) {
            acym_loadLanguage();
        }

        //We add lang to menu
        $updateHelper = new UpdateHelper();
        $updateHelper->installBackLanguages();

        if ($fromShare) {
            return $result;
        } else {
            return $this->displayLanguage();
        }
    }

    public function latest()
    {
        $this->displayLanguage();
    }

    public function share()
    {
        acym_checkToken();

        if ($this->saveLanguage(true)) {
            acym_setVar('layout', 'share');

            $file = new \stdClass();
            $file->name = acym_getVar('cmd', 'code');

            return parent::display(['file' => $file]);
        } else {
            return $this->displayLanguage();
        }
    }

    public function send()
    {
        acym_checkToken();

        $bodyEmail = acym_getVar('string', 'mailbody');
        $code = acym_getVar('cmd', 'code');
        acym_setVar('code', $code);

        if (empty($code)) {
            return;
        }

        $mailer = new MailerHelper();
        $mailer->Subject = '[ACYMAILING LANGUAGE FILE] '.$code;
        $mailer->Body = 'The website '.ACYM_LIVE.' using AcyMailing '.$this->config->get('level').' '.$this->config->get('version').' sent a language file : '.$code;
        $mailer->Body .= "\n\n\n".$bodyEmail;

        //Include the extra language file....
        $file = acym_getLanguagePath(ACYM_ROOT, $code).DS.$code.'.com_acym.ini';
        if (!file_exists($file)) {
            return;
        }

        $translation = acym_fileGetContent($file);

        // Include the custom translations
        $customFile = acym_getLanguagePath(ACYM_ROOT, $code).DS.$code.'.com_acym_custom.ini';

        if (file_exists($customFile)) {
            $customTranslation = acym_fileGetContent($customFile);

            if (!empty($customTranslation)) {
                // Replace translations in the main lang file by the custom ones if they exist
                $newKeys = [];
                $customKeys = [];
                preg_match_all('#([0-9A-Z_]+)="((?:[^"]|"_QQ_")+)"#is', $customTranslation, $customKeys);

                if (!empty($customKeys)) {
                    $mainKeys = [];
                    preg_match_all('#([0-9A-Z_]+)="((?:[^"]|"_QQ_")+)"#is', $translation, $mainKeys);

                    foreach ($customKeys[1] as $index => $oneKey) {
                        $position = array_search($oneKey, $mainKeys[1]);
                        if ($position !== false) {
                            // Replace the translation in the main file
                            $translation = str_replace($mainKeys[0][$position], $customKeys[0][$index], $translation);
                        } else {
                            // Add the additional translation to the email body, it may be useful somehow
                            $newKeys[] = $customKeys[0][$index];
                        }
                    }

                    if (!empty($newKeys)) {
                        $mailer->Body .= "\n\n\nCustom content:\n".implode("\n", $newKeys);
                    }
                }
            }
        }

        // Attach the file
        $mailer->addStringAttachment($translation, $code.'.com_acym.ini');

        $mailer->AddAddress(acym_currentUserEmail(), acym_currentUserName());
        $mailer->AddAddress('translate@acyba.com', 'Acyba Translation Team');
        $mailer->report = false;

        $result = $mailer->Send();

        if ($result) {
            acym_enqueueMessage(acym_translation('ACYM_THANK_YOU_SHARING').'<br>'.acym_translation('ACYM_MESSAGE_SENT'), 'success');
        } else {
            acym_enqueueMessage($mailer->reportMessage, 'error');
        }

        $this->displayLanguage();
    }

    public function displayLanguage()
    {
        acym_setVar('layout', 'default');

        $code = acym_getVar('string', 'code');
        if (empty($code)) {
            acym_display(acym_translation('ACYM_LANGUAGE_CODE_NOT_FOUND'), 'error');

            return;
        }

        $file = new \stdClass();
        $file->name = $code;
        $path = acym_getLanguagePath(ACYM_ROOT, $code).DS.$code.'.com_acym.ini';
        $file->path = $path;
        $file->content = '';
        $file->customcontent = '';


        $showLatest = true;
        $loadLatest = false;

        if (file_exists($path)) {
            $file->content = acym_fileGetContent($path);
            if (empty($file->content)) {
                acym_display(acym_translationSprintf('ACYM_FILE_NOT_FOUND', $path), 'error');
            }
        } else {
            $loadLatest = true;
            // Load the default language
            if ('{__CMS__}' === 'Joomla') {
                $message = acym_translation('ACYM_LOAD_ENGLISH_1');
                $message .= '<br />'.acym_translation('ACYM_LOAD_ENGLISH_2');
                $message .= '<br />'.acym_translation('ACYM_LOAD_ENGLISH_3');
                acym_enqueueMessage($message, 'info');
            }
            $file->content = acym_fileGetContent(acym_getLanguagePath(ACYM_ROOT, ACYM_DEFAULT_LANGUAGE).DS.ACYM_DEFAULT_LANGUAGE.'.com_acym.ini');
        }

        $custompath = acym_getLanguagePath(ACYM_ROOT, $code).DS.$code.'.com_acym_custom.ini';
        if (file_exists($custompath)) {
            $file->customcontent = acym_fileGetContent($custompath);
        }

        if ($loadLatest || acym_getVar('cmd', 'task') == 'latest') {
            if (file_exists(acym_getLanguagePath(ACYM_ROOT, $code))) {
                //__START__joomla_
                if ('{__CMS__}' === 'Joomla') {
                    acym_addScript(false, ACYM_UPDATEURL.'languageload&component=acym&code='.acym_getVar('cmd', 'code'));
                }
                //__END__joomla_
            } else {
                acym_enqueueMessage(acym_translationSprintf('ACYM_LANGUAGE_NOT_INSTALLED', $code), 'warning');
            }
            $showLatest = false;
        } elseif (acym_getVar('cmd', 'task') == 'save') {
            $showLatest = false;
        }

        $data = [
            'showLatest' => $showLatest,
            'file' => $file,
        ];

        parent::display($data);
    }
}
