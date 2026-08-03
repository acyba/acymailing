<?php

namespace AcyMailing\Helpers;

use AcyMailing\Classes\MailClass;
use AcyMailing\Core\AcymObject;

class PluginHelper extends AcymObject
{
    private MailerHelper $mailerHelper;
    public string $name = 'content';
    public string $wrappedText = '';
    public string $contextLanguage;

    /*
     * Convert an array of elements into a table with multiple columns based on the $parameter variable
     * $parameter->displaytype = table by default
     * $parameter->cols = 1 by default
     */
    public function getFormattedResult(array $elements, object $parameter): string
    {
        //We do not add an extra table or whatever when there is a single element...
        if (count($elements) < 2) {
            return implode('', $elements);
        }

        $beforeAll = [];
        $beforeAll['table'] = '<table cellspacing="0" cellpadding="0" border="0" width="100%" class="elementstable">'."\n";
        $beforeAll['ul'] = '<ul class="elementsul">'."\n";
        $beforeAll['br'] = '';

        $beforeBlock = [];
        $beforeBlock['table'] = '<tr class="elementstable_tr numrow{rownum}">'."\n";
        $beforeBlock['ul'] = '';
        $beforeBlock['br'] = '';

        $beforeOne = [];
        $beforeOne['table'] = '<td valign="top" width="{equalwidth}" style="{padding}" class="elementstable_td numcol{numcol}" >'."\n";
        $beforeOne['ul'] = '<li class="elementsul_li numrow{rownum}">'."\n";
        $beforeOne['br'] = '';

        $afterOne = [];
        $afterOne['table'] = '</td>'."\n";
        $afterOne['ul'] = '</li>'."\n";
        $afterOne['br'] = '<br />'."\n";

        $afterBlock = [];
        $afterBlock['table'] = '</tr>'."\n";
        $afterBlock['ul'] = '';
        $afterBlock['br'] = '';

        $afterAll = [];
        $afterAll['table'] = '</table>'."\n";
        $afterAll['ul'] = '</ul>'."\n";
        $afterAll['br'] = '';


        $type = 'table';
        $cols = 1;
        if (!empty($parameter->displaytype)) {
            $type = $parameter->displaytype;
        }
        if ($type == 'none') {
            return implode('', $elements);
        }
        if (!empty($parameter->cols)) {
            $cols = $parameter->cols;
        }

        $parameter->hpadding = !isset($parameter->hpadding) || is_null($parameter->hpadding) ? 10 : $parameter->hpadding;
        $parameter->vpadding = !isset($parameter->vpadding) || is_null($parameter->vpadding) ? 10 : $parameter->vpadding;

        $horizontalPadding = round($parameter->hpadding / 2);
        $verticalPadding = round($parameter->vpadding / 2);

        $string = $beforeAll[$type];
        $a = 0;
        $numrow = 1;
        foreach ($elements as $key => $oneElement) {
            $topPadding = $verticalPadding.'px';
            $rightPadding = $horizontalPadding.'px';
            $bottomPadding = $verticalPadding.'px';
            $leftPadding = $horizontalPadding.'px';

            if ($a == $cols) {
                $string .= $afterBlock[$type];
                $a = 0;
                $numrow++;
            }

            if ($a == 0) {
                $string .= str_replace('{rownum}', $numrow, $beforeBlock[$type]);
                $leftPadding = '0px';
            }

            if ($a + 1 == $cols) $rightPadding = '0px';
            if ($numrow == 1) $topPadding = '0px';
            if (round(count($elements) / $cols) == $numrow) $bottomPadding = '0px';

            $padding = 'padding: '.$topPadding.' '.$rightPadding.' '.$bottomPadding.' '.$leftPadding.' !important;';

            $string .= str_replace('{numcol}', $a + 1, $beforeOne[$type]).$oneElement.$afterOne[$type];
            $string = str_replace('{padding}', $padding, $string);
            $a++;
        }
        while ($cols > $a) {
            $string .= str_replace('{numcol}', $a + 1, $beforeOne[$type]).$afterOne[$type];
            $a++;
        }

        $string .= $afterBlock[$type];
        $string .= $afterAll[$type];

        $equalwidth = intval(100 / $cols).'%';

        return str_replace(['{equalwidth}'], [$equalwidth], $string);
    }

    /**
     * This function will apply extra parameters such as part:first, ucfirst, strtolower to the $string
     */
    public function formatString(&$replaceme, object $mytag): void
    {
        if (!empty($mytag->part)) {
            $parts = explode(' ', $replaceme);
            if ($mytag->part == 'last') {
                $replaceme = count($parts) > 1 ? end($parts) : '';
            } else {
                if (is_numeric($mytag->part) && count($parts) >= $mytag->part) {
                    $replaceme = $parts[$mytag->part - 1];
                } else {
                    $replaceme = reset($parts);
                }
            }
        }

        if (!empty($mytag->type)) {
            if (empty($mytag->format)) {
                $mytag->format = acym_translation('ACYM_DATE_FORMAT_LC3');
            }
            if ($mytag->type == 'date') {
                $replaceme = acym_getDate(acym_getTime($replaceme), $mytag->format);
            } elseif ($mytag->type == 'time') {
                $replaceme = acym_getDate($replaceme, $mytag->format);
            } elseif ($mytag->type == 'diff') {
                try {
                    //We have a date? Sure?
                    $date = $replaceme;
                    if (is_numeric($date)) {
                        $date = acym_getDate($replaceme, '%Y-%m-%d %H:%M:%S');
                    }
                    $dateObj = new \DateTime($date);
                    $nowObj = new \DateTime();
                    $diff = $dateObj->diff($nowObj);
                    $replaceme = $diff->format($mytag->format);
                } catch (\Exception $e) {
                    $replaceme = 'Error using the "diff" parameter in your tag. Please make sure the DateTime() and diff() functions are available on your server.';
                }
            }
        }

        //Two possibles keywords for that... we used to have "lower" and "upper" only but I added lowercase and uppercase as well on December 2014
        if (!empty($mytag->lower) || !empty($mytag->lowercase)) {
            $replaceme = function_exists('mb_strtolower') ? mb_strtolower($replaceme, 'UTF-8') : strtolower($replaceme);
        }
        if (!empty($mytag->upper) || !empty($mytag->uppercase)) {
            $replaceme = function_exists('mb_strtoupper') ? mb_strtoupper($replaceme, 'UTF-8') : strtoupper($replaceme);
        }
        if (!empty($mytag->ucwords)) {
            $replaceme = ucwords($replaceme);
        }
        if (!empty($mytag->ucfirst)) {
            $replaceme = ucfirst($replaceme);
        }
        //Remove a character at the end of the string...
        if (isset($mytag->rtrim)) {
            $replaceme = empty($mytag->rtrim) ? rtrim($replaceme) : rtrim($replaceme, $mytag->rtrim);
        }
        if (!empty($mytag->urlencode)) {
            $replaceme = urlencode($replaceme);
        }
        if (!empty($mytag->substr)) {
            //the parameter |substr:4,6 will select the 6 characters after the 4.
            $args = explode(',', $mytag->substr);
            if (isset($args[1])) {
                $replaceme = substr($replaceme, intval($args[0]), intval($args[1]));
            } else {
                $replaceme = substr($replaceme, intval($args[0]));
            }
        }


        if (!empty($mytag->maxheight) || !empty($mytag->maxwidth)) {
            $imageHelper = new ImageHelper();
            $imageHelper->maxHeight = empty($mytag->maxheight) ? 999 : $mytag->maxheight;
            $imageHelper->maxWidth = empty($mytag->maxwidth) ? 999 : $mytag->maxwidth;
            $replaceme = $imageHelper->resizePictures($replaceme);
        }
    }

