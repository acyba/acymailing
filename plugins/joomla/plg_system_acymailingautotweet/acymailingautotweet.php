<?php

use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\MailStatClass;
use AcyMailing\Helpers\MailerHelper;

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

//We need to load the autotweetbase.
$ds = DIRECTORY_SEPARATOR;
$file = JPATH_ROOT.$ds.'administrator'.$ds.'components'.$ds.'com_autotweet'.$ds.'helpers'.$ds.'autotweetbase.php';
if (!file_exists($file) || !require_once $file) return;
if (!class_exists('PlgAutotweetBase')) return;


class plgSystemAcymailingAutotweet extends PlgAutotweetBase
{
    /**
     * Save sent emails in Perfect Publisher
     */
    public function onAcymSendMail($mailId)
    {
        $lists = $this->params->get('lists');

        if (!empty($lists) && is_array($lists)) {
            $mailLists = acym_loadResultArray('SELECT list_id FROM #__acym_mail_has_list WHERE mail_id = '.intval($mailId));
            $commonLists = array_intersect($lists, $mailLists);
            if (empty($commonLists)) return;
        }

        $mailStatClass = new MailStatClass();
        $mailStat = $mailStatClass->getOneByMailId($mailId);
        // This should never happen but just to be sure
        if (empty($mailStat)) return;

        $mailClass = new MailClass();
        $mail = $mailClass->getOneById($mailId);
        // Only register campaigns
        if (empty($mail) || $mail->type !== $mailClass::TYPE_STANDARD) return;

        $date = new DateTime($mailStat->send_date);
        if ($date->format('Y-m-d') === '1970-01-01') {
            $date->modify(JFactory::getDate()->toSql());
        }

        $archiveLink = $this->getArchiveLink($mail);

        $jsonObject = json_encode($mail);

        // ID, date, title, subject, 0?, url, image, json object
        $this->postStatusMessage($mailId, $date->format('Y-m-d'), $mail->subject, 0, $archiveLink, '', $jsonObject);
    }

    public function getData($mailId, $typeinfo = null)
    {
        // typeinfo is not used (one message type only)

        // The AcyMailing library isn't necessary loaded
        if (!function_exists('acym_loadResultArray')) {
            $ds = DIRECTORY_SEPARATOR;
            $file = JPATH_ROOT.$ds.'administrator'.$ds.'components'.$ds.'com_acym'.$ds.'helpers'.$ds.'helper.php';
            if (!file_exists($file) || !include_once $file) {
                return ['is_valid' => false];
            }
        }

        // Load the mail and replace its dtexts/dcontents
        $mailerHelper = new MailerHelper();
        $newsletter = $mailerHelper->load($mailId);

        if (empty($mailId) || empty($newsletter->id)) {
            return ['is_valid' => false];
        }

        // Load the lists attached to the mail
        $catIds = acym_loadResultArray('SELECT list_id FROM #__acym_mail_has_list WHERE mail_id = '.intval($mailId));

        return [
            'title' => $newsletter->subject,
            'text' => $newsletter->subject,
            'hashtags' => '',
            'fulltext' => $newsletter->body,
            'catids' => empty($catIds) ? [] : $catIds,
            'author' => empty($newsletter->from_name) ? acym_config()->get('from_name') : $newsletter->from_name,
            'is_valid' => true,
        ];
    }

    private function getArchiveLink($email)
    {
        $archiveLink = 'archive&task=view&id='.$email->id.'&'.acym_noTemplate(false);
        if (!empty($email->links_language)) {
            if (acym_isPluginActive('languagefilter')) {
                $archiveLink .= '&lang='.substr($email->links_language, 0, strpos($email->links_language, '-'));
            } else {
                $archiveLink .= '&language='.$email->links_language;
            }
        }
        if (!empty($email->key)) $archiveLink .= '&key='.$email->key;

        return acym_frontendLink($archiveLink);
    }

    public function getExtendedData($id, $typeinfo, &$native_object)
    {
        return $this->getData($id, $typeinfo);
    }
}
