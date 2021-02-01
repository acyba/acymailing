<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\EditorHelper;
use AcyMailing\Helpers\MailerHelper;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Libraries\acymController;
use AcyMailing\Libraries\acymParameter;

class ArchiveController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        $this->setDefaultTask('view');
        $this->authorizedFrontTasks = ['view', 'listing', 'showArchive', 'search'];
    }

    public function view()
    {
        // Index Follow meta tag
        acym_addMetadata('robots', 'noindex,nofollow');

        $mailId = acym_getVar('int', 'id', 0);

        $isPopup = acym_getVar('int', 'is_popup', 0);

        $mailerHelper = new MailerHelper();
        $mailerHelper->loadedToSend = false;
        $oneMail = $mailerHelper->load($mailId);

        if (empty($oneMail->id)) {
            acym_raiseError(E_ERROR, 404, acym_translation('ACYM_EMAIL_NOT_FOUND'));

            return;
        }

        //Set the meta for facebook picture...
        //<meta property="og:image" content="path-to/mylogo.png" />
        //1 : do we have a picture with id "pictshare" ?
        //2 no? do we have the "pictshare" class then?
        //3 no? let's take the first
        if (preg_match('#<img[^>]*id="pictshare"[^>]*>#i', $oneMail->body, $pregres) && preg_match('#src="([^"]*)"#i', $pregres[0], $pict)) {
            acym_addMetadata('og:image', $pict[1]);
        } elseif (preg_match('#<img[^>]*class="[^"]*pictshare[^"]*"[^>]*>#i', $oneMail->body, $pregres) && preg_match('#src="([^"]*)"#i', $pregres[0], $pict)) {
            acym_addMetadata('og:image', $pict[1]);
        }

        acym_addMetadata('og:url', acym_frontendLink('archive&task=view&mailid='.$oneMail->id));
        acym_addMetadata('og:title', $oneMail->subject);

        // We may use those two options later, if users ask for it
        if (!empty($oneMail->metadesc)) {
            acym_addMetadata('og:description', $oneMail->metadesc);
            acym_addMetadata('description', $oneMail->metadesc);
        }
        if (!empty($oneMail->metakey)) {
            acym_addMetadata('keywords', $oneMail->metakey);
        }

        // Replace the user Dtexts for the preview if the key/userid here
        $userkeys = acym_getVar('string', 'userid', 0);
        if (!empty($userkeys)) {
            $userId = intval(substr($userkeys, 0, strpos($userkeys, '-')));
            $userKey = substr($userkeys, strpos($userkeys, '-') + 1);
            $receiver = acym_loadObject('SELECT * FROM #__acym_user WHERE `id` = '.intval($userId).' AND `key` = '.acym_escapeDB($userKey));
        }

        $currentEmail = acym_currentUserEmail();
        if (empty($receiver) && !empty($currentEmail)) {
            $userClass = new UserClass();
            $receiver = $userClass->getOneByEmail($currentEmail);
        }

        if (empty($receiver)) {
            $receiver = new \stdClass();
            $receiver->name = acym_translation('ACYM_VISITOR');
        }

        acym_trigger('replaceUserInformation', [&$oneMail, &$receiver, false]);

        // If there is the unsubscribe link for elastic email
        preg_match('@href="{unsubscribe:(.*)}"@', $oneMail->body, $match);
        if (!empty($match)) {
            //We replace the tag by the url
            $oneMail->body = str_replace($match[0], 'href="'.$match[1].'"', $oneMail->body);
        }

        // Add foundation for email CSS only for D&D emails
        if (strpos($oneMail->body, 'acym__wysid__template') !== false) {
            acym_addStyle(false, ACYM_CSS.'libraries/foundation_email.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'libraries'.DS.'foundation_email.min.css'));
        }
        acym_addStyle(true, acym_getEmailCssFixes());
        if (!empty($oneMail->stylesheet)) {
            acym_addStyle(true, $oneMail->stylesheet);
        }
        $editorHelper = new EditorHelper();
        $settings = json_decode($oneMail->settings, true);
        if (!empty($settings)) {
            $settings = $editorHelper->getSettingsStyle($settings);

            if (!empty($settings)) {
                acym_addStyle(true, $settings);
            }
        }

        // Make sure the background image works on the archive
        $oneMail->body = preg_replace('#background\-image: url\(&quot;([^)]*)&quot;\)#Uis', 'background-image: url($1)', $oneMail->body);

        $data = [
            'mail' => $oneMail,
            'receiver' => $receiver,
        ];

        acym_includeHeaders();
        parent::display($data);

        //We are forced to use exit because of WordPress that display a 0 if a exit isn't used
        if ($isPopup || 'wordpress' === ACYM_CMS) exit;
    }

    public function listing()
    {
        acym_setVar('layout', 'listing');

        $search = acym_getVar('string', 'acym_search', '');

        // Get the Joomla menu parameters
        $menu = acym_getMenu();
        if (!is_object($menu)) {
            acym_redirect(acym_rootURI());

            return;
        }
        $params = method_exists($menu, 'getParams') ? $menu->getParams() : $menu->params;
        $menuParams = new acymParameter($params);

        // Handle the core Joomla params
        $paramsJoomla = [];
        $paramsJoomla['suffix'] = $menuParams->get('pageclass_sfx', '');
        $paramsJoomla['page_heading'] = $menuParams->get('page_heading');
        $paramsJoomla['show_page_heading'] = $menuParams->get('show_page_heading', 0);

        if ($menuParams->get('menu-meta_description')) {
            acym_addMetadata('description', $menuParams->get('menu-meta_description'));
        }

        if ($menuParams->get('menu-meta_keywords')) {
            acym_addMetadata('keywords', $menuParams->get('menu-meta_keywords'));
        }

        if ($menuParams->get('robots')) {
            acym_addMetadata('robots', $menuParams->get('robots'));
        }

        // Initialize our own params then call the generic view
        $nbNewslettersPerPage = $menuParams->get('archiveNbNewslettersPerPage', 10);
        $listsSent = $menuParams->get('lists', '');
        $popup = $menuParams->get('popup', '1');

        $viewParams = [
            'nbNewslettersPerPage' => $nbNewslettersPerPage,
            'listsSent' => $listsSent,
            'popup' => $popup,
            'paramsCMS' => $paramsJoomla,
            'search' => $search,
        ];

        $this->showArchive($viewParams);
    }

    public function showArchive($viewParams)
    {
        acym_setVar('layout', 'listing');

        $data = $this->getDataForArchive($viewParams);

        acym_addScript(false, ACYM_JS.'front/frontarchive.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'front'.DS.'frontarchive.min.js'));

        parent::display($data);
    }

    private function getDataForArchive($viewParams)
    {
        $params = [];

        $userId = false;
        $userClass = new UserClass();
        $currentUser = $userClass->identify(true);
        if (!empty($currentUser)) {
            $params['userId'] = $currentUser->id;
            $userId = $currentUser->id;
        }

        if (!empty($viewParams['nbNewslettersPerPage'])) {
            $params['numberPerPage'] = $viewParams['nbNewslettersPerPage'];
        }

        if (!empty($viewParams['listsSent'])) {
            $params['lists'] = $viewParams['listsSent'];
        }

        if (!empty($viewParams['search'])) {
            $params['search'] = $viewParams['search'];
        }

        $params['page'] = acym_getVar('int', 'acym_front_page', 1);

        $campaignClass = new CampaignClass();
        $returnLastNewsletters = $campaignClass->getLastNewsletters($params);
        $pagination = new PaginationHelper();
        $pagination->setStatus($returnLastNewsletters['count'], $params['page'], $params['numberPerPage']);

        return [
            'newsletters' => $returnLastNewsletters['matchingNewsletters'],
            'paramsJoomla' => $viewParams['paramsCMS'],
            'pagination' => $pagination,
            'userId' => $userId,
            'popup' => '1' === $viewParams['popup'],
            'search' => $viewParams['search'],
            'actionUrl' => acym_currentURL(),
        ];
    }
}