    public function replaceVideos(string &$text): void
    {
        //Youtube videos
        $text = preg_replace(
            '#\[embed=videolink][^}]*youtube[^=]*=([^"/}]*)[^}]*}\[/embed]#i',
            '<a target="_blank" href="https://www.youtube.com/watch?v=$1"><img src="https://img.youtube.com/vi/$1/0.jpg"/></a>',
            $text
        );
        $text = preg_replace(
            '#<video[^>]*youtube\.com/embed/([^"/]*)[^>]*>[^>]*</video>#i',
            '<a target="_blank" href="https://www.youtube.com/watch?v=$1"><img src="https://img.youtube.com/vi/$1/0.jpg"/></a>',
            $text
        );
        $text = preg_replace(
            '#{JoooidContent[^}]*youtube[^}]*id"[^"]*"([^}"]*)"[^}]*}#i',
            '<a target="_blank" href="https://www.youtube.com/watch?v=$1"><img src="https://img.youtube.com/vi/$1/0.jpg"/></a>',
            $text
        );
        $text = preg_replace(
            '#<iframe[^>]*src="[^"]*youtube[^"]*embed/([^"?]*)(\?[^"]*)?"[^>]*>[^<]*</iframe>#Uis',
            '<a target="_blank" href="https://www.youtube.com/watch?v=$1"><img src="https://img.youtube.com/vi/$1/0.jpg"/></a>',
            $text
        );

        $text = preg_replace(
            '#{youtube}[^{]+v=([^{&]+)(&[^{]*)?{/youtube}#Uis',
            '<a target="_blank" href="https://www.youtube.com/watch?v=$1"><img src="https://img.youtube.com/vi/$1/0.jpg"/></a>',
            $text
        );

        $text = preg_replace('#{vimeo}(https://vimeo.com/[^{]+){/vimeo}#Uis', '<iframe src="$1"></iframe>', $text);
        $text = preg_replace('#{vimeo}([^{]+){/vimeo}#Uis', '<iframe src="https://player.vimeo.com/video/$1"></iframe>', $text);

        // Vimeo iframes
        if (preg_match_all('#<iframe[^>]*src="[^"]*vimeo[^"]*/(\d+)([&/\?][^"]*)?"[^>]*>[^<]*</iframe>#Uis', $text, $matches)) {
            foreach ($matches[1] as $key => $match) {
                $hash = acym_fileGetContent('https://vimeo.com/api/v2/video/'.$match.'.php');
                // @ on purpose, the unserialize returns false if $hash isn't a serialized data, but it also throws a notice...
                $hash = @unserialize($hash);
                if (empty($hash)) continue;

                if (strpos($matches[0][$key], ' width="') !== false) {
                    $extension = substr($hash[0]['thumbnail_large'], strrpos($hash[0]['thumbnail_large'], '.'));
                    preg_match('#width="([^"]*)"#Uis', $matches[0][$key], $width);

                    $replace = strpos($hash[0]['thumbnail_large'], '_') === false ? '.' : '_';
                    $hash[0]['thumbnail_large'] = substr($hash[0]['thumbnail_large'], 0, strrpos($hash[0]['thumbnail_large'], $replace)).'_'.$width[1].$extension;
                }
                $thumbnail = 'https://i.vimeocdn.com/filter/overlay?src='.urlencode($hash[0]['thumbnail_large']);
                $thumbnail .= '&src='.urlencode('https://f.vimeocdn.com/p/images/crawler_play.png');

                $text = str_replace(
                    $matches[0][$key],
                    '<a target="_blank" href="'.acym_escape($hash[0]['url']).'"><img class="donotresize" alt="" src="'.acym_escape($thumbnail).'" /></a>',
                    $text
                );
            }
        }

        //Other videos
        $text = preg_replace('#\[embed=videolink][^}]*video":"([^"]*)[^}]*}\[/embed]#i', '<a target="_blank" href="$1"><img src="'.ACYM_IMAGES.'/video.png"/></a>', $text);
        $text = preg_replace('#<video[^>]*src="([^"]*)"[^>]*>[^>]*</video>#i', '<a target="_blank" href="$1"><img src="'.ACYM_IMAGES.'/video.png"/></a>', $text);
    }

    /**
     * Convert pictures base64 code into a real picture
     */
    private function convertbase64pictures(string &$html): void
    {
        if (!preg_match_all('#<img[^>]*src=("data:image/([^;]{1,5});base64[^"]*")([^>]*)>#Uis', $html, $resultspictures)) {
            return;
        }

        //Just in case of... we will need it


        $dest = ACYM_MEDIA.'resized'.DS;
        acym_createDir($dest);
        foreach ($resultspictures[2] as $i => $extension) {
            $pictname = md5($resultspictures[1][$i]).'.'.$extension;
            $picturl = ACYM_LIVE.str_replace(DS, '/', ACYM_MEDIA_FOLDER).'resized/'.$pictname;
            $pictPath = $dest.$pictname;
            $pictCode = trim($resultspictures[1][$i], '"');
            if (file_exists($pictPath)) {
                //The picture is already there... lets use it then
                $html = str_replace($pictCode, $picturl, $html);
                continue;
            }

            if (!ini_get('allow_url_fopen')) {
                continue;
            }

            $getfunction = '';
            switch ($extension) {
                case 'gif':
                    $getfunction = 'ImageCreateFromGIF';
                    break;
                case 'jpg':
                case 'jpeg':
                    $getfunction = 'ImageCreateFromJPEG';
                    break;
                case 'png':
                    $getfunction = 'ImageCreateFromPNG';
                    break;
            }

            //The function does not exists or we didn't find the right function... we just skip that action
            if (empty($getfunction) || !function_exists($getfunction)) {
                continue;
            }

            $img = $getfunction($pictCode);
            if ($img === false) {
                continue;
            }

            if (in_array($extension, ['gif', 'png'])) {
                imagealphablending($img, false);
                imagesavealpha($img, false);
            }

            //We display the result and then save it to avoid the issue with FTP configurations
            ob_start();
            switch ($extension) {
                case 'gif':
                    $status = imagegif($img);
                    break;
                case 'jpg':
                case 'jpeg':
                    $status = imagejpeg($img, null, 100);
                    break;
                case 'png':
                    $status = imagepng($img, null, 1);
                    break;
            }
            $imageContent = ob_get_clean();
            $status = $status && acym_writeFile($pictPath, $imageContent);

            //we could not save or convert the picture, we skip it
            if (!$status) {
                continue;
            }
            $html = str_replace($pictCode, $picturl, $html);
        }
    }

