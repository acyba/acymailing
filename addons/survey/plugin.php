<?php
/**
 * @package     extension.site
 * @subpackage  com_communitysurveys
 *
 * @copyright   Copyright (C) 2023 BulaSikku Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */
defined('_JEXEC') or die;

use AcyMailing\Classes\MailClass;
use AcyMailing\Core\AcymPlugin;
use AcyMailing\Helpers\TabHelper;
use Joomla\CMS\Factory;
use Joomla\Component\CommunitySurveys\Site\Helper\RouteHelper;
use Joomla\Component\CommunitySurveys\Site\Model\ResponseModel;
use Joomla\Utilities\ArrayHelper;

class plgAcymSurvey extends AcymPlugin
{
    private bool $groupedByCategory = false;
    private int $currentCategory = 0;

    public function __construct()
    {
        parent::__construct();

        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'Community Surveys',
            'description' => '- Insert surveys in your emails',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/community-surveys',
            'category' => 'Content management',
            'level' => 'starter',
        ];

        $this->pluginDescription->name = 'Survey';
        $this->pluginDescription->title = 'Inserts the unique survey URL in the newsletters.';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/logo_128px.png';

        $this->installed = acym_isExtensionActive('com_communitysurveys') && class_exists(RouteHelper::class);
        if ($this->installed && ACYM_CMS === 'joomla') {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'intro' => ['ACYM_INTRO_TEXT', true],
                'cat' => ['ACYM_CATEGORY', false],
                'tags' => ['ACYM_TAGS', false],
                'author' => ['ACYM_AUTHOR', false],
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
                'itemid' => [
                    'type' => 'text',
                    'label' => 'ACYM_MENU_ID',
                    'value' => '',
                ],
            ];
        } else {
            $this->settings = [
                'not_installed' => '1',
            ];
        }
    }

    public function getStandardStructure(string &$customView): void
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{title}';
        $format->afterTitle = '';
        $format->afterArticle = '';
        $format->imagePath = '';
        $format->description = '{intro}';
        $format->link = '{link}';
        $format->customFields = [];
        $customView = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';
    }

    public function initReplaceOptionsCustomView(): void
    {
        $this->replaceOptions = [
            'link' => ['ACYM_LINK'],
            'readmore' => ['ACYM_READ_MORE'],
        ];
    }

    public function initElementOptionsCustomView(): void
    {
        $element = acym_getColumns('survey_surveys', false);
        if (empty($element)) {
            return;
        }

        foreach ($element as $value) {
            $this->elementOptions[$value] = [$value];
        }
    }

    public function getPossibleIntegrations(): ?object
    {
        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'hide') {
            return null;
        }

        return $this->pluginDescription;
    }

    public function insertionOptions(?object $defaultValues = null): void
    {
        $this->defaultValues = $defaultValues;

        $this->categories = acym_loadObjectList(
            'SELECT id, parent_id, title
            FROM `#__categories` 
            WHERE extension = "com_communitysurveys"'
        );

        $this->tagvalues = acym_loadObjectList(
            'SELECT `id` AS `term_id`, `title` AS `name`
			FROM #__tags 
			WHERE `level` > 0
			ORDER BY `name`'
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
                    'title' => 'ACYM_TRUNCATE',
                    'type' => 'intextfield',
                    'isNumber' => 1,
                    'name' => 'wrap',
                    'text' => 'ACYM_TRUNCATE_AFTER',
                    'default' => 0,
                ],
                [
                    'title' => 'ACYM_AUTO_LOGIN',
                    'tooltip' => 'ACYM_AUTO_LOGIN_DESCRIPTION',
                    'type' => 'boolean',
                    'name' => 'autologin',
                    'default' => false,
                ],
            ]
        );

        $zoneContent = $this->getFilteringZone().$this->prepareListing();
        $this->displaySelectionZone($zoneContent);
        $this->pluginHelper->displayOptions($displayOptions, $identifier, 'individual', $this->defaultValues);

        $tabHelper->endTab();
        $identifier = 'auto'.$this->name;
        $tabHelper->startTab(acym_translation('ACYM_BY_CATEGORY'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $catOptions = [
            [
                'title' => 'ACYM_LANGUAGE',
                'type' => 'language',
                'name' => 'language',
            ],
            [
                'title' => 'ACYM_ORDER_BY',
                'type' => 'select',
                'name' => 'order',
                'options' => [
                    'id' => 'ACYM_ID',
                    'publish_up' => 'ACYM_PUBLISHING_DATE',
                    'modified' => 'ACYM_MODIFICATION_DATE',
                    'ordering' => 'ACYM_ORDERING',
                    'title' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
            ],
            [
                'title' => 'ACYM_GROUP_BY_CATEGORY',
                'type' => 'boolean',
                'name' => 'groupbycat',
                'default' => false,
            ],
            [
                'title' => 'ACYM_META_KEYWORDS',
                'tooltip' => 'ACYM_META_KEYWORDS_DESC',
                'type' => 'text',
                'name' => 'keywords',
                'class' => ' ',
            ],
        ];

        $this->autoContentOptions($catOptions);
        $this->autoCampaignOptions($catOptions, true);
        $displayOptions = array_merge($displayOptions, $catOptions);

        $this->displaySelectionZone($this->getCategoryListing());
        $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();
        $identifier = $this->name.'_tags';
        $tabHelper->startTab(acym_translation('ACYM_BY_TAG'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $this->displaySelectionZone($this->getTagListing());
        $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();
        $tabHelper->display('plugin');
    }

    public function prepareListing(): string
    {
        $this->querySelect = 'SELECT element.id, element.title, element.publish_up ';
        $this->query = 'FROM #__survey_surveys AS element ';
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

    public function replaceContent(object &$email): void
    {
        $this->replaceMultiple($email);
        $this->replaceOne($email);
    }

    public function generateByCategory(object &$email): object
    {
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $tags = array_merge($tags, $this->pluginHelper->extractTags($email, $this->name.'_tags'));

        $this->tags = [];
        $time = time();

        if (empty($tags)) {
            return $this->generateCampaignResult;
        }

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) {
                continue;
            }

            $query = 'SELECT DISTINCT element.`id` 
                    FROM #__survey_surveys AS element 
                    LEFT JOIN #__categories AS category ON element.catid = category.id ';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                if (strpos($oneTag, '{'.$this->name.'_tags') === 0) {
                    $query .= 'JOIN #__contentitem_tag_map AS tags ON element.id = tags.content_item_id AND tags.type_alias = "com_communitysurveys.survey"';
                    $where[] = 'tags.tag_id IN ('.implode(',', $selectedArea).')';
                } else {
                    $where[] = 'element.catid IN ('.implode(',', $selectedArea).')';
                }
            }

            $where[] = 'element.published = 1';
            $where[] = 'element.`publish_up` < '.acym_escapeDB(date('Y-m-d H:i:s', $time - date('Z')));
            $where[] = 'element.`publish_down` > '.acym_escapeDB(date('Y-m-d H:i:s', $time - date('Z'))).' OR element.`publish_down` = 0 OR element.`publish_down` IS NULL';
            if (!empty($parameter->min_publish)) {
                $parameter->min_publish = acym_date(acym_replaceDate($parameter->min_publish), 'Y-m-d H:i:s', false);
                $where[] = 'element.`publish_up` >= '.acym_escapeDB($parameter->min_publish);
            }

            if (!empty($parameter->onlynew)) {
                $parameter->datefilter = 'onlynew';
            }

            if (!empty($parameter->datefilter)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $condition = 'element.publish_up > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                    if ($parameter->datefilter === 'onlymodified') {
                        $condition .= ' OR element.modified > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                    }
                    $where[] = $condition;
                }
            }

            if (!empty($parameter->language) && $parameter->language !== 'any') {
                $where[] = 'element.language IN ("*", '.acym_escapeDB($parameter->language).')';
            }

            if (!empty($parameter->keywords)) {
                $keywords = explode(',', $parameter->keywords);
                $conditionsMetaKeywords = [];
                foreach ($keywords as $oneKeyword) {
                    $conditionsMetaKeywords[] = 'element.metakey LIKE '.acym_escapeDB('%'.$oneKeyword.'%');
                }
                $where[] = '('.implode(' OR ', $conditionsMetaKeywords).')';
            }

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->groupedByCategory = !empty($parameter->groupbycat);
            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'element');
        }

        return $this->generateCampaignResult;
    }

    protected function handleOrderBy(&$query, $parameter, $table = null): void
    {
        if (empty($parameter->order)) {
            return;
        }

        $ordering = explode(',', $parameter->order);
        if ($ordering[0] === 'rand') {
            $query .= ' ORDER BY rand()';
        } elseif ($ordering[0] === 'ordering') {
            $query .= ' ORDER BY category.`title` '.acym_secureDBColumn(trim($ordering[1])).', element.`ordering` '.acym_secureDBColumn(trim($ordering[1]));
        } else {
            $table = null === $table ? '' : $table.'.';
            $column = $ordering[0];

            if (strpos($column, '.') !== false) {
                $parts = explode('.', $column, 2);
                $table = acym_secureDBColumn($parts[0]).'.';
                $column = $parts[1];
            }

            $query .= ' ORDER BY '.$table.'`'.acym_secureDBColumn(trim($column)).'` '.acym_secureDBColumn(trim($ordering[1]));
        }
    }

    protected function groupByCategory(array $elements): array
    {
        if (!$this->groupedByCategory || empty($elements)) {
            return $elements;
        }

        acym_arrayToInteger($elements);
        $idsWithCatids = acym_loadObjectList('SELECT `id`, `catid` FROM #__survey_surveys WHERE `id` IN ('.implode(', ', $elements).')');
        usort(
            $idsWithCatids,
            function ($a, $b) {
                return strtolower($a->catid) > strtolower($b->catid) ? 1 : -1;
            }
        );
        $elements = [];
        foreach ($idsWithCatids as $oneArticle) {
            $elements[] = $oneArticle->id;
        }

        return $elements;
    }

    public function replaceIndividualContent(object $tag): string
    {
        $query = 'SELECT element.*, `user`.`name` AS authorname, cat.`title` AS category_title, cat.`access` AS category_access
                    FROM #__survey_surveys AS element 
                    JOIN #__categories AS cat 
                        ON element.`catid` = cat.`id`
                    LEFT JOIN #__users AS `user` 
                        ON `user`.`id` = `element`.`created_by` 
                    WHERE element.published = 1
                        AND element.id = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);
        if (empty($element)) {
            return '';
        }

        $varFields = $this->getCustomLayoutVars($element);
        $link = '{LOADSURVEY["id":'.$element->id.']}';
        $link = $this->finalizeLink($link, $tag, intval($element->access) === 1 && intval($element->category_access) === 1);

        $menuId = $this->getParam('itemid');
        if (!empty($menuId)) {
            $link .= (strpos($link, '?') ? '&' : '?').'Itemid='.intval($menuId);
        }

        $varFields['{link}'] = $link;

        $title = '';
        $afterTitle = '';
        $afterArticle = '';
        $contentText = '';
        $customFields = [];
        $altImage = '';

        $varFields['{title}'] = $element->title;
        if (in_array('title', $tag->display)) {
            $title = $varFields['{title}'];
        }

        $varFields['{content}'] = $element->description.$element->fulltext;
        if (in_array('content', $tag->display)) {
            $contentText .= $varFields['{content}'];
        }

        $varFields['{intro}'] = $element->description;
        if (in_array('intro', $tag->display)) {
            $contentText .= $varFields['{intro}'];
        }

        $contentText = $this->cleanExtensionContent($contentText);

        if (empty($element->created_by_alias) && empty($element->authorname)) {
            $varFields['{author}'] = '';
        } else {
            $varFields['{author}'] = empty($element->created_by_alias) ? $element->authorname : $element->created_by_alias;
        }

        if (in_array('author', $tag->display) && !empty($varFields['{author}'])) {
            $customFields[] = [
                $varFields['{author}'],
                acym_translation('ACYM_AUTHOR'),
            ];
        }

        $varFields['{publishing}'] = acym_date($element->publish_up);
        if (in_array('publishing', $tag->display)) {
            $customFields[] = [
                $varFields['{publishing}'],
                acym_translation('ACYM_PUBLISHING_DATE'),
            ];
        }

        $category = acym_loadResult('SELECT title FROM #__categories WHERE id = '.intval($element->catid));
        $varFields['{cat}'] = '<a href="'.$this->finalizeLink(RouteHelper::getCategoryRoute($element->catid), $tag).'" target="_blank">'.
            acym_escapeHtml($category).
            '</a>';
        if (in_array('cat', $tag->display)) {
            $customFields[] = [
                $varFields['{cat}'],
                acym_translation('ACYM_CATEGORY'),
            ];
        }

        $tags = acym_loadObjectList(
            'SELECT tags.id, tags.title, tags.alias 
            FROM #__tags AS tags 
            JOIN #__contentitem_tag_map AS map ON tags.id = map.tag_id  
            WHERE map.type_alias = "com_communitysurveys.survey"
                  AND map.content_item_id = '.intval($tag->id)
        );
        foreach ($tags as $i => $oneTag) {
            $tags[$i] = '<a href="'.acym_escapeUrl(
                    $this->finalizeLink('index.php?option=com_tags&view=tag&id='.$oneTag->id.':'.$oneTag->alias, $tag)
                ).'" target="_blank">'.acym_escapeHtml(
                    $oneTag->title
                ).'</a>';
        }
        $varFields['{tags}'] = implode(', ', $tags);

        if (in_array('tags', $tag->display) && !empty($varFields['{tags}'])) {
            $customFields[] = [
                $varFields['{tags}'],
                acym_translation('ACYM_TAGS'),
            ];
        }

        $this->handleCustomFields($tag, $customFields);

        $readMoreText = empty($tag->readmore) ? acym_translation('ACYM_READ_MORE') : $tag->readmore;
        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.acym_escapeUrl(
                $link
            ).'"><span class="acymailing_readmore">'.acym_escapeHtml(
                $readMoreText
            ).'</span></a>';
        if (in_array('readmore', $tag->display)) {
            $afterArticle .= $varFields['{readmore}'];
        }

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = $title;
        $format->afterTitle = $afterTitle;
        $format->afterArticle = $afterArticle;
        $format->description = $contentText;
        $format->link = empty($tag->clickable) && empty($tag->clickableimg) ? '' : $link;
        $format->customFields = $customFields;
        $format->altImage = $altImage;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        $categoryTitle = '';
        if (!empty($tag->groupbycat) && $this->currentCategory !== (int)$element->catid) {
            $this->currentCategory = intval($element->catid);

            $categoryTitle = '<h1 class="acymailing_category_title">'.acym_escapeHtml($element->category_title).'</h1>';
            $categoryLink = $this->finalizeLink(RouteHelper::getCategoryRoute($element->catid), $tag);
            $categoryTitle = '<a target="_blank" href="'.acym_escapeUrl($categoryLink).'">'.$categoryTitle.'</a>';
        }

        return $categoryTitle.$this->finalizeElementFormat($result, $tag, $varFields);
    }

    protected function cleanExtensionContent(string $text): string
    {
        return preg_replace('#\{igallery[^}]+\}#Uis', '', $text);
    }

    public function replaceUserInformation(object &$email, ?object &$user, bool $send = true): void
    {
        if (!$send) {
            return;
        }

        $extractedTags = $this->pluginHelper->extractTags($email, 'LOADSURVEY');
        if (empty($extractedTags)) {
            return;
        }

        // Get the ResponseModel using Joomla's MVC Factory
        $responseModel = $this->getResponseModel();
        if (!$responseModel) {
            return;
        }

        $tags = [];
        foreach ($extractedTags as $shortcode => $oneTag) {
            if (isset($tags[$shortcode])) {
                continue;
            }

            // Get survey ID from the tag - handle both old and new formats
            $surveyId = $this->extractSurveyId($oneTag);
            if (empty($surveyId)) {
                continue;
            }

            $invitee = new \stdClass();
            $invitee->id = (int)$user->cms_id;
            $users = [$invitee];
            $result = $responseModel->createSurveyKeys($surveyId, 1, true, true, $users);

            // Build the survey URL with the key
            if ($result && !empty($result[0])) {
                $key = $result[0];
                $tags[$shortcode] = $this->buildSurveyUrl($surveyId, $key);
            }
        }

        $this->pluginHelper->replaceTags($email, $tags);
    }

    /**
     * Get the ResponseModel instance
     *
     * @return  ResponseModel|null  The model instance or null on failure
     *
     * @since   7.0.0
     */
    private function getResponseModel(): ?ResponseModel
    {
        try {
            $app = Factory::getApplication();
            $mvcFactory = $app->bootComponent('com_communitysurveys')->getMVCFactory();

            return $mvcFactory->createModel('Response', 'Site', ['ignore_request' => true]);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Build survey URL with unique key
     *
     * @param int $surveyId The survey ID
     * @param string $key   The unique access key
     *
     * @return  string  The full survey URL
     *
     * @since   7.0.0
     */
    private function buildSurveyUrl(int $surveyId, string $key): string
    {
        $baseUrl = RouteHelper::getSurveyRoute($surveyId);
        $url = $baseUrl.'&key='.$key;


        $menuId = $this->getParam('itemid');
        if (!empty($menuId)) {
            $url .= '&Itemid='.intval($menuId);
        }

        return $url;
    }

    /**
     * Extract survey ID from tag object
     * Handles both old format {LOADSURVEY:7} and new format {LOADSURVEY["id":7]}
     *
     * @param object $tag The tag object from AcyMailing
     *
     * @return  int|null  The survey ID or null if not found
     *
     * @since   7.0.0
     */
    private function extractSurveyId($tag): ?int
    {
        // New format: tag has 'id' property directly
        if (isset($tag->id) && is_numeric($tag->id)) {
            return (int)$tag->id;
        }

        // Fallback: check if tag itself is numeric (old format)
        if (is_numeric($tag)) {
            return (int)$tag;
        }

        return null;
    }

    public function onAcymFailedSendingEmail(int $mailId, int $userId, int $errorNumber): void
    {
        $mailClass = new MailClass();
        $email = $mailClass->getOneById($mailId);
        if (empty($email)) {
            return;
        }

        $extractedTags = $this->pluginHelper->extractTags($email, 'LOADSURVEY');
        if (empty($extractedTags)) {
            return;
        }

        $surveyIds = [];
        foreach ($extractedTags as $oneTag) {
            $surveyId = $this->extractSurveyId($oneTag);
            if (empty($surveyId) || in_array($surveyId, $surveyIds)) {
                continue;
            }


            $surveyIds[] = $surveyId;
        }

        $surveyIds = ArrayHelper::toInteger($surveyIds);
        if (!empty($surveyIds)) {
            // Delete the invitations with the survey ids
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                        ->delete('#__survey_keys')
                        ->where($db->quoteName('user_id').' = '.(int)$userId)
                        ->where($db->quoteName('survey_id').' IN ('.implode(',', $surveyIds).')')
                        ->where($db->quoteName('response_status').' = 0')
                        ->where($db->quoteName('response_id').' = 0');
            $db->setQuery($query);
            $db->execute();
        }
    }
}
