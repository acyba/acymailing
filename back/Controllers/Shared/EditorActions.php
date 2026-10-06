<?php

namespace AcyMailing\Controllers\Shared;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Helpers\MailerHelper;

trait EditorActions
{
    public function autoSave(): void
    {
        acym_checkToken();

        $mailClass = new MailClass();
        $mail = new \stdClass();

        $language = acym_getVar('string', 'language', 'main');
        $mail->id = acym_getVar('int', 'mailId', 0);
        $mail->autosave = base64_decode(acym_getVar('string', 'autoSave', '', 'REQUEST', ACYM_ALLOWRAW));

        if (empty($mail->id) || !$mailClass->hasUserAccess($mail->id, true) || !$mailClass->autoSave($mail, $language)) {
            acym_sendAjaxResponse('', [], false);
        } else {
            acym_sendAjaxResponse();
        }
    }

    public function ajaxCheckVideoUrl(): void
    {
        acym_checkToken();
        $videoUrl = acym_getVar('string', 'url', '');

        if (!acym_isValidUrl($videoUrl)) {
            acym_sendAjaxResponse('', [], false);
        }

        $image = '';
        $imageName = '';

        $youtubeMatch = '';
        $vimeoMatch = '';
        $dailymotionMatch = '';

        preg_match('/(?:https?:\/{2})?(?:w{3}\.)?youtu(?:be)?\.(?:com|be)(?:\/watch\?v=|\/)([^\s&?\/]+)/', $videoUrl, $youtubeMatch);
        preg_match('/^.*(vimeo\.com\/)((channels\/[A-z]+\/)|(groups\/[A-z]+\/videos\/))?([0-9]+)/', $videoUrl, $vimeoMatch);
        preg_match('/^(?:(?:http|https):\/\/)?(?:www.)?(dailymotion\.com|dai\.ly)\/((video\/([^_?]+))|(hub\/([^_?]+)|([^\/_?]+)))(?:\?.*)?$/', $videoUrl, $dailymotionMatch);

        if (!empty($youtubeMatch)) {
            $image = 'https://img.youtube.com/vi/'.$youtubeMatch[1].'/0.jpg';
            $imageName = $youtubeMatch[1];
        } elseif (!empty($dailymotionMatch)) {
            $dailymotionImage = $dailymotionMatch[4] ?? $dailymotionMatch[2];

            $image = 'https://www.dailymotion.com/thumbnail/video/'.$dailymotionImage;
            $imageName = $dailymotionMatch[2];
        } elseif (!empty($vimeoMatch)) {
            $image = unserialize(file_get_contents('https://vimeo.com/api/v2/video/'.$vimeoMatch[5].'.php'));
            $image = $image[0]['thumbnail_large'];
            $imageName = $vimeoMatch[5];
        }

        if (empty($image) || !acym_isValidUrl($image)) {
            acym_sendAjaxResponse('', [], false);
        }

        acym_sendAjaxResponse('', ['new_image_name' => $this->saveVideoPreview($image, urlencode($imageName).'.jpg')]);
    }

