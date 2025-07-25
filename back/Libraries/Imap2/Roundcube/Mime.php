<?php

/**
 * +-----------------------------------------------------------------------+
 * | This file is part of the Roundcube Webmail client                     |
 * |                                                                       |
 * | Copyright (C) The Roundcube Dev Team                                  |
 * | Copyright (C) Kolab Systems AG                                        |
 * |                                                                       |
 * | Licensed under the GNU General Public License version 3 or            |
 * | any later version with exceptions for skins & plugins.                |
 * | See the README file for a full license statement.                     |
 * |                                                                       |
 * | PURPOSE:                                                              |
 * |   MIME message parsing utilities                                      |
 * +-----------------------------------------------------------------------+
 * | Author: Thomas Bruederli <roundcube@gmail.com>                        |
 * | Author: Aleksander Machniak <alec@alec.pl>                            |
 * +-----------------------------------------------------------------------+
 */

namespace AcyMailing\Libraries\Imap2\Roundcube;

/**
 * Class for parsing MIME messages
 *
 * @package    Framework
 * @subpackage Storage
 */
class Mime
{
    private static $default_charset;


    /**
     * Object constructor.
     */
    function __construct($default_charset = null)
    {
        self::$default_charset = $default_charset;
    }

    /**
     * Returns message/object character set name
     *
     * @return string Character set name
     */
    public static function get_charset()
    {
        if (self::$default_charset) {
            return self::$default_charset;
        }

        if ($charset = IMAP2_CHARSET) {
            return $charset;
        }

        return IMAP2_CHARSET;
    }

    /**
     * Split an address list into a structured array list
     *
     * @param string|array $input    Input string (or list of strings)
     * @param int          $max      List only this number of addresses
     * @param boolean      $decode   Decode address strings
     * @param string       $fallback Fallback charset if none specified
     * @param boolean      $addronly Return flat array with e-mail addresses only
     *
     * @return array Indexed list of addresses
     */
    static function decode_address_list($input, $max = null, $decode = true, $fallback = null, $addronly = false)
    {
        // A common case when the same header is used many times in a mail message
        if (is_array($input)) {
            $input = implode(', ', $input);
        }

        $a = self::parse_address_list($input, $decode, $fallback);
        $out = [];
        $j = 0;

        // Special chars as defined by RFC 822 need to in quoted string (or escaped).
        $special_chars = '[\(\)\<\>\\\.\[\]@,;:"]';

        if (!is_array($a)) {
            return $out;
        }

        foreach ($a as $val) {
            $j++;
            $address = trim($val['address']);

            if ($addronly) {
                $out[$j] = $address;
            } else {
                $name = trim($val['name']);
                if ($name && $address && $name != $address) {
                    $string = sprintf('%s <%s>', preg_match("/$special_chars/", $name) ? '"'.addcslashes($name, '"').'"' : $name, $address);
                } elseif ($address) {
                    $string = $address;
                } elseif ($name) {
                    $string = $name;
                }

                $out[$j] = ['name' => $name, 'mailto' => $address, 'string' => $string];
            }

            if ($max && $j == $max) {
                break;
            }
        }

        return $out;
    }

    /**
     * Decode a message header value
     *
     * @param string $input    Header value
     * @param string $fallback Fallback charset if none specified
     *
     * @return string Decoded string
     */
    public static function decode_header($input, $fallback = null)
    {
        $str = self::decode_mime_string((string)$input, $fallback);

        return $str;
    }

