<?php
/**
 * @package     corejoomla.site
 * @subpackage  com_communityquiz
 *
 * @copyright   Copyright (C) 2009 - 2021 corejoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use AcyMailing\Helpers\TabHelper;
use AcyMailing\Libraries\acymPlugin;

class plgAcymQuiz extends acymPlugin
{
    public function __construct()
    {
        parent::__construct();

        // Joomla, WordPress or all for an addon that can work on both CMSs, don't forget the uppercases
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'Community Quiz',
            'description' => '- Insert your quiz in the emails',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/community-quiz',
            'category' => 'Content management',
            'level' => 'starter',
        ];

        // Title displayed on the tab in the dynamic texts popup or the dynamic content insertion button
        $this->pluginDescription->name = 'Quiz';

        // This is optional, if you set it, a tooltip text will be shown when hovering the button
        $this->pluginDescription->title = 'Inserts the quizzes into the newsletters.';

        // Path to the icon displayed on the button. It can be a svg, png, gif, jpg, etc... file
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/logo_128px.png';

        $this->installed = acym_isExtensionActive('com_communityquiz');

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'intro' => ['ACYM_INTRO_TEXT', true],
                'cat' => ['ACYM_CATEGORY', false],
                'publishing' => ['ACYM_PUBLISHING_DATE', false],
                'readmore' => ['ACYM_READ_MORE', false],
            ];

            $this->initCustomView();

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->replaceOptions, $this->elementOptions),
                ],
                'front' => [
                    'type' => 'select',
                    'label' => 'ACYM_FRONT_ACCESS',
                    'value' => 'all',
                    'data' => [
                        'all' => 'ACYM_ALL_ELEMENTS',
                        'author' => 'ACYM_ONLY_AUTHORS_ELEMENTS',
                        'hide' => 'ACYM_DONT_SHOW',
                    ],
                ],
            ];
        }
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
        $format->customFields = [];
        $customView = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';
    }

    public function initReplaceOptionsCustomView()
    {
        $this->replaceOptions = [
            'link' => ['ACYM_LINK'],
            'picthtml' => ['ACYM_IMAGE'],
            'readmore' => ['ACYM_READ_MORE'],
        ];
    }

    public function initElementOptionsCustomView()
    {
        $element = acym_getColumns('quiz_quizzes', false);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$value] = [$value];
        }
    }

    public function getPossibleIntegrations()
    {
        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'hide') return null;

        return $this->pluginDescription;
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;

        $this->categories = acym_loadObjectList(
            'SELECT id, parent_id, title
            FROM `#__categories`
            WHERE extension = "com_communityquiz"'
        );

        $tabHelper = new TabHelper();
        $identifier = $this->name;
        $tabHelper->startTab(acym_translation('ACYM_ONE_BY_ONE'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $displayOptions = [
            [
                'title' => 'ACYM_DISPLAY',
                'type' => 'checkbox',
                'name' => 'display',
                'options' => $this->displayOptions,
            ],
        ];

        $displayOptions = array_merge(
            $displayOptions,
            [
                [
                    'title' => 'ACYM_CLICKABLE_TITLE',
                    'type' => 'boolean',
                    'name' => 'clickable',
                    'default' => true,
                ],
                [
                    'title' => 'ACYM_CLICKABLE_IMAGE',
                    'type' => 'boolean',
                    'name' => 'clickableimg',
                    'default' => false,
                ],
                [
                    'title' => 'ACYM_TRUNCATE',
                    'type' => 'intextfield',
                    'isNumber' => 1,
                    'name' => 'wrap',
                    'text' => 'ACYM_TRUNCATE_AFTER',
                    'default' => 0,
                ],
                [
                    'title' => 'ACYM_AUTO_LOGIN',
                    'tooltip' => 'ACYM_AUTO_LOGIN_DESCRIPTION_WARNING',
                    'type' => 'boolean',
                    'name' => 'autologin',
                    'default' => false,
                ],
            ]
        );

        $zoneContent = $this->getFilteringZone().$this->prepareListing();
        echo $this->displaySelectionZone($zoneContent);
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'individual', $this->defaultValues);

        $tabHelper->endTab();
        $identifier = 'auto'.$this->name;
        $tabHelper->startTab(acym_translation('ACYM_BY_CATEGORY'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $catOptions = [
            [
                'title' => 'ACYM_ORDER_BY',
                'type' => 'select',
                'name' => 'order',
                'options' => [
                    'id' => 'ACYM_ID',
                    'publish_up' => 'ACYM_PUBLISHING_DATE',
                    'modified' => 'ACYM_MODIFICATION_DATE',
                    'title' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
            ],
        ];
        $this->autoContentOptions($catOptions);

        $this->autoCampaignOptions($catOptions);

        $displayOptions = array_merge($displayOptions, $catOptions);

        echo $this->displaySelectionZone($this->getCategoryListing());
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();

        $tabHelper->display('plugin');
    }

    public function prepareListing()
    {
        $this->querySelect = 'SELECT element.id, element.title, element.publish_up ';
        $this->query = 'FROM #__quiz_quizzes AS element ';
        $this->filters = [];
        $this->filters[] = 'element.published = 1';
        $this->searchFields = ['element.id', 'element.title'];
        $this->pageInfo->order = 'element.id';
        $this->elementIdTable = 'element';
        $this->elementIdColumn = 'id';

        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'author') {
            $this->filters[] = 'element.created_by = '.intval(acym_currentUserId());
        }

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
                    'type' => 'date',
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
        $this->replaceMultiple($email);
        $this->replaceOne($email);
    }

    protected function loadLibraries($email)
    {
        require_once JPATH_ROOT.'/components/com_cjlib/framework.php';
        require_once JPATH_ROOT.'/components/com_cjlib/framework/api.php';
        require_once JPATH_ROOT.'/components/com_communityquiz/helpers/constants.php';
        require_once JPATH_ROOT.'/components/com_communityquiz/router.php';
        require_once JPATH_ROOT.'/components/com_communityquiz/helpers/route.php';
        require_once JPATH_ROOT.'/components/com_communityquiz/helpers/helper.php';

        return true;
    }

    public function generateByCategory(&$email)
    {
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $this->tags = [];
        $time = time();

        if (empty($tags)) return $this->generateCampaignResult;

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            $query = 'SELECT DISTINCT element.`id` FROM #__quiz_quizzes AS element ';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $where[] = 'element.catid IN ('.implode(',', $selectedArea).')';
            }

            $where[] = 'element.published = 1';
            $where[] = '`publish_up` < '.acym_escapeDB(date('Y-m-d H:i:s', $time - date('Z')));
            $where[] = '`publish_down` > '.acym_escapeDB(date('Y-m-d H:i:s', $time - date('Z'))).' OR `publish_down` = 0';
            if (!empty($parameter->min_publish)) {
                $parameter->min_publish = acym_date(acym_replaceDate($parameter->min_publish), 'Y-m-d H:i:s', false);
                $where[] = '`publish_up` >= '.acym_escapeDB($parameter->min_publish);
            }

            if (!empty($parameter->onlynew)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $where[] = 'element.publish_up > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                }
            }

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'element');
        }

        return $this->generateCampaignResult;
    }

    public function replaceIndividualContent($tag)
    {
        $query = 'SELECT element.*
                    FROM #__quiz_quizzes AS element
                    WHERE element.published = 1
                        AND element.id = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);
        if (empty($element)) return '';

        $varFields = $this->getCustomLayoutVars($element);

        $completeId = $element->id;
        if (!empty($element->alias)) $completeId .= ':'.$element->alias;

        $link = CommunityQuizHelperRoute::getQuizRoute($completeId, $element->catid, $this->getLanguage($element->language, true));
        $link = $this->finalizeLink($link, $tag);
        $varFields['{link}'] = $link;

        $title = '';
        $afterArticle = '';
        $contentText = '';
        $customFields = [];

        $varFields['{title}'] = $element->title;
        if (in_array('title', $tag->display)) $title = $varFields['{title}'];

        $varFields['{intro}'] = $element->description;
        if (in_array('intro', $tag->display)) $contentText .= $varFields['{intro}'];

        $varFields['{publishing}'] = acym_date($element->publish_up);
        if (in_array('publishing', $tag->display)) {
            $customFields[] = [
                $varFields['{publishing}'],
                acym_translation('ACYM_PUBLISHING_DATE'),
            ];
        }

        $category = acym_loadResult('SELECT title FROM #__categories WHERE id = '.intval($element->catid));
        $varFields['{cat}'] = '<a href="'.$this->finalizeLink('index.php?option=com_communityquiz&view=category&id='.$element->catid, $tag).'" target="_blank">'.acym_escape(
                $category
            ).'</a>';
        if (in_array('cat', $tag->display)) {
            $customFields[] = [
                $varFields['{cat}'],
                acym_translation('ACYM_CATEGORY'),
            ];
        }

        $this->handleCustomFields($tag, $customFields);

        $readMoreText = empty($tag->readmore) ? acym_translation('ACYM_READ_MORE') : $tag->readmore;
        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'"><span class="acymailing_readmore">'.acym_escape(
                $readMoreText
            ).'</span></a>';
        if (in_array('readmore', $tag->display)) $afterArticle .= $varFields['{readmore}'];

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = $title;
        $format->afterArticle = $afterArticle;
        $format->description = $contentText;
        $format->link = empty($tag->clickable) && empty($tag->clickableimg) ? '' : $link;
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }
}
