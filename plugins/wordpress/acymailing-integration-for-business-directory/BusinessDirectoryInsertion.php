<?php

use AcyMailing\Helpers\TabHelper;

trait BusinessDirectoryInsertion
{
    public function getStandardStructure(string &$customView): void
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{title}';
        $format->afterTitle = '{picthtml}';
        $format->afterArticle = '';
        $format->imagePath = '{image}';
        $format->description = '{desc}';
        $format->link = '{link}';
        $format->customFields = [];
        $customView = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';
    }

    public function initReplaceOptionsCustomView(): void
    {
        $this->replaceOptions = [
            'link' => ['ACYM_LINK'],
            'picthtml' => ['ACYM_IMAGE'],
        ];
    }

    public function initElementOptionsCustomView(): void
    {
        $query = 'SELECT post.*
                    FROM #__posts AS post
                    WHERE post.post_type = '.acym_escapeDB($this->wpPostType).' 
                        AND post.post_status = "publish"';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function initCustomOptionsCustomView()
    {
        $fields = acym_loadObjectList('SELECT shortname, label FROM  #__wpbdp_form_fields WHERE association = "meta"');
        foreach ($fields as $field) {
            $this->customOptions[$field->shortname] = [$field->label];
        }
    }

    public function insertionOptions(?object $defaultValues = null): void
    {
        $this->defaultValues = $defaultValues;
        $this->prepareWPCategories($this->wpCategoryType);

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

        $customFields = acym_loadObjectList('SELECT id, label FROM #__wpbdp_form_fields WHERE association = "meta"');

        if (!empty($customFields)) {
            $customFieldsOptions = [];
            foreach ($customFields as $oneField) {
                $customFieldsOptions[$oneField->id] = [$oneField->label, false];
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
            ]
        );

        $zoneContent = $this->getFilteringZone().$this->prepareListing();
        $this->displaySelectionZone($zoneContent);
        $this->pluginHelper->displayOptions($displayOptions, $identifier, 'individual', $this->defaultValues);

        $tabHelper->endTab();

        $identifier = 'auto'.$this->name;
        $tabHelper->startTab(acym_translation('ACYM_BY_CATEGORY'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $orderingOptions = [
            'ID' => 'ACYM_ID',
            'post_date' => 'ACYM_PUBLISHING_DATE',
            'post_modified' => 'ACYM_MODIFICATION_DATE',
            'post_title' => 'ACYM_TITLE',
            'menu_order' => 'ACYM_MENU_ORDER',
            'rand' => 'ACYM_RANDOM',
        ];

        $headerOptions = [];

        if (!empty($customFields)) {
            usort($customFields, function ($a, $b) {
                return $a->label < $b->label ? -1 : 1;
            });
            foreach ($customFields as $oneField) {
                $headerOptions[$oneField->id] = $oneField->label;
            }
        }

        // Keeps key => val association
        $orderingOptions += $headerOptions;
        $headerOptions = [0 => 'ACYM_NONE'] + $headerOptions;

        $catOptions = [
            [
                'title' => 'ACYM_ORDER_BY',
                'type' => 'select',
                'name' => 'order',
                'options' => $orderingOptions,
            ],
            [
                'title' => 'ACYM_HEADER',
                'type' => 'select',
                'name' => 'header',
                'options' => $headerOptions,
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
        $this->querySelect = 'SELECT post.ID, post.post_title, post.post_date ';
        $this->query = 'FROM #__posts AS post ';
        $this->filters = [];
        $this->filters[] = 'post.post_type = '.acym_escapeDB($this->wpPostType);
        $this->filters[] = 'post.post_status = "publish"';
        $this->searchFields = ['post.ID', 'post.post_title'];
        $this->pageInfo->order = 'post.ID';
        $this->elementIdTable = 'post';
        $this->elementIdColumn = 'ID';

        parent::prepareListing();

        if (!empty($this->pageInfo->filter_cat)) {
            $this->query .= 'JOIN #__term_relationships AS cat ON post.ID = cat.object_id';
            $this->filters[] = 'cat.term_taxonomy_id = '.intval($this->pageInfo->filter_cat);
        }

        $listingOptions = [
            'header' => [
                'post_title' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '8',
                ],
                'post_date' => [
                    'label' => 'ACYM_DATE_CREATED',
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
            'rows' => $this->getElements(),
        ];

        return $this->getElementsListing($listingOptions);
    }

    public function replaceContent(object &$email): void
    {
        $this->wpbdpFields = acym_loadObjectList('SELECT * FROM #__wpbdp_form_fields WHERE association = "meta" ORDER BY weight DESC');
        foreach ($this->wpbdpFields as $key => $oneField) {
            $this->wpbdpFields[$key]->field_data = unserialize($oneField->field_data);
        }

        $this->replaceMultiple($email);
        $this->replaceOne($email);
    }

    public function generateByCategory(object &$email): object
    {
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);

        $this->tags = [];

        if (empty($tags)) return $this->generateCampaignResult;

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            $query = 'SELECT DISTINCT post.`ID` 
                    FROM #__posts AS post 
                    LEFT JOIN #__term_relationships AS cat ON post.ID = cat.object_id';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $where[] = 'cat.term_taxonomy_id IN ('.implode(',', $selectedArea).')';
            }

            $where[] = 'post.post_type = '.acym_escapeDB($this->wpPostType);
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

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'post');
        }

        return $this->generateCampaignResult;
    }

    protected function handleOrderBy(&$query, $parameter, $table = null): void
    {
        if (empty($parameter->order)) return;

        $ordering = explode(',', $parameter->order);

        $customFieldId = intval($ordering[0]);
        if (!empty($customFieldId)) {
            $query = str_replace(
                ' WHERE ',
                ' LEFT JOIN #__postmeta AS ordermeta 
                            ON ordermeta.`post_id` = post.`ID` 
                            AND ordermeta.`meta_key` = "_wpbdp[fields]['.intval($customFieldId).']" 
                        WHERE ',
                $query
            );
            $query .= ' ORDER BY ordermeta.`meta_value` '.acym_secureDBColumn(trim($ordering[1]));
        } else {
            parent::handleOrderBy($query, $parameter, $table);
        }
    }

    public function replaceIndividualContent(object $tag): string
    {
        $query = 'SELECT post.*
                    FROM #__posts AS post
                    WHERE post.post_type = '.acym_escapeDB($this->wpPostType).' 
                        AND post.post_status = "publish"
                        AND post.ID = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';

        $varFields = $this->getCustomLayoutVars($element);

        $link = get_permalink($element->ID);
        $varFields['{link}'] = $link;

        $title = '';
        $varFields['{title}'] = $element->post_title;
        if (in_array('title', $tag->display)) {
            $title = $varFields['{title}'];
        }

        $afterTitle = '';
        $imagePath = '';
        $imageId = get_post_thumbnail_id($tag->id);
        if (!empty($imageId)) {
            $imagePath = get_the_post_thumbnail_url($tag->id, 'full');
        }
        $varFields['{image}'] = $imagePath;
        // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
        $varFields['{picthtml}'] = '<img class="content_main_image" alt="" src="'.$imagePath.'">';
        if (!in_array('image', $tag->display)) {
            $imagePath = '';
        }

        $contentText = '';
        $excerpt = $element->post_excerpt;
        if (!empty($excerpt) && strpos($element->post_excerpt, '<') === false) {
            $excerpt = '<p>'.$excerpt.'</p>';
        }
        $varFields['{short_desc}'] = $excerpt;
        $varFields['{desc}'] = $this->cleanExtensionContent($element->post_content);
        if (in_array('short_desc', $tag->display)) {
            $contentText .= $varFields['{short_desc}'];
        }
        if (in_array('desc', $tag->display)) {
            $contentText .= $varFields['{desc}'];
        }

        $customFields = [];
        $varFields['{cats}'] = str_replace('href=', 'target="_blank" href=', get_the_term_list($tag->id, $this->wpCategoryType, '', ', '));
        if (in_array('cats', $tag->display)) {
            $customFields[] = [
                $varFields['{cats}'],
                acym_translation('ACYM_CATEGORIES'),
            ];
        }

        $varFields['{tags}'] = str_replace('href=', 'target="_blank" href=', get_the_term_list($tag->id, $this->wpTagType, '', ', '));
        if (in_array('tags', $tag->display)) {
            $customFields[] = [
                $varFields['{tags}'],
                acym_translation('ACYM_TAGS'),
            ];
        }

        // Custom fields
        if (!empty($tag->custom)) {
            $tag->custom = explode(',', $tag->custom);
            acym_arrayToInteger($tag->custom);
        } else {
            $tag->custom = [];
        }

        $customFieldsValues = [];
        $socialMediaLinks = [];
        foreach ($this->wpbdpFields as $oneField) {
            $customFieldsValues[$oneField->id] = get_post_meta($tag->id, '_wpbdp[fields]['.$oneField->id.']', true);

            $value = '';

            if (!empty($customFieldsValues[$oneField->id])) {
                if ($oneField->field_type === 'url') {
                    if (!empty($customFieldsValues[$oneField->id][0])) {
                        $url = $customFieldsValues[$oneField->id][0];
                        if (empty($customFieldsValues[$oneField->id][1])) {
                            $text = $customFieldsValues[$oneField->id][0];
                        } else {
                            $text = $customFieldsValues[$oneField->id][1];
                        }
                        $value = '<a href="'.$url.'" target="_blank">'.$text.'</a>';
                    }
                } elseif ($oneField->field_type === 'textarea') {
                    $value = nl2br($customFieldsValues[$oneField->id]);
                } elseif (in_array($oneField->field_type, ['checkbox', 'multiselect'])) {
                    $value = preg_replace('#\t#', ', ', $customFieldsValues[$oneField->id]);
                } elseif ($oneField->field_type === 'phone_number') {
                    $iconSrc = ACYM_PLUGINS_URL.'/'.basename(__DIR__).'/phone.svg';
                    // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
                    $icon = '<img alt="phone icon" src="'.esc_url($iconSrc).'" style="width: 13px;display: inline-block;margin-right: 5px;" />';
                    $value = $icon.'<a href="tel:'.$customFieldsValues[$oneField->id].'">'.$customFieldsValues[$oneField->id].'</a>';
                } elseif ($oneField->field_type === 'date') {
                    $format = str_replace(
                        [
                            'dd',
                            'mm',
                            'yyyy',
                            'yy',
                        ],
                        [
                            'd',
                            'm',
                            'Y',
                            'y',
                        ],
                        $oneField->field_data['date_format']
                    );

                    $value = gmdate($format, strtotime($customFieldsValues[$oneField->id]));
                } elseif ($oneField->field_type === 'image') {
                    $field_value = $customFieldsValues[$oneField->id];

                    $img_id = $field_value;
                    $caption = '';

                    if (is_array($field_value)) {
                        $img_id = $field_value[0];
                        $caption = $field_value[1];
                    }

                    if (!empty($img_id)) {
                        $value = wp_get_attachment_image(
                            $img_id,
                            'wpbdp-thumb',
                            false,
                            ['alt' => !empty($caption) ? $caption : esc_attr($oneField->label)]
                        );
                    }
                } elseif ($oneField->field_type === 'social-facebook') {
                    $value = sprintf(
                    // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
                        '<a target="_blank" rel="noopener" href="%s"><img src="%s" alt="facebook" style="width: 20px;display: inline-block;margin: 0 5px;"></a>',
                        esc_url($customFieldsValues[$oneField->id]),
                        ACYM_PLUGINS_URL.'/business-directory-plugin/assets/images/social/Facebook.svg'
                    );
                } elseif ($oneField->field_type === 'social-linkedin') {
                    $value = sprintf(
                    // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
                        '<a target="_blank" rel="noopener" href="%s"><img src="%s" alt="linkedin" style="width: 20px;display: inline-block;margin: 0 5px;"></a>',
                        esc_url($customFieldsValues[$oneField->id]),
                        ACYM_PLUGINS_URL.'/business-directory-plugin/assets/images/social/LinkedIn.svg'
                    );
                } elseif ($oneField->field_type === 'social-twitter') {
                    $value .= sprintf(
                        '<a href="https://twitter.com/%s" class="twitter-follow-button" data-show-count="%s" data-lang="%s">Follow @%s</a>',
                        $customFieldsValues[$oneField->id],
                        !empty($oneField->field_data['show_count']) ? 'true' : 'false',
                        substr(get_bloginfo('language'), 0, 2),
                        $customFieldsValues[$oneField->id]
                    );
                } elseif ($oneField->field_type === 'social-network') {
                    $userValue = $customFieldsValues[$oneField->id];
                    if (!empty($userValue[0])) {
                        $type = !empty($userValue['type']) ? $userValue['type'] : '';
                        $text = !empty($userValue['social-text']) ? $userValue['social-text'] : $userValue[0];

                        $value = '<a href="'.esc_url($userValue[0]).'" target="_blank">';

                        $socialIcon = '';

                        if (!empty($type)) {
                            if ('Other' === $type) {
                                if (!empty($userValue['social-icon'])) {
                                    $socialIcon = wp_get_attachment_image($userValue['social-icon'], 'wpbdp-thumb', false);
                                }
                            } else {
                                $socialIcon = sprintf(
                                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
                                    '<img src="%s" class="logo" alt="%s" style="width: 20px;display: inline-block;margin: 0 5px;">',
                                    ACYM_PLUGINS_URL.'/business-directory-plugin/assets/images/social/'.$type.'.svg',
                                    $type
                                );
                            }
                        }

                        $text = '<span class="social-text">'.esc_html($text).'</span>';

                        if ($oneField->field_data['display_order'] === 'icon_only') {
                            $value .= !empty($socialIcon) ? $socialIcon : $text;
                        } elseif ($oneField->field_data['display_order'] === 'text_only') {
                            $value .= $text;
                        } elseif ($oneField->field_data['display_order'] === 'text_first') {
                            $value .= $text.$socialIcon;
                        } else {
                            $value .= $socialIcon.$text;
                        }

                        $value .= '</a>';
                    }
                } else {
                    $value = $customFieldsValues[$oneField->id];

                    if (strpos($oneField->validators, 'email') !== false) {
                        $iconSrc = ACYM_PLUGINS_URL.'/'.basename(__DIR__).'/email.svg';
                        // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
                        $icon = '<img alt="email icon" src="'.esc_url($iconSrc).'" style="width: 13px;display: inline-block;margin-right: 5px;" />';
                        $value = $icon.$value;
                    }
                }
            }

            $varFields['{'.$oneField->shortname.'}'] = $value;
            $varFields['{cf'.$oneField->id.'}'] = $value;

            if (in_array($oneField->id, $tag->custom) && !empty($value)) {
                if (strpos($oneField->field_type, 'social-') === 0) {
                    $socialMediaLinks[] = $value;
                    continue;
                }

                $customField = [
                    $value,
                ];

                if (strpos($oneField->display_flags, 'nolabel') === false) {
                    $customField[] = $oneField->label;
                }

                $customFields[] = $customField;
            }
        }

        if (!empty($socialMediaLinks)) {
            $customFields[] = [
                implode(' ', $socialMediaLinks),
            ];
        }

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = $title;
        $format->afterTitle = $afterTitle;
        $format->afterArticle = '';
        $format->imagePath = $imagePath;
        $format->description = $contentText;
        $format->link = empty($tag->clickable) && empty($tag->clickableimg) ? '' : $link;
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content '.$this->name.'">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        if (!empty($tag->header) && !empty($varFields['{cf'.$tag->header.'}'])) {
            if (empty($this->currentHeader) || $this->currentHeader !== $varFields['{cf'.$tag->header.'}']) {
                $this->currentHeader = $varFields['{cf'.$tag->header.'}'];
                $result = '<div class="'.$this->name.'_header">'.$this->currentHeader.'</div>'.$result;
            }
        } else {
            $this->currentHeader = '';
        }

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }
}
