<?php

use AcyMailing\Helpers\TabHelper;

trait EasyDigitalDownloadsInsertion
{
    private $minProductDisplayLastPurchased = 1;
    private $maxProductDisplayLastPurchased = 3;

    public function getStandardStructure(string &$customView): void
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{title}';
        $format->afterTitle = '{price} <br> {picthtml}';
        $format->afterArticle = '';
        $format->imagePath = '';
        $format->description = '{shortdesc}';
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
        $query = 'SELECT download.*
                    FROM #__posts AS download
                    WHERE download.post_type = "download" 
                        AND download.post_status = "publish"';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function insertionOptions(?object $defaultValues = null): void
    {
        $this->defaultValues = $defaultValues;
        $this->prepareWPCategories('download_category');

        $this->tagvalues = acym_loadObjectList(
            'SELECT tax.term_taxonomy_id, term.`name`
			FROM #__terms AS term
			JOIN #__term_taxonomy AS tax ON term.term_id = tax.term_id
			WHERE tax.taxonomy = "download_tag"
			ORDER BY term.`name`'
        );

        $eddCategories = [];
        foreach ($this->categories as $oneCat) {
            $eddCategories[$oneCat->id] = $oneCat->title;
        }

        $dataQuery = [
            'numberposts' => -1,
            'post_type' => 'download',
            'post_status' => 'publish',
        ];
        $allDownloads = get_posts($dataQuery);
        $selectDownloads = [];
        foreach ($allDownloads as $download) {
            $selectDownloads[$download->ID] = $download->post_title;
        }
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
        ];

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
                    'ID' => 'ACYM_ID',
                    'post_date' => 'ACYM_PUBLISHING_DATE',
                    'post_modified' => 'ACYM_MODIFICATION_DATE',
                    'post_title' => 'ACYM_TITLE',
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
        $identifier = $this->name.'_tags';
        $tabHelper->startTab(acym_translation('ACYM_BY_TAG'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $this->displaySelectionZone($this->getTagListing());
        $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();
        $identifier = $this->name.'_coupon';
        $tabHelper->startTab(acym_translation('ACYM_COUPON'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $displayOptions = [
            [
                'title' => 'ACYM_DISCOUNT_NAME',
                'type' => 'text',
                'name' => 'name',
                'class' => 'acym_plugin__larger_text_field',
            ],
            [
                'title' => 'ACYM_DISCOUNT_CODE',
                'type' => 'text',
                'name' => 'code',
                'default' => '[name][key][value]',
                'class' => 'acym_plugin__larger_text_field',
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Discount type', 'easydigitaldownloads'),
                'type' => 'select',
                'name' => 'type',
                'options' => [
                    // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                    'percent' => __('Percentage', 'easydigitaldownloads'),
                    // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                    'flat' => __('Flat amount', 'easydigitaldownloads'),
                ],
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Discount amount', 'easydigitaldownloads'),
                'type' => 'number',
                'name' => 'amount',
                'default' => '0',
                'min' => '0',
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Download Requirements', 'easydigitaldownloads'),
                'type' => 'multiselect',
                'name' => 'dlRequirements',
                'options' => $selectDownloads,
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Condition', 'easydigitaldownloads'),
                'type' => 'select',
                'name' => 'condition',
                'options' => [
                    // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                    'all' => __('Cart must contain all selected Downloads', 'easydigitaldownloads'),
                    // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                    'any' => __('Cart needs one or more of the selected Downloads', 'easydigitaldownloads'),
                ],
                'class' => 'hide',
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Apply discount only to selected Downloads', 'easydigitaldownloads'),
                'type' => 'boolean',
                'name' => 'global',
                'default' => false,
                'class' => 'hide',
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Excluded downloads', 'easydigitaldownloads'),
                'type' => 'multiselect',
                'name' => 'excldownload',
                'options' => $selectDownloads,
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Start date', 'easydigitaldownloads'),
                'type' => 'date',
                'name' => 'startDate',
                'default' => '',
                'relativeDate' => '+',
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Expiration date', 'easydigitaldownloads'),
                'type' => 'date',
                'name' => 'endDate',
                'default' => '',
                'relativeDate' => '+',
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Max Uses', 'easydigitaldownloads'),
                'type' => 'number',
                'name' => 'maxUses',
                'default' => '',
                'min' => '0',
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Minimum amount', 'easydigitaldownloads'),
                'type' => 'number',
                'name' => 'minAmount',
                'default' => '',
                'min' => '0',
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Use once per customer', 'easydigitaldownloads'),
                'type' => 'boolean',
                'name' => 'once',
                'default' => false,
            ],
        ];

        $this->pluginHelper->displayOptions($displayOptions, $identifier, 'simple', $this->defaultValues);

        // Script to show more inputs if requirements are selected
        echo '<script type="text/javascript">
		// no declaration semantics because if we use them, 
		// javascript will redeclare these variables each time
		// while they are already existing (causing troubles)
		// may exist a better solution ?
			var requirements = document.getElementById("dlRequirementseasydigitaldownloads_coupon");
			var condition = document.getElementById("conditioneasydigitaldownloads_coupon");
			var global = document.getElementById("globaleasydigitaldownloads_coupon0");
            
			checkRequirementsSelected()
			requirements.onchange = checkRequirementsSelected;
			
			function checkRequirementsSelected(){
				if(requirements.value !== ""){
					condition.closest(".grid-x").style.display="";		
					global.closest(".grid-x").style.display="";		
				}else{
					condition.closest(".grid-x").style.display="none";		
					global.closest(".grid-x").style.display="none";	
				}
			}
		</script>';
        $tabHelper->endTab();

        $identifier = 'last'.$this->name;

        $tabHelper->startTab(
            acym_translation('ACYM_LAST_PURCHASED_DOWNLOAD'),
            !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab
        );

        $lastPurchasedOptions = [
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
        ];

        $this->displaySelectionZone($this->lastOrCartContentInsert('last'));
        $this->pluginHelper->displayOptions($lastPurchasedOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();

        // Downloads in cart
        $identifier = esc_attr('cart'.$this->name);
        $tabHelper->startTab(
            acym_translation('ACYM_CART_DOWNLOADS'),
            !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab
        );
        $this->displaySelectionZone($this->lastOrCartContentInsert('cart'));
        $this->pluginHelper->displayOptions($lastPurchasedOptions, $identifier, 'grouped', $this->defaultValues);
        $tabHelper->endTab();

        $tabHelper->display('plugin');
    }

    private function lastOrCartContentInsert($type = 'last')
    {
        if ($type == 'last') {
            $identifier = 'last'.$this->name;
            $partId = 'last__purchased';
            $endIdMin = '';
            $endIdMax = '';
        } elseif ($type == 'cart') {
            $identifier = 'cart'.$this->name;
            $partId = 'cart';
            $endIdMin = 'min';
            $endIdMax = 'max';
        }

        $selectedArea = empty($this->defaultValues->id) ? [] : $this->getSelectedArea($this->defaultValues);
        if (!isset($this->defaultValues->min) || (empty($this->defaultValues->min) && $this->defaultValues->min !== '0')) {
            $this->defaultValues->min = 1;
        }
        if (!isset($this->defaultValues->max) || (empty($this->defaultValues->max) && $this->defaultValues->max !== '0')) {
            $this->defaultValues->max = 2;
        }
        ob_start();
        ?>
		<div class="cell grid-x">
			<label for="acym__easydigitaldownloads__<?php echo esc_attr($partId); ?>__download__number<?php echo esc_attr($endIdMin); ?>" class="cell medium-6">
                <?php
                echo wp_kses(
                    acym_translation('ACYM_MIN_NB_ELEMENTS').acym_info(['textShownInTooltip' => 'ACYM_MIN_NUMBER_OF_PRODUCTS_DESC']),
                    [
                        'span' => ['class' => []],
                        'a' => ['href' => [], 'title' => [], 'target' => [], 'class' => []],
                    ]
                );
                ?>
			</label>
			<input type="number"
				   id="acym__easydigitaldownloads__<?php echo esc_attr($partId); ?>__download__number<?php echo esc_attr($endIdMin); ?>"
				   class="cell medium-6"
				   value="<?php echo esc_attr($this->defaultValues->min); ?>"
				   name="min"
				   min="0"
				   onchange="addAdditionalInfo<?php echo esc_attr($identifier); ?>('min', this.value)">
		</div>
		<div class="cell grid-x">
			<label for="acym__easydigitaldownloads__<?php echo esc_attr($partId); ?>__download__number<?php echo esc_attr($endIdMax); ?>" class="cell medium-6">
                <?php
                echo wp_kses(
                    acym_translation('ACYM_MAX_NB_ELEMENTS').acym_info(['textShownInTooltip' => 'ACYM_MAX_NUMBER_OF_PRODUCTS_DESC']),
                    [
                        'span' => ['class' => []],
                        'a' => ['href' => [], 'title' => [], 'target' => [], 'class' => []],
                    ]
                );
                ?>
			</label>
			<input type="number"
				   id="acym__easydigitaldownloads__<?php echo esc_attr($partId); ?>__download__number<?php echo esc_attr($endIdMax); ?>"
				   class="cell medium-6"
				   value="<?php echo esc_attr($this->defaultValues->max); ?>"
				   name="max"
				   min="0"
				   onchange="addAdditionalInfo<?php echo esc_attr($identifier); ?>('max', this.value)">
		</div>
		<div class="cell grid-x">
			<label for="acym__easydigitaldownloads__<?php echo esc_attr($partId); ?>__cat" class="cell medium-6">
                <?php
                echo wp_kses(
                    acym_translation('ACYM_CATEGORY_FILTER').acym_info(['textShownInTooltip' => 'ACYM_CATEGORY_FILTER_DESC']),
                    [
                        'span' => ['class' => []],
                        'a' => ['href' => [], 'title' => [], 'target' => [], 'class' => []],
                    ]
                );
                ?>
			</label>
			<div class="cell medium-6 acym__easydigitaldownloads__<?php echo esc_attr($partId); ?>__cat__container">
                <?php
                echo wp_kses(
                    acym_selectMultiple(
                        $this->catvalues,
                        'cat',
                        $selectedArea,
                        [
                            'id' => 'acym__easydigitaldownloads__'.$partId.'__cat',
                            'onchange' => '_selectedRows'.$identifier.' = {}
                        				for(let option of this.options){
                        					if(option.selected) _selectedRows'.$identifier.'[option.value] = true;
                        				} 	
                        				updateDynamic'.$identifier.'();',
                        ]
                    ),
                    [
                        'select' => ['name' => [], 'id' => [], 'class' => [], 'multiple' => [], 'onchange' => []],
                        'option' => ['value' => [], 'selected' => [], 'disabled' => [], 'data-hidden' => []],
                        'optgroup' => ['label' => []],
                    ]
                );
                ?>
			</div>
		</div>
		<script type="text/javascript">
            const _additionalInfo<?php echo esc_attr($identifier); ?> = {};
            <?php
            echo '_additionalInfo'.esc_attr($identifier).'.min = '.intval($this->defaultValues->min).';';
            echo '_additionalInfo'.esc_attr($identifier).'.max = '.intval($this->defaultValues->max).';';
            ?>
		</script>
        <?php
        if ($type == 'last') {
            ?>
			<div class="cell grid-x">
				<label class="cell medium-6">
                    <?php
                    echo wp_kses(
                        acym_translation('ACYM_START_DATE').acym_info(['textShownInTooltip' => 'ACYM_START_DATE_PURCHASED_PRODUCT_DESC']),
                        [
                            'span' => ['class' => []],
                            'a' => ['href' => [], 'title' => [], 'target' => [], 'class' => []],
                        ]
                    );
                    ?>
				</label>
                <?php
                echo wp_kses(
                    acym_dateField(
                        'min_date',
                        empty($this->defaultValues->min_date) ? '' : $this->defaultValues->min_date,
                        'cell medium-6',
                        'onchange="addAdditionalInfo'.esc_attr($identifier).'(\'min_date\', this.value)"'
                    ),
                    [
                        'div' => ['class' => [], 'style' => []],
                        'input' => [
                            'type' => [],
                            'name' => [],
                            'id' => [],
                            'value' => [],
                            'class' => [],
                            'data-open' => [],
                            'readonly' => [],
                            'data-acym-translate' => [],
                            'data-rs' => [],
                            'onchange' => [],
                            'data-reveal' => [],
                            'data-reveal-larger' => [],
                        ],
                        'span' => ['class' => [], 'aria-hidden' => []],
                        'button' => [
                            'type' => [],
                            'class' => [],
                            'data-close' => [],
                            'data-type' => [],
                            'aria-label' => [],
                            'data-open' => [],
                        ],
                        'select' => [
                            'id' => [],
                            'name' => [],
                            'class' => [],
                        ],
                        'optgroup' => ['label' => []],
                        'option' => ['value' => [], 'selected' => [], 'disabled' => []],
                    ]
                );
                ?>
			</div>
            <?php
        }

        return ob_get_clean();
    }

    public function prepareListing(): string
    {
        $this->querySelect = 'SELECT download.ID, download.post_title, download.post_date ';
        $this->query = 'FROM #__posts AS download ';
        $this->filters = [];
        $this->filters[] = 'download.post_type = "download"';
        $this->filters[] = 'download.post_status = "publish"';
        $this->searchFields = ['download.ID', 'download.post_title'];
        $this->pageInfo->order = 'download.ID';
        $this->elementIdTable = 'download';
        $this->elementIdColumn = 'ID';

        parent::prepareListing();

        if (!empty($this->pageInfo->filter_cat)) {
            $this->query .= 'JOIN #__term_relationships AS cat ON download.ID = cat.object_id';
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

    public function replaceContent(object &$email, bool $send = true): void
    {
        $this->replaceMultiple($email);
        $this->replaceOne($email);

        if ($send) $this->removeLastGeneratedPreview($email);
    }

    public function removeLastGeneratedPreview(&$email)
    {
        $tagsLast = $this->pluginHelper->extractTags($email, 'last'.$this->name);
        $tagsCart = $this->pluginHelper->extractTags($email, 'cart'.$this->name);
        $tags = array_merge($tagsLast, $tagsCart);

        if (empty($tags)) return;

        foreach ($tags as $tag => $parameter) {
            $this->tags[$tag] = $tag;
        }

        $this->pluginHelper->replaceTags($email, $this->tags, true);
    }

    public function generateByCategory(object &$email): object
    {
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $tags = array_merge($tags, $this->pluginHelper->extractTags($email, $this->name.'_tags'));

        $this->tags = [];

        if (empty($tags)) return $this->generateCampaignResult;

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            $query = 'SELECT DISTINCT download.`ID` 
                    FROM #__posts AS download 
                    LEFT JOIN #__term_relationships AS cat ON download.ID = cat.object_id';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $where[] = 'cat.term_taxonomy_id IN ('.implode(',', $selectedArea).')';
            }

            $where[] = 'download.post_type = "download"';
            $where[] = 'download.post_status = "publish"';
            if (!empty($parameter->min_publish)) {
                $parameter->min_publish = acym_date(acym_replaceDate($parameter->min_publish), 'Y-m-d H:i:s', false);
                $where[] = 'download.post_date_gmt >= '.acym_escapeDB($parameter->min_publish);
            }

            if (!empty($parameter->onlynew)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $where[] = 'download.post_date_gmt > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                }
            }

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'download');
        }

        return $this->generateCampaignResult;
    }

    public function replaceIndividualContent(object $tag): string
    {
        $query = 'SELECT download.*
                    FROM #__posts AS download
                    WHERE download.post_type = "download" 
                        AND download.post_status = "publish"
                        AND download.ID = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);
        $download = edd_get_download($tag->id);

        if (empty($element) || empty($download)) return '';
        $varFields = $this->getCustomLayoutVars($element);
        $link = get_permalink($element->ID);
        $varFields['{link}'] = $link;
        $title = '';
        $varFields['{title}'] = $element->post_title;

        if (in_array('title', $tag->display)) $title = $varFields['{title}'];

        $afterTitle = '';
        $varFields['{price}'] = edd_price($tag->id, false);

        if (in_array('price', $tag->display)) $afterTitle .= $varFields['{price}'];

        $imagePath = '';
        $imageHTML = get_the_post_thumbnail($download->id);

        if (!empty($imageHTML)) {
            $posURL = strpos($imageHTML, ' src="') + 6;
            $imagePath = substr($imageHTML, $posURL, strpos($imageHTML, '"', $posURL) - $posURL);
        }
        // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
        $varFields['{picthtml}'] = '<img alt="" src="'.$imagePath.'">';
        if (empty($tag->pict)) $imagePath = '';

        $contentText = '';
        $varFields['{shortdesc}'] = $element->post_excerpt;
        $varFields['{desc}'] = $this->cleanExtensionContent($element->post_content);
        if (in_array('shortdesc', $tag->display)) $contentText .= $varFields['{shortdesc}'];
        if (in_array('desc', $tag->display)) $contentText .= $varFields['{desc}'];

        $customFields = [];

        $varFields['{cats}'] = get_the_term_list($tag->id, 'download_category', '', ', ');

        if (in_array('cats', $tag->display) && $varFields['{cats}']) {
            $customFields[] = [
                $varFields['{cats}'],
                acym_translation('ACYM_CATEGORIES'),
            ];
        }

        $varFields['{note}'] = acym_loadResult('SELECT meta_value FROM #__postmeta WHERE meta_key = "edd_product_notes" AND post_id = '.intval($tag->id));

        if (in_array('note', $tag->display)) $contentText .= !empty($contentText) ? '<br><em>'.$varFields['{note}'].'</em>' : '<em>'.$varFields['{note}'].'</em>';

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = $title;
        $format->afterTitle = $afterTitle;
        $format->afterArticle = '';
        $format->imagePath = $imagePath;
        $format->description = $contentText;
        $format->link = empty($tag->clickable) && empty($tag->clickableimg) ? '' : $link;
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }

    public function replaceUserInformation(object &$email, ?object &$user, bool $send = true): array
    {
        if (empty($user)) {
			return [];
        }

        $this->replaceCoupons($email, $user, $send);
        $generated = $this->replaceLastPurchased($email, $user, $send);
        if ($generated === '') {
            return [
                'send' => false,
                'emogrifier' => false,
                'message' => acym_translationSprintf('ACYM_EMAIL_X_NOT_SENT_USER_X_NOT_BOUGHT_ENOUGH_PRODUCTS', $email->subject, $user->email),
            ];
        }
        $generatedCart = $this->replaceCart($email, $user, $send);
        if ($generatedCart === '') {
            return [
                'send' => false,
                'emogrifier' => false,
                'message' => acym_translationSprintf('ACYM_EMAIL_X_NOT_SENT_USER_X_NOT_PRODUCTS_IN_CART', $email->subject, $user->email),
            ];
        }

        if ($generated == 1 || $generatedCart == 1) {
			return ['send' => true, 'emogrifier' => true];
        }

		return [];
    }

    private function replaceLastPurchased(&$email, $user, $send)
    {
        $tags = $this->pluginHelper->extractTags($email, 'last'.$this->name);
        $tags = array_merge($tags, $this->pluginHelper->extractTags($email, $this->name.'_tags'));

        $this->tags = [];
        foreach ($tags as $oneTag => $parameter) {
            $minAtO = isset($parameter->min) && $parameter->min == 0;
            if (empty($user->cms_id) && !$minAtO) {
                $this->tags[$oneTag] = '';
                continue;
            } elseif (empty($user->cms_id) && $minAtO) {
                $this->tags[$oneTag] = '_EMPTYSEND_';
                continue;
            }
            if ($this->isOldDatabaseVersion) {
                //We get the lastest payments
                $dataQuery = [
                    'numberposts' => -1,
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                    'meta_key' => '_edd_payment_customer_id',
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                    'meta_value' => $user->cms_id,
                    // payment is a kind of list of download (like an order)
                    'post_type' => 'edd_payment',
                    // edd has not a function to know what are all status considered as paid
                    // So I put "publish" because it's the status "paid" for edd
                    // Maybe we can add more like "pending"
                    'post_status' => 'publish',
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                    'meta_query' => [
                        [
                            'key' => '_edd_payment_customer_id',
                            'value' => intval($user->cms_id),
                            'compare' => '=',
                        ],
                    ],
                ];

                if (!empty($parameter->min_date)) {
                    $minDate = acym_replaceDate($parameter->min_date);
                    $dataQuery['date_query'] = [
                        'after' => gmdate('Y-m-d', $minDate),
                    ];
                }
                $customer_payments = get_posts($dataQuery);

                if ($minAtO && empty($customer_payments)) {
                    $this->tags[$oneTag] = '_EMPTYSEND_';
                    continue;
                }

                if (empty($customer_payments)) {
                    $this->tags[$oneTag] = '';
                    continue;
                }

                //We get the downloads from the last payments
                $download_ids = [];

                foreach ($customer_payments as $customer_payment) {
                    $payment = new EDD_Payment($customer_payment->ID);
                    $downloads = $payment->downloads;
                    foreach ($downloads as $download) {
                        $download_id = $download['id'];
                        $download_ids[] = $download_id;
                    }
                }
            } else {
                $query =
                    'SELECT DISTINCT item.product_id 
					 FROM `#__edd_order_items` as item 
    				 JOIN `#__edd_orders` as orders on item.order_id = orders.id 
				 	 WHERE orders.user_id ='.$user->cms_id.' AND orders.status = '.acym_escapeDB('complete');

                if (!empty($parameter->min_date)) {
                    $query .= ' AND orders.date_completed >= '.acym_escapeDB($parameter->min_date);
                }

                $download_ids = acym_loadResultArray($query);
            }

            $query = 'SELECT DISTINCT download.`ID` FROM #__posts AS download ';

            //We filter the downloads if we selected categories
            if (!empty($parameter->id)) {
                $selectedArea = $this->getSelectedArea($parameter);
                if (!empty($selectedArea)) {
                    $query .= ' JOIN #__term_relationships AS cat ON download.ID = cat.object_id
                    AND (cat.term_taxonomy_id = '.implode(' OR cat.term_taxonomy_id = ', $selectedArea).')';
                }
            }

            $query .= ' WHERE download.ID IN ('.implode(',', $download_ids).')';

            if ($send) {
                $parameter->min = empty($parameter->min) && !$minAtO ? $this->minProductDisplayLastPurchased : $parameter->min;
            } else {
                $parameter->min = 0;
            }
            $parameter->max = empty($parameter->max) ? $this->maxProductDisplayLastPurchased : $parameter->max;

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'download');
            if ($this->generateCampaignResult->status == false && $send) $this->tags[$oneTag] = '';
        }

        $emptyTags = true;
        $nbEmptySend = 0;
        foreach ($this->tags as $i => $tag) {
            if ($tag == '_EMPTYSEND_') {
                $nbEmptySend++;
                $this->tags[$i] = '';
            }
            if (!empty($tag)) {
                $emptyTags = false;
                break;
            }
        }

        $this->pluginHelper->replaceTags($email, $this->tags, true);

        if (count($this->tags) == $nbEmptySend) return 0;

        if ($emptyTags) return '';

        $this->replaceOne($email);

        return 1;
    }

    private function replaceCart(&$email, $user, $send)
    {
        $tags = $this->pluginHelper->extractTags($email, 'cart'.$this->name);
        $tags = array_merge($tags, $this->pluginHelper->extractTags($email, $this->name.'_tags'));

        if (empty($tags)) return 0;

        // Get user cart
        $cart_items = edd_get_cart_contents();
        $this->tags = [];
        $noItems = empty($cart_items);
        foreach ($tags as $oneTag => $parameter) {
            $minAtO = isset($parameter->min) && $parameter->min == 0;
            if ((empty($user->cms_id) || $noItems) && !$minAtO) {
                $this->tags[$oneTag] = '';
                continue;
            }
            if ($minAtO && $noItems) {
                $this->tags[$oneTag] = '_EMPTYSEND_';
                continue;
            }
            $download_ids = [];
            foreach ($cart_items as $oneItem) {
                $download_ids[] = $oneItem['id'];
            }

            $query = 'SELECT DISTINCT download.`ID` FROM #__posts AS download ';
            //We filters the downloads if we selected categories
            if (!empty($parameter->id)) {
                $selectedArea = $this->getSelectedArea($parameter);
                if (!empty($selectedArea)) {
                    $download_ids = array_unique($download_ids);
                    $query .= ' JOIN #__term_relationships AS cat ON download.ID = cat.object_id 
                    AND cat.term_taxonomy_id = '.implode(' OR cat.term_taxonomy_id = ', $selectedArea).'';
                }
            }

            $query .= ' WHERE download.ID IN ('.implode(',', $download_ids).')';

            if ($send) {
                $parameter->min = empty($parameter->min) && !$minAtO ? $this->minProductDisplayLastPurchased : $parameter->min;
            } else {
                $parameter->min = 0;
            }

            $parameter->max = empty($parameter->max) ? $this->maxProductDisplayLastPurchased : $parameter->max;

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'download');
            if ($this->generateCampaignResult->status == false && $send) $this->tags[$oneTag] = '';
        }

        $emptyTags = true;
        $nbEmptySend = 0;
        foreach ($this->tags as $i => $tag) {
            if ($tag == '_EMPTYSEND_') {
                $nbEmptySend++;
                $this->tags[$i] = '';
            }
            if (!empty($tag)) {
                $emptyTags = false;
                break;
            }
        }

        $this->pluginHelper->replaceTags($email, $this->tags, true);

        if (count($this->tags) == $nbEmptySend) return 0;

        if ($emptyTags) return '';

        $this->replaceOne($email);

        return 1;
    }

