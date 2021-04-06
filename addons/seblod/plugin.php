<?php

use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Helpers\TabHelper;

class plgAcymSeblod extends acymPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->installed = acym_isExtensionActive('com_cck');

        $this->pluginDescription->name = 'Seblod';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.svg';

        if ($this->installed) {
            $this->initDisplayOptionsCustomView();
            $this->initElementOptionsCustomView();
            $this->initReplaceOptionsCustomView();

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
        } else {
            $this->settings = [
                'not_installed' => '1',
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
        $format->afterTitle = '';
        $format->afterArticle = '';
        $format->imagePath = '{image}';
        $format->description = '{introtext}';
        $format->link = '{link}';
        $format->customFields = [];
        $customView = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';
    }

    public function initDisplayOptionsCustomView()
    {
        $this->displayOptions = [];
        $this->displayOptions = [
            'title' => ['ACYM_TITLE', true],
            'introtext' => ['ACYM_INTRO_TEXT', true],
            'fulltext' => ['ACYM_FULL_TEXT', false],
            'created' => ['ACYM_DATE_CREATED', false],
            'pubdate' => ['ACYM_PUBLISHING_DATE', false],
            'image' => ['ACYM_IMAGE', true],
        ];

        // Load custom fields
        $query = 'SELECT a.name, a.title 
                    FROM `#__cck_core_fields` AS a 
                    WHERE a.published = 1 
                        AND (a.storage LIKE "custom" 
                            OR a.storage_table LIKE "#__cck_store_item_content" 
                            OR a.storage_field LIKE "introtext" 
                            OR a.folder = 1) 
                    ORDER BY a.title';
        $customFields = acym_loadObjectList($query);

        if (!empty($customFields)) {
            foreach ($customFields as $onefield) {
                if (in_array($onefield->name, ['art_introtext', 'art_fulltext', 'cat_description'])) {
                    continue;
                }

                $this->displayOptions[$onefield->name] = [$onefield->title, false];
            }
        }
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
        $this->elementOptions = [];
        $query = 'SELECT a.*,b.alias AS catalias,c.name AS username FROM #__content AS a ';
        $query .= 'JOIN #__categories AS b ON a.catid = b.id ';
        $query .= 'LEFT JOIN #__users AS c ON a.created_by = c.id ';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
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

        $this->categories = acym_loadObjectList('SELECT id, parent_id, title FROM #__categories WHERE extension = "com_content" ORDER BY `id` DESC');

        $tabHelper = new TabHelper();
        $identifier = $this->name;
        $tabHelper->startTab(acym_translation('ACYM_ONE_BY_ONE'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $displayOptions = [
            [
                'title' => 'ACYM_FIELDS_TO_DISPLAY',
                'type' => 'checkbox',
                'name' => 'displays',
                'options' => $this->displayOptions,
                'separator' => '; ',
            ],
            [
                'title' => 'ACYM_CLICKABLE_TITLE',
                'type' => 'boolean',
                'name' => 'clickable',
                'default' => true,
            ],
            [
                'title' => 'ACYM_DISPLAY_PICTURES',
                'type' => 'pictures',
                'name' => 'pictures',
            ],
        ];

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
                    'created' => 'ACYM_DATE_CREATED',
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
        $this->querySelect = 'SELECT a.*,b.*,c.*,a.id AS gID, a.title AS gtitle ';
        $this->query = 'FROM `#__content` AS a 
                        JOIN #__categories AS b ON a.catid = b.id 
                        LEFT JOIN `#__users` AS c ON a.created_by = c.id';
        $this->filters = [];
        $this->filters[] = 'a.state != -2';
        $this->searchFields = ['a.id', 'a.title', 'b.title', 'c.username'];
        $this->pageInfo->order = 'a.id';
        $this->elementIdTable = 'a';
        $this->elementIdColumn = 'id';

        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'author') {
            $this->filters[] = 'a.created_by = '.intval(acym_currentUserId());
        }

        parent::prepareListing();

        if (!empty($this->pageInfo->filter_cat)) {
            $filters[] = 'a.catid = '.intval($this->pageInfo->filter_cat);
        }

        $rows = $this->getElements();
        foreach ($rows as $i => $row) {
            if (strpos($row->created, ': ') != false) {
                $rows[$i]->created = str_replace('/', '', strrchr(strip_tags($row->created), '/'));
            }
        }

        $listingOptions = [
            'header' => [
                'gtitle' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '5',
                ],
                'created' => [
                    'label' => 'ACYM_DATE_CREATED',
                    'size' => '3',
                    'type' => 'date',
                ],
                'title' => [
                    'label' => 'ACYM_CATEGORY',
                    'size' => '3',
                ],
                'gID' => [
                    'label' => 'ACYM_ID',
                    'size' => '1',
                    'class' => 'text-center',
                ],
            ],
            'id' => 'gID',
            'rows' => $rows,
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
        require_once JPATH_SITE.DS.'plugins'.DS.'content'.DS.'cck'.DS.'cck.php';
        require_once __DIR__.DS.'acyseblodfield.php';

        //let's determine the Itemid
        $menuid = acym_loadResult('SELECT id FROM #__menu WHERE link LIKE "%index.php?option=com_content&view=article%" LIMIT 1');
        $this->itemId = empty($menuid) ? '' : '&Itemid='.$menuid;

        acym_loadLanguageFile('com_cck_default', JPATH_SITE);

        $this->addedcss = false;

        return true;
    }

    public function replaceIndividualContent($tag)
    {
        if (!empty($tag->displays)) {
            $tag->displays = explode(';', $tag->displays);
        } else {
            $tag->displays = ['title'];
        }

        foreach ($tag->displays as $i => $oneField) {
            $tag->displays[$i] = trim($oneField);
        }
        //2 : Load the Seblod article
        $query = 'SELECT a.*,b.alias AS catalias,c.name AS username FROM #__content AS a ';
        $query .= 'JOIN #__categories AS b ON a.catid = b.id ';
        $query .= 'LEFT JOIN #__users AS c ON a.created_by = c.id ';
        $query .= 'WHERE a.id = '.intval($tag->id);
        $article = acym_loadObject($query);
        $result = '';
        $varFields = [];

        //In case of we could not load the article for any reason
        if (empty($article)) {
            if (acym_isAdmin()) {
                acym_enqueueMessage('The article "'.$tag->id.'" could not be loaded', 'notice');
            }

            return $result;
        }

        $link = 'index.php?option=com_content&view=article&id='.$article->id.'&catid='.$article->catid.$this->itemId;
        $link = $this->finalizeLink($link);
        $varFields['{link}'] = $link;
        $resultTitle = $article->title;
        $created = '';
        //create a clickable title or a simple title
        if (in_array('title', $tag->displays)) {
            if (!empty($tag->clickable)) {
                $resultTitle = '<a href="'.$link.'" target="_blank" >'.$resultTitle.'</a>';
            }
            $resultTitle = '<tr><td colspan="2"><h2 class="acym_title">'.$resultTitle.'</h2></td></tr>';
        }
        $varFields['{created}'] = acym_getDate(acym_getTime($article->created), acym_translation('ACYM_DATE_FORMAT_LC1'));
        if (in_array('created', $tag->displays)) {
            $created = '<tr><td>'.acym_translation('ACYM_DATE_CREATED').' : </td><td>'.$varFields['{created}'].'</td></tr>';
        }

        $pubdate = '';
        $varFields['{pubdate}'] = acym_getDate(acym_getTime($article->publish_up), acym_translation('ACYM_DATE_FORMAT_LC1'));
        if (in_array('pubdate', $tag->displays)) {
            $pubdate = '<tr><td>'.acym_translation('ACYM_PUBLISHING_DATE').' : </td><td>'.$varFields['{pubdate}'].'</td></tr>';
        }

        $answer = [];
        preg_match_all('#::([^/:]+)::(.*)::/#Uis', $article->introtext, $fields);

        if (!empty($fields)) {
            foreach ($fields[1] as $i => $property) {
                $answer[$property] = $fields[2][$i];
            }
        }

        $description = '';

        if (in_array('image', $tag->displays) && !empty($article->images) && !empty($tag->pict)) {
            $images = json_decode($article->images);
            $pictVar = 'image_intro';
            if (empty($images->$pictVar)) {
                $pictVar = 'image_fulltext';
            }
            if (!empty($images->$pictVar)) {
                $varFields['{picthtml}'] = '<img style="float:left;padding-right:10px;padding-bottom:10px;" alt="" border="0" src="'.acym_rootURI().$images->$pictVar.'" />';
                $result .= $varFields['{picthtml}'];
            }
        }


        $varFields['{introtext}'] = !empty($answer['introtext']) ? $answer['introtext'] : '';
        if (in_array('introtext', $tag->displays) && !empty($answer['introtext'])) {
            $description .= '<tr><td colspan="2">'.$answer['introtext'].'</td></tr>';
        }

        $varFields['{fulltext}'] = !empty($answer['fulltext']) ? $answer['fulltext'] : '';
        if (in_array('fulltext', $tag->displays) && !empty($answer['fulltext'])) {
            $description .= '<tr><td colspan="2">'.$answer['fulltext'].'</td></tr>';
        }
        //this is the description
        $article->text = $article->introtext.$article->fulltext;

        $params = [];
        $acyCCK = new AcyplgContentCCK();
        $acyCCK->acyDisplays = $tag->displays;
        $acyCCK->onContentPrepare('com_content.article', $article, $params, 0);

        foreach ($article as $fieldName => $oneField) {
            $varFields['{'.$fieldName.'}'] = $oneField;
        }

        $result .= $article->text;

        //if there are parameters from seblod in the description, we do not display it
        if (strlen(strip_tags($description)) < 3) {
            $description = '';
        }

        //we add all informations in a table
        $result = '<div class="acym_content" style="clear:both"><table cellspacing="0" cellpadding="5" border="0" style="width:100%;">'.$resultTitle.$description.$created.$pubdate.'<tr><td colspan="2">'.$result.'</td></tr></table></div>';
        $result = preg_replace('#administrator/#', '', $result);

        $result = str_replace('&nbsp;', ' ', $result);
        $result = preg_replace(
            '#<iframe[^>]*(http[^"]*embed/)([^"]*)[^<]*</iframe>#',
            '<a href="$1$2" target="_blank"><img alt="" src="https://img.youtube.com/vi/$2/1.jpg"/></a>',
            $result
        );
        $result = str_replace('/embed/', '/watch?v=', $result);

        //if the user created a custom template
        if (!empty($tag->tmpl) && file_exists(ACYM_MEDIA.'plugins'.DS.'seblod_'.$tag->tmpl.'.php')) {
            ob_start();
            require ACYM_MEDIA.'plugins'.DS.'seblod_'.$tag->tmpl.'.php';
            $result = ob_get_clean();
            $result = str_replace(array_keys($varFields), $varFields, $result);
        } elseif (file_exists(ACYM_MEDIA.'plugins'.DS.'seblod.php')) {
            ob_start();
            require ACYM_MEDIA.'plugins'.DS.'seblod.php';
            $result = ob_get_clean();
            $result = str_replace(array_keys($varFields), $varFields, $result);
        }


        if (!$this->addedcss) {
            $result .= '<style type="text/css">
							div.cck_value, div.cck_label {
								vertical-align: top;
								display: inline-block;
							}

							div.cck_label {
								min-width: 50px;
							}
							</style>';
            $this->addedcss = true;
        }

        $result = $this->pluginHelper->managePicts($tag, $result);

        return $result;
    }

    public function generateByCategory(&$email)
    {
        //load the tags
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $this->tags = [];

        if (empty($tags)) return $this->generateCampaignResult;

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            $query = 'SELECT id FROM #__content';
            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $where[] = 'catid IN ('.implode(',', $selectedArea).')';
            }

            $where[] = 'state = 1';

            if (!empty($parameter->onlynew)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $where[] = 'publish_up > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                }
            }

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter);
        }

        return $this->generateCampaignResult;
    }
}