    //This function will remove or replace code we should not have in an html view.
    public function cleanHtml(string &$html): void
    {
        $this->convertbase64pictures($html);

        //add line-height: 0px; in the TR style when there is only images in the cell, not extra text (new issue with gmail)
        //handle a line-height automatically for <tr> <td> <img...></td></tr>
        $pregreplace = [];
        $pregreplace['#<tr([^>"]*>([^<]*<td[^>]*>[ \n\s]*<img[^>]*>[ \n\s]*</ *td[^>]*>[ \n\s]*)*</ *tr)#Uis'] = '<tr style="line-height: 0px;" $1';
        $pregreplace['#<td(((?!style|>).)*>[ \n\s]*(<a[^>]*>)?[ \n\s]*<img[^>]*>[ \n\s]*(</a[^>]*>)?[ \n\s]*</ *td)#Uis'] = '<td style="line-height: 0px;" $1';

        //No number tab system {tab=...}{/tabs} or {tab }{/tabs} and jcomments system
        $pregreplace['#{tab[ =][^}]*}#is'] = '';
        $pregreplace['#{/tabs}#is'] = '';
        $pregreplace['#{jcomments\s+(on|off|lock)}#is'] = '';

        //Remove the JS...
        $pregreplace["#(onmouseout|onmouseover|onclick|onfocus|onload|onblur) *= *\"(?:(?!\").)*\"#Ui"] = '';
        $pregreplace["#< *script(?:(?!< */ *script *>).)*< */ *script *>#Uis"] = '';
        $pregreplace["#< *iframe(?:(?!< */ *iframe *>).)*< */ *iframe *>#Uis"] = '';

        // May God punish every person using Outlook, or imposing this monstrosity for his employees
        $pregreplace['#(<p style=")([^>]*>\s*<img *[^>]*margin-left: auto; margin-right: auto;[^>]*>\s*</p>)#Uis'] = '$1text-align: center;$2';
        // Outlook doesn't handle webp images
        $pregreplace['#(<img [^>]*src="[^"]+\.webp"[^>]*>)#Uis'] = '<!--[if !mso]><!-->$1<!--<![endif]-->';

        $newbody = preg_replace(array_keys($pregreplace), $pregreplace, $html);
        //we do it in two steps as this regex can break the page
        if (!empty($newbody)) {
            $html = $newbody;
        }

        $body = preg_replace_callback('/src="([^"]* [^"]*)"/Ui', [$this, 'convertSpaces'], $html);
        if (!empty($body)) $html = $body;

        $html = acym_cmsCleanHtml($html);
    }

    public function convertSpaces(array $matches): string
    {
        return "src='".str_replace(' ', '%20', $matches[1])."'";
    }

    /*
     * Replace tags in all the email variables where it is possible, in text version or html version
     */
    public function replaceTags(object &$email, array $tags, bool $html = false): void
    {
        if (empty($tags)) return;

        $htmlVars = ['body'];
        $textVars = [
            'subject',
            'AltBody',
            'From',
            'FromName',
            'ReplyTo',
            'ReplyName',
            'bcc',
            'cc',
            'fromname',
            'fromemail',
            'replyname',
            'replyemail',
            'params',
            'preheader',
        ];

        $variables = array_merge($htmlVars, $textVars);

        if ($html) {
            if (empty($this->mailerHelper)) {
                $this->mailerHelper = new MailerHelper();
            }

            $textreplace = [];
            foreach ($tags as $i => $replacement) {
                if (isset($textreplace[$i])) continue;
                $textreplace[$i] = $this->mailerHelper->textVersion($replacement);
            }
        } else {
            $textreplace = $tags;
        }

        foreach ($variables as $var) {
            if (empty($email->$var)) continue;
            $email->$var = $this->replaceDText($email->$var, in_array($var, $htmlVars) ? $tags : $textreplace);
        }
    }

    public function replaceDText($text, $replacement)
    {
        if (is_array($text)) {
            foreach ($text as &$oneCell) {
                if (empty($oneCell)) continue;
                $oneCell = $this->replaceDText($oneCell, $replacement);
            }
        } elseif (is_string($text) && !empty($text)) {
            foreach ($replacement as $code => $value) {
                $codes = [$code, urlencode($code)];
                if (is_null($value)) {
                    $value = '';
                }
                $safePregValue = str_replace('$', '\$', $value);

                foreach ($codes as $oneCode) {
                    // Dtext specific syntax
                    $text = preg_replace(
                        '#<span[^>]+'.preg_quote($oneCode, '#').'.+</em>[^<]*</span>#Uis',
                        $safePregValue,
                        $text
                    );

                    // Dcontent specific syntax
                    $text = preg_replace(
                        '#(<tr[^>]+)data-dynamic="'.preg_quote($oneCode, '#').'"([^>]+>[^<]*<td[^>]*>).+</i>[^<]*</td>[^<]*</tr>#Uis',
                        '${1}${2}'.$safePregValue.'</td></tr>',
                        $text
                    );

                    // If the code was inserted directly in the email (in the subject for example, or a copy-paste)
                    $text = str_replace($oneCode, $value, $text);
                }
            }
        }

        return $text;
    }

    /**
     * This function extracts tags from the mail by checking the subject,body and altbody and returns an array of tag objects
     * tagfamily is "vmproduct" for example to handle tags such as {vmproduct:23|file|price}
     */
    public function extractTags(object $email, string $tagfamily): array
    {
        $results = [];

        $match = '#(?:{|%7B)'.$tagfamily.'(?:%3A|\\:)(.*)(?:}|%7D)#Ui';
        //If you add a variable there, don't forget to add it in the replaceTags function as well!
        $variables = [
            'subject',
            'AltBody',
            'body',
            'From',
            'FromName',
            'ReplyTo',
            'ReplyName',
            'bcc',
            'cc',
            'fromname',
            'fromemail',
            'replyname',
            'replyemail',
            'params',
            'preheader',
        ];
        $found = false;
        foreach ($variables as $var) {
            if (empty($email->$var)) continue;

            if (is_array($email->$var)) {
                foreach ($email->$var as $i => $arrayField) {
                    if (empty($arrayField)) continue;

                    if (is_array($arrayField)) {
                        foreach ($arrayField as $a => $oneval) {
                            $found = preg_match_all($match, $oneval, $results[$var.$i.'-'.$a]) || $found;
                            if (empty($results[$var.$i.'-'.$a][0])) unset($results[$var.$i.'-'.$a]);
                        }
                    } else {
                        $found = preg_match_all($match, $arrayField, $results[$var.$i]) || $found;
                        if (empty($results[$var.$i][0])) unset($results[$var.$i]);
                    }
                }
            } else {
                $found = preg_match_all($match, $email->$var, $results[$var]) || $found;
                //we unset the results so that we won't handle it later... it will save some memory and processing
                if (empty($results[$var][0])) unset($results[$var]);
            }
        }

        //If we didn't find anything...
        if (!$found) {
            return [];
        }

        $tags = [];
        foreach ($results as $var => $allresults) {
            foreach ($allresults[0] as $i => $oneTag) {
                //Don't need to process twice a tag we already have!
                if (isset($tags[$oneTag])) {
                    continue;
                }
                $tags[$oneTag] = $this->extractTag($allresults[1][$i]);
            }
        }

        return $tags;
    }

    /**
     * Converts a tag 23|file:myfile|price into an object.
     */
    public function extractTag(string $oneTag): \stdClass
    {
        $oneTag = str_replace(['[time]+', '[time]-'], [urlencode('[time]+'), urlencode('[time]-')], $oneTag);
        $arguments = explode('|', acym_stripTags(urldecode($oneTag)));
        $tag = new \stdClass();
        $tag->id = $arguments[0];
        $tag->default = '';
        for ($i = 1, $a = count($arguments); $i < $a; $i++) {
            $args = explode(':', $arguments[$i], 2);
            $arg0 = trim($args[0]);
            if (empty($arg0)) continue;

            if (isset($args[1])) {
                $tag->$arg0 = $args[1];
                //We may have an extra parameter, especially for date format.
                if (isset($args[2])) {
                    $tag->{$args[0]} .= ':'.$args[2];
                }
            } else {
                $tag->$arg0 = true;
            }
        }

        return $tag;
    }

