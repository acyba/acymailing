<?php

namespace AcyMailing\WpInit;

use AcyMailing\Helpers\MailerHelper;

class OverrideEmail
{
    public function __construct()
    {
        add_filter('wp_mail', [$this, 'overrideEmailFunction']);
    }

    public function overrideEmailFunction($args)
    {
        // A previous email in the same request may have registered the "blockEmailSending" and "blockEmailSendingPostSMTP" hooks below.
        // We remove them systematically so each email is evaluated independently and we never missblock an email
        remove_action('phpmailer_init', [$this, 'blockEmailSending']);
        remove_filter('post_smtp_do_send_email', [$this, 'blockEmailSendingPostSMTP']);

        // Let integrators exclude specific emails from AcyMailing's handling
        if (!apply_filters('acym_override_wp_mail', true, $args)) {
            return $args;
        }

        if (empty($args['to'])) {
            return $args;
        }

        $passedTo = $args['to'];
        if (!is_array($passedTo)) {
            $passedTo = explode(',', $passedTo);
            array_map('trim', $passedTo);
        }

        $to = array_shift($passedTo);
        $bcc = $passedTo;

        $contentType = '';
        $boundary = '';
        $headers = [];
        if (!empty($args['headers'])) {
            $rawHeaders = is_array($args['headers']) ? $args['headers'] : explode("\n", str_replace("\r\n", "\n", $args['headers']));

            foreach ($rawHeaders as $oneHeader) {
                if (strpos($oneHeader, ':') === false) {
                    if (stripos($oneHeader, 'boundary=') !== false) {
                        $parts = preg_split('/boundary=/i', trim($oneHeader));
                        $boundary = trim(str_replace(["'", '"'], '', $parts[1]));
                    }

                    continue;
                }

                [$name, $content] = explode(':', trim($oneHeader), 2);

                if (in_array(strtolower($name), ['cc', 'bcc'])) {
                    $bcc[] = trim($content);
                } elseif (strtolower($name) === 'content-type') {
                    if (strpos($content, ';') !== false) {
                        [$type, $charsetContent] = explode(';', $content);
                        $contentType = trim($type);
                        if (stripos($charsetContent, 'boundary=') !== false) {
                            $boundary = trim(str_replace(['BOUNDARY=', 'boundary=', '"'], '', $charsetContent));
                        }
                        // Avoid setting an empty $content_type.
                    } elseif (trim($content) !== '') {
                        $contentType = trim($content);
                    }
                } else {
                    $headers[$name] = $content;
                }
            }
        }

        if (stripos($contentType, 'multipart') !== false && !empty($boundary)) {
            $headers['Content-Type'] = $contentType.'; boundary="'.$boundary.'"';
        }

        $attachments = [];
        if (!empty($args['attachments'])) {
            $rawAttachments = is_array($args['attachments']) ? $args['attachments'] : explode("\n", str_replace("\r\n", "\n", $args['attachments']));

            foreach ($rawAttachments as $fileName => $attachmentUrl) {
                $fileName = is_string($fileName) ? $fileName : basename($attachmentUrl);
                $attachments[] = (object)[
                    'filename' => $attachmentUrl,
                    'name' => $fileName,
                    'url' => str_replace(ACYM_ROOT, ACYM_LIVE, $attachmentUrl),
                ];
            }
        }

        $mailerHelper = new MailerHelper();
        $mailerHelper->report = false;

        $success = $mailerHelper->overrideEmail(
            [
                'subject' => $args['subject'],
                'message' => $args['message'],
                'to' => $to,
                'isHtml' => $contentType !== 'text/plain',
                'headers' => $headers,
                'bcc' => $bcc,
                'attachments' => $attachments,
            ]
        );

        if ($success) {
            add_action('phpmailer_init', [$this, 'blockEmailSending']);
            add_filter('post_smtp_do_send_email', [$this, 'blockEmailSendingPostSMTP']);
        }

        return $args;
    }

    public function blockEmailSending(&$phpmailer)
    {
        $phpmailer = new MailDisabler();
    }

    public function blockEmailSendingPostSMTP($shouldSend)
    {
        return false;
    }
}
