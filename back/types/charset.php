<?php

namespace AcyMailing\Types;

use AcyMailing\Libraries\acymObject;

class CharsetType extends acymObject
{
    var $charsets = [];
    var $values = [];

    public function __construct()
    {
        parent::__construct();
        $charsets = [
            'BIG5' => 'BIG5', //Iconv,mbstring
            'ISO-8859-1' => 'ISO-8859-1', //Iconv,mbstring
            'ISO-8859-2' => 'ISO-8859-2', //Iconv,mbstring
            'ISO-8859-3' => 'ISO-8859-3', //Iconv,mbstring
            'ISO-8859-4' => 'ISO-8859-4', //Iconv,mbstring
            'ISO-8859-5' => 'ISO-8859-5', //Iconv,mbstring
            'ISO-8859-6' => 'ISO-8859-6', //Iconv,mbstring
            'ISO-8859-7' => 'ISO-8859-7', //Iconv,mbstring
            'ISO-8859-8' => 'ISO-8859-8', //Iconv,mbstring
            'ISO-8859-9' => 'ISO-8859-9', //Iconv,mbstring
            'ISO-8859-10' => 'ISO-8859-10', //Iconv,mbstring
            'ISO-8859-13' => 'ISO-8859-13', //Iconv,mbstring
            'ISO-8859-14' => 'ISO-8859-14', //Iconv,mbstring
            'ISO-8859-15' => 'ISO-8859-15', //Iconv,mbstring
            'ISO-2022-JP' => 'ISO-2022-JP', //mbstring for sure... not sure about Iconv
            'US-ASCII' => 'US-ASCII', //Iconv,mbstring
            'UTF-7' => 'UTF-7', //Iconv,mbstring
            'UTF-8' => 'UTF-8', //Iconv,mbstring
            'UTF-16' => 'UTF-16', //Iconv,mbstring
            'Windows-1251' => 'Windows-1251', //Iconv,mbstring
            'Windows-1252' => 'Windows-1252', //Iconv,mbstring
        ];

        if (function_exists('iconv')) {
            $charsets['ARMSCII-8'] = 'ARMSCII-8';
            $charsets['ISO-8859-16'] = 'ISO-8859-16';
        }

        $this->charsets = $charsets;

        $this->values = [];
        foreach ($charsets as $code => $charset) {
            $this->values[] = acym_selectOption($code, $charset);
        }
    }

    public function display($map, $value)
    {
        return acym_select(
            $this->values,
            $map,
            $value,
            [
                'size' => 1,
                'style' => 'width:150px;',
            ]
        );
    }
}
