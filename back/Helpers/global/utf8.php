<?php

// utf8_encode is deprecated in PHP 8.2
function acym_utf8Encode($string)
{
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
    } else {
        $string .= $string;
        $len = strlen($string);

        for ($i = $len >> 1, $j = 0; $i < $len; ++$i, ++$j) {
            if ($string[$i] < "\x80") {
                $string[$j] = $string[$i];
            } elseif ($string[$i] < "\xC0") {
                $string[$j] = "\xC2";
                $string[++$j] = $string[$i];
            } else {
                $string[$j] = "\xC3";
                $string[++$j] = chr(ord($string[$i]) - 64);
            }
        }

        return substr($string, 0, $j);
    }
}

// utf8_decode is deprecated in PHP 8.2
function acym_utf8Decode($string)
{
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($string, 'ISO-8859-1', 'UTF-8');
    } else {
        $newString = (string)$string;
        $len = strlen($newString);

        for ($i = 0, $j = 0; $i < $len; ++$i, ++$j) {
            switch ($newString[$i] & "\xF0") {
                case "\xC0":
                case "\xD0":
                    $c = (ord($newString[$i] & "\x1F") << 6) | ord($newString[++$i] & "\x3F");
                    $newString[$j] = $c < 256 ? chr($c) : '?';
                    break;

                case "\xF0":
                    ++$i;
                // no break

                case "\xE0":
                    $newString[$j] = '?';
                    $i += 2;
                    break;

                default:
                    $newString[$j] = $newString[$i];
            }
        }

        return substr($newString, 0, $j);
    }
}
