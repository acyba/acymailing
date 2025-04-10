<?php

namespace AcyMailing\Libraries\Imap2;

use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Header\HeaderConsts;

class Polyfill
{
    public static function convert8bit($string)
    {
        return quoted_printable_encode($string);
    }

    public static function mimeHeaderDecode($string)
    {
        return $string;
    }

    public static function mutf7ToUtf8($string)
    {
        // MUTF-7 to UTF-7
        $string = str_replace(['&', ','], ['+', '/'], $string);

        return mb_convert_encoding($string, 'UTF-8', 'UTF-7');
    }

    public static function qPrint($string)
    {
        return quoted_printable_decode($string);
    }

    public static function rfc822ParseAdrList($string, $defaultHost)
    {
        $message = Message::from('To: '.$string, false);

        return Functions::getAddressObjectList(
            $message->getHeader(HeaderConsts::TO)->getAddresses(),
            $defaultHost
        );
    }

    public static function rfc822ParseHeaders($headers, $defaultHost = 'UNKNOWN')
    {
        $message = Message::from($headers, false);

        $date = $message->getHeaderValue(HeaderConsts::DATE);
        $subject = $message->getHeaderValue(HeaderConsts::SUBJECT);

        $hasReplyTo = $message->getHeader(HeaderConsts::REPLY_TO) !== null;
        $hasSender = $message->getHeader(HeaderConsts::SENDER) !== null;

        return (object)[
            'date' => $date,
            'Date' => $date,
            'subject' => $subject,
            'Subject' => $subject,
            'message_id' => '<'.$message->getHeaderValue(HeaderConsts::MESSAGE_ID).'>',
            'toaddress' => $message->getHeaderValue(HeaderConsts::TO),
            'to' => Functions::getAddressObjectList($message->getHeader(HeaderConsts::TO)->getAddresses()),
            'fromaddress' => $message->getHeaderValue(HeaderConsts::FROM),
            'from' => Functions::getAddressObjectList($message->getHeader(HeaderConsts::FROM)->getAddresses()),
            'reply_toaddress' => $message->getHeaderValue($hasReplyTo ? HeaderConsts::REPLY_TO : HeaderConsts::FROM),
            'reply_to' => Functions::getAddressObjectList($message->getHeader($hasReplyTo ? HeaderConsts::REPLY_TO : HeaderConsts::FROM)->getAddresses()),
            'senderaddress' => $message->getHeaderValue($hasSender ? HeaderConsts::SENDER : HeaderConsts::FROM),
            'sender' => Functions::getAddressObjectList($message->getHeader($hasSender ? HeaderConsts::SENDER : HeaderConsts::FROM)->getAddresses()),
        ];
    }

    public static function rfc822WriteHeaders($mailbox, $hostname, $personal)
    {
        $address = $mailbox.'@'.$hostname;
        if (!empty($personal)) {
            $address = $personal.' <'.$address.'>';
        }

        return $address;
    }

    public static function utf7Decode($string)
    {
        // MUTF-7 to UTF-7
        $string = str_replace(['&', ','], ['+', '/'], $string);

        return mb_convert_encoding($string, 'ISO-8859-1', 'UTF-7');
    }

    public static function utf7Encode($string)
    {
        $utf7 = mb_convert_encoding($string, 'UTF-7', 'ISO-8859-1');

        // Modify it to be Modified UTF-7 (MUTF-7)
        $mutf7 = str_replace(['+', '/', '='], ['&', ',', ''], $utf7);

        return $mutf7;
    }

    public static function utf8ToMutf7($string)
    {
        $utf7 = mb_convert_encoding($string, 'UTF-7', 'UTF-8');

        // Modify it to be Modified UTF-7 (MUTF-7)
        $mutf7 = str_replace(['+', '/', '='], ['&', ',', ''], $utf7);

        return $mutf7;
    }

    public static function utf8($string)
    {
        return iconv_mime_decode($string, 0, 'UTF-8');
    }

    public static function mailCompose($envelope, $bodies)
    {
        return false;
    }

    public static function base64($string)
    {
        return base64_decode($string, true);
    }

    public static function binary($string)
    {
        return base64_encode($string);
    }
}
