<?php

use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Helpers\TabHelper;

class plgAcymFlexicontent extends acymPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->installed = acym_isExtensionActive('com_flexicontent');

        $this->pluginDescription->name = 'FLEXIContent';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            $this->initDisplayOptions();
            $this->initCustomView();

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->replaceOptions, $this->elementOptions),
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

    public function initDisplayOptions()
    {
        $fields = acym_loadObjectList('SELECT * FROM #__flexicontent_fields WHERE `published` = 1 ORDER BY `iscore` DESC, `ordering` ASC');
        foreach ($fields as $field) {
            if ($field->field_type == 'title' && empty($this->mainFields['title'])) $this->mainFields['title'] = $field->id;
            if ($field->field_type == 'maintext' && empty($this->mainFields['maintext'])) $this->mainFields['maintext'] = $field->id;
            $this->displayOptions[$field->id] = [$field->label, in_array($field->field_type, ['title', 'maintext'])];
        }
    }

    public function initElementOptionsCustomView()
    {
        $query = 'SELECT content.*, user.name AS creator, user_bis.name AS modifier, types.name AS doctype, f.title AS cattitle ';
        $query .= 'FROM #__content AS content JOIN #__flexicontent_items_ext AS items ON items.item_id = content.id ';
        $query .= 'JOIN #__flexicontent_types AS types ON types.id = items.type_id LEFT JOIN #__users AS user ON content.created_by = user.id ';
        $query .= 'LEFT JOIN #__users AS user_bis ON content.modified_by = user_bis.id LEFT JOIN #__categories AS f ON content.catid = f.id ';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
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
        $format->imagePath = '';
        $format->description = '{picthtml} <br/> {introtext} <br/> {readmore}';
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

    public function getPossibleIntegrations()
    {
        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'hide') return null;

        return $this->pluginDescription;
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;

        // Get the categories, always with the columns "id", "parent_id" and "title". Use the MySQL "AS" if needed
        $this->categories = acym_loadObjectList(
            'SELECT id, parent_id, title
            FROM `#__categories` 
            WHERE extension = "com_content"'
        );

        $optionsFields['readmore'] = ['ACYM_READ_MORE', true];

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
        //we load all elements with the categories
        $this->querySelect = 'SELECT element.id, element.title, element.publish_up ';
        $this->query = 'FROM #__content AS element ';
        $this->query .= 'JOIN #__flexicontent_items_ext AS items ON element.id = items.item_id ';
        $this->filters = [];
        $this->filters[] = 'element.state = 1';
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
        require_once JPATH_SITE.DS.'components'.DS.'com_content'.DS.'helpers'.DS.'route.php';
        require_once JPATH_ADMINISTRATOR.DS.'components/com_flexicontent/defineconstants.php';
        JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_flexicontent'.DS.'tables');
        require_once JPATH_SITE.DS.'components/com_flexicontent/classes/flexicontent.fields.php';
        require_once JPATH_SITE.DS.'components/com_flexicontent/classes/flexicontent.helper.php';
        require_once JPATH_SITE.DS.'components/com_flexicontent/models/'.FLEXI_ITEMVIEW.'.php';

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

            $query = 'SELECT DISTINCT element.`id` FROM #__content AS element JOIN #__flexicontent_items_ext AS items ON element.id = items.item_id ';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $where[] = 'element.catid IN ('.implode(',', $selectedArea).')';
            }

            $where[] = 'element.state = 1';
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

            if (!empty($parameter->language) && $parameter->language !== 'any') {
                $where[] = 'element.language IN ("*", '.acym_escapeDB($parameter->language).')';
            }

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'element');
        }

        return $this->generateCampaignResult;
    }

    public function initIndividualContent(&$tag, $query)
    {
        $itemsParts = acym_loadObjectList($query);

        if (empty($itemsParts)) {
            if (acym_isAdmin()) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_CONTENT_NOT_FOUND', $tag->id), 'notice');
            }

            return false;
        }

        $item = new stdClass();

        foreach ($itemsParts as $part) {
            $item->{$part->name} = $part->value;
        }

        if (empty($tag->display)) {
            $tag->display = [];
        } else {
            $tag->display = explode(',', $tag->display);
        }

        return $item;
    }

    public function replaceIndividualContent(&$tag)
    {
        if (empty($tag->display)) return '';
        $tag->display = explode(',', $tag->display);

        $format = new stdClass();

        $query = 'SELECT content.*, user.name AS creator, user_bis.name AS modifier, types.name AS doctype, f.title AS cattitle ';
        $query .= 'FROM #__content AS content JOIN #__flexicontent_items_ext AS items ON items.item_id = content.id ';
        $query .= 'JOIN #__flexicontent_types AS types ON types.id = items.type_id LEFT JOIN #__users AS user ON content.created_by = user.id ';
        $query .= 'LEFT JOIN #__users AS user_bis ON content.modified_by = user_bis.id LEFT JOIN #__categories AS f ON content.catid = f.id WHERE content.id = '.intval($tag->id);
        $item = acym_loadObject($query);

        if (empty($item)) return '';

        $varFields = [];
        $link = 'index.php?option=com_flexicontent&view=item&cid='.$item->catid.'&id='.$tag->id.':'.$item->alias;
        $link = $this->finalizeLink($link);
        $varFields['{link}'] = $link;
        $format->link = empty($tag->clickable) ? '' : $link;

        $format->afterArticle = '';

        $readMoreText = acym_translation('ACYM_READ_MORE');
        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'"><span class="acymailing_readmore">'.acym_escape(
                $readMoreText
            ).'</span></a>';
        if (in_array('readmore', $tag->display)) {
            unset($tag->display[array_search('readmore', $tag->display)]);
            $format->afterArticle .= $varFields['{readmore}'];
        }


        $format->imagePath = '';
        $varFields['{picthtml}'] = '';
        if (!empty($item->images)) {
            $images = json_decode($item->images);
            $pictVar = empty($images->image_fulltext) ? 'image_intro' : 'image_fulltext';
            if (!empty($images->$pictVar)) {
                $format->imagePath = acym_rootURI().$images->$pictVar;
                $varFields['{picthtml}'] = '<img alt="" src="'.acym_escape($format->imagePath).'" />';
            }
        }
        if (empty($tag->pict)) $format->imagePath = '';

        $fields = acym_loadObjectList('SELECT * FROM #__flexicontent_fields', 'name');
        $fieldsId = [];
        foreach ($fields as $field) {
            $fieldsId[$field->id] = $field;
        }

        $allCats = acym_loadObjectList(
            'SELECT cat.title, cat.id FROM #__categories AS cat JOIN #__flexicontent_cats_item_relations AS catitem ON cat.id = catitem.catid WHERE catitem.itemid = '.intval(
                $tag->id
            ).' ORDER BY cat.title'
        );
        $cats = [];
        foreach ($allCats as $key => $cat) {
            $link = 'index.php?option=com_flexicontent&view=category&layout=mcats&cids[0]='.$cat->id;
            $link = $this->finalizeLink($link);
            $cats[] = '<a target="_blank" href="'.$link.'">'.$cat->title.'</a>';
        }

        foreach ($item as $field => $value) {
            $varFields['{'.$field.'}'] = $value;
        }

        if (in_array($fields['title']->id, $tag->display)) {
            $format->title = $item->title;
        }

        // HANDLE CORE FIELDS

        $format->description = '';
        if ((in_array($fields['text']->id, $tag->display)) && !(empty($item->introtext) && !empty($item->fulltext))) {
            $format->description = $item->introtext.$item->fulltext;
        }

        $format->customFields = [];

        foreach ($tag->display as $key => $oneField) {
            if ((is_numeric(
                        $oneField
                    ) && $fieldsId[$oneField]->iscore != 1) || ($fieldsId[$oneField]->field_type == 'title' || $fieldsId[$oneField]->field_type == 'text')) {
                continue;
            } // Not a core field
            $oneFieldObject = $fieldsId[$oneField];
            $oneField = $oneFieldObject->name;
            if (!isset($item->$oneField) && $oneFieldObject->field_type != 'categories' && $oneFieldObject->field_type != 'tags') continue;

            if ($oneField == 'created_by') {
                $displayedValue = $item->creator;
            } elseif ($oneField == 'modified_by') {
                $displayedValue = $item->modifier;
            } elseif ($oneField == 'document_type') {
                $displayedValue = $item->doctype;
            } elseif ($oneField == 'categories') {
                $displayedValue = empty($cats) ? '' : implode(', ', $cats);
            } elseif ($oneField == 'tags') {
                $tagsArticle = acym_loadObjectList(
                    'SELECT tags.id, tags.name, tags.alias 
                    FROM #__flexicontent_fields AS fields 
                    JOIN #__flexicontent_items_versions AS items ON items.field_id = fields.id 
                    JOIN #__flexicontent_tags AS tags ON items.value = tags.id 
                    WHERE items.item_id = '.intval($tag->id).' 
                        AND fields.field_type = "tags"'
                );

                $flexiTags = [];
                foreach ($tagsArticle as $oneFlexiTag) {
                    $flexiTags[] = '<a target="_blank" href="index.php?option=com_flexicontent&view=tags&id='.$oneFlexiTag->id.':'.$oneFlexiTag->alias.'">'.$oneFlexiTag->name.'</a>';
                }

                $displayedValue = implode(', ', $flexiTags);
            } elseif ($oneField == 'created' || $oneField == 'modified') {
                if ($item->$oneField == '0000-00-00 00:00:00') continue;
                $displayedValue = acym_date($item->$oneField, acym_translation('DATE_FORMAT_LC'));
            } elseif ($oneField == 'voting') {
                $votesdb = acym_loadObject('SELECT * FROM #__content_rating AS a WHERE content_id = '.intval($tag->id));
                $displayedValue = empty($votesdb) || empty($votesdb->rating_count)
                    ? acym_translation('ACYM_NONE')
                    : number_format(
                        $votesdb->rating_sum / $votesdb->rating_count,
                        1
                    ).'/5';
            } else {
                $displayedValue = $item->$oneField;
            }

            $format->customFields[] = [
                $displayedValue,
                $fields[$oneField]->label,
            ];
            $varFields['{'.$oneField.'}'] = $displayedValue;
        }

        // HANDLE CUSTOM FIELDS

        $customFieldToDisplay = [];
        foreach ($tag->display as $fieldToDisplay) {
            if (is_numeric($fieldToDisplay)) $customFieldToDisplay[] = $fieldToDisplay;
        }

        acym_arrayToInteger($customFieldToDisplay);

        //load item's data
        $query = 'SELECT fields.id, fields.name AS fieldname, fields.attribs, fields_item.value ';
        $query .= 'FROM `#__flexicontent_fields` AS fields ';
        $query .= 'JOIN `#__flexicontent_fields_item_relations` AS fields_item ';
        $query .= 'ON fields.id = fields_item.field_id '.'WHERE fields_item.item_id = '.intval($tag->id);
        $query .= ' AND fields_item.field_id IN ('.implode(', ', $customFieldToDisplay).') AND fields.iscore = 0 ';
        $query .= 'ORDER by fields.ordering';

        $results = acym_loadObjectList($query);

        if (!empty($results)) {
            //Retrieving the HTML display of FLEXIcontent's fields

            $values = [];
            foreach ($results as $oneField) {
                //for flexicontent 0 is a default value for "no" in checkbox select etc
                if ((empty($oneField->value) && $oneField->value != 0) || $oneField->value == '0000-00-00 00:00:00') {
                    continue;
                }
                if (empty($values[$oneField->fieldname])) $values[$oneField->fieldname] = [];
                $values[$oneField->fieldname][] = $oneField->value;
            }

            //we need this to make FC functions work
            $item->favs = $item->fav = $item->vote = $item->tags = $item->type_id = null;

            //we need cat's information
            $item->cats = [];
            $item->parameters = JComponentHelper::getParams('com_flexicontent');
            $item->cats[] = acym_loadObject('SELECT * FROM #__categories WHERE id = '.intval($item->catid));
            $item->cats[0]->slug = $item->cats[0]->alias;
            $item->creator = null;
            $item->modifier = null;
            $item->typename = null;
            $item->slug = $item->alias;

            ksort($values);
            foreach ($values as $i => $onevalue) {
                //we have to create a copy of the field, otherwise it takes a pointer and it can not take right information
                $myfield = clone($fields[$i]);
                $newfield = FlexicontentFields::renderField($item, $myfield, $onevalue);
                if (!empty($newfield->display)) {
                    $dispVal = '';
                    if (!empty($newfield->thumbs_src['large'])) {
                        foreach ($newfield->thumbs_src['large'] as $path) {
                            $dispVal .= '<a href="'.$link.'"><img src="'.$path.'" alt="'.$newfield->label.'"/></a>';
                        }
                        $varFields['{'.$i.'}'] = $path;
                    } elseif (in_array($newfield->field_type, ['select', 'selectmultiple', 'checkbox'])) {
                        $dispVal = $newfield->display;
                    } elseif ($newfield->field_type == 'email' && !empty($newfield->value[0])) {
                        $newfield->value = unserialize($newfield->value[0]);
                        if (!empty($newfield->value) && !empty($newfield->value['addr'])) $dispVal = '<a href="mailto:'.$newfield->value['addr'].'">'.$newfield->value['addr'].'</a>';
                    } else {
                        // if something is added after the posttext, remove it !
                        $newfield->posttext = $newfield->parameters->get('posttext');
                        if (!empty($newfield->posttext) && preg_match('#'.$newfield->posttext.'$#i', $newfield->display) == 0) {
                            $newfield->display = str_replace(substr($newfield->display, strrpos($newfield->display, $newfield->posttext)), $newfield->posttext, $newfield->display);
                        }

                        if (strlen($newfield->display) < 5 && !empty($newfield->value[0])) {
                            $newfield->display = $newfield->value[0];
                        }

                        $dispVal = str_replace('/administrator/', '/', $newfield->display);
                        $varFields['{'.$i.'}'] = $dispVal;
                    }

                    if (!empty($dispVal)) {
                        $format->customFields[] = [
                            $dispVal,
                            $newfield->label,
                        ];
                    }
                }
            }
        }

        $format->description = preg_replace('#<div class="multiBoxDesc[^<>]*> *</div>#i', '', $format->description);
        $format->description = preg_replace('#&nbsp;#i', ' ', $format->description);

        $format->description = '<table>'.$format->description.'</table>';

        $format->tag = $tag;

        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }
}
