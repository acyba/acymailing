<?php
/**
 * @package     corejoomla.site
 * @subpackage  com_communitysurveys
 *
 * @copyright   Copyright (C) 2009 - 2015 corejoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use AcyMailing\Libraries\acymPlugin;

class plgAcymSurvey extends acymPlugin
{
    public function __construct()
    {
        parent::__construct();

        // Joomla, WordPress or all for an addon that can work on both CMSs, don't forget the uppercases
        $this->cms = 'Joomla';

        // Title displayed on the tab in the dynamic texts popup or the dynamic content insertion button
        $this->pluginDescription->name = 'Survey';

        // This is optional, if you set it, a tooltip text will be shown when hovering the button
        $this->pluginDescription->title = 'Inserts the unique survey URL in the newsletters.';

        // Path to the icon displayed on the button. It can be a svg, png, gif, jpg, etc... file
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/logo_128px.png';

        $this->installed = acym_isExtensionActive('com_communitysurveys');
        if ($this->installed) {
            $this->initCustomView();

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->replaceOptions, $this->elementOptions),
                ],
            ];
        } else {
            $this->settings = [
                'not_installed' => '1',
            ];
        }
    }

    public function initElementOptionsCustomView()
    {
        $this->elementOptions = [];
        $element = acym_loadObject('SELECT * FROM #__survey_surveys LIMIT 1');
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function initReplaceOptionsCustomView()
    {
        $this->replaceOptions = [
            'link' => ['ACYM_LINK'],
            'readmore' => ['ACYM_READ_MORE'],
        ];
    }

    public function getPossibleIntegrations()
    {
        return $this->pluginDescription;
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;

        $this->categories = acym_loadObjectList(
            'SELECT id, parent_id, title
            FROM `#__categories`
            WHERE extension = "com_communitysurveys"'
        );

        $identifier = $this->name;
        $displayOptions = [
            [
                'title' => 'ACYM_DISPLAY',
                'type' => 'checkbox',
                'name' => 'display',
                'options' => [
                    'title' => ['ACYM_TITLE', true],
                    'intro' => ['ACYM_INTRO_TEXT', true],
                    'readmore' => ['ACYM_READ_MORE', false],
                ],
            ],
            [
                'title' => 'ACYM_TRUNCATE',
                'type' => 'intextfield',
                'isNumber' => 1,
                'name' => 'wrap',
                'text' => 'ACYM_TRUNCATE_AFTER',
                'default' => 0,
            ],
        ];

        $zoneContent = $this->getFilteringZone().$this->prepareListing();
        echo $this->displaySelectionZone($zoneContent);
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'individual', $this->defaultValues);
    }

    public function getStandardStructure(&$customView)
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{title}';
        $format->afterArticle = '';
        $format->description = '{intro}';
        $format->link = '{link}';
        $customView = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';
    }

    public function prepareListing()
    {
        $this->querySelect = 'SELECT element.id, element.title, element.publish_up ';
        $this->query = 'FROM #__survey_surveys AS element ';
        $this->filters = [];
        $this->filters[] = 'element.published = 1';
        $this->searchFields = ['element.id', 'element.title'];
        $this->pageInfo->order = 'element.id';
        $this->elementIdTable = 'element';
        $this->elementIdColumn = 'id';

        parent::prepareListing();

        if (!empty($this->pageInfo->filter_cat)) {
            $this->filters[] = 'element.catid = '.intval($this->pageInfo->filter_cat);
        }

        $listingOptions = [
            'header' => [
                'title' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '7',
                ],
                'publish_up' => [
                    'label' => 'ACYM_PUBLISHING_DATE',
                    'size' => '4',
                    'type' => 'text',
                ],
                'id' => [
                    'label' => 'ACYM_ID',
                    'size' => '1',
                    'class' => 'text-center',
                ],
            ],
            'id' => 'id',
            'rows' => $this->getElements(),
        ];

        return $this->getElementsListing($listingOptions);
    }

    public function replaceContent(&$email)
    {
        $this->replaceOne($email);
    }

    protected function loadLibraries($email)
    {
        require_once JPATH_ROOT.'/components/com_cjlib/framework.php';
        require_once JPATH_ROOT.'/components/com_cjlib/framework/api.php';
        require_once JPATH_ROOT.'/components/com_communitysurveys/helpers/constants.php';
        require_once JPATH_ROOT.'/components/com_communitysurveys/router.php';
        require_once JPATH_ROOT.'/components/com_communitysurveys/helpers/route.php';
        require_once JPATH_ROOT.'/components/com_communitysurveys/helpers/helper.php';
        CJLib::import('corejoomla.framework.core');

        return true;
    }

    public function replaceIndividualContent($tag)
    {
        $query = 'SELECT element.*
                    FROM #__survey_surveys AS element
                    WHERE element.published = 1
                        AND element.id = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';

        $varFields = $this->getCustomLayoutVars($element);

        $link = '{LOADSURVEY:'.$element->id.'}';
        $varFields['{link}'] = $link;

        $title = '';
        if (in_array('title', $tag->display)) $title = $element->title;

        $afterTitle = '';
        $afterTopic = '';

        $contentText = '';
        $varFields['{intro}'] = $element->description;
        if (in_array('intro', $tag->display)) {
            $contentText .= $varFields['{intro}'];
        }

        $readMoreText = empty($tag->readmore) ? acym_translation('ACYM_READ_MORE') : $tag->readmore;
        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'">';
        $varFields['{readmore}'] .= '<span class="acymailing_readmore">'.acym_escape($readMoreText).'</span>';
        $varFields['{readmore}'] .= '</a>';
        if (in_array('readmore', $tag->display)) {
            $afterTopic .= $varFields['{readmore}'];
        }

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = $title;
        $format->afterTitle = $afterTitle;
        $format->afterArticle = $afterTopic;
        $format->description = $contentText;
        $format->link = $link;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }

    /**
     * This method is almost the same as the previous one, except that it is called FOR EACH user the email is sent to
     * used to replace the "shortcodes" in the sent emails. {LOADSURVEY:7} in this example
     *
     * $email => The email object, if you need to modify something in this object, make sure to do it right...
     * $user  => The user object, you can then access to its data if you want to customize what you display
     * $send  => If this variable is true, the email will be sent, if it's false the email won't be sent (on the summary page for example)
     */
    public function replaceUserInformation(&$email, &$user, $send = true)
    {
        if (!$send) return;

        $extractedTags = $this->pluginHelper->extractTags($email, 'LOADSURVEY');
        if (empty($extractedTags)) return;

        // load survey urls
        JLoader::import('response', JPATH_ROOT.'/components/com_communitysurveys/models');
        $responseModel = JModelLegacy::getInstance('response', 'CommunitySurveysModel');

        $tags = [];
        foreach ($extractedTags as $shortcode => $oneTag) {
            if (isset($tags[$shortcode])) continue;

            // The current user hasn't any account created on the site (only an AcyMailing user)
            if (empty($user->cms_id)) {
                $tags[$shortcode] = '';
                continue;
            }

            $invitee = new stdClass();
            $invitee->id = (int)$user->cms_id;
            $users = [$invitee];
            $responseModel->createSurveyKeys($oneTag->id, 1, true, true, $users);

            $tags[$shortcode] = $users[0]->url;
        }

        $this->pluginHelper->replaceTags($email, $tags);
    }
}
