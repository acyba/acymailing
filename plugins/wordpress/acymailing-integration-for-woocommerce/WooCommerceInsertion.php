<?php

use AcyMailing\Helpers\TabHelper;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\QueueClass;
use AcyMailing\Classes\FollowupClass;

trait WooCommerceInsertion
{
    private int $minProductDisplayLastPurchased = 1;
    private int $maxProductDisplayLastPurchased = 3;

    public function dynamicText(?int $mailId): ?object
    {
        $followupId = acym_getVar('int', 'followup_id');
        if (empty($followupId)) {
            return null;
        }

        if ($this->isWooCommerceFollowup($followupId)) {
            return $this->pluginDescription;
        }

        return null;
    }

    public function textPopup(): void
    {
        ?>
		<script type="text/javascript">
            let selectedWooTag = '';

            function applyWooTag(tagname, element) {
                if (!tagname) {
                    return;
                }

                selectedWooTag = tagname;
                let string = '{wootag:' + tagname + '}';
                setTag(string, jQuery(element));
            }
		</script>
        <?php
        $text = '<h1 class="acym__title acym__title__secondary text-center cell">'.acym_translation('ACYM_ORDER').'</h1>';

        $orderFields = [
            'billing_email',
            'status',
            'total_amount',
            'date_created_gmt',
            'date_updated_gmt',
            'date_completed',
            'date_paid',
            'billing_address',
            'shipping_address',
            'first_name',
            'last_name',
            'phone',
        ];

        foreach ($orderFields as $orderFieldName) {
            $text .= '<div class="cell acym__row__no-listing acym__listing__row__popup" onclick="applyWooTag(\''.$orderFieldName.'\', this);" >'.$orderFieldName.'</div>';
        }

        echo wp_kses(
            $text,
            [
                'h1' => ['class' => []],
                'div' => ['class' => [], 'onclick' => []],
            ]
        );
    }

    public function replaceOrderInformation(object &$email, object &$user, bool $send = true)
    {
        $extractedTags = $this->pluginHelper->extractTags($email, 'wootag');
        if (empty($extractedTags)) {
            return;
        }

        $mailClass = new MailClass();
        $queueClass = new QueueClass();
        $params = $queueClass->getQueueParams($mailClass->getMainMailId($email->id), $user->id);

        if (empty($params)) {
            return;
        }

        $orderId = $params['woo_order_id'] ?? null;

        if (empty($orderId)) {
            return;
        }

        $order = wc_get_order($orderId);

        if (empty($order)) {
            return;
        }

        $tags = [];
        $productTags = $this->processTags($order, $extractedTags);
        $tags = array_merge($tags, $productTags);

        $this->pluginHelper->replaceTags($email, $tags);
    }

