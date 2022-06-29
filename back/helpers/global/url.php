<?php

function acym_absoluteURL($text)
{
    static $mainurl = '';
    if (empty($mainurl)) {
        $urls = parse_url(ACYM_LIVE);
        if (!empty($urls['path'])) {
            $mainurl = substr(ACYM_LIVE, 0, strrpos(ACYM_LIVE, $urls['path'])).'/';
        } else {
            $mainurl = ACYM_LIVE;
        }
    }

    //It will remove the undefined thing added by tinyMCE
    // And URL with twice the domain
    $text = str_replace(
        [
            'href="../undefined/',
            'href="../../undefined/',
            'href="../../../undefined//',
            'href="undefined/',
            ACYM_LIVE.'http://',
            ACYM_LIVE.'https://',
        ],
        [
            'href="'.$mainurl,
            'href="'.$mainurl,
            'href="'.$mainurl,
            'href="'.ACYM_LIVE,
            'http://',
            'https://',
        ],
        $text
    );
    //We remove errors with /administrator links and our tags
    //We replace /{ by { , it's also a bug with the editor...
    $text = preg_replace('#href="(/?administrator)?/({|%7B)#Ui', 'href="$2', $text);

    //We replace http:/ by http:// , it will avoid a user bug! seriously we are nice guys...
    $text = preg_replace('#href="http:/([^/])#Ui', 'href="http://$1', $text);

    //Sometimes clients add links without http:// but directly with their website url... let's try to catch some of these errors
    $text = preg_replace(
        '#href="'.preg_quote(str_replace(['http://', 'https://'], '', $mainurl), '#').'#Ui',
        'href="'.$mainurl,
        $text
    );

    $replace = [];
    $replaceBy = [];
    //We don't convert urls starting with { or [ into absolute url otherwise it could break a tag
    //WE don't modify links starting with \\ as it replaces the protocole http / https
    if ($mainurl !== ACYM_LIVE) {
        //url like ../ your site...
        //We don't transform mailto: # http:// ...

        $replace[] = '#(href|src|action|background)[ ]*=[ ]*\"(?!(\{|%7B|\[|\#|\\\\|[a-z]{3,15}:|/))(?:\.\./)#i';
        $replaceBy[] = '$1="'.substr(ACYM_LIVE, 0, strrpos(rtrim(ACYM_LIVE, '/'), '/') + 1);

        //sub folder : substr(ACYM_LIVE,strrpos(rtrim(ACYM_LIVE,'/'),'/'))
        //We remove the sub folder if there is a tag... otherwise we will break the whole thing.
        //We had an issue with that when selecting the readonline link via the front-end

        $subfolder = substr(ACYM_LIVE, strrpos(rtrim(ACYM_LIVE, '/'), '/'));
        $replace[] = '#(href|src|action|background)[ ]*=[ ]*\"'.preg_quote($subfolder, '#').'(\{|%7B)#i';
        $replaceBy[] = '$1="$2';
    }

    $replace[] = '#(href|src|action|background)[ ]*=[ ]*\"(?!(\{|%7B|\[|\#|\\\\|[a-z]{3,15}:|/))(?:\.\./|\./)?#i';
    $replaceBy[] = '$1="'.ACYM_LIVE;
    $replace[] = '#(href|src|action|background)[ ]*=[ ]*\"(?!(\{|%7B|\[|\#|\\\\|[a-z]{3,15}:))/#i';
    $replaceBy[] = '$1="'.$mainurl;

    //background images for div
    $replace[] = '#((?:background-image|background)[ ]*:[ ]*url\((?:\'|"|&quot;)?(?!(\\\\|[a-z]{3,15}:|/|\'|"|&quot;))(?:\.\./|\./)?)#i';
    $replaceBy[] = '$1'.ACYM_LIVE;

    return preg_replace($replace, $replaceBy, $text);
}

function acym_mainURL(&$link)
{
    static $mainurl = '';
    static $otherarguments = false;
    if (empty($mainurl)) {
        $urls = parse_url(ACYM_LIVE);
        if (isset($urls['path']) && strlen($urls['path']) > 0) {
            $mainurl = substr(ACYM_LIVE, 0, strrpos(ACYM_LIVE, $urls['path'])).'/';
            $otherarguments = trim(str_replace($mainurl, '', ACYM_LIVE), '/');
            if (strlen($otherarguments) > 0) {
                $otherarguments .= '/';
            }
        } else {
            $mainurl = ACYM_LIVE;
        }
    }

    if ($otherarguments && strpos($link, $otherarguments) === false) {
        $link = $otherarguments.$link;
    }

    return $mainurl;
}

function acym_currentURL()
{
    $url = isset($_SERVER['HTTPS']) || !empty($_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS']) ? 'https' : 'http';
    $url .= '://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

    return $url;
}

function acym_isLocalWebsite()
{
    return strpos(ACYM_LIVE, 'localhost') !== false || strpos(ACYM_LIVE, '127.0.0.1') !== false;
}

function acym_internalUrlToPath($url)
{
    $base = str_replace(['http://www.', 'https://www.', 'http://', 'https://'], '', ACYM_LIVE);
    $replacements = ['https://www.'.$base, 'http://www.'.$base, 'https://'.$base, 'http://'.$base];
    foreach ($replacements as $oneReplacement) {
        if (strpos($url, $oneReplacement) === false) {
            continue;
        }
        $url = str_replace([$oneReplacement, '/'], [ACYM_ROOT, DS], urldecode($url));
        break;
    }

    return $url;
}