    private function saveVideoPreview(string $image, string $fileName): string
    {
        if (!$this->config->get('add_play_button_video', 1)) {
            return $image;
        }

        $imageVideo = imagecreatefromjpeg($image);
        $playButton = @imagecreatefrompng(ACYM_ROOT.ACYM_MEDIA_FOLDER.'images'.DS.'editor'.DS.'video_insertion'.DS.'play_button.png');
        if ($playButton === false || $imageVideo === false) {
            return $image;
        }
        $imageWidth = imagesx($imageVideo);
        $imageHeight = imagesy($imageVideo);
        $logoWidth = imagesx($playButton);
        $logoHeight = imagesy($playButton);

        $left = round(($imageWidth - $logoWidth) / 2);
        $top = round(($imageHeight - $logoHeight) / 2);
        imagecopy($imageVideo, $playButton, $left, $top, 0, 0, $logoWidth, $logoHeight);
        $tmpFilePath = ACYM_TMP_FOLDER.'tmp.jpg';
        acym_createFolder(ACYM_TMP_FOLDER);
        imagepng($imageVideo, $tmpFilePath, 9);
        $input = imagecreatefrompng($tmpFilePath);
        $output = imagecreatetruecolor($imageWidth, $imageHeight);
        $white = imagecolorallocate($output, 255, 255, 255);

        imagefilledrectangle($output, 0, 0, $imageWidth, $imageHeight, $white);
        imagecopy($output, $input, 0, 0, 0, 0, $imageWidth, $imageHeight);

        ob_start();
        $status = imagejpeg($output, null, 95);
        $imageContent = ob_get_clean();
        if ($status && acym_writeFile(ACYM_ROOT.ACYM_UPLOAD_FOLDER.$fileName, $imageContent)) {
            acym_deleteFile($tmpFilePath);

            return ACYM_UPLOADS_URL.$fileName;
        }

        acym_deleteFile($tmpFilePath);

        return '';
    }

