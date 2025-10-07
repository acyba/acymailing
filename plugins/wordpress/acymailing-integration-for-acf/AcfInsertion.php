<?php

use AcyMailing\Helpers\TabHelper;

trait AcymAcfInsertion
{
    private $currentCategory = null;
    private $postTypes = [];
    private $fieldsByFieldGroup = [];
    private $handledFieldTypes = [
        'text',
        'textarea',
        'number',
        'range',
        'email',
        'url',
        'password',
        'image',
        'file',
        'select',
        'checkbox',
        'radio',
        'true_false',
        'date_picker',
        'date_time_picker',
        'time_picker',
        'color_picker',
        'button_group',
        'link',
    ];

    public function getStandardStructure(&$customView): void
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{title}';
        $format->afterTitle = '';
        $format->afterArticle = '';
        $format->imagePath = '{image}';
        $format->description = '{intro}';
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
        $columns = acym_getColumns('posts', false);

        foreach ($columns as $column) {
            $this->elementOptions[$column] = [$column];
        }
    }

    public function initCustomOptionsCustomView()
    {
        $customFields = acym_loadObjectList('SELECT post_title, post_excerpt, post_content FROM #__posts WHERE post_status = "publish" AND post_type = "acf-field"');
        if (empty($customFields)) {
            return;
        }

        foreach ($customFields as $customField) {
            $settings = unserialize($customField->post_content);
            if (!in_array($settings['type'], $this->handledFieldTypes)) {
                continue;
            }

            $this->customOptions[$customField->post_excerpt] = [$customField->post_title];
        }
    }

    public function insertionOptions($defaultValues = null): void
    {
        $this->initAcfData();

        $this->defaultValues = $defaultValues;

        $tabHelper = new TabHelper();
        $identifier = $this->name;
        $tabHelper->startTab(acym_translation('ACYM_ONE_BY_ONE'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $defaultSizes = ['thumbnail', 'medium', 'medium_large', 'large', 'post-thumbnail'];
        $imageFeaturedSize = ['full' => 'ACYM_ORIGINAL_IMAGE_RESOLUTION'];

        foreach (wp_get_registered_image_subsizes() as $sizeName => $sizes) {
            if (in_array($sizeName, $defaultSizes) && (!empty($sizes['width']) || !empty($sizes['height']))) {
                $imageFeaturedSize[$sizeName] = $sizes['width'].'x'.$sizes['height'].'px';
            }
        }

        $displayOptions = [
            [
                'title' => 'ACYM_DISPLAY',
                'type' => 'checkbox',
                'name' => 'display',
                'options' => $this->displayOptions,
            ],
        ];

        if (!empty($this->fieldsByFieldGroup)) {
            $customFieldsOptions = [];
            foreach ($this->fieldsByFieldGroup as $groupId => $fields) {
                foreach ($fields as $field) {
                    if (!in_array($field['type'], $this->handledFieldTypes)) {
                        continue;
                    }
                    $customFieldsOptions[$field['ID']] = [$field['label'], false, 'data-group-id="'.intval($groupId).'"'];
                }
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
                    'title' => 'ACYM_FEATURED_IMAGE_SIZE',
                    'type' => 'select',
                    'name' => 'size',
                    'options' => $imageFeaturedSize,
                    'default' => 'full',
                ],
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
                    'title' => 'ACYM_REPLACE_SHORTCODES',
                    'type' => 'boolean',
                    'name' => 'replaceshortcode',
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
            ]
        );

        $this->displaySelectionZone($this->getFilteringZone().$this->prepareListing());
        $this->pluginHelper->displayOptions($displayOptions, $identifier, 'individual', $this->defaultValues);
        ?>
		<script>
            jQuery(function ($) {
                const typesGroups = <?php echo wp_json_encode($this->postTypes); ?>;
                $('#plugin_category').on('change', function () {
                    const selectedPostType = parseInt($(this).val());
                    if (selectedPostType === 0) {
                        $('#tab_one_by_one_0 [data-group-id]').show();
                        return;
                    }

                    if (typesGroups[selectedPostType]) {
                        const groupIds = typesGroups[selectedPostType]['field_groups'];
                        $('#tab_one_by_one_0 [data-group-id]').each(function () {
                            if (groupIds.indexOf(parseInt($(this).attr('data-group-id'))) === -1) {
                                $(this).hide();
                            } else {
                                $(this).show();
                            }
                        });
                    }
                });
            });
		</script>
        <?php
        $tabHelper->endTab();
        $identifier = 'auto'.$this->name;
        $tabHelper->startTab(acym_translation('ACYM_POST_TYPE'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $catOptions = [
            [
                'title' => 'ACYM_ORDER_BY',
                'type' => 'select',
                'name' => 'order',
                'options' => [
                    'ID' => 'ACYM_ID',
                    'post_date' => 'ACYM_PUBLISHING_DATE',
                    'post_modified' => 'ACYM_MODIFICATION_DATE',
                    'post_title' => 'ACYM_TITLE',
                    'menu_order' => 'ACYM_MENU_ORDER',
                    'rand' => 'ACYM_RANDOM',
                ],
            ],
        ];

        $this->autoContentOptions($catOptions);
        $this->autoCampaignOptions($catOptions);

        $displayOptions = array_merge($displayOptions, $catOptions);

        $this->displaySelectionZone($this->getCategoryListing(false));
        $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);
        ?>
		<script>
            jQuery(function ($) {
                const typesGroups = <?php echo wp_json_encode($this->postTypes); ?>;
                $('#tab_post_type_1 .acym__popup__listing .acym__listing__row__popup').on('click', function () {
                    showFieldsForSelectedTypes();
                });

                function showFieldsForSelectedTypes() {
                    const $selectedPostTypes = $('#tab_post_type_1 .selected_row');
                    if ($selectedPostTypes.length === 0) {
                        $('#tab_post_type_1 [data-group-id]').show();
                        return;
                    }

                    $('#tab_post_type_1 [data-group-id]').hide();

                    $selectedPostTypes.each(function () {
                        const postTypeId = $(this).attr('data-id');
                        if (typesGroups[postTypeId]) {
                            const groupIds = typesGroups[postTypeId]['field_groups'];
                            $('#tab_post_type_1 [data-group-id]').each(function () {
                                if (groupIds.indexOf(parseInt($(this).attr('data-group-id'))) !== -1) {
                                    $(this).show();
                                }
                            });
                        }
                    });
                }

                showFieldsForSelectedTypes();
            });
		</script>
        <?php
        $tabHelper->endTab();
        $tabHelper->display('plugin');
    }

    public function prepareListing(): string
    {
        $this->initAcfData();

        $this->querySelect = 'SELECT post.ID, post.post_title, post.post_type, post.post_date, post.post_content ';
        $this->query = 'FROM #__posts AS post ';
        $this->filters = [];
        $this->filters[] = 'post.post_status = "publish"';
        $this->searchFields = ['post.ID', 'post.post_title'];
        $this->pageInfo->order = 'post.ID';
        $this->elementIdTable = 'post';
        $this->elementIdColumn = 'ID';

        parent::prepareListing();

        if (empty($this->pageInfo->filter_cat) || empty($this->postTypes[$this->pageInfo->filter_cat])) {
            $securedPostTypes = [];
            foreach ($this->postTypes as $postType) {
                $securedPostTypes[] = acym_escapeDB($postType['post_type']);
            }
            $this->filters[] = 'post.post_type IN ('.implode(',', $securedPostTypes).')';
        } else {
            $this->filters[] = 'post.post_type = '.acym_escapeDB($this->postTypes[$this->pageInfo->filter_cat]['post_type']);
        }

        $rows = $this->getElements();
        foreach ($rows as $row) {
            if (str_replace(['wp:core-embed', 'wp:shortcode'], '', $row->post_content) !== $row->post_content) {
                $row->post_title = acym_tooltip(
                        [
                            'hoveredText' => '<i class="acymicon-exclamation-triangle"></i>',
                            'textShownInTooltip' => acym_translation('ACYM_SPECIAL_CONTENT_WARNING'),
                        ]
                    ).$row->post_title;
            }
        }

        $listingOptions = [
            'header' => [
                'post_title' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '5',
                ],
                'post_type' => [
                    'label' => 'ACYM_POST_TYPE',
                    'size' => '3',
                ],
                'post_date' => [
                    'label' => 'ACYM_PUBLISHING_DATE',
                    'size' => '3',
                    'type' => 'date',
                ],
                'ID' => [
                    'label' => 'ACYM_ID',
                    'size' => '1',
                    'class' => 'text-center',
                ],
            ],
            'id' => 'ID',
            'rows' => $rows,
        ];

        return $this->getElementsListing($listingOptions);
    }

    public function replaceContent(&$email, $send): void
    {
        $this->initAcfData();
        $this->replaceMultiple($email);
        $this->replaceOne($email);
    }

    public function generateByCategory(&$email): stdClass
    {
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $this->tags = [];

        if (empty($tags)) {
            return $this->generateCampaignResult;
        }

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) {
                continue;
            }

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);

            if (empty($selectedArea)) {
                $securedPostTypes = [];
                foreach ($this->postTypes as $postType) {
                    $securedPostTypes[] = acym_escapeDB($postType['post_type']);
                }
                $where[] = 'post.post_type IN ('.implode(',', $securedPostTypes).')';
            } else {
                foreach ($selectedArea as $key => $postTypeId) {
                    if (!empty($this->postTypes[$postTypeId])) {
                        $selectedArea[$key] = acym_escapeDB($this->postTypes[$postTypeId]['post_type']);
                    }
                }
                $where[] = 'post.post_type IN ('.implode(',', $selectedArea).')';
            }

            $where[] = 'post.post_status = "publish"';
            if (!empty($parameter->min_publish)) {
                $parameter->min_publish = acym_date(acym_replaceDate($parameter->min_publish), 'Y-m-d H:i:s', false);
                $where[] = 'post.post_date_gmt >= '.acym_escapeDB($parameter->min_publish);
            }

            if (!empty($parameter->onlynew)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $where[] = 'post.post_date_gmt > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                }
            }

            $query = 'SELECT DISTINCT post.`ID` 
                    FROM #__posts AS post 
                    WHERE ('.implode(') AND (', $where).')';

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'post');
        }

        return $this->generateCampaignResult;
    }

    public function replaceIndividualContent($tag): string
    {
        $query = 'SELECT post.*, `user`.`user_nicename`, `user`.`display_name` 
                    FROM #__posts AS post 
                    LEFT JOIN #__users AS `user` 
                        ON `user`.`ID` = `post`.`post_author` 
                    WHERE post.ID = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);
        if (empty($element)) {
            return '';
        }

        $varFields = $this->getCustomLayoutVars($element);

        $link = get_permalink($element->ID);
        $link = $this->getLinkTranslated($link);
        $varFields['{link}'] = $link;

        $title = '';
        $varFields['{title}'] = $element->post_title;
        if (in_array('title', $tag->display)) {
            $title = $varFields['{title}'];
        }

        $afterTitle = '';
        $afterArticle = '';

        $imagePath = '';
        $imageId = get_post_thumbnail_id($tag->id);
        if (!empty($imageId)) {
            if (empty($tag->size)) $tag->size = 'full';
            $imagePath = get_the_post_thumbnail_url($tag->id, $tag->size);
        }
        $varFields['{image}'] = $imagePath;
        // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
        $varFields['{picthtml}'] = '<img class="content_main_image" alt="" src="'.$imagePath.'">';
        if (!in_array('image', $tag->display)) {
            $imagePath = '';
        }

        $contentText = '';
        $varFields['{excerpt}'] = $this->cleanExtensionContent($element->post_excerpt);
        if (in_array('excerpt', $tag->display) && !empty($varFields['{excerpt}'])) {
            $contentText .= '<p>'.$varFields['{excerpt}'].'</p>';
        }

        $varFields['{content}'] = $this->cleanExtensionContent($element->post_content);
        $varFields['{intro}'] = $this->cleanExtensionContent($this->getIntro($element->post_content));

        if (!empty($tag->replaceshortcode)) {
            $varFields['{content}'] = $this->replaceShortcode($varFields['{content}']);
            $varFields['{intro}'] = $this->replaceShortcode($varFields['{intro}']);
        }

        if (in_array('content', $tag->display)) {
            $contentText .= $varFields['{content}'];
        } elseif (in_array('intro', $tag->display)) {
            $contentText .= $varFields['{intro}'];
        }

        $customFields = [];
        $varFields['{author}'] = empty($element->display_name) ? $element->user_nicename : $element->display_name;
        if (in_array('author', $tag->display) && !empty($varFields['{author}'])) {
            $customFields[] = [
                $varFields['{author}'],
                acym_translation('ACYM_AUTHOR'),
            ];
        }

        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'">';
        $varFields['{readmore}'] .= '<span class="acymailing_readmore">'.acym_escape(acym_translation('ACYM_READ_MORE')).'</span>';
        $varFields['{readmore}'] .= '</a>';

        if (in_array('readmore', $tag->display)) {
            $afterArticle .= $varFields['{readmore}'];
        }

        $allowedFields = [];
        foreach ($this->postTypes as $postType) {
            if ($postType['post_type'] !== $element->post_type) {
                continue;
            }

            foreach ($postType['field_groups'] as $fieldGroupId) {
                if (!empty($this->fieldsByFieldGroup[$fieldGroupId])) {
                    $allowedFields = array_merge($allowedFields, $this->fieldsByFieldGroup[$fieldGroupId]);
                }
            }
        }

        $tag->custom = empty($tag->custom) ? [] : explode(',', $tag->custom);

        foreach ($allowedFields as $field) {
            $varFields['{'.$field['name'].'}'] = acf_get_value($tag->id, $field);

            if (empty($varFields['{'.$field['name'].'}']) && (!is_string($varFields['{'.$field['name'].'}']) || strlen($varFields['{'.$field['name'].'}']) === 0)) {
                continue;
            }

            $varFields['{'.$field['name'].'}'] = $this->getFormattedFieldValue($varFields['{'.$field['name'].'}'], $tag->id, $field);

            if (in_array($field['ID'], $tag->custom) && strlen($varFields['{'.$field['name'].'}']) > 0) {
                $customFields[] = [
                    $varFields['{'.$field['name'].'}'],
                    acf_get_field_label($field),
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

    protected function getTranslationId($elementId, $translationTool, $defaultLanguage = false): int
    {
        $elementId = intval($elementId);
        $languageCode = $this->emailLanguage;

        if ($defaultLanguage) {
            $languageCode = $this->config->get('multilingual_default', ACYM_DEFAULT_LANGUAGE);
        } else {
            $idDefaultLanguage = $this->getTranslationId($elementId, $translationTool, true);

            // We only translate inserted articles of the default language
            if ($idDefaultLanguage !== $elementId) {
                return $elementId;
            }
        }

        $languageCode = substr($languageCode, 0, 2);

        if ($translationTool === 'polylang') {
            if (acym_isExtensionActive('polylang/polylang.php') && function_exists('pll_get_post')) {
                $translationId = pll_get_post($elementId, $languageCode);
                if (!empty($translationId)) $elementId = $translationId;
            }
        } elseif ($translationTool === 'wpml') {
            if (acym_isExtensionActive('sitepress-multilingual-cms/sitepress.php')) {
                $elementId = apply_filters('wpml_object_id', $elementId, 'post', true, $languageCode);
            }
        }

        return intval($elementId);
    }

    private function initAcfData()
    {
        if (!$this->installed || !empty($this->postTypes)) {
            return;
        }

        $acfPostTypes = acf_get_acf_post_types();
        if (empty($acfPostTypes)) {
            return;
        }

        foreach ($acfPostTypes as $acfPostType) {
            if (empty($acfPostType['active'])) {
                continue;
            }

            $fieldGroups = acf_get_field_groups(['post_type' => $acfPostType['post_type']]);
            $fieldGroupIds = [];
            foreach ($fieldGroups as $fieldGroup) {
                if (empty($fieldGroup['active'])) {
                    continue;
                }

                $fieldGroupIds[] = $fieldGroup['ID'];
                $this->fieldsByFieldGroup[$fieldGroup['ID']] = acf_get_fields($fieldGroup);
            }

            $this->postTypes[intval($acfPostType['ID'])] = [
                'post_type' => $acfPostType['post_type'],
                'field_groups' => $fieldGroupIds,
            ];
            $this->categories[] = (object)[
                'id' => $acfPostType['ID'],
                'parent_id' => $this->rootCategoryId,
                'title' => $acfPostType['title'],
            ];
        }
    }

    private function getFormattedFieldValue($value, $postId, $field)
    {
        $value = acf_format_value($value, $postId, $field);

        if ($field['type'] === 'email') {
            $value = '<a href="mailto:'.$value.'">'.$value.'</a>';
        } elseif ($field['type'] === 'url') {
            $value = '<a href="'.$value.'" target="_blank">'.$value.'</a>';
        } elseif ($field['type'] === 'image') {
            if (empty($value['url'])) {
                $value = '';
            } else {
                $alt = acym_escape($value['alt']);

                $value = '<img alt="'.$alt.'" src="'.$value['url'].'" />';
            }
        } elseif (in_array($field['type'], ['checkbox', 'select'])) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
        } elseif ($field['type'] === 'true_false') {
            $value = empty($value) ? acym_translation('ACYM_NO') : acym_translation('ACYM_YES');
        } elseif ($field['type'] === 'file') {
            if (empty($value['link'])) {
                $value = '';
            } else {
                $value = '<a href="'.$value['link'].'" target="_blank">'.acym_escape(
                        $value['filename']
                    ).'</a>';
            }
        } elseif ($field['type'] === 'textarea') {
            $value = nl2br($value);
        } elseif (in_array($field['type'], ['time_picker', 'date_picker', 'date_time_picker'])) {
            if ($field['display_format'] !== $field['return_format']) {
                $value = gmdate($field['display_format'], strtotime($value));
            }
        } else {
            $value = acym_escape($value);
        }

        return $value;
    }
}
