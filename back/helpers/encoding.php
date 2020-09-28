<?php

namespace AcyMailing\Helpers;

use AcyMailing\Libraries\acymObject;
use AcyMailing\Types\CharsetType;

class EncodingHelper extends acymObject
{
    /**
     * Changes the encoding of data from input to output charset
     *
     * @param string $data
     * @param string $inputCharset
     * @param string $outputCharset
     *
     * @return string
     */
    public function change($data, $inputCharset, $outputCharset)
    {
        $inputCharset = strtoupper(trim($inputCharset));
        $outputCharset = strtoupper(trim($outputCharset));

        $supportedEncodings = [
            'BIG5',
            'ISO-8859-1',
            'ISO-8859-2',
            'ISO-8859-3',
            'ISO-8859-4',
            'ISO-8859-5',
            'ISO-8859-6',
            'ISO-8859-7',
            'ISO-8859-8',
            'ISO-8859-9',
            'ISO-8859-10',
            'ISO-8859-13',
            'ISO-8859-14',
            'ISO-8859-15',
            'ISO-2022-JP',
            'US-ASCII',
            'UTF-7',
            'UTF-8',
            'UTF-16',
            'WINDOWS-1251',
            'WINDOWS-1252',
            'ARMSCII-8',
            'ISO-8859-16',
        ];
        if (!in_array($inputCharset, $supportedEncodings)) {
            acym_enqueueMessage(acym_translation_sprintf('ACYM_ENCODING_NOT_SUPPORTED_X', $inputCharset), 'error');
        } elseif (!in_array($outputCharset, $supportedEncodings)) {
            acym_enqueueMessage(acym_translation_sprintf('ACYM_ENCODING_NOT_SUPPORTED_X', $outputCharset), 'error');
        }

        if ($inputCharset == $outputCharset) {
            return $data;
        }

        if ($inputCharset == 'UTF-8' && $outputCharset == 'ISO-8859-1') {
            $data = str_replace(['€', '„', '“'], ['EUR', '"', '"'], $data);
        }

        if (function_exists('iconv')) {
            set_error_handler('AcyMailing\Helpers\acym_error_handler_encoding');
            $encodedData = iconv($inputCharset, $outputCharset.'//IGNORE', $data);
            restore_error_handler();
            //Sometimes the function does not return error but remove the whole content...
            if (!empty($encodedData) && !acym_error_handler_encoding('result')) {
                return $encodedData;
            }
        }

        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($data, $outputCharset, $inputCharset);
        }

        if ($inputCharset == 'UTF-8' && $outputCharset == 'ISO-8859-1') {
            return utf8_decode($data);
        }

        if ($inputCharset == 'ISO-8859-1' && $outputCharset == 'UTF-8') {
            return utf8_encode($data);
        }

        return $data;
    }

    // Returns the encoding of the content passed as parameter
    public function detectEncoding(&$content)
    {
        if (!function_exists('mb_check_encoding')) {
            return '';
        }

        $toTest = ['UTF-8'];
        //Depending on the current language, we test based on other charset...

        $tag = acym_getLanguageTag();

        // If you're greek, we check based on the greek charset first!
        if ($tag == 'el-GR') {
            $toTest[] = 'ISO-8859-7';
        }
        $toTest[] = 'ISO-8859-1';
        $toTest[] = 'ISO-8859-2';
        $toTest[] = 'Windows-1252';

        foreach ($toTest as $oneEncoding) {
            if (mb_check_encoding($content, $oneEncoding)) {
                return $oneEncoding;
            }
        }

        // Could not detect it...
        return '';
    }

    public function encodingField($name, $selected, $attribs = null)
    {
        if ($attribs === null) {
            $attribs = [
                'class' => 'acym__select',
                'acym-data-infinite' => '',
            ];
        }
        $attribs['style'] = empty($attribs['style']) ? 'max-width:200px;' : 'max-width:200px;'.$attribs['style'];

        echo acym_select(
            [
                'binary' => 'Binary',
                'quoted' => 'Quoted-printable',
                '7bit' => '7 Bit',
                '8bit' => '8 Bit',
                'base64' => 'Base 64',
            ],
            $name,
            $selected,
            $attribs,
            '',
            '',
            'config_encoding'
        );
    }

    public function charsetField($name, $selected, $attribs = null)
    {
        $charsetType = new CharsetType();

        return acym_select($charsetType->charsets, $name, $selected, $attribs, '', '');
    }
}

/**
 * log the errors happening in the iconv function in order to know if we can use its result or not
 *
 * @param mixed  $errno  if the value is the string "result", it return the value of the static var and reinit it
 * @param string $errstr (php internal fills it)
 *
 * @return boolean true: an error occured, false, no error occured
 */
function acym_error_handler_encoding($errno, $errstr = '')
{
    static $error = false;
    if (is_string($errno) && $errno == 'result') {
        //return the value of the static flag and re init it
        $currentError = $error;
        $error = false;

        return $currentError;
    }
    //set the satic flag
    $error = true;

    //no need to display the notice
    return true;
}