    /**
     * Wrap the text using the wrapValue
     */
    public function wrapText(string $text, object $tag): string
    {
        if (empty($tag->wrap)) {
            return $text;
        }

        $tag->wrap = intval($tag->wrap);

        $newText = str_replace(['&lt', '&gt'], ['<', '>'], $text);

        if (mb_strlen(acym_stripTags($newText)) <= $tag->wrap) {
            return $newText;
        }

        if (!class_exists('DOMDocument', false)) {
            return mb_substr(acym_stripTags($newText), 0, $tag->wrap).'…';
        }

        if (function_exists('libxml_use_internal_errors')) {
            libxml_use_internal_errors(true);
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML(
            '<?xml encoding="UTF-8">'.$newText,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $count = 0;
        $this->truncateNode($dom, $dom, $tag->wrap, $count);

        return $dom->saveHTML();
    }

    private function truncateNode(\DOMDocument $dom, \DOMNode $node, int $limit, int &$count): bool
    {
        for ($i = 0; $i < $node->childNodes->length; $i++) {
            $child = $node->childNodes->item($i);

            // Text node
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = $child->nodeValue;
                $len = mb_strlen($text);

                if ($count + $len > $limit) {
                    $remaining = $limit - $count;

                    // Take the max allowed slice
                    $slice = mb_substr($text, 0, $remaining);

                    // Try to cut at a word boundary (spaces or punctuation)
                    if (preg_match('/^(.+?)([\s\p{P}]+)[^\s\p{P}]*$/u', $slice, $m)) {
                        $slice = rtrim($m[1]);
                    }

                    // Final assignment
                    $child->nodeValue = $slice.'…';

                    // Remove remaining siblings
                    while ($child->nextSibling) {
                        $node->removeChild($child->nextSibling);
                    }

                    return true;
                }

                $count += $len;
            }

            // Element node → recurse
            if ($child->hasChildNodes() && $this->truncateNode($dom, $child, $limit, $count)) {
                while ($child->nextSibling) {
                    $node->removeChild($child->nextSibling);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Returns a formatted version of the passed content
     *
     * TOP_LEFT : title on top, image with float left and covering description
     * TOP_RIGHT : title on top, image with float rigth and covering description
     * TITLE_IMG : image on title's left on top, description below
     * TITLE_IMG_RIGHT : image on title's right on top, description below
     * CENTER_IMG : title on top, image on one line and description below
     * TOP_IMG : image on top, then title and description
     * COL_LEFT : image on left column, title and description on right column
     * COL_RIGHT : image on right column, title and description on left column
     */
    public function getStandardDisplay(object $format): string
    {
        // By default, put float left on the picture
        if (empty($format->tag->format)) {
            $format->tag->format = 'TOP_LEFT';
        }
        if (!in_array($format->tag->format, ['TOP_LEFT', 'TOP_RIGHT', 'TITLE_IMG', 'TITLE_IMG_RIGHT', 'CENTER_IMG', 'TOP_IMG', 'COL_LEFT', 'COL_RIGHT'])) {
            $format->tag->format = 'TOP_LEFT';
        }

        $invertValues = [
            'TOP_LEFT' => 'TOP_RIGHT',
            'TITLE_IMG' => 'TITLE_IMG_RIGHT',
            'COL_LEFT' => 'COL_RIGHT',
            'TOP_RIGHT' => 'TOP_LEFT',
            'TITLE_IMG_RIGHT' => 'TITLE_IMG',
            'COL_RIGHT' => 'COL_LEFT',
        ];
        if (!empty($format->tag->invert) && !empty($invertValues[$format->tag->format])) {
            $format->tag->format = $invertValues[$format->tag->format];
        }

        // Get the image with float left/right or no style
        $image = '';
        if (!empty($format->imagePath)) {
            $style = '';
            $linkStyle = '';

            if (in_array($format->tag->format, ['TOP_LEFT', 'TITLE_IMG'])) {
                $style = 'left';
            } elseif (in_array($format->tag->format, ['TOP_RIGHT', 'TITLE_IMG_RIGHT'])) {
                $style = 'right';
            }

            if (!empty($style)) {
                $linkStyle = 'style="float:'.$style.';"';

                if ($style === 'left') {
                    $style = 'style="float:left; margin-right: 7px; margin-bottom: 7px;"';
                } else {
                    $style = 'style="float:right; margin-left: 7px; margin-bottom: 7px;"';
                }
            }

            preg_match('#src="([^"]+)"#Uis', $format->imagePath, $matches);
            if (!empty($matches[1])) $format->imagePath = $matches[1];
            $altImage = !empty($format->altImage) ? $format->altImage : '';
            $image = '<img class="content_main_image" alt="'.acym_escape($altImage).'" src="'.$format->imagePath.'" '.$style.' />';

            if (!empty($format->imageCaption) && !in_array($format->tag->format, ['TITLE_IMG', 'TITLE_IMG_RIGHT'])) {
                $image .= '<p class="content_main_image_caption">'.acym_escapeHtml($format->imageCaption).'</p>';
            }
        }

        // If TITLE_IMG, add the image to the title
        $result = '';
        if (in_array($format->tag->format, ['TITLE_IMG', 'TITLE_IMG_RIGHT'])) {
            $format->title = $image.$format->title;
            $image = '';
        }

        if (!empty($format->link) && !empty($image) && !empty($format->tag->clickableimg)) {
            $image = '<a target="_blank" href="'.$format->link.'" '.$linkStyle.'>'.$image.'</a>';
        }

        // If the image should be displayed before the title, do it
        if ($format->tag->format === 'TOP_IMG' && !empty($image)) {
            $result = $image;
            $image = '';
        }

        // If we want to keep the left/right column for the image, put the whole article in a table
        if (in_array($format->tag->format, ['COL_LEFT', 'COL_RIGHT'])) {
            $maxWidth = empty($format->tag->maxwidth) ? '' : ' width: '.$format->tag->maxwidth.'px;';
            if (empty($image)) {
                $format->tag->format = 'TOP_LEFT';
            } else {
                $result = '<table><tr><td valign="middle" style="vertical-align: middle; padding-right: 7px;" class="acyleftcol">';
                if ($format->tag->format === 'COL_LEFT') {
                    $result = '<table><tr><td valign="middle" style="vertical-align: middle; padding-right: 7px; '.$maxWidth.'" class="acyleftcol">';
                    $result .= $image.'</td><td valign="top" class="acyrightcol">';
                }
            }
        }

        // Display the title
        if (!empty($format->title)) {
            if (!empty($format->link) && !empty($format->tag->clickable)) {
                if (empty($format->tag->type) || $format->tag->type !== 'title') {
                    $format->title = '<h2 class="acym_title">'.$format->title.'</h2>';
                }

                $title = '<a';
                if (!empty($format->tag->type) && $format->tag->type === 'title') $title .= ' class="acym_title"';
                $title .= ' href="'.$format->link.'" target="_blank" name="'.$this->name.'-'.$format->tag->id.'">';
                $title .= $format->title;
                $title .= '</a>';
                $format->title = $title;
            } else {
                if (empty($format->tag->type) || $format->tag->type != 'title') {
                    $format->title = '<h2 class="acym_title">'.$format->title.'</h2>';
                }
            }

            $result .= $format->title;
        }

        if (!empty($format->afterTitle)) {
            $result .= $format->afterTitle;
        }

        if (!empty($format->description)) {
            $format->description = $this->wrapText($format->description, $format->tag);
        }
        $this->wrappedText = $format->description;


        $rowText = '<div class="acydescription">';
        $endRow = '</div><br />';
        // Display the main content based on the chosen format
        if (in_array($format->tag->format, ['TOP_LEFT', 'TOP_RIGHT', 'TITLE_IMG', 'TITLE_IMG_RIGHT', 'TOP_IMG'])) {
            if (!empty($image) || !empty($format->description)) {
                $result .= $rowText.$image.$format->description.$endRow;
            }
        } elseif ($format->tag->format == 'CENTER_IMG') {
            if (!empty($image)) {
                $result .= '<div class="acymainimage">'.$image.$endRow;
            }

            if (!empty($format->description)) {
                $result .= $rowText.$format->description.$endRow;
            }
        } elseif (in_array($format->tag->format, ['COL_LEFT', 'COL_RIGHT'])) {
            if (!empty($format->description)) {
                $result .= $rowText.$format->description.$endRow;
            }

            if ($format->tag->format === 'COL_RIGHT') {
                $result .= '</td><td valign="middle" style="vertical-align: middle; padding-left: 7px; '.$maxWidth.'" class="acyrightcol">'.$image;
            }
            $result .= '</td></tr></table>';
        }

        // Add the custom fields on 1 or 2 columns, with or without the labels
        if (!empty($format->customFields)) {
            $result .= '<table style="width:100%;" class="customfieldsarea"><tr>';

            $format->cols = empty($format->tag->nbcols) ? 1 : intval($format->tag->nbcols);
            if (empty($format->cols)) $format->cols = 1;

            $i = 0;
            foreach ($format->customFields as $oneField) {
                if ($i != 0 && $i % $format->cols == 0) $result .= '</tr><tr>';

                if (empty($oneField[1])) {
                    $result .= '<td class="cfvalue" colspan="2">';
                } else {
                    $result .= '<td nowrap="nowrap" class="cflabel">'.$oneField[1].'</td><td class="cfvalue">';
                }

                $result .= $oneField[0].'</td>';
                $i++;
            }

            while ($i % $format->cols != 0) {
                $result .= '<td colspan="2"></td>';
                $i++;
            }

            $result .= '</tr></table>';
        }

        // Add what should be displayed at the end (share buttons, read more link, etc...)
        if (!empty($format->afterArticle)) {
            $result .= $format->afterArticle;
        }

        return $result;
    }

    /**
     * Resizes or removes the pictures according to the parameters
     */
    public function managePicts(object $tag, string $result): string
    {
        if (!isset($tag->pict)) {
            return $result;
        }

        $imageHelper = new ImageHelper();
        if ($tag->pict === 'resized') {
            $imageHelper->maxHeight = empty($tag->maxheight) ? 150 : $tag->maxheight;
            $imageHelper->maxWidth = empty($tag->maxwidth) ? 150 : $tag->maxwidth;
            if ($imageHelper->available()) {
                $result = $imageHelper->resizePictures($result);
            } elseif (acym_isAdmin()) {
                acym_enqueueMessage($imageHelper->error, 'notice');
            }
        } elseif ($tag->pict == '0') {
            $result = $imageHelper->removePictures($result);
        }

        return acym_absoluteURL($result);
    }

    public function displayOptions(array $options, string $dynamicIdentifier, string $type = 'individual', $defaultValues = null): void
    {
        $suffix = preg_replace('#[^a-zA-Z0-9]#U', '_', $dynamicIdentifier);
        $updateFunction = 'updateDynamic'.$suffix;

        $jsOptionsMerge = [];
        $topOptions = [];
        $sections = [];

        // ---- Phase 1: classify options + collect the JS (in original loop order) ----
        foreach ($options as $option) {
            if (isset($defaultValues->{$option['name']})) {
                $option['default'] = $defaultValues->{$option['name']};
            }

            // Preserve the early skip: never rendered, no JS collected.
            if (
                $option['type'] === 'boolean'
                && $option['name'] === 'autologin'
                && $this->config->get('autologin_urls', 0) != 1
            ) {
                continue;
            }

            $this->appendOptionJs($jsOptionsMerge, $option, $suffix, $type);

            $isTop = !empty($option['main']) || in_array($option['type'], ['pictures', 'checkbox'], true);

            if ($isTop) {
                $topOptions[$option['title']] = ['kind' => 'field', 'option' => $option];

                // The format block is attached right after the "display" checkbox.
                if (
                    $option['type'] === 'checkbox'
                    && $option['title'] === 'ACYM_DISPLAY'
                    && (!isset($option['format']) || $option['format'])
                ) {
                    $topOptions['ACYM_FORMAT'] = ['kind' => 'format', 'option' => $option];
                }
                continue;
            }

            $section = empty($option['section']) ? 'ACYM_OTHER_OPTIONS' : $option['section'];
            $sections[$section][$option['name']] = $option;
        }

        // "Other Options" is always rendered last.
        if (isset($sections['ACYM_OTHER_OPTIONS'])) {
            $otherOptions = $sections['ACYM_OTHER_OPTIONS'];
            unset($sections['ACYM_OTHER_OPTIONS']);
            $sections['ACYM_OTHER_OPTIONS'] = $otherOptions;
        }

        // ---- Phase 2: render (echo directly, in the original visual order) ----
        foreach ($topOptions as $headerLabel => $entry) {
            $this->displayPanelHeader($headerLabel);
            echo '<div class="acym__wysid__right__toolbar__design--show acym__wysid__right__toolbar__design acym__wysid__context__modal__container grid-x">';
            if ($entry['kind'] === 'format') {
                $this->displayFormatPanel($suffix, $updateFunction, $type, $defaultValues);
            } else {
                $this->displayOptionField($entry['option'], $suffix, $updateFunction, $defaultValues);
            }
            echo '</div>';
        }

        foreach ($sections as $sectionName => $sectionOptions) {
            $this->displayPanelHeader($sectionName);
            echo '<div class="acym__wysid__right__toolbar__design--show acym__wysid__right__toolbar__design acym__wysid__context__modal__container grid-x">';
            foreach ($sectionOptions as $option) {
                echo '<div class="cell grid-x margin-bottom-1">';
                $this->displayFieldLabel($option, $suffix);
                echo '<div class="cell large-7">';
                $this->displayOptionField($option, $suffix, $updateFunction, $defaultValues);
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
        }

        // ---- Phase 3: the inline script + individual extras ----
        $this->displayDynamicScript($jsOptionsMerge, $suffix, $updateFunction, $dynamicIdentifier, $type, $defaultValues);

        if ($type === 'individual') {
            acym_trigger('displayCustomViewEditor', [], 'plgAcym'.ucfirst($dynamicIdentifier));
            echo '<input type="hidden" id="acym__dynamic__update__function" value="'.acym_escape($updateFunction).'">';
        }
    }

    private function displayPanelHeader(string $labelKey): void
    {
        echo '<p class="acym__wysid__right__toolbar__p acym__wysid__right__toolbar__p__open acym__title">';
        echo acym_escapeHtml(acym_translation($labelKey));
        echo '<i class="acymicon-keyboard-arrow-up"></i>';
        echo '</p>';
    }

    private function displayFieldLabel(array $option, string $suffix): void
    {
        echo '<label class="cell large-5 acym_plugin_field acym_plugin_field_'.acym_escape($option['type']).'" for="'.acym_escape($option['name'].$suffix).'">';
        echo acym_escapeHtml(acym_translation($option['title']));
        if (!empty($option['tooltip'])) {
            echo '&nbsp;';
            acym_info(
                [
                    'textShownInTooltip' => $option['tooltip'],
                    'classIcon' => 'acym_plugin_field_'.$option['name'],
                ]
            );
        }
        echo '</label>';
    }

    private function displayOptionField(array $option, string $suffix, string $updateFunction, $defaultValues): void
    {
        switch ($option['type']) {
            case 'pictures':
                $this->displayPicturesField($option, $suffix, $updateFunction, $defaultValues);
                break;
            case 'checkbox':
                $this->displayCheckboxField($option, $suffix, $updateFunction);
                break;
            case 'boolean':
                acym_boolean($option['name'].$suffix, $option['default'], $option['name'].$suffix, ['onclick' => $updateFunction.'();']);
                break;
            case 'radio':
                $this->displayRadioField($option, $suffix, $updateFunction);
                break;
            case 'select':
                $this->displaySelectField($option, $suffix, $updateFunction);
                break;
            case 'multiselect':
                $this->displayMultiselectField($option, $suffix, $updateFunction);
                break;
            case 'text':
                $default = $option['default'] ?? '';
                $class = empty($option['class']) ? 'acym_plugin_text_field' : $option['class'];
                echo '<input type="text" 
                            name="'.acym_escape($option['name'].$suffix).'" 
                            id="'.acym_escape($option['name'].$suffix).'" 
                            onchange="'.acym_escape($updateFunction).'();" 
                            value="'.acym_escape($default).'" 
                            class="'.acym_escape($class).'"';
                if (!empty($option['placeholder'])) {
                    echo ' placeholder="'.acym_escape($option['placeholder']).'"';
                }
                echo '/>';
                break;
            case 'number':
                $class = empty($option['class']) ? 'acym_plugin_text_field' : $option['class'];
                echo '<input type="number" 
                            min="'.acym_escape(empty($option['min']) ? '0' : $option['min']).'" 
                            id="'.acym_escape($option['name'].$suffix).'" 
                            onchange="'.acym_escape($updateFunction).'();" 
                            value="'.intval($option['default']).'" 
                            name="'.acym_escape($option['name'].$suffix).'" 
                            class="'.acym_escape($class).'" ';
                if (!empty($option['max'])) {
                    echo ' max="'.acym_escape($option['max']).'"';
                }
                echo ' />';
                break;
            case 'intextfield':
                $inputType = (!empty($option['isNumber']) && $option['isNumber'] === 1) ? 'number' : 'text';
                echo acym_escapeHtmlWithAllowedTags(
                    acym_translationSprintf(
                        $option['text'],
                        '<input type="'.acym_escape($inputType).'"
                            name="'.acym_escape($option['name'].$suffix).'"
                            id="'.acym_escape($option['name'].$suffix).'"
                            class="intext_input"
                            value="'.acym_escape($option['default']).'"
                            onchange="'.acym_escape($updateFunction).'();"/>'
                    ),
                    [
                        'input' => [
                            'type' => true,
                            'name' => true,
                            'id' => true,
                            'class' => true,
                            'value' => true,
                            'onchange' => true,
                        ],
                    ]
                );
                break;
            case 'date':
                $relativeTime = empty($option['relativeDate']) ? '-' : $option['relativeDate'];
                $dateDefault = $option['default'];
                if (!empty($dateDefault) && !is_numeric($dateDefault) && strpos($dateDefault, '[time]') === false) {
                    $dateDefault = strtotime($dateDefault);
                }
                echo acym_escapeHtmlWithAllowedTags(
                    acym_dateField($option['name'].$suffix, $dateDefault, '', ' onchange="'.acym_escape($updateFunction).'();"', $relativeTime),
                    SecurityHelper::ALLOWED_HTML_DATE
                );
                break;
            case 'language':
                $this->displayLanguageField($option, $suffix, $updateFunction);
                break;
            case 'custom':
                echo $option['output'];
                break;
        }
    }

    private function displayPicturesField(array $option, string $suffix, string $updateFunction, $defaultValues): void
    {
        $displayedPictures = $option['default'] ?? 'resized';
        if (isset($defaultValues->pict)) {
            $displayedPictures = $defaultValues->pict;
        }
        $maxWidth = $defaultValues->maxwidth ?? 150;
        $maxHeight = $defaultValues->maxheight ?? 150;

        $valImages = [
            acym_selectOption('1', 'ACYM_YES'),
            acym_selectOption('resized', 'ACYM_RESIZED'),
            acym_selectOption('0', 'ACYM_NO'),
        ];

        echo '<div class="cell large-5 acym_plugin_field">';
        echo acym_escapeHtml(acym_translation('ACYM_DISPLAY'));
        echo '</div>';
        echo '<div class="cell large-7">';
        acym_radio(
            $valImages,
            'pict'.$suffix,
            $displayedPictures,
            ['onclick' => $updateFunction.'();'],
            ['containerClass' => 'dcontent_pictures'],
            !acym_isAdmin()
        );
        echo '</div>';

        echo '<div id="pictsize'.acym_escape($suffix).'" class="cell grid-x margin-y margin-top-1"';
        if ('resized' !== $displayedPictures) {
            echo ' style="display: none;"';
        }
        echo '>';
        echo '<div class="cell large-5 acym_plugin_field">';
        echo acym_escapeHtml(acym_translation('ACYM_MAX_WIDTH'));
        echo '</div>';
        echo '<div class="cell large-7">';
        echo '<input class="intext_input" 
                    name="pictwidth'.acym_escape($suffix).'" 
                    type="number" 
                    onchange="'.acym_escape($updateFunction).'();" 
                    value="'.intval($maxWidth).'"/>';
        echo '</div>';
        echo '<div class="cell large-5 acym_plugin_field">';
        echo acym_escapeHtml(acym_translation('ACYM_MAX_HEIGHT'));
        echo '</div>';
        echo '<div class="cell large-7">';
        echo '<input class="intext_input" 
                    name="pictheight'.acym_escape($suffix).'" 
                    type="number" 
                    onchange="'.acym_escape($updateFunction).'();" 
                    value="'.intval($maxHeight).'"/>';
        echo '</div>';
        echo '</div>';

        if (!empty($option['caption'])) {
            echo '<div class="cell grid-x margin-top-1">';
            echo '<label class="cell large-5 acym_plugin_field">';
            echo acym_escapeHtml(acym_translation('ACYM_CAPTION'));
            echo '</label>';
            acym_radio(
                [
                    acym_selectOption('1', 'ACYM_YES'),
                    acym_selectOption('0', 'ACYM_NO'),
                ],
                'caption'.$suffix,
                $defaultValues->caption ?? '0',
                ['onclick' => $updateFunction.'();'],
                ['containerClass' => 'cell large-7']
            );
            echo '</div>';
        }
    }

    private function displayCheckboxField(array $option, string $suffix, string $updateFunction): void
    {
        if (!empty($option['default'])) {
            $checkedValues = explode(',', $option['default']);
            foreach ($option['options'] as $key => $oneOption) {
                $oneOption[1] = in_array($key, $checkedValues);
                $option['options'][$key] = $oneOption;
            }
        }

        echo '<div class="cell grid-x">';
        foreach ($option['options'] as $value => $title) {
            echo '<div class="cell medium-6" ';
            if (!empty($title[2]) && is_array($title[2])) {
                foreach ($title[2] as $attribute => $value) {
                    echo acym_escape($attribute).'="'.acym_escape($value).'"';
                }
            }
            echo '>';
            echo '<input type="checkbox" 
                        name="'.acym_escape($option['name'].$suffix).'" 
                        value="'.acym_escape($value).'" 
                        id="'.acym_escape($value.$suffix).'" 
                        onclick="'.acym_escape($updateFunction).'();" ';
            acym_checked((bool)$title[1]);
            echo ' />';
            echo '<label style="margin-left:5px" for="'.acym_escape($value.$suffix).'">';
            echo acym_escapeHtml(acym_translation($title[0]));
            echo '</label>';
            echo '</div>';
        }
        echo '</div>';
    }

    private function displayRadioField(array $option, string $suffix, string $updateFunction): void
    {
        $radioOptions = [];
        foreach ($option['options'] as $value => $title) {
            $radioOptions[] = acym_selectOption($value, $title);
        }
        acym_radio(
            $radioOptions,
            $option['name'].$suffix,
            $option['default'],
            ['onclick' => $updateFunction.'();'],
            ['pluginMode' => true],
            !acym_isAdmin()
        );
    }

    private function displaySelectField(array $option, string $suffix, string $updateFunction): void
    {
        $selectOptions = [];
        foreach ($option['options'] as $value => $title) {
            if (is_object($title)) {
                $selectOptions[] = acym_selectOption($title->value, $title->text);
            } else {
                $selectOptions[] = acym_selectOption($value, $title);
            }
        }

        $default = empty($option['default']) ? null : $option['default'];
        $defaultOrder = null;
        if (!empty($default) && strpos($default, ',')) {
            [$default, $defaultOrder] = explode(',', $default);
        }

        $attributes = [
            'onchange' => $updateFunction.'();',
            'id' => $option['name'].$suffix,
        ];
        if ($option['name'] === 'order') {
            $attributes['class'] = 'acym__dynamics__ordering__select';
        }

        acym_select(
            $selectOptions,
            $option['name'].$suffix,
            $default,
            $attributes,
            'value',
            'text',
            null,
            false,
            true
        );

        if ($option['name'] === 'order') {
            $dirs = [
                'desc' => acym_translation('ACYM_DESC'),
                'asc' => acym_translation('ACYM_ASC'),
            ];
            if (empty($defaultOrder)) {
                $defaultOrder = empty($option['defaultdir']) ? null : $option['defaultdir'];
            }
            echo ' ';
            acym_select(
                $dirs,
                'orderdir'.$suffix,
                $defaultOrder,
                [
                    'onchange' => $updateFunction.'();',
                    'style' => 'width: 115px;',
                    'class' => 'acym__dynamics__ordering__select',
                ],
                'value',
                'text',
                null,
                false,
                true
            );
        }
    }

    private function displayMultiselectField(array $option, string $suffix, string $updateFunction): void
    {
        $selectOptions = [];
        foreach ($option['options'] as $value => $title) {
            $selectOptions[] = acym_selectOption($value, $title);
        }

        if (!isset($option['default'])) {
            $option['default'] = [];
        }
        if (!is_array($option['default'])) {
            $option['default'] = explode(',', $option['default']);
        }

        acym_selectMultiple(
            $selectOptions,
            $option['name'].$suffix,
            $option['default'],
            ['onchange' => $updateFunction.'();', 'id' => $option['name'].$suffix],
            'value',
            'text',
            true
        );
    }

    private function displayLanguageField(array $option, string $suffix, string $updateFunction): void
    {
        $languageOptions = ['any' => acym_translation('ACYM_ANY')];
        foreach (acym_getLanguages(true) as $language) {
            $languageOptions[$language->language] = $language->name;
        }

        if (empty($option['default'])) {
            $option['default'] = acym_getVar('string', 'language');
            if (acym_isMultilingual() && (empty($option['default']) || $option['default'] === 'main')) {
                $option['default'] = $this->config->get('multilingual_default');
            }
        }

        acym_select(
            $languageOptions,
            $option['name'].$suffix,
            empty($option['default']) ? null : $option['default'],
            ['onchange' => $updateFunction.'();', 'id' => $option['name'].$suffix],
            'value',
            'text',
            null,
            false,
            true
        );
    }

    private function displayFormatPanel(string $suffix, string $updateFunction, string $type, $defaultValues): void
    {
        echo '<div class="grid-x">';
        echo '<div class="cell large-3">';
        echo acym_escapeHtml(acym_translation('ACYM_FORMAT'));
        echo '</div>';
        echo '<div class="cell large-9 dcontentFormatContainer">';

        $default = empty($defaultValues->format) ? 'TOP_LEFT' : $defaultValues->format;
        $formats = ['TOP_LEFT', 'TOP_RIGHT', 'TITLE_IMG', 'TITLE_IMG_RIGHT', 'CENTER_IMG', 'TOP_IMG', 'COL_LEFT', 'COL_RIGHT'];
        foreach ($formats as $oneFormat) {
            $class = 'button-radio';
            if ($default === $oneFormat) {
                $class .= ' button-radio-selected';
            }
            echo '<button class="'.acym_escape($class).'" 
                        acym-button-radio-group="dcontentFormat'.acym_escape($suffix).'" 
                        acym-data-type="'.acym_escape($oneFormat).'" 
                        acym-callback="'.acym_escape($updateFunction).'">';
            echo '<img alt="'.acym_escape($oneFormat).'" src="'.acym_escapeUrl(ACYM_IMAGES.'editor/dcontent_formats/'.strtolower($oneFormat).'.png').'"/>';
            echo '</button>';
        }
        echo '</div>';

        if ($type === 'grouped') {
            echo '<div class="cell large-3">';
            echo acym_escapeHtml(acym_translation('ACYM_ALTERNATE'));
            acym_info(['textShownInTooltip' => 'ACYM_ALTERNATE_DESC']);
            echo '</div>';
            echo '<div class="cell large-9">';
            acym_boolean(
                'alternate'.$suffix,
                !empty($defaultValues->alternate),
                'alternate'.$suffix,
                ['onclick' => $updateFunction.'();']
            );
            echo '</div>';
        }

        echo '</div>';
    }

    private function appendOptionJs(array &$jsOptionsMerge, array $option, string $suffix, string $type): void
    {
        switch ($option['type']) {
            case 'pictures':
                if (!empty($option['caption'])) {
                    $jsOptionsMerge[] = 'otherinfo += "| caption:" + jQuery(\'input[name="caption'.acym_escape($suffix).'"]:checked\').val();';
                }
                $jsOptionsMerge[] = '
                var _pictVal'.acym_escape($suffix).' = jQuery(\'input[name="pict'.acym_escape($suffix).'"]:checked\').val();
                otherinfo += "| pict:" + _pictVal'.acym_escape($suffix).';

                if(_pictVal'.acym_escape($suffix).' == "resized"){
                    jQuery("#pictsize'.acym_escape($suffix).'").show();
                    otherinfo += "| maxwidth:" + jQuery(\'input[name="pictwidth'.acym_escape($suffix).'"]\').val();
                    otherinfo += "| maxheight:" + jQuery(\'input[name="pictheight'.acym_escape($suffix).'"]\').val();
                }else{
                    jQuery("#pictsize'.acym_escape($suffix).'").hide();
                }';
                break;

            case 'checkbox':
                $separator = empty($option['separator']) ? ',' : $option['separator'];
                $jsOptionsMerge[] = 'var _checked'.acym_escape($option['name'].$suffix).' = [];
                jQuery("input:checkbox[name='.acym_escape($option['name'].$suffix).']:checked").each(function(){
                    _checked'.acym_escape($option['name'].$suffix).'.push(jQuery(this).val());
                });
                if(_checked'.acym_escape($option['name'].$suffix).'.length) otherinfo += "| '.acym_escape($option['name']).':" + _checked'.acym_escape(
                        $option['name'].$suffix
                    ).'.join("'.acym_escape($separator).'");';

                if ($option['title'] === 'ACYM_DISPLAY' && (!isset($option['format']) || $option['format'])) {
                    if ($type === 'grouped') {
                        $jsOptionsMerge[] = 'var alternate = jQuery(\'input[name="alternate'.acym_escape($suffix).'"]:checked\').val();';
                        $jsOptionsMerge[] = 'if (!acym_helper.empty(alternate)) otherinfo += "| alternate";';
                    }
                    $jsOptionsMerge[] = 'var selectedFormatOption = jQuery(\'.button-radio-selected[acym-button-radio-group="dcontentFormat'.acym_escape($suffix).'"]\')';
                    $jsOptionsMerge[] = 'if (!acym_helper.empty(selectedFormatOption)) otherinfo += "| format:" + selectedFormatOption.attr("acym-data-type");';
                }
                break;

            case 'boolean':
            case 'radio':
                $jsOptionsMerge[] = 'otherinfo += "| '.acym_escape($option['name']).':" + jQuery(\'input[name="'.acym_escape($option['name'].$suffix).'"]:checked\').val();';
                break;

            case 'select':
                if ($option['name'] === 'order') {
                    $jsOptionsMerge[] = 'otherinfo += "| '.acym_escape($option['name']).':" + jQuery(\'[name="'.acym_escape(
                            $option['name'].$suffix
                        ).'"]\').val() + "," + jQuery(\'[name="orderdir'.acym_escape($suffix).'"]\').val();';
                } else {
                    $jsOptionsMerge[] = 'otherinfo += "| '.acym_escape($option['name']).':" + jQuery(\'[name="'.acym_escape($option['name'].$suffix).'"]\').val();';
                }
                break;

            case 'language':
                $jsOptionsMerge[] = 'otherinfo += "| '.acym_escape($option['name']).':" + jQuery(\'[name="'.acym_escape($option['name'].$suffix).'"]\').val();';
                break;

            case 'multiselect':
                $jsOptionsMerge[] = '
                var theMultiSelect = document.querySelector(\'[name="'.acym_escape($option['name'].$suffix).'[]"]\');
                var selectedOptions = [];
                for(var i = 0 ; i < theMultiSelect.length ; i++){
                    if(theMultiSelect[i].selected){
                        selectedOptions.push(theMultiSelect[i].value);
                    }
                }
                otherinfo += "| '.acym_escape($option['name']).':" + selectedOptions.join(",");';
                break;

            case 'text':
            case 'number':
            case 'intextfield':
            case 'date':
                $jsOptionsMerge[] = 'otherinfo += "| '.acym_escape($option['name']).':" + jQuery(\'input[name="'.acym_escape($option['name'].$suffix).'"]\').val();';
                break;

            case 'custom':
                //TODO
                $jsOptionsMerge[] = $option['js'];
                break;
        }
    }

    private function displayDynamicScript(array $jsOptionsMerge, string $suffix, string $updateFunction, string $dynamicIdentifier, string $type, $defaultValues): void
    {
        $storageVar = 'window._additionalInfo'.$suffix;

        echo '<script type="text/javascript">';
        echo 'var _selectedRows'.acym_escape($suffix).' = [];';
        echo 'var _selectedRows = [];';
        echo acym_escape($storageVar).' = '.acym_escape($storageVar).' || {};';

        if (!empty($defaultValues->id) && (empty($defaultValues->defaultPluginTab) || $dynamicIdentifier === $defaultValues->defaultPluginTab)) {
            $delimiter = strpos($defaultValues->id, '-') ? '-' : ',';
            foreach (explode($delimiter, $defaultValues->id) as $value) {
                if (empty($value)) {
                    continue;
                }
                echo '_selectedRows'.acym_escape($suffix).'['.intval($value).'] = true;';
            }
        }

        echo 'function applyContent'.acym_escape($suffix).'(contentid, row){';
        echo 'if(_selectedRows'.acym_escape($suffix).'[contentid]){';
        echo 'jQuery(row).removeClass("selected_row");';
        echo 'delete _selectedRows'.acym_escape($suffix).'[contentid];';
        echo '}else{';
        if ($type === 'individual') {
            echo 'for(let elementKey in _selectedRows'.acym_escape($suffix).') {';
            echo 'if(!_selectedRows'.acym_escape($suffix).'.hasOwnProperty(elementKey)) continue;';
            echo 'jQuery(\'[data-id="\' + elementKey + \'"]\').removeClass("selected_row");';
            echo 'delete _selectedRows'.acym_escape($suffix).'[elementKey];';
            echo '}';
        }
        echo 'jQuery(row).addClass("selected_row");';
        echo '_selectedRows'.acym_escape($suffix).'[contentid] = true;';
        echo '}';
        echo acym_escape($updateFunction).'();';
        echo 'if(typeof _selectedRows !== "undefined"){ _selectedRows = _selectedRows'.acym_escape($suffix).'; }';
        echo '}';

        echo 'function '.acym_escape($updateFunction).'(){';
        echo 'var tag = ""; var otherinfo = "";';
        echo implode("\r\n\r\n", $jsOptionsMerge);
        echo 'for (let [index, info] of Object.entries('.acym_escape($storageVar).')){ otherinfo += "| "+index+":"+info; }';

        if ($type === 'individual') {
            echo 'for(var i in _selectedRows'.acym_escape($suffix).'){';
            echo 'if(!_selectedRows'.acym_escape($suffix).'.hasOwnProperty(i)) continue;';
            echo 'tag = tag + "{'.acym_escape($dynamicIdentifier).':" + i + otherinfo + "}";';
            echo '}';
        } elseif ($type === 'grouped') {
            echo 'tag = "{'.acym_escape($dynamicIdentifier).':";';
            echo 'for(var icat in _selectedRows'.acym_escape($suffix).'){';
            echo 'if(!_selectedRows'.acym_escape($suffix).'.hasOwnProperty(icat)) continue;';
            echo 'tag += icat + "-";';
            echo '}';
            echo 'tag += otherinfo + "}";';
        } elseif ($type === 'simple') {
            echo 'tag = "{'.acym_escape($dynamicIdentifier).':" + otherinfo + "}";';
        }

        echo 'acym_editorWysidDynamic.insertDContent(tag);';
        echo '}';

        echo 'function addAdditionalInfo'.acym_escape($suffix).'(index, value){';
        echo acym_escape($storageVar).'[index] = value;';
        echo acym_escape($updateFunction).'();';
        echo '}';
        echo '</script>';
    }

    /**
     * In Joomla, we can translate elements in extensions using the FaLang extension
     */
    public function translateItem(object &$item, object &$tag, string $referenceTable, int $referenceId = 0): void
    {
        if (!acym_isExtensionActive('com_falang')) {
            return;
        }

        if (!empty($tag->falang)) {
            $langId = $tag->falang;
        } elseif (!empty($tag->lang)) {
            $langId = intval(substr($tag->lang, strpos($tag->lang, ',') + 1));
        } elseif (!empty($this->contextLanguage)) {
            $languages = acym_loadObjectList('SELECT `lang_id`, `lang_code`, `sef` FROM #__languages');
            foreach ($languages as $language) {
                if (in_array($this->contextLanguage, [$language->lang_code, $language->sef])) {
                    $langId = $language->lang_id;
                    break;
                }
            }
        }

        if (empty($langId)) {
            return;
        }

        if (empty($referenceId)) {
            $referenceId = $tag->id;
        }

        $translations = acym_loadObjectList(
            'SELECT `reference_field`, `value` 
            FROM `#__falang_content` 
            WHERE `published` = 1 
                AND `reference_table` = '.acym_escapeDB($referenceTable).' 
                AND `language_id` = '.intval($langId).' 
                AND `reference_id` = '.intval($referenceId)
        );

        if (empty($translations)) {
            return;
        }

        foreach ($translations as $oneTranslation) {
            if (empty($oneTranslation->value)) {
                continue;
            }

            $translatedField = $oneTranslation->reference_field;
            $item->$translatedField = $oneTranslation->value;
        }
    }

    public function createDummyEmailObject(int $mailId, string $code, string $previewBody): object
    {
        if (!empty($mailId)) {
            $mailClass = new MailClass();
            $email = $mailClass->getOneById($mailId);
        }

        if (empty($email)) {
            $email = new \stdClass();
            $email->id = 0;
            $email->name = '';
            $email->subject = '';
            $email->from_name = '';
            $email->from_email = '';
            $email->reply_to_name = '';
            $email->reply_to_email = '';
            $email->bcc = '';
            $email->links_language = '';
        }

        $language = acym_getVar('string', 'language', 'main');
        if (!empty($language)) {
            if ($language === 'main') {
                $language = $this->config->get('multilingual_default', ACYM_DEFAULT_LANGUAGE);
            }
            $email->links_language = $language;
        }

        $email->creation_date = acym_date('now', 'Y-m-d H:i:s', false);
        $email->creator_id = acym_currentUserId();
        $email->thumbnail = '';
        $email->drag_editor = '1';
        $email->type = MailClass::TYPE_STANDARD;
        $email->settings = '';
        $email->stylesheet = '';
        $email->attachments = '';

        // This is only the dynamic text/content code
        $email->body = $code;
        // This is the whole editor current content
        $email->previewBody = $previewBody;

        return $email;
    }
}