    private function processTags(object $order, array $extractedTags): array
    {
        $tags = [];

        foreach ($extractedTags as $oneTag) {
            $field = $oneTag->id;
            $value = $oneTag->default;

            switch ($field) {
                case 'billing_address':
                    $value = $order->get_formatted_billing_address();
                    break;

                case 'shipping_address':
                    $value = $order->get_formatted_shipping_address();
                    break;

                case 'date_created_gmt':
                    $value = $order->get_date_created() ? acym_date($order->get_date_created(), 'Y-m-d H:i:s', false) : null;
                    break;

                case 'date_updated_gmt':
                    $value = $order->get_date_modified() ? acym_date($order->get_date_modified(), 'Y-m-d H:i:s', false) : null;
                    break;

                case 'date_completed':
                    $value = $order->get_date_completed() ? acym_date($order->get_date_completed(), 'Y-m-d H:i:s', false) : null;
                    break;

                case 'date_paid':
                    $value = $order->get_date_paid() ? acym_date($order->get_date_paid(), 'Y-m-d H:i:s', false) : null;
                    break;

                case 'billing_email':
                    $value = $order->get_billing_email();
                    break;

                case 'status':
                    $value = $order->get_status();
                    break;

                case 'total_amount':
                    $currency = $order->get_currency();
                    $value = wc_price($order->get_total(), $currency);
                    break;

                case 'first_name':
                    $value = $order->get_billing_first_name();
                    break;

                case 'last_name':
                    $value = $order->get_billing_last_name();
                    break;

                case 'phone':
                    $value = $order->get_billing_phone();
                    break;

                default:
                    if (method_exists($order, "get_{$field}")) {
                        $value = $order->{"get_{$field}"}();
                    }
                    break;
            }

            $this->pluginHelper->formatString($value, $oneTag);
            $tags["{wootag:$field}"] = $value;
        }

        return $tags;
    }

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
        $query = 'SELECT product.*
                    FROM #__posts AS product
                    WHERE product.post_type = "product" 
                        AND product.post_status = "publish"';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function insertionOptions(?object $defaultValues = null): void
    {
        if (empty($defaultValues)) {
            $defaultValues = new stdClass();
        }
        $this->defaultValues = $defaultValues;
        $this->prepareWPCategories('product_cat');

        $this->tagvalues = acym_loadObjectList(
            'SELECT tax.term_taxonomy_id, term.`name`
			FROM #__terms AS term
			JOIN #__term_taxonomy AS tax ON term.term_id = tax.term_id
			WHERE tax.taxonomy = "product_tag"
			ORDER BY term.`name`'
        );

        $wooCategories = [];
        foreach ($this->categories as $oneCat) {
            $wooCategories[$oneCat->id] = $oneCat->title;
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
                    'menu_order' => 'ACYM_MENU_ORDER',
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
                'title' => 'ACYM_DISCOUNT_CODE',
                'type' => 'text',
                'name' => 'code',
                'default' => '[name][key][value]',
                'class' => 'acym_plugin__larger_text_field',
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Coupon expiry date', 'woocommerce'),
                'type' => 'date',
                'name' => 'end',
                'default' => '',
                'relativeDate' => '+',
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Discount type', 'woocommerce'),
                'type' => 'select',
                'name' => 'type',
                'options' => [
                    // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                    'fixed_cart' => __('Fixed cart discount', 'woocommerce'),
                    // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                    'fixed_product' => __('Fixed product discount', 'woocommerce'),
                    // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                    'percent' => __('Percentage discount', 'woocommerce'),
                ],
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Coupon amount', 'woocommerce'),
                'type' => 'number',
                'name' => 'amount',
                'default' => '0',
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Allow free shipping', 'woocommerce'),
                'type' => 'boolean',
                'name' => 'free',
                'default' => false,
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Exclude sale items', 'woocommerce'),
                'type' => 'boolean',
                'name' => 'exclsale',
                'default' => false,
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Minimum spend', 'woocommerce'),
                'type' => 'number',
                'name' => 'min',
                'default' => '',
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Maximum spend', 'woocommerce'),
                'type' => 'number',
                'name' => 'max',
                'default' => '',
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Usage limit per coupon', 'woocommerce'),
                'type' => 'number',
                'name' => 'use',
                'default' => '1',
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Limit usage to X items', 'woocommerce'),
                'type' => 'number',
                'name' => 'items',
                'default' => '',
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Products', 'woocommerce'),
                'type' => 'text',
                'name' => 'prod',
                'class' => 'acym_plugin__larger_text_field',
                'default' => '',
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Exclude products', 'woocommerce'),
                'type' => 'text',
                'name' => 'exclprod',
                'class' => 'acym_plugin__larger_text_field',
                'default' => '',
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Product categories', 'woocommerce'),
                'type' => 'multiselect',
                'name' => 'cat',
                'options' => $wooCategories,
            ],
            [
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                'title' => __('Exclude categories', 'woocommerce'),
                'type' => 'multiselect',
                'name' => 'exclcat',
                'options' => $wooCategories,
            ],
        ];

        $this->pluginHelper->displayOptions($displayOptions, $identifier, 'simple', $this->defaultValues);

        $tabHelper->endTab();

        $identifier = 'last'.$this->name;