    /**
     * Decode a mime-encoded string to internal charset
     *
     * @param string $input    Header value
     * @param string $fallback Fallback charset if none specified
     *
     * @return string Decoded string
     */
    public static function decode_mime_string($input, $fallback = null)
    {
        $default_charset = $fallback ? : self::get_charset();

        // rfc: all line breaks or other characters not found
        // in the Base64 Alphabet must be ignored by decoding software
        // delete all blanks between MIME-lines, differently we can
        // receive unnecessary blanks and broken utf-8 symbols
        $input = preg_replace("/\?=\s+=\?/", '?==?', $input);

        // encoded-word regexp
        $re = '/=\?([^?]+)\?([BbQq])\?([^\n]*?)\?=/';

        // Find all RFC2047's encoded words
        if (preg_match_all($re, $input, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            // Initialize variables
            $tmp = [];
            $out = '';
            $start = 0;

            foreach ($matches as $idx => $m) {
                $pos = $m[0][1];
                $charset = $m[1][0];
                $encoding = $m[2][0];
                $text = $m[3][0];
                $length = strlen($m[0][0]);

                // Append everything that is before the text to be decoded
                if ($start != $pos) {
                    $substr = substr($input, $start, $pos - $start);
                    $out .= Charset::convert($substr, $default_charset);
                    $start = $pos;
                }
                $start += $length;

                // Per RFC2047, each string part "MUST represent an integral number
                // of characters . A multi-octet character may not be split across
                // adjacent encoded-words." However, some mailers break this, so we
                // try to handle characters spanned across parts anyway by iterating
                // through and aggregating sequential encoded parts with the same
                // character set and encoding, then perform the decoding on the
                // aggregation as a whole.

                $tmp[] = $text;
                if ($next_match = $matches[$idx + 1]) {
                    if ($next_match[0][1] == $start
                        && $next_match[1][0] == $charset
                        && $next_match[2][0] == $encoding
                    ) {
                        continue;
                    }
                }

                $count = count($tmp);
                $text = '';

                // Decode and join encoded-word's chunks
                if ($encoding == 'B' || $encoding == 'b') {
                    $rest = '';
                    // base64 must be decoded a segment at a time.
                    // However, there are broken implementations that continue
                    // in the following word, we'll handle that (#6048)
                    for ($i = 0 ; $i < $count ; $i++) {
                        $chunk = $rest.$tmp[$i];
                        $length = strlen($chunk);
                        if ($length % 4) {
                            $length = floor($length / 4) * 4;
                            $rest = substr($chunk, $length);
                            $chunk = substr($chunk, 0, $length);
                        }

                        $text .= base64_decode($chunk);
                    }
                } else { //if ($encoding == 'Q' || $encoding == 'q') {
                    // quoted printable can be combined and processed at once
                    for ($i = 0 ; $i < $count ; $i++) {
                        $text .= $tmp[$i];
                    }

                    $text = str_replace('_', ' ', $text);
                    $text = quoted_printable_decode($text);
                }

                $out .= Charset::convert($text, $charset);
                $tmp = [];
            }

            // add the last part of the input string
            if ($start != strlen($input)) {
                $out .= Charset::convert(substr($input, $start), $default_charset);
            }

            // return the results
            return $out;
        }

        // no encoding information, use fallback
        return Charset::convert($input, $default_charset);
    }

    /**
     * Decode a mime part
     *
     * @param string $input    Input string
     * @param string $encoding Part encoding
     *
     * @return string Decoded string
     */
    public static function decode($input, $encoding = '7bit')
    {
        switch (strtolower($encoding)) {
            case 'quoted-printable':
                return quoted_printable_decode($input);
            case 'base64':
                return base64_decode($input);
            case 'x-uuencode':
            case 'x-uue':
            case 'uue':
            case 'uuencode':
                return convert_uudecode($input);
            case '7bit':
            default:
                return $input;
        }
    }

    /**
     * Split RFC822 header string into an associative array
     */
    public static function parse_headers($headers)
    {
        $a_headers = [];
        $headers = preg_replace('/\r?\n(\t| )+/', ' ', $headers);
        $lines = explode("\n", $headers);
        $count = count($lines);

        for ($i = 0 ; $i < $count ; $i++) {
            if ($p = strpos($lines[$i], ': ')) {
                $field = strtolower(substr($lines[$i], 0, $p));
                $value = trim(substr($lines[$i], $p + 1));
                if (!empty($value)) {
                    $a_headers[$field] = $value;
                }
            }
        }

        return $a_headers;
    }

    /**
     * E-mail address list parser
     */
    private static function parse_address_list($str, $decode = true, $fallback = null)
    {
        // remove any newlines and carriage returns before
        $str = preg_replace('/\r?\n(\s|\t)?/', ' ', $str);

        // extract list items, remove comments
        $str = self::explode_header_string(',;', $str, true);
        $result = [];

        // simplified regexp, supporting quoted local part
        $email_rx = '(\S+|("\s*(?:[^"\f\n\r\t\v\b\s]+\s*)+"))@\S+';

        foreach ($str as $key => $val) {
            $name = '';
            $address = '';
            $val = trim($val);

            if (preg_match('/(.*)<('.$email_rx.')>$/', $val, $m)) {
                $address = $m[2];
                $name = trim($m[1]);
            } elseif (preg_match('/^('.$email_rx.')$/', $val, $m)) {
                $address = $m[1];
                $name = '';
            } // special case (#1489092)
            elseif (preg_match('/(\s*<MAILER-DAEMON>)$/', $val, $m)) {
                $address = 'MAILER-DAEMON';
                $name = substr($val, 0, -strlen($m[1]));
            } elseif (preg_match('/('.$email_rx.')/', $val, $m)) {
                $name = $m[1];
            } else {
                $name = $val;
            }

            // dequote and/or decode name
            if ($name) {
                if ($name[0] == '"' && $name[strlen($name) - 1] == '"') {
                    $name = substr($name, 1, -1);
                    $name = stripslashes($name);
                }
                if ($decode) {
                    $name = self::decode_header($name, $fallback);
                    // some clients encode addressee name with quotes around it
                    if ($name[0] == '"' && $name[strlen($name) - 1] == '"') {
                        $name = substr($name, 1, -1);
                    }
                }
            }

            if (!$address && $name) {
                $address = $name;
                $name = '';
            }

            if ($address) {
                $address = self::fix_email($address);
                $result[$key] = ['name' => $name, 'address' => $address];
            }
        }

        return $result;
    }

    /**
     * Explodes header (e.g. address-list) string into array of strings
     * using specified separator characters with proper handling
     * of quoted-strings and comments (RFC2822)
     *
     * @param string $separator       String containing separator characters
     * @param string $str             Header string
     * @param bool   $remove_comments Enable to remove comments
     *
     * @return array Header items
     */
    public static function explode_header_string($separator, $str, $remove_comments = false)
    {
        $length = strlen($str);
        $result = [];
        $quoted = false;
        $comment = 0;
        $out = '';

        for ($i = 0 ; $i < $length ; $i++) {
            // we're inside a quoted string
            if ($quoted) {
                if ($str[$i] == '"') {
                    $quoted = false;
                } elseif ($str[$i] == "\\") {
                    if ($comment <= 0) {
                        $out .= "\\";
                    }
                    $i++;
                }
            } // we are inside a comment string
            elseif ($comment > 0) {
                if ($str[$i] == ')') {
                    $comment--;
                } elseif ($str[$i] == '(') {
                    $comment++;
                } elseif ($str[$i] == "\\") {
                    $i++;
                }
                continue;
            } // separator, add to result array
            elseif (strpos($separator, $str[$i]) !== false) {
                if ($out) {
                    $result[] = $out;
                }
                $out = '';
                continue;
            } // start of quoted string
            elseif ($str[$i] == '"') {
                $quoted = true;
            } // start of comment
            elseif ($remove_comments && $str[$i] == '(') {
                $comment++;
            }

            if ($comment <= 0) {
                $out .= $str[$i];
            }
        }

        if ($out && $comment <= 0) {
            $result[] = $out;
        }

        return $result;
    }

    /**
     * Interpret a format=flowed message body according to RFC 2646
     *
     * @param string  $text  Raw body formatted as flowed text
     * @param string  $mark  Mark each flowed line with specified character
     * @param boolean $delsp Remove the trailing space of each flowed line
     *
     * @return string Interpreted text with unwrapped lines and stuffed space removed
     */
    public static function unfold_flowed($text, $mark = null, $delsp = false)
    {
        $text = preg_split('/\r?\n/', $text);
        $last = -1;
        $q_level = 0;
        $marks = [];

        foreach ($text as $idx => $line) {
            if ($q = strspn($line, '>')) {
                // remove quote chars
                $line = substr($line, $q);
                // remove (optional) space-staffing
                if ($line[0] === ' ') $line = substr($line, 1);

                // The same paragraph (We join current line with the previous one) when:
                // - the same level of quoting
                // - previous line was flowed
                // - previous line contains more than only one single space (and quote char(s))
                if ($q == $q_level
                    && isset($text[$last]) && $text[$last][strlen($text[$last]) - 1] == ' '
                    && !preg_match('/^>+ {0,1}$/', $text[$last])
                ) {
                    if ($delsp) {
                        $text[$last] = substr($text[$last], 0, -1);
                    }
                    $text[$last] .= $line;
                    unset($text[$idx]);

                    if ($mark) {
                        $marks[$last] = true;
                    }
                } else {
                    $last = $idx;
                }
            } else {
                if ($line == '-- ') {
                    $last = $idx;
                } else {
                    // remove space-stuffing
                    if ($line[0] === ' ') $line = substr($line, 1);

                    if (isset($text[$last]) && $line && !$q_level
                        && $text[$last] != '-- '
                        && $text[$last][strlen($text[$last]) - 1] == ' '
                    ) {
                        if ($delsp) {
                            $text[$last] = substr($text[$last], 0, -1);
                        }
                        $text[$last] .= $line;
                        unset($text[$idx]);

                        if ($mark) {
                            $marks[$last] = true;
                        }
                    } else {
                        $text[$idx] = $line;
                        $last = $idx;
                    }
                }
            }
            $q_level = $q;
        }

        if (!empty($marks)) {
            foreach (array_keys($marks) as $mk) {
                $text[$mk] = $mark.$text[$mk];
            }
        }

        return implode("\r\n", $text);
    }

    /**
     * Wrap the given text to comply with RFC 2646
     *
     * @param string $text    Text to wrap
     * @param int    $length  Length
     * @param string $charset Character encoding of $text
     *
     * @return string Wrapped text
     */
    public static function format_flowed($text, $length = 72, $charset = null)
    {
        $text = preg_split('/\r?\n/', $text);

        foreach ($text as $idx => $line) {
            if ($line != '-- ') {
                if ($level = strspn($line, '>')) {
                    // remove quote chars
                    $line = substr($line, $level);
                    // remove (optional) space-staffing and spaces before the line end
                    $line = rtrim($line, ' ');
                    if ($line[0] === ' ') $line = substr($line, 1);

                    $prefix = str_repeat('>', $level).' ';
                    $line = $prefix.self::wordwrap($line, $length - $level - 2, " \r\n$prefix", false, $charset);
                } elseif ($line) {
                    $line = self::wordwrap(rtrim($line), $length - 2, " \r\n", false, $charset);
                    // space-stuffing
                    $line = preg_replace('/(^|\r\n)(From| |>)/', '\\1 \\2', $line);
                }

                $text[$idx] = $line;
            }
        }

        return implode("\r\n", $text);
    }

    /**
     * Improved wordwrap function with multibyte support.
     * The code is based on Zend_Text_MultiByte::wordWrap().
     *
     * @param string $string      Text to wrap
     * @param int    $width       Line width
     * @param string $break       Line separator
     * @param bool   $cut         Enable to cut word
     * @param string $charset     Charset of $string
     * @param bool   $wrap_quoted When enabled quoted lines will not be wrapped
     *
     * @return string Text
     */
    public static function wordwrap($string, $width = 75, $break = "\n", $cut = false, $charset = null, $wrap_quoted = true)
    {
        // Note: Never try to use iconv instead of mbstring functions here
        //       Iconv's substr/strlen are 100x slower (#1489113)

        if ($charset && $charset != IMAP2_CHARSET) {
            mb_internal_encoding($charset);
        }

        // Convert \r\n to \n, this is our line-separator
        $string = str_replace("\r\n", "\n", $string);
        $separator = "\n"; // must be 1 character length
        $result = [];

        while (($stringLength = mb_strlen($string)) > 0) {
            $breakPos = mb_strpos($string, $separator, 0);

            // quoted line (do not wrap)
            if ($wrap_quoted && $string[0] == '>') {
                if ($breakPos === $stringLength - 1 || $breakPos === false) {
                    $subString = $string;
                    $cutLength = null;
                } else {
                    $subString = mb_substr($string, 0, $breakPos);
                    $cutLength = $breakPos + 1;
                }
            } // next line found and current line is shorter than the limit
            elseif ($breakPos !== false && $breakPos < $width) {
                if ($breakPos === $stringLength - 1) {
                    $subString = $string;
                    $cutLength = null;
                } else {
                    $subString = mb_substr($string, 0, $breakPos);
                    $cutLength = $breakPos + 1;
                }
            } else {
                $subString = mb_substr($string, 0, $width);

                // last line
                if ($breakPos === false && $subString === $string) {
                    $cutLength = null;
                } else {
                    $nextChar = mb_substr($string, $width, 1);

                    if ($nextChar === ' ' || $nextChar === $separator) {
                        $afterNextChar = mb_substr($string, $width + 1, 1);

                        // Note: mb_substr() does never return False
                        if ($afterNextChar === false || $afterNextChar === '') {
                            $subString .= $nextChar;
                        }

                        $cutLength = mb_strlen($subString) + 1;
                    } else {
                        $spacePos = mb_strrpos($subString, ' ', 0);

                        if ($spacePos !== false) {
                            $subString = mb_substr($subString, 0, $spacePos);
                            $cutLength = $spacePos + 1;
                        } elseif ($cut === false) {
                            $spacePos = mb_strpos($string, ' ', 0);

                            if ($spacePos !== false && ($breakPos === false || $spacePos < $breakPos)) {
                                $subString = mb_substr($string, 0, $spacePos);
                                $cutLength = $spacePos + 1;
                            } elseif ($breakPos === false) {
                                $subString = $string;
                                $cutLength = null;
                            } else {
                                $subString = mb_substr($string, 0, $breakPos);
                                $cutLength = $breakPos + 1;
                            }
                        } else {
                            $cutLength = $width;
                        }
                    }
                }
            }

            $result[] = $subString;

            if ($cutLength !== null) {
                $string = mb_substr($string, $cutLength, ($stringLength - $cutLength));
            } else {
                break;
            }
        }

        if ($charset && $charset != IMAP2_CHARSET) {
            mb_internal_encoding(IMAP2_CHARSET);
        }

        return implode($break, $result);
    }

    /**
     * Detect image type of the given binary data by checking magic numbers.
     *
     * @param string $data Binary file content
     *
     * @return string Detected mime-type or jpeg as fallback
     */
    public static function image_content_type($data)
    {
        $type = 'jpeg';
        if (preg_match('/^\x89\x50\x4E\x47/', $data)) {
            $type = 'png';
        } elseif (preg_match('/^\x47\x49\x46\x38/', $data)) {
            $type = 'gif';
        } elseif (preg_match('/^\x00\x00\x01\x00/', $data)) $type = 'ico';

        //  else if (preg_match('/^\xFF\xD8\xFF\xE0/', $data)) $type = 'jpeg';

        return 'image/'.$type;
    }

    /**
     * Try to fix invalid email addresses
     */
    public static function fix_email($email)
    {
        $parts = Utils::explode_quoted_string('@', $email);
        foreach ($parts as $idx => $part) {
            // remove redundant quoting (#1490040)
            if ($part[0] == '"' && preg_match('/^"([a-zA-Z0-9._+=-]+)"$/', $part, $m)) {
                $parts[$idx] = $m[1];
            }
        }

        return implode('@', $parts);
    }
}