    public function setNewThumbnail(): void
    {
        if (!acym_isAdmin() || (!acym_isAllowed('mails') && !acym_isAllowed('campaigns'))) {
            die('Access denied for thumbnail creation');
        }

        acym_checkToken();
        $contentThumbnail = acym_getVar('string', 'content', '');
        if (strpos($contentThumbnail, 'data:image/png') !== 0) {
            acym_sendAjaxResponse('This file is not allowed.', [], false);
        }

        $mailId = acym_getVar('int', 'mailId', 0);
        if (!empty($mailId)) {
            $mailClass = new MailClass();
            $mail = $mailClass->getOneById($mailId);
            if (!empty($mail)) {
                $file = $mail->thumbnail;
            }
        }

        if (empty($file) || strpos($file, 'http') === 0) {
            $thumbNb = $this->config->get('numberThumbnail', 2);
            $file = 'thumbnail_'.($thumbNb + 1).'.png';
            $this->config->saveConfig(['numberThumbnail' => $thumbNb + 1]);
        }

        $extension = acym_fileGetExt($file);
        if (strpos($file, 'thumbnail_') === false || !in_array($extension, ['png', 'jpeg', 'jpg', 'gif', 'webp'])) {
            acym_sendAjaxResponse('This file is not allowed.', [], false);
        }

        acym_createFolder(ACYM_UPLOAD_FOLDER_THUMBNAIL);
        file_put_contents(ACYM_UPLOAD_FOLDER_THUMBNAIL.$file, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $contentThumbnail)));

        acym_sendAjaxResponse('', ['fileName' => $file]);
    }

    public function setNewIconShare(): void
    {
        acym_checkToken();
        $socialName = acym_getVar('string', 'social', '');
        $socialMedias = acym_getSocialMedias();
        if (!in_array($socialName, $socialMedias)) {
            acym_sendAjaxResponse(acym_translationSprintf('ACYM_UNKNOWN_SOCIAL', $socialName), [], false);
        }

        $file = acym_getVar('array', 'file', [], 'FILES');
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newPath = ACYM_UPLOAD_FOLDER.'socials'.DS.$socialName;
        $newPathComplete = $newPath.'.'.$extension;

        $allowedExtensions = acym_getImageFileExtensions(true);
        if (!in_array($extension, $allowedExtensions)) {
            $errorMessage = acym_translationSprintf('ACYM_ACCEPTED_TYPE', acym_escapeHtml($extension), implode(', ', $allowedExtensions));
        } elseif (!acym_uploadFile($file['tmp_name'], ACYM_ROOT.$newPathComplete)) {
            $errorMessage = acym_translationSprintf('ACYM_ERROR_UPLOADING_FILE_X', $newPathComplete);
        } elseif (strtolower($extension) === 'svg' && !acym_isSvgFileSafe(ACYM_ROOT.$newPathComplete)) {
            acym_deleteFile(ACYM_ROOT.$newPathComplete);
            $errorMessage = acym_translationSprintf('ACYM_ERROR_UPLOADING_FILE_X', $newPathComplete);
        }

        if (!empty($errorMessage)) {
            acym_sendAjaxResponse($errorMessage, [], false);
        }

        $newImg = acym_rootURI().$newPathComplete;
        $newImgWithoutExtension = acym_rootURI().$newPath;

        $socialIcons = json_decode($this->config->get('social_icons', '{}'), true);
        $socialIcons[$socialName] = $newImg;
        $this->config->saveConfig(['social_icons' => json_encode($socialIcons)]);

        acym_sendAjaxResponse(
            acym_translation('ACYM_ICON_IMPORTED'),
            [
                'url' => $newImgWithoutExtension,
                'extension' => $extension,
            ]
        );
    }

    public function sendTest(): void
    {
        acym_checkToken();
        $controller = acym_getVar('string', 'controller', 'mails');
        $level = 'info';

        $testNote = acym_getVar('string', 'test_note', '');
        $mailClass = new MailClass();
        $mailerHelper = new MailerHelper();
        $mailerHelper->autoAddUser = true;
        $mailerHelper->report = false;

        if (in_array($controller, ['mails', 'frontmails'])) {
            $mailId = acym_getVar('int', 'id', 0);
        } else {
            $campaignId = acym_getVar('int', 'id', 0);
            $campaignClass = new CampaignClass();
            $campaign = $campaignClass->getOneById($campaignId);
            if (empty($campaign)) {
                acym_sendAjaxResponse('', ['level' => 'error', 'message' => acym_translation('ACYM_CAMPAIGN_NOT_FOUND')], false);
            }
            if (!$campaignClass->hasUserAccess($campaignId)) {
                die('A test of this campaign cannot be sent');
            }

            $mailId = $campaign->mail_id;
            $mailerHelper->isAbTest = $campaignClass->isAbTestMail($mailId);

            $campaignVersion = acym_getVar('string', 'lang_version', 'main');
            if (!empty($campaignVersion) && $campaignVersion !== 'main') {
                if ($campaignVersion === 'B') {
                    $versions = $mailClass->getVersionsById($mailId);
                    if (!empty($versions)) {
                        $mailIds = array_keys($versions);
                        $mailId = array_pop($mailIds);
                    }
                } else {
                    $translationId = $mailClass->getTranslationId($mailId, $campaignVersion);
                    if (!empty($translationId)) {
                        $mailId = $translationId;
                    }
                }
            }
        }

        $mail = $mailClass->getOneById($mailId);

        if (empty($mail) || !$mailClass->hasUserAccess($mailId)) {
            acym_sendAjaxResponse('', ['level' => 'error', 'message' => acym_translation('ACYM_EMAIL_NOT_FOUND')], false);
        }

        $report = [];

        $testEmails = explode(',', acym_getVar('string', 'test_emails'));
        $options = [
            'isTest' => true,
            'testNote' => $testNote,
        ];
        foreach ($testEmails as $oneAddress) {
            if (!$mailerHelper->sendOne($mail->id, $oneAddress, $options)) {
                $level = 'error';
            }

            if (!empty($mailerHelper->reportMessage)) {
                $report[] = $mailerHelper->reportMessage;
            }
        }

        acym_sendAjaxResponse('', ['level' => $level, 'message' => implode('<br/>', $report)], $level !== 'error');
    }

    public function getMailContent(): void
    {
        $from = acym_getVar('int', 'from', 0);

        if (empty($from)) {
            acym_sendAjaxResponse(acym_translation('ACYM_EMAIL_NOT_FOUND'), [], false);
        }

        $mailClass = new MailClass();
        $mail = $mailClass->getOneById($from);

        if (empty($mail)) {
            acym_sendAjaxResponse(acym_translation('ACYM_EMAIL_NOT_FOUND'), [], false);
        }

        if ($mail->drag_editor == 0) {
            acym_sendAjaxResponse('Only available for the drag and drop editor.', [], false);
        }

        acym_sendAjaxResponse();
    }
}
