<?php

use AcyMailing\Helpers\TabHelper;

trait EasyblogInsertion
{
    private $EBconfig = null;

    public function getStandardStructure(string &$customView): void
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{title}';
        $format->afterTitle = '';
        $format->afterArticle = '{readmore}';
        $format->imagePath = '';
        $format->description = '{picthtml}<br/>{intro}';
        $format->link = '{link}';
        $format->customFields = [];
        $customView = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';
    }

    public function initReplaceOptionsCustomView(): void
    {
        $this->replaceOptions = [
            'link' => ['ACYM_LINK'],
            'picthtml' => ['ACYM_IMAGE'],
            'readmore' => ['ACYM_READ_MORE'],
        ];
    }

    public function initElementOptionsCustomView(): void
    {
        $element = acym_getColumns('easyblog_post', false);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$value] = [$value];
        }
    }

    public function initCustomOptionsCustomView()
    {
        $query = 'SELECT title FROM  #__easyblog_fields';
        $element = acym_loadObjectList($query);
        foreach ($element as $value) {
            $this->customOptions[$value->title] = [$value->title];
        }
    }

    public function insertionOptions(?object $defaultValues = null): void
    {
        $this->defaultValues = $defaultValues;

        // Get the categories, always with the columns "id", "parent_id" and "title". Use the MySQL "AS" if needed
        $this->categories = acym_loadObjectList(
            'SELECT id, parent_id, title
            FROM `#__easyblog_category` 
            WHERE published = 1'
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


        $customFields = acym_loadObjectList('SELECT id, title FROM #__easyblog_fields WHERE state = 1 AND `type` != "heading"');

        if (!empty($customFields)) {
            $customFieldsOptions = [];
            foreach ($customFields as $oneField) {
                $customFieldsOptions[$oneField->id] = [$oneField->title, false];
            }
            $displayOptions[] = [
                'title' => 'ACYM_CUSTOM_FIELDS',
                'type' => 'checkbox',
                'name' => 'custom',
                'options' => $customFieldsOptions,
            ];
        }

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
                    'title' => 'ACYM_DISPLAY_PICTURES',
                    'type' => 'pictures',
                    'name' => 'pictures',
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
        $this->displaySelectionZone($zoneContent);
        $this->pluginHelper->displayOptions($displayOptions, $identifier, 'individual', $this->defaultValues);

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

        $this->displaySelectionZone($this->getCategoryListing());
        $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();

        $tabHelper->display('plugin');
    }

    public function prepareListing(): string
    {
        //we load all elements with the categories
        $this->querySelect = 'SELECT element.id, element.title, element.publish_up ';
        $this->query = 'FROM #__easyblog_post AS element ';
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

        // If we filtered the listing for a specific category, we display only the elements of this category
        if (!empty($this->pageInfo->filter_cat)) {
            $this->query .= 'JOIN #__easyblog_post_category AS map ON map.post_id = element.id ';
            $this->filters[] = 'map.category_id = '.intval($this->pageInfo->filter_cat);
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
        $this->tags = [];
        $time = time();

        if (empty($tags)) return $this->generateCampaignResult;

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            $query = 'SELECT DISTINCT element.`id` FROM #__easyblog_post AS element ';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $query .= 'JOIN #__easyblog_post_category AS map ON map.post_id = element.id ';
                $where[] = 'map.category_id IN ('.implode(',', $selectedArea).')';
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

    protected function loadLibraries(?object $email): bool
    {
        $EBHelper = JPATH_SITE.DS.'components'.DS.'com_easyblog'.DS.'helpers'.DS.'helper.php';
        if (file_exists($EBHelper)) include_once $EBHelper;
        $EBIncludes = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_easyblog'.DS.'includes'.DS.'easyblog.php';
        if (file_exists($EBIncludes)) include_once $EBIncludes;

        if (!class_exists('EB')) return false;

        $this->EBconfig = EB::config();
        if (method_exists('EB', 'loadLanguages')) EB::loadLanguages();

        return true;
    }

    public function replaceIndividualContent(object $tag): string
    {
        $query = 'SELECT element.* FROM #__easyblog_post AS element WHERE element.published = 1 AND element.id = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';

        $element->extra_fields = empty($element->extra_fields) ? [] : json_decode($element->extra_fields, true);

        $varFields = $this->getCustomLayoutVars($element);

        $link = 'index.php?option=com_easyblog&view=entry&id='.$tag->id;

        //Get the related menu item if specified
        $menuId = $this->getParam('itemid');
        if (!empty($menuId)) {
            $link .= '&Itemid='.intval($menuId);
        }

        $link = $this->finalizeLink($link, $tag);
        $varFields['{link}'] = $link;

        $title = '';
        $varFields['{title}'] = $element->title;
        if (in_array('title', $tag->display)) $title = $varFields['{title}'];

        $afterTitle = '';
        $afterArticle = '';

        $imagePath = '';
        if (!empty($element->image)) {
            $image = json_decode($element->image);

            if (empty($image)) {
                if (strpos($element->image, 'post:') !== false) {
                    // Some pictures do not use the ID of the current post but from a previous post
                    preg_match('/post:(\d*)\/.*/', $element->image, $matches);
                    if (!empty($matches[1]) && is_numeric($matches[1])) {
                        $idForImgPath = $matches[1];
                    } else {
                        $idForImgPath = $element->id;
                    }
                    $imagePath = $this->EBconfig->get('main_articles_path', 'images/easyblog_articles/').$idForImgPath.'/';
                    $imagePath .= basename($element->image);
                } elseif (strpos($element->image, 'user:') !== false) {
                    $imagePath = $this->EBconfig->get('main_users_path', 'images/easyblog_images/').substr($element->image, 5);
                } else {
                    $imagePath = $this->EBconfig->get('main_shared_path', 'images/site/shared/');
                    $imagePath .= basename($element->image);
                }
            }
        }

        $varFields['picthtml'] = '<img alt="" src="'.$imagePath.'">';
        if (empty($tag->pict)) {
            $imagePath = '';
        }

        $contentText = '';
        $varFields['{intro}'] = $element->intro;
        $varFields['{content}'] = $element->content;
        if (in_array('intro', $tag->display)) $contentText .= $varFields['{intro}'];
        if (in_array('content', $tag->display)) $contentText .= $varFields['{content}'];

        $varFields['{intro}'] = $this->formatText($varFields['{intro}']);
        $varFields['{content}'] = $this->formatText($varFields['{content}']);
        $contentText = $this->formatText($contentText);

        $customFields = [];
        $rawCategories = acym_loadObjectList(
            'SELECT category.id, category.title 
                FROM #__easyblog_category AS category 
                JOIN #__easyblog_post_category AS map 
                    ON category.id = map.category_id 
                WHERE category.published = 1 
                    AND map.post_id = '.intval($element->id)
        );

        $varFields['{cat}'] = [];
        foreach ($rawCategories as $category) {
            $linkCat = $this->finalizeLink('index.php?option=com_easyblog&view=categories&layout=listings&id='.$category->id, $tag);
            $varFields['{cat}'][] = '<a href="'.$linkCat.'" target="_blank">'.acym_escape($category->title).'</a>';
        }
        $varFields['{cat}'] = implode(', ', $varFields['{cat}']);
        if (in_array('cat', $tag->display)) {
            $customFields[] = [
                $varFields['{cat}'],
                acym_translation('ACYM_CATEGORIES'),
            ];
        }

        $readMoreText = acym_translation('ACYM_READ_MORE');
        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'"><span class="acymailing_readmore">'.acym_escape(
                $readMoreText
            ).'</span></a>';
        if (in_array('readmore', $tag->display)) $afterArticle .= $varFields['{readmore}'];


        $customFieldsValues = acym_loadObjectList(
            'SELECT `values`.`value`, `field`.`title`, `field`.`type`, `field`.`options`, `field`.`id` 
                FROM #__easyblog_fields_values AS `values` 
                JOIN #__easyblog_fields AS `field` 
                    ON `field`.`id` = `values`.`field_id` 
                WHERE `field`.`state` = 1 
                    AND `values`.`post_id` = '.intval($tag->id).' 
                ORDER BY `field`.`group_id` ASC, `field`.`title` ASC'
        );

        $formattedFields = [];
        foreach ($customFieldsValues as $oneField) {
            if (empty($oneField->value)) continue;

            if (in_array($oneField->type, ['radio', 'select'])) {
                $options = json_decode($oneField->options);
                foreach ($options as $oneOption) {
                    if ($oneField->value === $oneOption->value) {
                        $oneField->value = $oneOption->title;
                        break;
                    }
                }
            } elseif ($oneField->type === 'date') {
                $oneField->value = acym_date($oneField->value, 'ACYM_DATE_FORMAT_LC1', false);
            } elseif ($oneField->type === 'hyperlink') {
                $options = json_decode($oneField->value);
                $oneField->value = '<a href="'.$options->url.'" target="_blank">'.$options->textlink.'</a>';
            }

            $varFields['{'.$oneField->title.'}'] = $oneField->value;
            $formattedFields[$oneField->id.'-'.$oneField->title][] = $oneField;
        }

        if (!empty($tag->custom)) {
            $tag->custom = explode(',', $tag->custom);
            acym_arrayToInteger($tag->custom);

            foreach ($formattedFields as $title => $oneField) {
                if (!in_array($oneField->id, $tag->custom)) continue;
                $title = substr($title, strpos($title, '-') + 1);
                $customFields[] = [
                    implode(', ', $oneField),
                    $title,
                ];
            }
        }

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = $title;
        $format->afterTitle = $afterTitle;
        $format->afterArticle = $afterArticle;
        $format->imagePath = $imagePath;
        $format->description = $contentText;
        $format->link = empty($tag->clickable) && empty($tag->clickableimg) ? '' : $link;
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }

    private function formatText($text)
    {
        $text = str_replace('"//localhost', '"https://localhost', $text);
        $text = preg_replace('#(<img[^>]*src=")(//[^>]*>)#Uis', '$1https:$2', $text);
        $text = preg_replace(
            '#\[embed=videolink][^}]*video":"([^"]*)[^}]*}\[/embed]#i',
            '<a target="_blank" href="$1"><img src="https://img.youtube.com/vi/0.jpg" alt="youtube video"/></a>',
            $text
        );
        $text = preg_replace(
            '#<video[^>]*src="([^"]*)"[^>]*>[^>]*</video>#i',
            '<a target="_blank" href="$1"><img src="https://img.youtube.com/vi/0.jpg" alt="youtube video"/></a>',
            $text
        );

        return $text;
    }
}
