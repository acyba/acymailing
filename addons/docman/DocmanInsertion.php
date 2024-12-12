<?php

use AcyMailing\Helpers\TabHelper;

trait DocmanInsertion
{
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
        $format->description = '{intro}<br/>{details}';
        $format->link = '{link}';
        $format->customFields = [];
        $customView = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';
    }

    public function initReplaceOptionsCustomView()
    {
        $this->replaceOptions = [
            'link' => ['ACYM_LINK'],
            'linkdownload' => ['ACYM_LINK_DOWNLOAD'],
            'picthtml' => ['ACYM_IMAGE_HTML_TAG'],
            'readmore' => ['ACYM_READ_MORE'],
        ];
    }

    public function initElementOptionsCustomView()
    {
        $query = 'SELECT documents.*, category.title AS category_title, category.slug AS category_slug, user.username FROM #__docman_documents AS documents';
        $query .= ' JOIN #__docman_categories AS category ON documents.docman_category_id = category.docman_category_id';
        $query .= ' JOIN #__users AS user ON category.created_by = user.id';
        $query .= ' WHERE documents.enabled = 1';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;

        acym_loadLanguageFile('com_docman', JPATH_ADMINISTRATOR.DS.'components'.DS.'com_docman');
        $this->categories = acym_loadObjectList('SELECT `docman_category_id` AS id, 0 AS `parent_id`, `title` FROM `#__docman_categories` WHERE enabled = 1', 'id');

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
                    'name' => 'clickableimage',
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
                    'docman_document_id' => 'ACYM_ID',
                    'publish_on' => 'ACYM_DATE',
                    'title' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
                'default' => 'docman_document_id',
                'defaultdir' => 'desc',
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
        $this->querySelect = 'SELECT document.*';
        $this->query = 'FROM `#__docman_documents` AS document ';
        $this->filters = [];
        $this->filters[] = 'document.enabled = 1';
        $this->searchFields = ['document.docman_document_id', 'document.title'];
        $this->pageInfo->order = 'docman_document_id';
        $this->elementIdTable = 'document';
        $this->elementIdColumn = 'docman_document_id';

        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'author') {
            $this->filters[] = 'document.created_by = '.intval(acym_currentUserId());
        }

        parent::prepareListing();

        if (!empty($this->pageInfo->filter_cat)) {
            $this->filters[] = 'document.`docman_category_id` = '.intval($this->pageInfo->filter_cat);
        }

        $listingOptions = [
            'header' => [
                'title' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '11',
                ],
                'docman_document_id' => [
                    'label' => 'ACYM_ID',
                    'size' => '1',
                    'class' => 'text-center',
                ],
            ],
            'id' => 'docman_document_id',
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
        //load the tags
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $this->tags = [];

        if (empty($tags)) return $this->generateCampaignResult;


        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;
            $selectedArea = $this->getSelectedArea($parameter);

            $query = 'SELECT DISTINCT document.docman_document_id FROM #__docman_documents AS document';

            $where = [];

            if (!empty($selectedArea)) {
                $query .= ' JOIN #__docman_categories AS category ON document.docman_category_id = category.docman_category_id';
                $where[] = 'category.docman_category_id IN ('.implode(',', $selectedArea).')';
            }

            $where[] = 'document.access = 0';
            $where[] = 'document.enabled = 1';
            if (!empty($parameter->min_publish)) {
                $parameter->min_publish = acym_date(acym_replaceDate($parameter->min_publish), 'Y-m-d H:i:s', false);
                $where[] = 'document.publish_on >= '.acym_escapeDB($parameter->min_publish);
            }

            if (!empty($parameter->onlynew)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $where[] = '(document.publish_on > '.acym_escapeDB(
                            acym_date($lastGenerated, 'Y-m-d H:i:s', false)
                        ).' OR (document.publish_on > 0000-00-00 00:00:00 AND created_on > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false)).'))';
                }
            }
            $query .= ' WHERE ('.implode(') AND (', $where).')';


            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter);
        }

        return $this->generateCampaignResult;
    }

    public function replaceIndividualContent($tag)
    {
        $query = 'SELECT documents.*, category.title AS category_title, category.slug AS category_slug, user.username FROM #__docman_documents AS documents';
        $query .= ' JOIN #__docman_categories AS category ON documents.docman_category_id = category.docman_category_id';
        $query .= ' JOIN #__users AS user ON category.created_by = user.id';
        $query .= ' WHERE documents.enabled = 1 AND documents.docman_document_id = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';

        $varFields = $this->getCustomLayoutVars($element);

        //Get the itemId for the link and download link
        $menuId = $this->getParam('itemid');
        if (empty($menuId)) {
            $menuId = acym_loadResult('SELECT id FROM #__menu WHERE link LIKE "%index.php?option=com_docman&view=%list%"');
        }
        $itemId = empty($menuId) ? '' : '&Itemid='.$menuId;

        //We set the link for the title
        $link = 'index.php?option=com_docman&view=document&slug='.$element->slug.$itemId.'&category_slug='.$element->category_slug;
        $link = acym_frontendLink($link, false);
        $varFields['{link}'] = $link;

        //We set the link to download the document
        $linkDownload = 'index.php?option=com_docman&view=download&slug='.$element->slug.$itemId.'&category_slug='.$element->category_slug;
        $linkDownload = $this->finalizeLink($linkDownload, $tag);
        $varFields['{linkdownload}'] = $linkDownload;

        $details = [];
        $varFields['{details}'] = [];
        if (!empty($element->storage_path)) {
            //Display the extension of the file
            preg_match('/\.[^.]+$/i', $element->storage_path, $ext);
            $details[] = [$ext[0], acym_translation('ACYM_FILE_TYPE')];
            $varFields['{details}'][] = acym_translation('ACYM_FILE_TYPE').': '.$ext[0];

            //Display the path file
            $details[] = [$element->storage_path, acym_translation('ACYM_FILE')];
            $varFields['{details}'][] = acym_translation('ACYM_FILE').': '.$element->storage_path;

            //Display the file size
            $filename = ACYM_ROOT.'joomlatools-files'.DS.'docman-files'.DS.$element->storage_path;
            if (file_exists($filename)) {
                $fileSize = $this->bytes2text(filesize($filename));
                $details[] = [$fileSize, acym_translation('ACYM_FILE_SIZE')];
                $varFields['{details}'][] = acym_translation('ACYM_FILE_SIZE').': '.$fileSize;
            }
        }

        //Display the username of the author
        if (!empty($element->username)) {
            $details[] = [$element->username, acym_translation('ACYM_AUTHOR')];
            $varFields['{details}'][] = acym_translation('ACYM_AUTHOR').': '.$element->username;
        }

        //Display the creation date
        if (!empty($element->created_on)) {
            $details[] = [$element->created_on, acym_translation('ACYM_DATE_CREATED')];
            $varFields['{details}'][] = acym_translation('ACYM_DATE_CREATED').': '.$element->created_on;
        }

        $varFields['{details}'] = implode('<br/>', $varFields['{details}']);

        if (!in_array('details', $tag->display)) $details = [];

        $downloadCallToAction = '<a href="'.$linkDownload.'" target="_blank">'.acym_translation('ACYM_DOWNLOAD').'</a>';
        $varFields['{download}'] = $downloadCallToAction;
        if (in_array('download', $tag->display)) $details[] = [$varFields['{download}'], ''];


        $imagePath = ACYM_LIVE.'joomlatools-files/docman-images/'.$element->image;
        $varFields['{picthtml}'] = '<img alt="" src="'.$imagePath.'">';
        $varFields['{image}'] = $imagePath;
        if (!in_array('image', $tag->display)) $imagePath = '';

        $afterArticle = '';
        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'"><span class="acymailing_readmore">'.acym_escape(
                acym_translation('ACYM_READ_MORE')
            ).'</span></a>';
        if (in_array('readmore', $tag->display)) $afterArticle = $varFields['{readmore}'];

        $description = '';
        $varFields['{content}'] = $element->description;
        $varFields['{intro}'] = $this->getIntro($element->description);
        if (in_array('content', $tag->display)) {
            $description .= $varFields['{content}'];
        } elseif (in_array('intro', $tag->display)) {
            $description .= $varFields['{intro}'];
        }

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = $element->title;
        $format->description = $description;
        $format->customFields = $details;
        $format->link = empty($tag->clickable) && empty($tag->clickableimg) ? '' : $link;
        $format->imagePath = $imagePath;
        $format->afterArticle = $afterArticle;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }

    //function to convert bytes into smart display
    private function bytes2text($bytes)
    {
        if ($bytes > 1024) {
            $fileSize = $bytes / 1024;
            if ($fileSize > 1024) {
                $fileSize = $fileSize / 1024;
                if ($fileSize > 1024) {
                    $fileSize = $fileSize / 1024;
                    $fileSize = (int)($fileSize);
                    $fileSize .= " Gigabytes";
                } else {
                    $fileSize = (int)($fileSize);
                    $fileSize .= " Megabytes";
                }
            } else {
                $fileSize = (int)($fileSize);
                $fileSize .= " Kilobytes";
            }
        } else {
            $fileSize = (int)($bytes);
            $fileSize = $fileSize." Bytes";
        }

        return $fileSize;
    }
}
