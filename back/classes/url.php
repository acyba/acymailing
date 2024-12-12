<?php

namespace AcyMailing\Classes;

use AcyMailing\Libraries\acymClass;

class UrlClass extends acymClass
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'url';
        $this->pkey = 'id';
    }

    public function save($url)
    {
        if (empty($url)) return false;

        foreach ($url as $oneAttribute => $value) {
            if (empty($value)) {
                continue;
            }

            $url->$oneAttribute = strip_tags($value);
        }

        return parent::save($url);
    }

    public function getOneUrlById($id)
    {
        return acym_loadObject('SELECT * FROM #__acym_url WHERE `id` = '.intval($id));
    }

    public function getOneByUrl($url)
    {
        return acym_loadObject('SELECT * FROM #__acym_url WHERE `url` = '.acym_escapeDB($url));
    }

    public function getAdd($url)
    {
        $currentUrl = $this->getOneByUrl($url);
        if (empty($currentUrl->id)) {
            $currentUrl = new \stdClass();
            $currentUrl->name = $url;
            $currentUrl->url = $url;
            $currentUrl->id = $this->save($currentUrl);

            if (empty($currentUrl->id)) {
                return null;
            }
        }

        return $currentUrl;
    }

    public function getUrl($url, $mailid, $userid)
    {
        if (empty($url) || empty($mailid) || empty($userid)) return '';

        static $allurls;

        $url = str_replace('&amp;', '&', $url);

        if (empty($allurls[$url])) {
            $allurls[$url] = $this->getAdd($url);
        }

        if (empty($allurls[$url]->id)) {
            return $url;
        }

        return acym_frontendLink('fronturl&task=click&urlid='.$allurls[$url]->id.'&userid='.$userid.'&mailid='.$mailid);
    }

    // Used in checkDB to address a bug before the 12/04/19
    public function getDuplicatedUrls()
    {
        return acym_loadResultArray(
            'SELECT DISTINCT duplicates.id
            FROM #__acym_url AS duplicates
            JOIN #__acym_url AS original ON duplicates.url = original.url
            JOIN #__acym_url_click AS clickoriginal ON original.id = clickoriginal.url_id
            LEFT JOIN #__acym_url_click AS click ON duplicates.id = click.url_id
            WHERE click.url_id IS NULL
            LIMIT 500'
        );
        /*
        To clean the potential duplicated rows in the #__acym_url table, you can execute this query when no email is being sent:
        DELETE url.*
        FROM #__acym_url AS url
        LEFT JOIN #__acym_url_click AS urlclick ON url.id = urlclick.url_id
        WHERE urlclick.url_id IS NULL

        If an email is currently being sent, exclude its urls from the delete query
         */
    }
}