    private function replaceCoupons(&$email, &$user, $send = true)
    {
        $tags = $this->pluginHelper->extractTags($email, 'easydigitaldownloads_coupon');
        if (empty($tags)) {
            return;
        }

        $tagsReplaced = [];
        foreach ($tags as $i => $oneTag) {
            if (isset($tagsReplaced[$i])) {
                continue;
            }
            if (!$send || empty($user->id)) {
                $tagsReplaced[$i] = '<i>'.acym_translation('ACYM_CHECK_EMAIL_COUPON').'</i>';
            } else {
                $tagsReplaced[$i] = $this->generateCoupon($oneTag, $user);
            }
        }

        $this->pluginHelper->replaceTags($email, $tagsReplaced, true);
    }

    private function generateCoupon($tag, $user)
    {
        if (empty($tag->name) || empty($tag->code) || empty($tag->amount) || empty($tag->type) || !in_array($tag->type, ['flat', 'percent'])) return '';
        $intAttributes = ['amount', 'minAmount', 'maxUses', 'once'];
        foreach ($intAttributes as $oneAttribute) {
            if (empty($tag->$oneAttribute)) $tag->$oneAttribute = 0;
            $tag->$oneAttribute = intval($tag->$oneAttribute);
        }

        if (empty($tag->amount)) return '';

        $clean_name = strtoupper($user->name);
        $space = strpos($clean_name, ' ');
        if (!empty($space)) $clean_name = substr($clean_name, 0, $space);

        $couponCode = str_replace(
            [
                '[name]',
                '[userid]',
                '[email]',
                '[key]',
                '[value]',
            ],
            [
                $clean_name,
                $user->id,
                $user->email,
                acym_generateKey(5),
                $tag->amount,
            ],
            $tag->code
        );
        if ($this->isOldDatabaseVersion) {
            $coupon = [
                'post_title' => $tag->name,
                'post_content' => '',
                //edd discounts can have active or inactive status
                'post_status' => 'active',
                'post_author' => 1,
                'post_type' => 'edd_discount',
            ];

            $couponId = wp_insert_post($coupon);

            // Add Details
            // Check if couponCode already existing ?
            update_post_meta($couponId, '_edd_discount_code', $couponCode);
            update_post_meta($couponId, '_edd_discount_name', $tag->name);
            update_post_meta($couponId, '_edd_discount_status', 'active');
            update_post_meta($couponId, '_edd_discount_uses', 0);

            update_post_meta($couponId, '_edd_discount_max_uses', empty($tag->maxUses) ? '' : $tag->maxUses);
            update_post_meta($couponId, '_edd_discount_amount', $tag->amount);
            update_post_meta($couponId, '_edd_discount_start', empty($tag->startDate) ? '' : acym_date(acym_replaceDate($tag->startDate), 'Y-m-d'));
            update_post_meta($couponId, '_edd_discount_expiration', empty($tag->endDate) ? '' : acym_date(acym_replaceDate($tag->endDate), 'Y-m-d'));

            update_post_meta($couponId, '_edd_discount_type', $tag->type);
            update_post_meta($couponId, '_edd_discount_min_price', empty($tag->minAmount) ? '' : $tag->minAmount);

            update_post_meta($couponId, '_edd_discount_product_reqs', $this->cleanElements($tag->dlRequirements));
            update_post_meta($couponId, '_edd_discount_product_condition', empty($tag->condition) ? 'all' : $tag->condition);
            update_post_meta($couponId, '_edd_discount_is_not_global', empty($tag->global) ? false : $tag->global);

            update_post_meta($couponId, '_edd_discount_excluded_products', $this->cleanElements($tag->excldownload));

            update_post_meta($couponId, '_edd_discount_is_single_use', empty($tag->once) ? '' : $tag->once);
        } else {
            $discount = new stdClass();
            $discount->name = $tag->name;
            $discount->code = $couponCode;
            $discount->status = 'active';
            $discount->type = 'discount';
            $discount->scope = empty($tag->global) ? 'not_global' : 'global';
            $discount->amount_type = $tag->type;
            $discount->amount = $tag->amount;
            $discount->max_uses = empty($tag->maxUses) ? '' : $tag->maxUses;
            $discount->once_per_customer = empty($tag->once) ? 0 : $tag->once;
            $discount->min_charge_amount = empty($tag->minAmount) ? '' : $tag->minAmount;
            $discount->start_date = empty($tag->startDate) ? '' : acym_date(acym_replaceDate($tag->startDate), 'Y-m-d H:i:s');
            $discount->end_date = empty($tag->endDate) ? '' : acym_date(acym_replaceDate($tag->endDate), 'Y-m-d H:i:s');

            $insertedDiscount = acym_insertObject('#__edd_adjustments', $discount);
            if (!empty($insertedDiscount)) {
                $meta = new stdClass();
                foreach ($this->cleanElements($tag->dlRequirements) as $downloadId) {
                    $meta->edd_adjustment_id = $insertedDiscount;
                    $meta->meta_key = 'product_requirement';
                    $meta->meta_value = $downloadId;

                    acym_insertObject('#__edd_adjustmentmeta', $meta);
                }
                if (!empty($tag->condition)) {
                    $meta->edd_adjustment_id = $insertedDiscount;
                    $meta->meta_key = 'product_condition';
                    $meta->meta_value = $tag->condition;

                    acym_insertObject('#__edd_adjustmentmeta', $meta);
                }


                foreach ($this->cleanElements($tag->excldownload) as $downloadId) {
                    $meta->edd_adjustment_id = $insertedDiscount;
                    $meta->meta_key = 'excluded_product';
                    $meta->meta_value = $downloadId;

                    acym_insertObject('#__edd_adjustmentmeta', $meta);
                }
            }
        }

        return $couponCode;
    }

    private function cleanElements($elements)
    {
        $elements = empty($elements) ? [] : explode(',', $elements);
        acym_arrayToInteger($elements);
        foreach ($elements as $i => $oneElement) {
            if (empty($oneElement)) unset($elements[$i]);
        }

        return $elements;
    }
}
