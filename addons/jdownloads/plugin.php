<?php

use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Helpers\TabHelper;

class plgAcymJdownloads extends acymPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        if (!defined('JPATH_ADMINISTRATOR') || !file_exists(rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.'com_jdownloads'.DS)) {
            $this->installed = false;
        } else {
            $manifest = acym_loadResult('SELECT `manifest_cache` FROM #__extensions WHERE `element` = "com_jdownloads" AND `type` = "component"');

            if (!empty($manifest)) {
                try {
                    $decoded = json_decode($manifest);
                    if (!empty($decoded->version) && version_compare($decoded->version, '3.9.0', '<')) {
                        $this->installed = false;
                    }
                } catch (Exception $exception) {

                }
            }
        }

        $this->pluginDescription->name = 'jDownloads';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'symbol' => ['COM_JDOWNLOADS_CATSLIST_PIC', true],
                'image' => ['ACYM_IMAGE', true],
                'shortdesc' => ['COM_JDOWNLOADS_BACKEND_FILESEDIT_DESCRIPTION_SHORT', true],
                'longdesc' => ['COM_JDOWNLOADS_BACKEND_FILESEDIT_DESCRIPTION_LONG', false],
                'tags' => ['COM_JDOWNLOADS_TAGS_LABEL', false],
                'license' => ['COM_JDOWNLOADS_ACTIONLOG_TYPE_LICENSE', false],
                'author' => ['COM_JDOWNLOADS_FE_SORT_ORDER_AUTHOR', false],
                'creation' => ['COM_JDOWNLOADS_CREATED_DATE', false],
                'cat' => ['ACYM_CATEGORY', false],
                'price' => ['COM_JDOWNLOADS_BACKEND_FILESEDIT_PRICE', true],
                'readmore' => ['ACYM_READ_MORE', true],
            ];

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
        $format->title = '{symbol} {title}';
        $format->afterTitle = '';
        $format->afterArticle = acym_translation('ACYM_PRICE').': {price}<br> {readmore}';
        $format->imagePath = '{image}';
        $format->description = '{shortdesc}';
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
        $query = 'SELECT element.*
                    FROM #__jdownloads_files AS element
                    WHERE element.published = 1';
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

        acym_loadLanguageFile('com_jdownloads', JPATH_ADMINISTRATOR);

        // Get the categories, always with the columns "id", "parent_id" and "title". Use the MySQL "AS" if needed
        $this->categories = acym_loadObjectList(
            'SELECT id, parent_id, title
            FROM `#__jdownloads_categories` 
            WHERE published = "1"'
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
            [
                'title' => 'ACYM_ONLY_FEATURED',
                'type' => 'boolean',
                'name' => 'featured',
                'default' => false,
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
        $this->query = 'FROM #__jdownloads_files AS element ';
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

    public function generateByCategory(&$email)
    {
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $this->tags = [];
        $time = time();

        if (empty($tags)) return $this->generateCampaignResult;

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            $query = 'SELECT DISTINCT element.`id` FROM #__jdownloads_files AS element ';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $where[] = 'element.catid IN ('.implode(',', $selectedArea).')';
            }

            $where[] = 'element.published = 1';
            $where[] = '`publish_up` < '.acym_escapeDB(date('Y-m-d H:i:s', $time - date('Z')));
            $where[] = '`publish_down` > '.acym_escapeDB(date('Y-m-d H:i:s', $time - date('Z'))).' OR `publish_down` = 0';

            if ($parameter->featured) $where[] = 'element.featured = 1';

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

    protected function loadLibraries($email)
    {
        $params = JComponentHelper::getParams('com_jdownloads');

        $this->symbolWidth = $params->get('file_pic_size', '32');
        $this->symbolHeight = $params->get('file_pic_size_height', '32');

        acym_loadLanguageFile('com_jdownloads', JPATH_ADMINISTRATOR);

        return true;
    }

    public function replaceIndividualContent($tag)
    {
        $query = 'SELECT element.*
                    FROM #__jdownloads_files AS element
                    WHERE element.published = 1
                        AND element.id = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';

        $varFields = $this->getCustomLayoutVars($element);

        $completeId = intval($element->id);
        if (!empty($element->alias)) $completeId .= ':'.$element->alias;

        $link = 'index.php?option=com_jdownloads&view=download&id='.$completeId.'&catid='.intval($element->catid);
        $link = $this->finalizeLink($link);
        $varFields['{link}'] = $link;

        $title = '';
        $afterTitle = '';
        $afterArticle = '';
        $imagePath = '';
        $contentText = '';
        $customFields = [];

        $varFields['{title}'] = $element->title;
        if (in_array('title', $tag->display)) $title = $varFields['{title}'];

        $varFields['{symbol}'] = '';
        if (!empty($element->file_pic)) {
            $varFields['{symbol}'] = '<img 
                            alt="file symbol" 
                            style="float:left;margin:5px;max-width: '.$this->symbolWidth.'px;max-height: '.$this->symbolHeight.'px;" 
                            src="'.acym_rootURI().'images/jdownloads/fileimages/'.$element->file_pic.'" /> ';
        }
        if (in_array('symbol', $tag->display) && !empty($element->file_pic)) {
            $title = $varFields['{symbol}'].$title;
        }

        $varFields['{image}'] = '';
        $varFields['{picthtml}'] = '';
        if (!empty($element->images)) {
            $images = explode('|', $element->images);
            $varFields['{image}'] = acym_rootURI().'images/jdownloads/screenshots/'.$images[0];
            $varFields['{picthtml}'] = '<img alt="" src="'.acym_escape($imagePath).'" />';
        }
        if (in_array('image', $tag->display) && !empty($element->images)) {
            $imagePath = acym_rootURI().'images/jdownloads/screenshots/'.$images[0];
        }

        $varFields['{shortdesc}'] = !empty($element->description) ? $element->description : '';
        if (in_array('shortdesc', $tag->display) && !empty($element->description)) $contentText .= $varFields['{shortdesc}'];

        $varFields['{longdesc}'] = !empty($element->description_long) ? $element->description_long : '';
        if (in_array('longdesc', $tag->display) && !empty($element->description_long)) $contentText .= $element->description_long;

        $varFields['{price}'] = !empty($element->price) ? $element->price : '';
        if (in_array('price', $tag->display) && !empty($element->price)) {
            $customFields[] = [
                $varFields['{price}'],
                acym_translation('COM_JDOWNLOADS_BACKEND_FILESEDIT_PRICE'),
            ];
        }

        $varFields['{author}'] = empty($element->author) ? '' : (!empty($element->url_author) ? '<a href="'.$element->url_author.'" target="_blank">'.$element->author.'</a>' : $element->author);
        if (in_array('author', $tag->display) && !empty($element->author)) {
            $customFields[] = [
                $varFields['{author}'],
                acym_translation('COM_JDOWNLOADS_FE_SORT_ORDER_AUTHOR'),
            ];
        }


        $varFields['{creation}'] = !empty($element->created) ? acym_date($element->created, acym_translation('ACYM_DATE_FORMAT_LC1'), false) : '';
        if (in_array('creation', $tag->display) && !empty($element->created)) {
            $customFields[] = [
                $varFields['{creation}'],
                acym_translation('COM_JDOWNLOADS_CREATED_DATE'),
            ];
        }

        $varFields['{license}'] = '';
        $license = acym_loadObject('SELECT title, url FROM #__jdownloads_licenses WHERE published = 1 AND id = '.intval($element->license));
        if (!empty($license)) {
            $licenseName = $license->title;
            if (!empty($license->url)) {
                $licenseName = '<a href="'.$license->url.'" target="_blank">'.$licenseName.'</a>';
            }
            $varFields['{license}'] = $licenseName;
        }
        if (in_array('license', $tag->display) && !empty($element->license) && !empty($varFields['{license}'])) {
            $customFields[] = [
                $varFields['{license}'],
                acym_translation('COM_JDOWNLOADS_ACTIONLOG_TYPE_LICENSE'),
            ];
        }


        $tags = $this->getElementTags('com_jdownloads.download', $element->id);
        $varFields['{tags}'] = implode(', ', $tags);
        if (in_array('tags', $tag->display) && !empty($varFields['{tags}'])) {
            $customFields[] = [
                $varFields['{tags}'],
                acym_translation('COM_JDOWNLOADS_TAGS_LABEL'),
            ];
        }

        $category = acym_loadResult('SELECT title FROM #__categories WHERE id = '.intval($element->catid));
        $varFields['{cat}'] = '<a href="index.php?option=com_jdownloads&view=category&catid='.$element->catid.'" target="_blank">'.$category.'</a>';
        if (in_array('cat', $tag->display) && !empty($element->catid)) {
            $customFields[] = [
                $varFields['{cat}'],
                acym_translation('ACYM_CATEGORY'),
            ];
        }

        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'">
                                        <span class="acymailing_readmore">'.acym_translation('ACYM_READ_MORE').'</span>
                                    </a>';
        if (in_array('readmore', $tag->display)) $afterArticle .= $varFields['{readmore}'];

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = $title;
        $format->afterTitle = $afterTitle;
        $format->afterArticle = $afterArticle;
        $format->imagePath = $imagePath;
        $format->description = $contentText;
        $format->link = empty($tag->clickable) ? '' : $link;
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }
}
