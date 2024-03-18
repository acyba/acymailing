<?php

use AcyMailing\Classes\MailClass;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');

class plgSearchAcymailing extends CMSPlugin
{
    public function onContentSearchAreas()
    {
        $language = Factory::getLanguage();
        $language->load('com_acym', JPATH_ROOT, null, true);
        $language->load('com_acym_custom', JPATH_ROOT, null, true);

        return ['acymailing' => Text::_('ACYM_NEWSLETTERS')];
    }

    public function onContentSearch($text, $type = 'any', $ordering = '', $areas = null)
    {
        // Make sure we need to search something
        $text = trim($text);
        if (empty($text)) return [];
        if (is_array($areas) && !array_intersect($areas, array_keys($this->onContentSearchAreas()))) return [];


        // Load Acy library
        $ds = DIRECTORY_SEPARATOR;
        $helperFile = rtrim(JPATH_ADMINISTRATOR, $ds).$ds.'components'.$ds.'com_acym'.$ds.'helpers'.$ds.'helper.php';
        if (!file_exists($helperFile) || !include_once $helperFile) return [];


        // Prepare the search query
        $words = $type === 'exact' ? [$text] : explode(' ', $text);
        $conditions = [];
        foreach ($words as $word) {
            $word = acym_escapeDB('%'.acym_utf8Encode($word).'%', false);
            $subConditions = [];
            $subConditions[] = 'mail.subject LIKE '.$word;
            $subConditions[] = 'mail.body LIKE '.$word;
            $conditions[] = implode(' OR ', $subConditions);
        }
        $where = '('.implode($type === 'all' ? ') AND (' : ') OR (', $conditions).')';

        // When browsernav = 1 there is a target=blank on the returned elements' links
        $query = 'SELECT mail.id, mail.subject, mail.body, mail.creation_date, list.name AS section, "2" AS browsernav 
                FROM `#__acym_campaign` AS campaign 
                JOIN `#__acym_mail` AS mail ON mail.id = campaign.mail_id OR mail.parent_id = campaign.mail_id 
                JOIN `#__acym_mail_has_list` AS map ON map.mail_id = mail.id OR map.mail_id = mail.parent_id 
                JOIN `#__acym_list` AS list ON list.id = map.list_id 
                WHERE ( '.$where.' ) 
                    AND mail.`type` = "standard" 
                    AND campaign.active = 1 
                    AND campaign.visible = 1 
                    AND campaign.sent = 1 
                GROUP BY mail.id ';

        if ($ordering === 'oldest') {
            $query .= 'ORDER BY mail.creation_date ASC';
        } elseif ($ordering === 'category') {
            $query .= 'ORDER BY list.name ASC';
        } elseif ($ordering === 'alpha') {
            $query .= 'ORDER BY mail.subject ASC';
        } else {
            $query .= 'ORDER BY mail.creation_date DESC';
        }

        $emails = acym_loadObjectList($query, '', 0, 50);
        if (empty($emails)) return [];

        $mailClass = new MailClass();
        $emails = $mailClass->decode($emails);

        // Prepare the result
        $return = [];
        foreach ($emails as $key => $item) {
            $emails[$key]->href = acym_frontendLink('archive&task=view&id='.$item->id.'&'.acym_noTemplate());
            $emails[$key]->created = acym_date($item->creation_date, 'Y-m-d H:i:s');
            $emails[$key]->title = $emails[$key]->subject;
            $emails[$key]->text = $emails[$key]->body;

            if (SearchHelper::checkNoHtml($emails[$key], $text, ['title', 'text'])) {
                $return[] = $emails[$key];
            }
        }

        return $return;
    }
}