        $tabHelper->startTab(
            acym_translation('ACYM_LAST_PURCHASED_PRODUCT'),
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

        $followupTrigger = acym_getVar('string', 'followupTrigger');
        if ($followupTrigger === 'woocommerce_purchase') {
            $identifier = 'woocommerce_ordered';
            $tabHelper->startTab(acym_translation('ACYM_BOUGHT_PRODUCT'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);
            $boughtOptions = [
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

            $this->pluginHelper->displayOptions($boughtOptions, $identifier, 'grouped', $this->defaultValues);

            $tabHelper->endTab();
        }

        // Products in cart
        $identifier = 'cart'.$this->name;
        $tabHelper->startTab(
            acym_translation('ACYM_CART_PRODUCTS'),
            !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab
        );
        $this->displaySelectionZone($this->lastOrCartContentInsert('cart'));
        $this->pluginHelper->displayOptions($lastPurchasedOptions, $identifier, 'grouped', $this->defaultValues);
        $tabHelper->endTab();

        $tabHelper->display('plugin');
    }

    private function lastOrCartContentInsert($type = 'last')
    {
        if ($type === 'cart') {
            $identifier = 'cart'.$this->name;
            $partId = 'cart';
            $endIdMin = 'min';
            $endIdMax = 'max';
        } else {
            $identifier = 'last'.$this->name;
            $partId = 'last__purchased';
            $endIdMin = '';
            $endIdMax = '';
        }

        $selectedArea = empty($this->defaultValues->id) ? [] : $this->getSelectedArea($this->defaultValues);
        if (!isset($this->defaultValues->min) || (empty($this->defaultValues->min) && $this->defaultValues->min !== '0')) {
            $this->defaultValues->min = $this->minProductDisplayLastPurchased;
        }
        if (!isset($this->defaultValues->max) || (empty($this->defaultValues->max) && $this->defaultValues->max !== '0')) {
            $this->defaultValues->max = $this->maxProductDisplayLastPurchased;
        }
        ob_start();
        ?>
		<div class="cell grid-x margin-bottom-1">
			<label for="acym__woocommerce__<?php echo esc_attr($partId); ?>__product__number<?php echo esc_attr($endIdMin); ?>" class="cell medium-6">
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
				   id="acym__woocommerce__<?php echo esc_attr($partId); ?>__product__number<?php echo esc_attr($endIdMin); ?>"
				   class="cell medium-6"
				   value="<?php echo esc_attr($this->defaultValues->min); ?>"
				   name="min"
				   onchange="addAdditionalInfo<?php echo esc_attr($identifier); ?>('min', this.value)">
		</div>
		<div class="cell grid-x margin-bottom-1">
			<label for="acym__woocommerce__<?php echo esc_attr($partId); ?>__product__number<?php echo esc_attr($endIdMax); ?>" class="cell medium-6">
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
				   id="acym__woocommerce__<?php echo esc_attr($partId); ?>__product__number<?php echo esc_attr($endIdMax); ?>"
				   class="cell medium-6"
				   value="<?php echo esc_attr($this->defaultValues->max); ?>"
				   name="max"
				   onchange="addAdditionalInfo<?php echo esc_attr($identifier); ?>('max', this.value)">
		</div>
		<div class="cell grid-x margin-bottom-1">
			<label for="acym__woocommerce__<?php echo esc_attr($partId); ?>__cat" class="cell medium-6">
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
			<div class="cell medium-6 acym__woocommerce__<?php echo esc_attr($partId); ?>__cat__container">
                <?php
                echo wp_kses(
                    acym_selectMultiple(
                        $this->catvalues,
                        'cat',
                        $selectedArea,
                        [
                            'id' => 'acym__woocommerce__'.$partId.'__cat',
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
			window._additionalInfo<?php echo esc_html($identifier); ?> = window._additionalInfo<?php echo esc_html($identifier); ?> || {};
            <?php
            echo esc_html('window._additionalInfo'.$identifier.'.min = '.$this->defaultValues->min.';');
            echo esc_html('window._additionalInfo'.$identifier.'.max = '.$this->defaultValues->max.';');
            ?>
		</script>
        <?php
        if ($type === 'last') {
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
                        'onchange="addAdditionalInfo'.$identifier.'(\'min_date\', this.value)"'
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
        $this->querySelect = 'SELECT product.ID, product.post_title, product.post_date ';
        $this->query = 'FROM #__posts AS product LEFT JOIN #__postmeta AS product_sku ON product.ID = product_sku.post_id AND product_sku.meta_key = "_sku" ';
        $this->filters = [];
        $this->filters[] = 'product.post_type = "product"';
        $this->filters[] = 'product.post_status = "publish"';
        $this->searchFields = ['product.ID', 'product.post_title', 'product_sku.meta_value'];
        $this->pageInfo->order = 'product.ID';
        $this->elementIdTable = 'product';
        $this->elementIdColumn = 'ID';

        parent::prepareListing();

        if (!empty($this->pageInfo->filter_cat)) {
            $this->query .= 'JOIN #__term_relationships AS cat ON product.ID = cat.object_id';
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

            $query = 'SELECT DISTINCT product.`ID` 
                    FROM #__posts AS product 
                    LEFT JOIN #__term_relationships AS cat ON product.ID = cat.object_id';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $where[] = 'cat.term_taxonomy_id IN ('.implode(',', $selectedArea).')';
            }

            $where[] = 'product.post_type = "product"';
            $where[] = 'product.post_status = "publish"';
            if (!empty($parameter->min_publish)) {
                $parameter->min_publish = acym_date(acym_replaceDate($parameter->min_publish), 'Y-m-d H:i:s', false);
                $where[] = 'product.post_date_gmt >= '.acym_escapeDB($parameter->min_publish);
            }

            if (!empty($parameter->onlynew)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $where[] = 'product.post_date_gmt > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                }
            }

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'product');
        }

        return $this->generateCampaignResult;
    }

    public function replaceIndividualContent(object $tag): string
    {
        $query = 'SELECT product.*
                    FROM #__posts AS product
                    WHERE product.post_type = "product" 
                        AND product.post_status = "publish"
                        AND product.ID = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);
        $product = wc_get_product($tag->id);

        if (empty($element) || empty($product)) return '';

        $varFields = $this->getCustomLayoutVars($element);

        $link = get_permalink($element->ID);
        $varFields['{link}'] = $link;

        $title = '';
        $varFields['{title}'] = $element->post_title;
        if (in_array('title', $tag->display)) $title = $varFields['{title}'];

        $afterTitle = '';
        $varFields['{price}'] = $product->get_price_html();
        if (in_array('price', $tag->display)) $afterTitle .= $varFields['{price}'];

        $imagePath = '';
        $imageHTML = $product->get_image('full');
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
        $varFields['{cats}'] = get_the_term_list($tag->id, 'product_cat', '', ', ');
        if (in_array('cats', $tag->display)) {
            $customFields[] = [
                $varFields['{cats}'],
                acym_translation('ACYM_CATEGORIES'),
            ];
        }

        $tmpCustomField = [];
        $varFields['{attribs}'] = '';
        $attributes = acym_loadResult('SELECT meta_value FROM #__postmeta WHERE meta_key = "_product_attributes" AND post_id = '.intval($tag->id));
        if (is_string($attributes)) {
            $attributes = unserialize($attributes);
            if (!empty($attributes)) {
                $varFields['{attribs}'] = [];
                foreach ($attributes as $oneAttribute) {
                    if ($oneAttribute['is_visible'] != 1) continue;

                    $varFields['{attribs}'][] = $oneAttribute['name'].': '.str_replace('|', ', ', $oneAttribute['value']);
                    $tmpCustomField[] = [
                        str_replace('|', ', ', $oneAttribute['value']),
                        $oneAttribute['name'],
                    ];
                }
                $varFields['{attribs}'] = implode('<br/>', $varFields['{attribs}']);
            }
        }

        if (in_array('attribs', $tag->display)) $customFields = array_merge($customFields, $tmpCustomField);

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
        $this->replaceOrderInformation($email, $user, $send);
        $this->replaceOrderedProducts($email, $user);
        $generated = $this->replaceLastPurchased($email, $user, $send);
        if ($generated === '' && (!isset($email->isTest) || $email->isTest !== true)) {
            return [
                'send' => false,
                'emogrifier' => false,
                'message' => acym_translationSprintf('ACYM_EMAIL_X_NOT_SENT_USER_X_NOT_BOUGHT_ENOUGH_PRODUCTS', $email->subject, $user->email),
            ];
        }

        $generatedCart = $this->replaceCart($email, $user, $send);
        if ($generatedCart === '' && (!isset($email->isTest) || $email->isTest !== true)) {
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

        if (empty($tags)) {
            return 0;
        }

        $this->tags = [];
        foreach ($tags as $oneTag => $parameter) {
            $minAtO = isset($parameter->min) && $parameter->min == 0;

            //We get the lastest orders
            if ($this->isHposActive()) {
                $postTypesOrder = wc_get_order_types();
                $postTypesOrder = array_map('acym_escapeDB', $postTypesOrder);

                $postStatusesOrder = wc_get_is_paid_statuses();
                $postStatusesOrder = array_map(function ($status) {
                    return 'wc-'.$status;
                }, $postStatusesOrder);
                $postStatusesOrder = array_map('acym_escapeDB', $postStatusesOrder);

                $query = 'SELECT `order`.id AS ID 
					FROM #__wc_orders AS `order`
					WHERE `order`.type IN ('.implode(',', $postTypesOrder).') 
						AND `order`.status IN ('.implode(',', $postStatusesOrder).') ';
                $userFilter = '`order`.billing_email = '.acym_escapeDB($user->email);
                if (!empty($user->cms_id)) {
                    $userFilter = '(`order`.customer_id = '.intval($user->cms_id).' OR '.$userFilter.') ';
                }
                $query .= ' AND '.$userFilter;

                if (!empty($parameter->min_date)) {
                    $query .= ' AND `order`.date_created_gmt > '.acym_escapeDB(acym_date(acym_replaceDate($parameter->min_date), 'Y-m-d', false));
                }

                $customerOrders = acym_loadObjectList($query);
            } else {
                $userFilter = [
                    [
                        'key' => '_billing_email',
                        'value' => $user->email,
                        'compare' => '=',
                    ],
                ];

                if (!empty($user->cms_id)) {
                    $userFilter = array_merge(
                        [
                            'relation' => 'OR',
                            [
                                'key' => '_customer_user',
                                'value' => intval($user->cms_id),
                                'compare' => '=',
                            ],
                        ],
                        $userFilter
                    );
                }

                $postStatusesOrder = wc_get_is_paid_statuses();
                $postStatusesOrder = array_map(function ($status) {
                    return 'wc-'.$status;
                }, $postStatusesOrder);

                $dataQuery = [
                    'numberposts' => -1,
                    'post_type' => wc_get_order_types(),
                    'post_status' => $postStatusesOrder,
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                    'meta_query' => $userFilter,
                ];

                if (!empty($parameter->min_date)) {
                    $minDate = acym_replaceDate($parameter->min_date);
                    $dataQuery['date_query'] = [
                        'after' => gmdate('Y-m-d', $minDate),
                    ];
                }
                $customerOrders = get_posts($dataQuery);
            }

            if (empty($customerOrders)) {
                $this->tags[$oneTag] = $minAtO ? '_EMPTYSEND_' : '';
                continue;
            }

            //We get the products from the orders
            $product_ids = [];
            foreach ($customerOrders as $customerOrder) {
                $order = wc_get_order($customerOrder->ID);
                $items = $order->get_items();
                foreach ($items as $item) {
                    $product_id = $item->get_product_id();
                    $product_ids[] = $product_id;
                }
            }

            $query = 'SELECT DISTINCT product.`ID` FROM #__posts AS product ';
            //We filters the products if we selected categories
            if (!empty($parameter->id)) {
                $selectedArea = $this->getSelectedArea($parameter);
                if (!empty($selectedArea)) {
                    $product_ids = array_unique($product_ids);
                    $query .= ' JOIN #__term_relationships AS cat ON product.ID = cat.object_id 
                    AND cat.term_taxonomy_id = '.implode(' OR cat.term_taxonomy_id = ', $selectedArea).'';
                }
            }

            $query .= ' WHERE product.ID IN ('.implode(',', $product_ids).')';

            if ($send) {
                $parameter->min = empty($parameter->min) && !$minAtO ? $this->minProductDisplayLastPurchased : $parameter->min;
            } else {
                $parameter->min = 0;
            }
            $parameter->max = empty($parameter->max) ? $this->maxProductDisplayLastPurchased : $parameter->max;

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'product');
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

    private function replaceOrderedProducts(object &$email, object &$user)
    {
        $tags = $this->pluginHelper->extractTags($email, 'woocommerce_ordered');
        if (empty($tags)) {
            return;
        }

        $tagsReplaced = [];
        foreach ($tags as $i => $oneTag) {
            if (isset($tagsReplaced[$i])) {
                continue;
            }

            $tagsReplaced[$i] = $this->replaceOrderedProduct($oneTag, $user, $email);
        }

        $this->pluginHelper->replaceTags($email, $tagsReplaced, true);

        $this->replaceOne($email);
    }

    private function replaceOrderedProduct(object $oneTag, object $user, object $email): string
    {
        if (empty($user->cms_id)) {
            return '';
        }

        $queueClass = new QueueClass();
        $mailClass = new MailClass();
        $params = $queueClass->getQueueParams($mailClass->getMainMailId($email->id), $user->id);

        $orderId = $params['woo_order_id'] ?? null;

        if (empty($orderId)) {
            //We get one random product
            $query = 'SELECT product.`ID` FROM #__posts AS product WHERE product.post_type = "product" AND product.post_status = "publish" AND product.`ID` IS NOT NULL';

            $oneTag->max = 1;
        } else {
            //We get the products from the orders
            $product_ids = [];
            $order = wc_get_order($orderId);
            foreach ($order->get_items() as $item_id => $item) {
                $product_id = $item->get_product_id();
                $product_ids[] = $product_id;
            }

            $query = 'SELECT DISTINCT product.`ID` FROM #__posts AS product ';
            $query .= ' WHERE product.ID IN ('.implode(',', $product_ids).')';
        }

        return $this->finalizeCategoryFormat($query, $oneTag, 'product');
    }

    private function replaceCart(&$email, $user, $send)
    {
        $tags = $this->pluginHelper->extractTags($email, 'cart'.$this->name);
        $tags = array_merge($tags, $this->pluginHelper->extractTags($email, $this->name.'_tags'));

        if (empty($tags)) return 0;

        // Get user session
        $sessionHandler = new \WC_Session_Handler();
        $session = $sessionHandler->get_session(empty($user->cms_id) ? 0 : $user->cms_id);
        $cart_items = maybe_unserialize($session['cart']);

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
            $product_ids = [];
            foreach ($cart_items as $oneItem) {
                $product_ids[] = $oneItem['product_id'];
            }

            $query = 'SELECT DISTINCT product.`ID` FROM #__posts AS product ';
            //We filters the products if we selected categories
            if (!empty($parameter->id)) {
                $selectedArea = $this->getSelectedArea($parameter);
                if (!empty($selectedArea)) {
                    $product_ids = array_unique($product_ids);
                    $query .= ' JOIN #__term_relationships AS cat ON product.ID = cat.object_id 
                    AND cat.term_taxonomy_id = '.implode(' OR cat.term_taxonomy_id = ', $selectedArea).'';
                }
            }

            $query .= ' WHERE product.ID IN ('.implode(',', $product_ids).')';

            if ($send) {
                $parameter->min = empty($parameter->min) && !$minAtO ? $this->minProductDisplayLastPurchased : $parameter->min;
            } else {
                $parameter->min = 0;
            }

            $parameter->max = empty($parameter->max) ? $this->maxProductDisplayLastPurchased : $parameter->max;

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'product');
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
        $tags = $this->pluginHelper->extractTags($email, 'woocommerce_coupon');
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
        if (empty($tag->code) || empty($tag->amount) || empty($tag->type) || !in_array($tag->type, ['fixed_cart', 'fixed_product', 'percent'])) return '';

        $intAttributes = ['amount', 'free', 'min', 'max', 'exclsale', 'use', 'items'];
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


        $coupon = [
            'post_title' => $couponCode,
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'shop_coupon',
        ];

        $couponId = wp_insert_post($coupon);

        // Add Details
        update_post_meta($couponId, 'discount_type', $tag->type);
        update_post_meta($couponId, 'coupon_amount', $tag->amount);
        update_post_meta($couponId, 'expiry_date', empty($tag->end) ? '' : acym_date(acym_replaceDate($tag->end), 'Y-m-d'));
        update_post_meta($couponId, 'date_expires', empty($tag->end) ? null : acym_replaceDate($tag->end));

        update_post_meta($couponId, 'usage_limit', $tag->use);
        update_post_meta($couponId, 'usage_limit_per_user', 0);
        update_post_meta($couponId, 'limit_usage_to_x_items', $tag->items);
        update_post_meta($couponId, 'usage_count', 0);

        update_post_meta($couponId, 'minimum_amount', empty($tag->min) ? '' : $tag->min);
        update_post_meta($couponId, 'maximum_amount', empty($tag->max) ? '' : $tag->max);

        update_post_meta($couponId, 'free_shipping', empty($tag->free) ? 'no' : 'yes');
        update_post_meta($couponId, 'exclude_sale_items', empty($tag->exclsale) ? 'no' : 'yes');


        update_post_meta($couponId, 'product_ids', implode(',', $this->cleanElements($tag->prod)));
        update_post_meta($couponId, 'exclude_product_ids', implode(',', $this->cleanElements($tag->exclprod)));

        update_post_meta($couponId, 'product_categories', $this->cleanElements($tag->cat));
        update_post_meta($couponId, 'exclude_product_categories', $this->cleanElements($tag->exclcat));


        // Apply the coupon only to the current user
        update_post_meta($couponId, 'individual_use', 'yes');
        update_post_meta($couponId, 'customer_email', [$user->email]);


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

    private function isWooCommerceFollowup(int $followupId): bool
    {
        $followupClass = new FollowupClass();
        $followup = $followupClass->getOneById($followupId);
        if (!empty($followup->trigger) && $followup->trigger === 'woocommerce_purchase') {
            return true;
        }

        return false;
    }
}
