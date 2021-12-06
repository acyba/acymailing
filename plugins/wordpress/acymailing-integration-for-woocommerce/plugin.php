<?php

use AcyMailing\Classes\FollowupClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\OverrideClass;
use AcyMailing\Controllers\CampaignsController;
use AcyMailing\Helpers\PluginHelper;
use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Helpers\TabHelper;
use AcyMailing\Classes\UserStatClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\AutomationClass;
use AcyMailing\Types\OperatorinType;

class plgAcymWoocommerce extends acymPlugin
{
    const MAILTYPE = 'woocommerce_cart';
    const FOLLOWTRIGGER = 'woocommerce_purchase';
    const MAIL_OVERRIDE_SOURCE_NAME = 'woocommerce';
    const PLUGIN_DISPLAYED_NAME = 'WooCommerce';
    const MIN_PRODUCT_DISPLAY_LAST_PURCHASED = 1;
    const MAX_PRODUCT_DISPLAY_LAST_PURCHASED = 3;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('woocommerce/woocommerce.php');
        $this->rootCategoryId = 0;

        $this->pluginDescription->name = 'WooCommerce';
        $this->pluginDescription->icon = ACYM_PLUGINS_URL.'/'.basename(__DIR__).'/icon.png';
        $this->pluginDescription->category = 'Content management';
        $this->pluginDescription->features = '["content","automation"]';
        $this->pluginDescription->description = '- Insert products and generate coupons in your emails<br />- Filter users based on their purchases';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'price' => ['ACYM_PRICE', true],
                'shortdesc' => ['ACYM_SHORT_DESCRIPTION', true],
                'desc' => ['ACYM_DESCRIPTION', false],
                'cats' => ['ACYM_CATEGORIES', false],
                'attribs' => ['ACYM_DETAILS', false],
            ];

            $this->initCustomView();

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->replaceOptions, $this->elementOptions),
                ],
                'track' => [
                    'type' => 'switch',
                    'label' => 'ACYM_TRACKING',
                    'value' => 0,
                    'info' => 'ACYM_TRACKING_WOOCOMMERCE_DESC',
                ],
                'cookie_expire' => [
                    'type' => 'number',
                    'label' => 'ACYM_COOKIE_EXPIRATION',
                    'value' => 1,
                    'info' => 'ACYM_COOKIE_EXPIRATION_DESC',
                    'post_text' => acym_translation('ACYM_HOURS'),
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
        $format->afterTitle = '{price} <br> {picthtml}';
        $format->afterArticle = '';
        $format->imagePath = '';
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
        ];
    }

    public function initElementOptionsCustomView()
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

    public function getPossibleIntegrations()
    {
        return $this->pluginDescription;
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;
        $this->prepareWPCategories('product_cat');

        $this->tagvalues = acym_loadObjectList(
            'SELECT term.term_id, term.`name`
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

        echo $this->displaySelectionZone($this->getCategoryListing());
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();
        $identifier = $this->name.'_tags';
        $tabHelper->startTab(acym_translation('ACYM_BY_TAG'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        echo $this->displaySelectionZone($this->getTagListing());
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

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
                'title' => __('Coupon expiry date', 'woocommerce'),
                'type' => 'date',
                'name' => 'end',
                'default' => '',
                'relativeDate' => '+',
            ],
            [
                'title' => __('Discount type', 'woocommerce'),
                'type' => 'select',
                'name' => 'type',
                'options' => [
                    'fixed_cart' => __('Fixed cart discount', 'woocommerce'),
                    'fixed_product' => __('Fixed product discount', 'woocommerce'),
                    'percent' => __('Percentage discount', 'woocommerce'),
                ],
            ],
            [
                'title' => __('Coupon amount', 'woocommerce'),
                'type' => 'number',
                'name' => 'amount',
                'default' => '0',
            ],
            [
                'title' => __('Allow free shipping', 'woocommerce'),
                'type' => 'boolean',
                'name' => 'free',
                'default' => false,
            ],
            [
                'title' => __('Exclude sale items', 'woocommerce'),
                'type' => 'boolean',
                'name' => 'exclsale',
                'default' => false,
            ],
            [
                'title' => __('Minimum spend', 'woocommerce'),
                'type' => 'number',
                'name' => 'min',
                'default' => '',
            ],
            [
                'title' => __('Maximum spend', 'woocommerce'),
                'type' => 'number',
                'name' => 'max',
                'default' => '',
            ],
            [
                'title' => __('Usage limit per coupon', 'woocommerce'),
                'type' => 'number',
                'name' => 'use',
                'default' => '1',
            ],
            [
                'title' => __('Limit usage to X items', 'woocommerce'),
                'type' => 'number',
                'name' => 'items',
                'default' => '',
            ],
            [
                'title' => __('Products', 'woocommerce'),
                'type' => 'text',
                'name' => 'prod',
                'class' => 'acym_plugin__larger_text_field',
                'default' => '',
            ],
            [
                'title' => __('Exclude products', 'woocommerce'),
                'type' => 'text',
                'name' => 'exclprod',
                'class' => 'acym_plugin__larger_text_field',
                'default' => '',
            ],
            [
                'title' => __('Product categories', 'woocommerce'),
                'type' => 'multiselect',
                'name' => 'cat',
                'options' => $wooCategories,
            ],
            [
                'title' => __('Exclude categories', 'woocommerce'),
                'type' => 'multiselect',
                'name' => 'exclcat',
                'options' => $wooCategories,
            ],
        ];

        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'simple', $this->defaultValues);

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

        echo $this->displaySelectionZone($this->lastOrCartContentInsert('last'));
        echo $this->pluginHelper->displayOptions($lastPurchasedOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();

        // Products in cart
        $identifier = 'cart'.$this->name;
        $tabHelper->startTab(
            acym_translation('ACYM_CART_PRODUCTS'),
            !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab
        );
        echo $this->displaySelectionZone($this->lastOrCartContentInsert('cart'));
        echo $this->pluginHelper->displayOptions($lastPurchasedOptions, $identifier, 'grouped', $this->defaultValues);
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
		if(!isset($this->defaultValues->min) || (empty($this->defaultValues->min) && $this->defaultValues->min !== '0')){
			$this->defaultValues->min = self::MIN_PRODUCT_DISPLAY_LAST_PURCHASED;
		}
		if(!isset($this->defaultValues->max) || (empty($this->defaultValues->max) && $this->defaultValues->max !== '0')){
			$this->defaultValues->max = self::MAX_PRODUCT_DISPLAY_LAST_PURCHASED;
		}
        ob_start();
        ?>
		<div class="cell grid-x">
			<label for="acym__woocommerce__<?php echo $partId; ?>__product__number<?php echo $endIdMin; ?>" class="cell medium-6">
                <?php echo acym_translation('ACYM_MIN_NB_ELEMENTS').acym_info('ACYM_MIN_NUMBER_OF_PRODUCTS_DESC'); ?>
			</label>
			<input type="number"
				   id="acym__woocommerce__<?php echo $partId; ?>__product__number<?php echo $endIdMin; ?>"
				   class="cell medium-6"
				   value="<?php echo $this->defaultValues->min; ?>"
				   name="min"
				   onchange="addAdditionalInfo<?php echo $identifier; ?>('min', this.value)">
		</div>
		<div class="cell grid-x">
			<label for="acym__woocommerce__<?php echo $partId; ?>__product__number<?php echo $endIdMax; ?>" class="cell medium-6">
                <?php echo acym_translation('ACYM_MAX_NB_ELEMENTS').acym_info('ACYM_MAX_NUMBER_OF_PRODUCTS_DESC'); ?>
			</label>
			<input type="number"
				   id="acym__woocommerce__<?php echo $partId; ?>__product__number<?php echo $endIdMax; ?>"
				   class="cell medium-6"
				   value="<?php echo $this->defaultValues->max; ?>"
				   name="max"
				   onchange="addAdditionalInfo<?php echo $identifier; ?>('max', this.value)">
		</div>
		<div class="cell grid-x">
			<label for="acym__woocommerce__<?php echo $partId; ?>__cat" class="cell medium-6">
                <?php echo acym_translation('ACYM_CATEGORY_FILTER').acym_info('ACYM_CATEGORY_FILTER_DESC'); ?>
			</label>
			<div class="cell medium-6 acym__woocommerce__<?php echo $partId; ?>__cat__container">
                <?php echo acym_selectMultiple($this->catvalues, 'cat', $selectedArea, [
                        'id' => 'acym__woocommerce__'.$partId.'__cat',
                        'onchange' => '_selectedRows'.$identifier.' = {}
                        				for(let option of this.options){
                        					if(option.selected) _selectedRows'.$identifier.'[option.value] = true;
                        				} 	
                        				updateDynamic'.$identifier.'();',
                    ]); ?>
			</div>
		</div>
		<script type="text/javascript">
            var _additionalInfo<?php echo $identifier; ?> = {};
            <?php
            echo '_additionalInfo'.$identifier.'[\'min\']='.$this->defaultValues->min.';';
            echo '_additionalInfo'.$identifier.'[\'max\']='.$this->defaultValues->max.';';
            ?>
		</script>
        <?php
        if ($type == 'last') {
            ?>
			<div class="cell grid-x">
				<label class="cell medium-6">
                    <?php echo acym_translation('ACYM_START_DATE').acym_info('ACYM_START_DATE_PURCHASED_PRODUCT_DESC'); ?>
				</label>
                <?php echo acym_dateField(
                    'min_date',
                    empty($this->defaultValues->min_date) ? '' : $this->defaultValues->min_date,
                    'cell medium-6',
                    'onchange="addAdditionalInfo'.$identifier.'(\'min_date\', this.value)"'
                ); ?>
			</div>
            <?php
        }

        return ob_get_clean();
    }

    public function prepareListing()
    {
        $this->querySelect = 'SELECT product.ID, product.post_title, product.post_date ';
        $this->query = 'FROM #__posts AS product ';
        $this->filters = [];
        $this->filters[] = 'product.post_type = "product"';
        $this->filters[] = 'product.post_status = "publish"';
        $this->searchFields = ['product.ID', 'product.post_title'];
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

    public function replaceContent(&$email, $send)
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

    public function generateByCategory(&$email)
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

    public function replaceIndividualContent($tag)
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
        $format->link = empty($tag->clickable) ? '' : $link;
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }

    public function replaceUserInformation(&$email, &$user, $send = true)
    {
        if (empty($user)) return;
        $this->_replaceCoupons($email, $user, $send);
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

        if ($generated == 1 || $generatedCart == 1) return ['send' => true, 'emogrifier' => true];
    }

    private function replaceLastPurchased(&$email, $user, $send)
    {
        $tags = $this->pluginHelper->extractTags($email, 'last'.$this->name);
        $tags = array_merge($tags, $this->pluginHelper->extractTags($email, $this->name.'_tags'));

        if (empty($tags)) return 0;

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
            //We get the lastest orders
            $dataQuery = [
                'numberposts' => -1,
                'meta_key' => '_customer_user',
                'meta_value' => $user->cms_id,
                'post_type' => wc_get_order_types(),
                'post_status' => array_keys(wc_get_is_paid_statuses()),
                'meta_query' => [
                    [
                        'key' => '_customer_user',
                        'value' => intval($user->cms_id),
                        'compare' => '=',
                    ],
                ],
            ];
            if (!empty($parameter->min_date)) {
                $minDate = acym_replaceDate($parameter->min_date);
                $dataQuery['date_query'] = [
                    'after' => date('Y-m-d', $minDate),
                ];
            }
            $customer_orders = get_posts($dataQuery);

            if ($minAtO && empty($customer_orders)) {
                $this->tags[$oneTag] = '_EMPTYSEND_';
                continue;
            }

            if (empty($customer_orders)) {
                $this->tags[$oneTag] = '';
                continue;
            }

            //We get the products from the orders
            $product_ids = [];
            foreach ($customer_orders as $customer_order) {
                $order = wc_get_order($customer_order->ID);
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
                $parameter->min = empty($parameter->min) && !$minAtO ? self::MIN_PRODUCT_DISPLAY_LAST_PURCHASED : $parameter->min;
            } else {
                $parameter->min = 0;
            }
            $parameter->max = empty($parameter->max) ? self::MAX_PRODUCT_DISPLAY_LAST_PURCHASED : $parameter->max;

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
                $parameter->min = empty($parameter->min) && !$minAtO ? self::MIN_PRODUCT_DISPLAY_LAST_PURCHASED : $parameter->min;
            } else {
                $parameter->min = 0;
            }

            $parameter->max = empty($parameter->max) ? self::MAX_PRODUCT_DISPLAY_LAST_PURCHASED : $parameter->max;

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

    private function _replaceCoupons(&$email, &$user, $send = true)
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

    public function searchProduct()
    {
        $ids = $this->getIdsSelectAjax();

        if (!empty($ids)) {
            $args = [
                'post__in' => $ids,
                'post_type' => 'product',
            ];
            $posts = new WP_Query($args);

            $value = [];
            if ($posts->have_posts()) {
                foreach ($posts->get_posts() as $post) {
                    $value[] = ['text' => $post->post_title, 'value' => $post->ID];
                }
            }
            echo json_encode($value);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');

        $search_results = new WP_Query([
                's' => $search,
                'post_status' => 'publish',
                'ignore_sticky_posts' => 1,
                'post_type' => 'product',
                'posts_per_page' => 20,
            ]);

        if ($search_results->have_posts()) {
            while ($search_results->have_posts()) {
                $search_results->the_post();
                $return[] = [$search_results->post->ID, $search_results->post->post_title];
            }
        }

        echo json_encode($return);
        exit;
    }

    public function searchCat()
    {
        $ids = $this->getIdsSelectAjax();

        if (!empty($ids)) {
            $cats = $this->getWooCategories($ids);

            $value = [];
            if (!empty($cats)) {
                foreach ($cats as $cat) {
                    $value[] = ['text' => $cat->name, 'value' => $cat->term_id];
                }
            }
            echo json_encode($value);
            exit;
        }

        $search = acym_getVar('string', 'search', '');
        $cats = $this->getWooCategories([], $search);
        $categories = [];
        foreach ($cats as $oneCat) {
            $categories[] = [$oneCat->term_id, $oneCat->name];
        }

        echo json_encode($categories);
        exit;
    }

    private function getWooCategories($ids = [], $nameSearch = '')
    {
        $query = 'SELECT term.`term_id`, term.`name` 
			FROM #__terms AS term 
			JOIN #__term_taxonomy AS tax 
				ON term.`term_id` = tax.`term_id` 
			WHERE tax.`taxonomy` = "product_cat" ';
        if (!empty($ids)) {
            acym_arrayToInteger($ids);
            $query .= ' AND term.`term_id` IN ("'.implode('","', $ids).'")';
        }
        if (!empty($nameSearch)) {
            $query .= ' AND term.`name` LIKE '.acym_escapeDB('%'.$nameSearch.'%');
        }
        $query .= ' ORDER BY term.`name`';

        return acym_loadObjectList($query, 'term_id');
    }

    public function onAcymDeclareConditions(&$conditions)
    {
        $categories = [
            'any' => acym_translation('ACYM_ANY_CATEGORY'),
        ];
        $cats = $this->getWooCategories();
        foreach ($cats as $oneCat) {
            $categories[$oneCat->term_id] = $oneCat->name;
        }

        $conditions['user']['woopurchased'] = new stdClass();
        $conditions['user']['woopurchased']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'WooCommerce', acym_translation('ACYM_PURCHASED'));
        $conditions['user']['woopurchased']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['woopurchased']->option .= '<div class="cell acym_vcenter shrink">'.acym_translation('ACYM_BOUGHT').'</div>';

        $conditions['user']['woopurchased']->option .= '<div class="intext_select_automation cell">';
        $ajaxParams = json_encode([
                'plugin' => 'plgAcymWoocommerce',
                'trigger' => 'searchProduct',
            ]);
        $conditions['user']['woopurchased']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][woopurchased][product]',
            null,
            'class="acym__select acym_select2_ajax" data-placeholder="'.acym_translation('ACYM_AT_LEAST_ONE_PRODUCT', true).'" data-params="'.acym_escape($ajaxParams).'"'
        );
        $conditions['user']['woopurchased']->option .= '</div>';

        $conditions['user']['woopurchased']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['woopurchased']->option .= acym_select(
            $categories,
            'acym_condition[conditions][__numor__][__numand__][woopurchased][category]',
            'any',
            'class="acym__select"'
        );
        $conditions['user']['woopurchased']->option .= '</div>';

        $conditions['user']['woopurchased']->option .= '</div>';

        $conditions['user']['woopurchased']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['woopurchased']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][woopurchased][datemin]', '', 'cell shrink');
        $conditions['user']['woopurchased']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['woopurchased']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_DATE_CREATED').'</span>';
        $conditions['user']['woopurchased']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['woopurchased']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][woopurchased][datemax]', '', 'cell shrink');
        $conditions['user']['woopurchased']->option .= '</div>';


        $paymentMethods = ['any' => acym_translation('ACYM_ANY_PAYMENT_METHOD')];
        if (function_exists('WC')) {
            $payments = WC()->payment_gateways()->payment_gateways;
            foreach ($payments as $oneMethod) {
                $paymentMethods[$oneMethod->id] = $oneMethod->title;
            }
        }

        $conditions['user']['wooreminder'] = new stdClass();
        $conditions['user']['wooreminder']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'WooCommerce', acym_translation('ACYM_REMINDER'));
        $conditions['user']['wooreminder']->option = '<div class="cell">';
        $conditions['user']['wooreminder']->option .= acym_translationSprintf(
            'ACYM_ORDER_WITH_STATUS',
            '<input type="number" name="acym_condition[conditions][__numor__][__numand__][wooreminder][days]" value="1" min="1" class="intext_input"/>',
            '<div class="intext_select_automation cell margin-right-1">'.acym_select(
                $this->getOrderStatuses(),
                'acym_condition[conditions][__numor__][__numand__][wooreminder][status]',
                'wc-pending',
                'class="acym__select"'
            ).'</div>'
        );
        $conditions['user']['wooreminder']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['wooreminder']->option .= acym_select(
            $paymentMethods,
            'acym_condition[conditions][__numor__][__numand__][wooreminder][payment]',
            'any',
            'class="acym__select"'
        );
        $conditions['user']['wooreminder']->option .= '</div>';
        $conditions['user']['wooreminder']->option .= '</div>';

        // WooCommerce Subscriptions filter
        if (acym_isExtensionActive('woocommerce-subscriptions/woocommerce-subscriptions.php')) {
            $conditions['user']['woosubscription'] = new stdClass();
            $conditions['user']['woosubscription']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'WooCommerce', __('Subscription', 'woocommerce-subscriptions'));
            $conditions['user']['woosubscription']->option = '<div class="cell grid-x grid-margin-x">';

            $conditions['user']['woosubscription']->option .= '<div class="cell shrink acym_vcenter">';
            $conditions['user']['woosubscription']->option .= acym_translation('ACYM_HAS_SUBSCRIPTION');
            $conditions['user']['woosubscription']->option .= '</div>';

            $conditions['user']['woosubscription']->option .= '<div class="intext_select_automation cell">';
            $ajaxParams = json_encode([
                    'plugin' => 'plgAcymWoocommerce',
                    'trigger' => 'searchProduct',
                ]);
            $conditions['user']['woosubscription']->option .= acym_select(
                [],
                'acym_condition[conditions][__numor__][__numand__][woosubscription][product]',
                null,
                'class="acym__select acym_select2_ajax" data-placeholder="'.acym_translation('ACYM_ANY_PRODUCT', true).'" data-params="'.acym_escape($ajaxParams).'"'
            );
            $conditions['user']['woosubscription']->option .= '</div>';

            $conditions['user']['woosubscription']->option .= '<div class="intext_select_automation cell">';
            $conditions['user']['woosubscription']->option .= acym_select(
                $categories,
                'acym_condition[conditions][__numor__][__numand__][woosubscription][category]',
                'any',
                'class="acym__select"'
            );
            $conditions['user']['woosubscription']->option .= '</div>';

            $subscriptionStatuses = [
                'any' => acym_translation('ACYM_SUBSCRIPTION_STATUS'),
            ];
            $statuses = wcs_get_subscription_statuses();
            foreach ($statuses as $status => $statusName) {
                $subscriptionStatuses[$status] = $statusName;
            }
            $conditions['user']['woosubscription']->option .= '<div class="intext_select_automation cell">';
            $conditions['user']['woosubscription']->option .= acym_select(
                $subscriptionStatuses,
                'acym_condition[conditions][__numor__][__numand__][woosubscription][status]',
                'any',
                'class="acym__select"'
            );
            $conditions['user']['woosubscription']->option .= '</div>';

            $conditions['user']['woosubscription']->option .= '</div>';

            $conditions['user']['woosubscription']->option .= '<div class="cell grid-x grid-margin-x">';
            $conditions['user']['woosubscription']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][woosubscription][datemin]', '', 'cell shrink');
            $conditions['user']['woosubscription']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
            $conditions['user']['woosubscription']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_START_DATE').'</span>';
            $conditions['user']['woosubscription']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
            $conditions['user']['woosubscription']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][woosubscription][datemax]', '', 'cell shrink');
            $conditions['user']['woosubscription']->option .= '</div>';

            $conditions['user']['woosubscription']->option .= '<div class="cell grid-x grid-margin-x">';
            $conditions['user']['woosubscription']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][woosubscription][nextdatemin]', '', 'cell shrink');
            $conditions['user']['woosubscription']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
            $conditions['user']['woosubscription']->option .= '<span class="acym_vcenter">'.__('Next Payment', 'woocommerce-subscriptions').'</span>';
            $conditions['user']['woosubscription']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
            $conditions['user']['woosubscription']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][woosubscription][nextdatemax]', '', 'cell shrink');
            $conditions['user']['woosubscription']->option .= '</div>';

            $conditions['user']['woosubscription']->option .= '<div class="cell grid-x grid-margin-x">';
            $conditions['user']['woosubscription']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][woosubscription][enddatemin]', '', 'cell shrink');
            $conditions['user']['woosubscription']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
            $conditions['user']['woosubscription']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_END_DATE').'</span>';
            $conditions['user']['woosubscription']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
            $conditions['user']['woosubscription']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][woosubscription][enddatemax]', '', 'cell shrink');
            $conditions['user']['woosubscription']->option .= '</div>';
        }
    }

    public function onAcymDeclareFilters(&$filters)
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_woopurchased(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_woopurchased($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    private function processConditionFilter_woopurchased(&$query, $options, $num)
    {
        $conditions = [];
        $conditions[] = 'post'.$num.'.post_type = "shop_order"';
        $conditions[] = 'post'.$num.'.post_status = "wc-completed"';

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $conditions[] = 'post'.$num.'.post_date > '.acym_escapeDB(acym_date($options['datemin'], 'Y-m-d H:i:s'));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $conditions[] = 'post'.$num.'.post_date < '.acym_escapeDB(acym_date($options['datemax'], 'Y-m-d H:i:s'));
            }
        }

        $query->join['woopurchased_post'.$num] = '#__posts AS post'.$num.' ON '.implode(' AND ', $conditions);

        $query->join['woopurchased_postmeta'.$num] = '#__postmeta AS postmeta'.$num.' ON postmeta'.$num.'.post_id = post'.$num.'.ID AND postmeta'.$num.'.meta_value = user.cms_id AND postmeta'.$num.'.meta_value != 0 AND postmeta'.$num.'.meta_key = "_customer_user"';

        if (!empty($options['product'])) {
            $query->join['woopurchased_order_items'.$num] = '#__woocommerce_order_items AS woooi'.$num.' ON post'.$num.'.ID = woooi'.$num.'.order_id AND woooi'.$num.'.order_item_type = "line_item"';
            $query->join['woopurchased_order_itemmeta'.$num] = '#__woocommerce_order_itemmeta AS woooim'.$num.' ON woooi'.$num.'.order_item_id = woooim'.$num.'.order_item_id AND woooim'.$num.'.meta_key = "_product_id" AND woooim'.$num.'.meta_value = '.intval(
                    $options['product']
                );
        } elseif (!empty($options['category']) && $options['category'] != 'any') {
            $query->join['woopurchased_order_items'.$num] = '#__woocommerce_order_items AS woooi'.$num.' ON post'.$num.'.ID = woooi'.$num.'.order_id AND woooi'.$num.'.order_item_type = "line_item"';
            $query->join['woopurchased_order_itemmeta'.$num] = '#__woocommerce_order_itemmeta AS woooim'.$num.' ON woooi'.$num.'.order_item_id = woooim'.$num.'.order_item_id AND woooim'.$num.'.meta_key = "_product_id"';
            $query->join['woopurchased_cat_map'.$num] = '#__term_relationships AS termrel'.$num.' ON termrel'.$num.'.object_id = woooim'.$num.'.meta_value';
            $query->join['woopurchased_cat'.$num] = '#__term_taxonomy AS termtax'.$num.' ON termtax'.$num.'.term_taxonomy_id = termrel'.$num.'.term_taxonomy_id AND termtax'.$num.'.term_id = '.intval(
                    $options['category']
                );
        }
    }

    public function onAcymProcessCondition_woopurchased(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_woopurchased($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function onAcymProcessFilter_woopurchased(&$query, $options, $num)
    {
        $this->processConditionFilter_woopurchased($query, $options, $num);
    }

    public function onAcymProcessFilterCount_wooreminder(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_wooreminder($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    private function processConditionFilter_wooreminder(&$query, $options, $num)
    {
        $options['days'] = intval($options['days']);

        $query->join['wooreminder_post'.$num] = '#__posts AS post'.$num.' ON post'.$num.'.post_type = "shop_order"';
        $query->join['wooreminder_postmeta'.$num] = '#__postmeta AS postmeta'.$num.' ON postmeta'.$num.'.post_id = post'.$num.'.ID AND postmeta'.$num.'.meta_value = user.cms_id AND postmeta'.$num.'.meta_key = "_customer_user"';
        $query->where[] = 'user.cms_id != 0';
        $query->where[] = 'SUBSTRING(post'.$num.'.post_date, 1, 10) = '.acym_escapeDB(date('Y-m-d', time() - ($options['days'] * 86400)));
        $query->where[] = 'post'.$num.'.post_status = '.acym_escapeDB($options['status']);

        if (!empty($options['payment']) && $options['payment'] != 'any') {
            $query->join['wooreminder_postmeta'.$num] = '#__postmeta AS postmeta'.$num.' ON postmeta'.$num.'.post_id = post'.$num.'.ID';
            $query->where[] = 'postmeta'.$num.'.meta_key = "_payment_method"';
            $query->where[] = 'postmeta'.$num.'.meta_value = '.acym_escapeDB($options['payment']);
        }
    }

    public function onAcymProcessCondition_wooreminder(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_wooreminder($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function onAcymProcessFilter_wooreminder(&$query, $options, $num)
    {
        $this->processConditionFilter_wooreminder($query, $options, $num);
    }

    public function onAcymProcessFilterCount_woosubscription(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_woosubscription($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    private function processConditionFilter_woosubscription(&$query, $options, $num)
    {
        // Retrieve the Acy users with subscriptions
        $conditions = [];
        $conditions[] = 'post'.$num.'.post_type = "shop_subscription"';

        $statuses = wcs_get_subscription_statuses();
        if (!empty($options['status']) && in_array($options['status'], array_keys($statuses))) {
            $conditions[] = 'post'.$num.'.post_status = '.acym_escapeDB($options['status']);
        }

        $query->join['woosubscription_post'.$num] = '#__posts AS post'.$num.' ON '.implode(' AND ', $conditions);

        $query->join['woosubscription_user'.$num] = '#__postmeta AS wcsuser'.$num.' 
        	ON wcsuser'.$num.'.post_id = post'.$num.'.ID 
        	AND wcsuser'.$num.'.meta_value = user.cms_id 
        	AND wcsuser'.$num.'.meta_value != 0 
        	AND wcsuser'.$num.'.meta_key = "_customer_user"';


        // Apply condition on product / category linked to the subscription
        if (!empty($options['product'])) {
            $query->join['woosubscription_order_items'.$num] = '#__woocommerce_order_items AS woooi'.$num.' 
            	ON post'.$num.'.ID = woooi'.$num.'.order_id 
            	AND woooi'.$num.'.order_item_type = "line_item"';
            $query->join['woosubscription_order_itemmeta'.$num] = '#__woocommerce_order_itemmeta AS woooim'.$num.' 
            	ON woooi'.$num.'.order_item_id = woooim'.$num.'.order_item_id 
            	AND woooim'.$num.'.meta_key = "_product_id" 
            	AND woooim'.$num.'.meta_value = '.intval($options['product']);
        } elseif (!empty($options['category']) && $options['category'] != 'any') {
            $query->join['woosubscription_order_items'.$num] = '#__woocommerce_order_items AS woooi'.$num.' 
            	ON post'.$num.'.ID = woooi'.$num.'.order_id 
            	AND woooi'.$num.'.order_item_type = "line_item"';
            $query->join['woosubscription_order_itemmeta'.$num] = '#__woocommerce_order_itemmeta AS woooim'.$num.' 
            	ON woooi'.$num.'.order_item_id = woooim'.$num.'.order_item_id 
            	AND woooim'.$num.'.meta_key = "_product_id"';
            $query->join['woosubscription_cat_map'.$num] = '#__term_relationships AS termrel'.$num.' ON termrel'.$num.'.object_id = woooim'.$num.'.meta_value';
            $query->join['woosubscription_cat'.$num] = '#__term_taxonomy AS termtax'.$num.' 
            	ON termtax'.$num.'.term_taxonomy_id = termrel'.$num.'.term_taxonomy_id 
            	AND termtax'.$num.'.term_id = '.intval($options['category']);
        }


        // Prepare date fields values
        $dateOptions = ['datemin', 'datemax', 'nextdatemin', 'nextdatemax', 'enddatemin', 'enddatemax'];
        foreach ($dateOptions as $oneDateOption) {
            if (empty($options[$oneDateOption])) continue;

            $options[$oneDateOption] = acym_replaceDate($options[$oneDateOption]);
            if (!is_numeric($options[$oneDateOption])) $options[$oneDateOption] = strtotime($options[$oneDateOption]);
            if (!empty($options[$oneDateOption])) {
                $options[$oneDateOption] = acym_date($options[$oneDateOption], 'Y-m-d H:i:s', false);
            }
        }

        // Apply date conditions
        $dateOptions = [
            'date' => '_schedule_start',
            'nextdate' => '_schedule_next_payment',
            'enddate' => '_schedule_end',
        ];
        foreach ($dateOptions as $oneDateType => $metaKey) {
            if (!empty($options[$oneDateType.'min']) || !empty($options[$oneDateType.'max'])) {
                $query->join['woosubscription_meta'.$oneDateType.$num] = '#__postmeta AS wcs_'.$oneDateType.$num.' 
					ON wcs_'.$oneDateType.$num.'.post_id = post'.$num.'.ID 
					AND wcs_'.$oneDateType.$num.'.meta_value != 0 
					AND wcs_'.$oneDateType.$num.'.meta_key = '.acym_escapeDB($metaKey);

                if (!empty($options[$oneDateType.'min'])) {
                    $query->join['woosubscription_meta'.$oneDateType.$num] .= ' AND wcs_'.$oneDateType.$num.'.meta_value > '.acym_escapeDB($options[$oneDateType.'min']);
                }

                if (!empty($options[$oneDateType.'max'])) {
                    $query->join['woosubscription_meta'.$oneDateType.$num] .= ' AND wcs_'.$oneDateType.$num.'.meta_value < '.acym_escapeDB($options[$oneDateType.'max']);
                }
            }
        }
    }

    public function onAcymProcessCondition_woosubscription(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_woosubscription($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function onAcymProcessFilter_woosubscription(&$query, $options, $num)
    {
        $this->processConditionFilter_woosubscription($query, $options, $num);
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        if (!empty($automationCondition['wooreminder'])) {

            $paymentMethods = ['any' => acym_translation('ACYM_ANY_PAYMENT_METHOD')];
            if (function_exists('WC')) {
                $payments = WC()->payment_gateways()->payment_gateways;
                foreach ($payments as $oneMethod) {
                    $paymentMethods[$oneMethod->id] = $oneMethod->title;
                }
            }

            $orderStatus = $this->getOrderStatuses();

            $automationCondition = acym_translationSprintf(
                'ACYM_CONDITION_ECOMMERCE_REMINDER',
                $paymentMethods[$automationCondition['wooreminder']['payment']],
                intval($automationCondition['wooreminder']['days']),
                $orderStatus[$automationCondition['wooreminder']['status']]
            );
        }

        if (!empty($automationCondition['woopurchased'])) {

            if (empty($automationCondition['woopurchased']['product'])) {
                $product = acym_translation('ACYM_AT_LEAST_ONE_PRODUCT');
            } else {
                $product = get_post($automationCondition['woopurchased']['product']);
                $product = $product->post_title;
            }

            $cats = $this->getWooCategories();
            if (empty($cats[$automationCondition['woopurchased']['category']])) {
                $category = acym_translation('ACYM_ANY_CATEGORY');
            } else {
                $category = $cats[$automationCondition['woopurchased']['category']]->name;
            }

            $finalText = acym_translationSprintf('ACYM_CONDITION_PURCHASED', $product, $category);

            $dates = [];
            if (!empty($automationCondition['woopurchased']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['woopurchased']['datemin'], true);
            }

            if (!empty($automationCondition['woopurchased']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['woopurchased']['datemax'], true);
            }

            if (!empty($dates)) {
                $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $automationCondition = $finalText;
        }

        if (!empty($automationCondition['woosubscription'])) {

            if (empty($automationCondition['woosubscription']['product'])) {
                $product = acym_translation('ACYM_AT_LEAST_ONE_PRODUCT');
            } else {
                $product = get_post($automationCondition['woosubscription']['product']);
                $product = $product->post_title;
            }

            $cats = $this->getWooCategories();
            if (empty($cats[$automationCondition['woosubscription']['category']])) {
                $category = acym_translation('ACYM_ANY_CATEGORY');
            } else {
                $category = $cats[$automationCondition['woosubscription']['category']]->name;
            }

            $finalText = acym_translationSprintf('ACYM_HAS_SUBSCRIPTION_SUMMARY', $product, $category);
            if (!empty($automationCondition['woosubscription']['status']) && $automationCondition['woosubscription']['status'] !== 'any') {
                $statuses = wcs_get_subscription_statuses();
                if (in_array($automationCondition['woosubscription']['status'], array_keys($statuses))) {
                    $finalText .= '<br/>'.acym_translation('ACYM_SUBSCRIPTION_STATUS').' : '.$statuses[$automationCondition['woosubscription']['status']];
                }
            }

            $dateOptions = [
                'date' => acym_translation('ACYM_START_DATE'),
                'nextdate' => __('Next Payment', 'woocommerce-subscriptions'),
                'enddate' => acym_translation('ACYM_END_DATE'),
            ];
            foreach ($dateOptions as $oneDateOption => $dateLabel) {
                $dates = [];
                if (!empty($automationCondition['woosubscription'][$oneDateOption.'min'])) {
                    $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['woosubscription'][$oneDateOption.'min'], true);
                }

                if (!empty($automationCondition['woosubscription'][$oneDateOption.'max'])) {
                    $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['woosubscription'][$oneDateOption.'max'], true);
                }

                if (!empty($dates)) {
                    $finalText .= '<br/>'.$dateLabel.' : '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
                }
            }

            $automationCondition = $finalText;
        }
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }

    public function onRegacyOptionsDisplay($lists)
    {
        if (!is_plugin_active('woocommerce/woocommerce.php')) return;

        ?>
		<div class="acym__configuration__subscription acym__content acym_area padding-vertical-1 padding-horizontal-2">
			<div class="acym__title acym__title__secondary"><?php echo acym_escape(acym_translationSprintf('ACYM_XX_INTEGRATION', 'WooCommerce')); ?></div>

			<div class="grid-x margin-y">
				<div class="cell grid-x grid-margin-x">
                    <?php
                    $subOptionTxt = acym_translationSprintf('ACYM_SUBSCRIBE_OPTION_ON_XX_CHECKOUT', 'WooCommerce').acym_info('ACYM_SUBSCRIBE_OPTION_ON_XX_CHECKOUT_DESC');
                    echo acym_switch(
                        'config[woocommerce_sub]',
                        $this->config->get('woocommerce_sub'),
                        $subOptionTxt,
                        [],
                        'xlarge-3 medium-5 small-9',
                        'auto',
                        '',
                        'acym__config__woocommerce_sub'
                    );
                    ?>
				</div>
				<div class="cell grid-x margin-y" id="acym__config__woocommerce_sub">
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__woocommerce-text">
                            <?php echo acym_translation('ACYM_SUBSCRIBE_CAPTION').acym_info('ACYM_SUBSCRIBE_CAPTION_OPT_DESC'); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
						<input type="text"
							   name="config[woocommerce_text]"
							   id="acym__config__woocommerce-text"
							   value="<?php echo acym_escape($this->config->get('woocommerce_text')); ?>" />
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__woocommerce-autolists">
                            <?php echo acym_translation('ACYM_AUTO_SUBSCRIBE_TO').acym_info('ACYM_SUBSCRIBE_OPTION_AUTO_SUBSCRIBE_TO_DESC'); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        echo acym_selectMultiple(
                            $lists,
                            'config[woocommerce_autolists]',
                            explode(',', $this->config->get('woocommerce_autolists', '')),
                            ['class' => 'acym__select', 'id' => 'acym__config__woocommerce-autolists'],
                            'id',
                            'name'
                        );
                        ?>
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
				</div>
			</div>
		</div>
        <?php
    }

    public function onBeforeSaveConfigFields(&$formData)
    {
        $formData['woocommerce_autolists'] = !empty($formData['woocommerce_autolists']) ? $formData['woocommerce_autolists'] : [];
    }

    public function onAcymIsTrackingWoocommerce(&$trackingWoocommerce)
    {
        $trackingWoocommerce = $this->getParam('track', 0) == 1;
    }

    public function formatCookie(&$cookie, &$formattedCookie)
    {
        if (strpos($cookie, '_') === false) return;

        $cookie = explode('_', $cookie);

        foreach ($cookie as $value) {
            if (strpos($value, '-') === false) continue;

            $value = explode('-', $value, 2);
            $formattedCookie[$value[0]] = $value[1];
        }
    }

    public function getCurrency(&$currency)
    {
        if (empty($currency)) $currency = get_woocommerce_currency();
        $woocommerceCurrencies = get_woocommerce_currency_symbols();
        $currency = $woocommerceCurrencies[$currency];
    }

    public function onAcymInitWordpressAddons()
    {
        add_filter('woocommerce_checkout_fields', [$this, 'addSubscriptionFieldWC']);
        add_action('woocommerce_checkout_order_processed', [$this, 'subscribeUserOnCheckoutWC'], 15, 3);
        if (acym_isTrackingSalesActive()) {
            add_action('woocommerce_payment_successful_result', [$this, 'trackingWoocommerce'], 10, 2);
            $this->trackingWoocommerceAddCookie();
        }
        add_action('woocommerce_order_status_changed', [$this, 'onWooCommerceOrderStatusChange'], 50, 4);
        add_filter('woocommerce_mail_callback_params', [$this, 'onWooCommerceEmailSend'], 10, 2);
    }

    public function acym_displayTrackingMessage(&$message)
    {

        $remindme = json_decode($this->config->get('remindme', '[]'), true);

        if ($this->getParam('track', 0) != 1 && acym_isExtensionActive('woocommerce/woocommerce.php') && acym_isAdmin() && ACYM_CMS == 'wordpress' && !in_array(
                'woocommerce_tracking',
                $remindme
            )) {
            $message = acym_translation('ACYM_WOOCOMMERCE_TRACKING_INFO');
            $message .= ' <a target="_blank" href="https://docs.acymailing.com/addons/wordpress-add-ons/woocommerce#tracking">'.acym_translation('ACYM_READ_MORE').'</a>';
            $message .= ' <a href="#" class="acym__do__not__remindme acym__do__not__remindme__info" title="woocommerce_tracking">'.acym_translation('ACYM_DO_NOT_REMIND_ME').'</a>';
            acym_display($message, 'info', false);
        } elseif (!in_array('woocommerce_tracking', $remindme)) {
            $remindme[] = 'woocommerce_tracking';
            $this->config->save(['remindme' => json_encode($remindme)]);
        }
    }

    public function trackingWoocommerceAddCookie()
    {
        $trackingWoo = acym_getVar('string', 'linkReferal', '');
        if (empty($trackingWoo)) return;

        $trackingWoo = explode('-', $trackingWoo);

        $hours = $this->getParam('cookie_expire', 1);

        $time = time() + (3600 * $hours);

        setcookie('acym_track_woocommerce', 'mailid-'.$trackingWoo[0].'_userid-'.$trackingWoo[1], $time, COOKIEPATH, COOKIE_DOMAIN);
    }

    public function trackingWoocommerce($result, $order_id)
    {
        $cookie = acym_getVar('string', 'acym_track_woocommerce', '', 'COOKIE');
        if (empty($cookie)) return $result;

        $formattedCookie = [];

        acym_trigger('formatCookie', [&$cookie, &$formattedCookie], 'plgAcymWoocommerce');

        if (empty($formattedCookie['userid']) || empty($formattedCookie['mailid'])) return $result;

        $order = wc_get_order($order_id);

        $currency = $order->get_currency();
        if (empty($currency)) return $result;

        $total = (float)$order->get_total() - $order->get_total_tax() - $order->get_total_shipping() - $order->get_shipping_tax();

        $this->saveTrackingWoocommerceMailStat($formattedCookie, $total, $currency);
        $this->saveTrackingWoocommerceUserStat($formattedCookie, $total, $currency);

        return $result;
    }

    private function saveTrackingWoocommerceMailStat($formattedCookie, $total, $currency)
    {
        $mailStatClass = new MailStatClass();
        $mailStat = $mailStatClass->getOneById($formattedCookie['mailid']);

        if (empty($mailStat)) return;

        $newMailStat = [
            'mail_id' => $mailStat->mail_id,
            'tracking_sale' => empty($mailStat->tracking_sale) ? $total : $mailStat->tracking_sale + $total,
            'currency' => $currency,
        ];

        $mailStatClass->save($newMailStat);
    }

    private function saveTrackingWoocommerceUserStat($formattedCookie, $total, $currency)
    {
        $userStatClass = new UserStatClass();
        $userStat = $userStatClass->getOneByMailAndUserId($formattedCookie['mailid'], $formattedCookie['userid']);
        if (empty($userStat)) return;
        unset($userStat->statusSending);
        unset($userStat->open);
        unset($userStat->open_date);

        $userStat->tracking_sale = empty($userStat->tracking_sale) ? $total : $userStat->tracking_sale + $total;
        $userStat->currency = $currency;

        $userStatClass->save($userStat);
    }


    /**
     * Subscribe user when the WooCommerce checkout is processed
     *
     * @param $order_id : WooCommerce order ID
     * @param $posted_data : All data WooCommerce will get from form on checkout process
     * @param $order : WooCommerce order
     */
    public function subscribeUserOnCheckoutWC($order_id, $posted_data, $order)
    {
        $config = acym_config();
        if (!$config->get('woocommerce_sub', 0)) return;

        if (empty($posted_data['billing_email']) || empty($posted_data['acym_regacy_sub'])) return;


        // Get existing AcyMailing user or create one
        $userClass = new UserClass();

        $user = $userClass->getOneByEmail($posted_data['billing_email']);
        if (empty($user)) {
            $user = new stdClass();
            $user->email = $posted_data['billing_email'];
            $userName = [];
            if (!empty($posted_data['billing_first_name'])) $userName[] = $posted_data['billing_first_name'];
            if (!empty($posted_data['billing_last_name'])) $userName[] = $posted_data['billing_last_name'];
            if (!empty($userName)) $user->name = implode(' ', $userName);
            $user->source = 'woocommerce';
            $user->id = $userClass->save($user);
        }

        if (empty($user->id)) return;

        // Subscribe the user
        $listsToSubscribe = $config->get('woocommerce_autolists', '');
        if (empty($listsToSubscribe)) return;
        $hiddenLists = explode(',', $listsToSubscribe);
        $userClass->subscribe($user->id, $hiddenLists);
    }

    /**
     * Declare the field for WooCommerce to display it in the checkout and get it on checkout validation.
     *
     * @param $fields (available WooCommerce fields)
     *
     * @return mixed
     */
    public function addSubscriptionFieldWC($fields)
    {
        $config = acym_config();
        if (!$config->get('woocommerce_sub', 0)) return $fields;

        // Add our field at the end of the billing fields (where the email is mandatory)
        $text = $config->get('woocommerce_text');
        $displayTxt = empty($text) ? acym_translation('ACYM_SUBSCRIBE_NEWSLETTER') : $text;
        $acyfield = [
            'type' => 'checkbox',
            'label' => $displayTxt,
            'required' => false,
            'class' => ['form-row-wide'],
        ];
        $fields['billing']['acym_regacy_sub'] = $acyfield;

        return $fields;
    }

    public function onAcymDeclareTriggers(&$triggers, &$defaultValues)
    {
        $orderStatuses = $this->getOrderStatuses(true);

        $triggers['user']['woocommerce_order_change'] = new stdClass();
        $triggers['user']['woocommerce_order_change']->name = acym_translation('ACYM_ON_WOOCOMMERCE_ORDER_CHANGE');
        $triggers['user']['woocommerce_order_change']->option = '<div class="grid-x grid-margin-x" style="height: 40px;">';
        $triggers['user']['woocommerce_order_change']->option .= '<div class="cell medium-shrink acym_vcenter">'.acym_translation('ACYM_FROM').'</div>';
        $triggers['user']['woocommerce_order_change']->option .= '<div class="cell medium-4">'.acym_select(
                $orderStatuses,
                '[triggers][user][woocommerce_order_change][from]',
                empty($defaultValues['woocommerce_order_change']['from']) ? 0 : $defaultValues['woocommerce_order_change']['from'],
                'data-class="acym__select"'
            ).'</div>';
        $triggers['user']['woocommerce_order_change']->option .= '<div class="cell medium-shrink acym_vcenter">'.acym_translation('ACYM_TO').'</div>';
        $triggers['user']['woocommerce_order_change']->option .= '<div class="cell medium-4">'.acym_select(
                $orderStatuses,
                '[triggers][user][woocommerce_order_change][to]',
                empty($defaultValues['woocommerce_order_change']['to']) ? 'wc-completed' : $defaultValues['woocommerce_order_change']['to'],
                'data-class="acym__select"'
            ).'</div>';
        $triggers['user']['woocommerce_order_change']->option .= '</div>';
    }

    public function onAcymExecuteTrigger(&$step, &$execute, &$data)
    {
        $triggers = $step->triggers;
        $from = false;
        $to = false;
        if (!empty($triggers['woocommerce_order_change'])) {
            // Woocommerce removes the "wc-" prefix on the native order statuses that are sent in the hook
            $fromStatus = 'wc-'.$data['statusFrom'];
            $toStatus = 'wc-'.$data['statusTo'];
            if ($fromStatus === $triggers['woocommerce_order_change']['from'] || $triggers['woocommerce_order_change']['from'] === '0') $from = true;
            if ($toStatus === $triggers['woocommerce_order_change']['to'] || $triggers['woocommerce_order_change']['to'] === '0') $to = true;

            if ($from && $to) $execute = true;
        }
    }

    public function onAcymDeclareSummary_triggers(&$automation)
    {
        if (empty($automation->triggers['woocommerce_order_change'])) return;

        $orderStatuses = $this->getOrderStatuses(true);

        $automation->triggers['woocommerce_order_change'] = acym_translationSprintf(
            'ACYM_TRIGGER_WOOCOMMERCE_ORDER_CHANGE_SUMMARY',
            $orderStatuses[$automation->triggers['woocommerce_order_change']['from']],
            $orderStatuses[$automation->triggers['woocommerce_order_change']['to']]
        );
    }

    private function getOrderStatuses($withDefaultStatus = false)
    {
        if (!function_exists('wc_get_order_statuses')) return [];

        $orderStatuses = [];
        if ($withDefaultStatus) $orderStatuses[0] = acym_translation('ACYM_ANY_STATUS');

        // Get all order statuses from WooCommerce (natives and from other plugins)
        $allWooCoommerceOrderStatuses = wc_get_order_statuses();
        $orderStatuses = array_merge($orderStatuses, $allWooCoommerceOrderStatuses);

        return $orderStatuses;
    }

    public function onWooCommerceOrderStatusChange($orderId, $statusFrom, $statusTo, $order)
    {
        $userClass = new UserClass();
        $wpUserId = $order->get_user_id();
        if (!empty($wpUserId)) $acyUser = $userClass->getOneByCMSId($wpUserId);
        if (empty($acyUser)) {
            $billingEmail = $order->get_billing_email();
            if (!empty($billingEmail)) {
                $acyUser = $userClass->getOneByEmail($billingEmail);
            }
        }
        if (empty($acyUser)) return;

        $items = $order->get_items();
        $productIds = [];
        $categoriesIds = [];
        foreach ($items as $item) {
            $productIds[] = $item->get_product_id();
            $terms = get_the_terms($item->get_product_id(), 'product_cat');
            if (!empty($terms)) {
                foreach ($terms as $term) {
                    if (!in_array($term->term_id, $categoriesIds)) $categoriesIds[] = $term->term_id;
                }
            }
        }

        $params = [
            'woo_order_status' => $statusTo,
            'woo_order_product_ids' => $productIds,
            'woo_order_cat_ids' => $categoriesIds,
        ];

        $followupClass = new FollowupClass();
        $followupClass->addFollowupEmailsQueue(self::FOLLOWTRIGGER, $acyUser->id, $params);

        $automationClass = new AutomationClass();
        $automationClass->trigger('woocommerce_order_change', [
                'userId' => $acyUser->id,
                'statusFrom' => $statusFrom,
                'statusTo' => $statusTo,
            ]);
    }

    public function getNewEmailsTypeBlock(&$extraBlocks)
    {
        if (acym_isAdmin()) {
            $woocomerceMailLink = acym_completeLink('campaigns&task=edit&step=chooseTemplate&campaign_type='.self::MAILTYPE);
        } else {
            $woocomerceMailLink = acym_frontendLink('frontcampaigns&task=edit&step=chooseTemplate&campaign_type='.self::MAILTYPE);
        }

        $extraBlocks[] = [
            'name' => $this->pluginDescription->name,
            'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_DESC'),
            'icon' => 'acymicon-cart-arrow-down',
            'link' => $woocomerceMailLink,
            'level' => 1,
        ];
    }

    public function getCampaignTypes(&$types)
    {
        $types[self::MAILTYPE] = self::MAILTYPE;
    }

    public function getCampaignSpecificSendSettings($type, $sendingParams, &$specificSettings)
    {
        if ($type != self::MAILTYPE) return;

        $timeSelectOptions = [
            'days' => acym_translation('ACYM_DAYS'),
            'weeks' => acym_translation('ACYM_WEEKS'),
            'months' => acym_translation('ACYM_MONTHS'),
        ];

        $selectedtType = 'days';
        if (!empty($sendingParams) && isset($sendingParams[self::MAILTYPE.'_type'])) {
            $selectedtType = $sendingParams[self::MAILTYPE.'_type'];
        }
        $timeSelect = '<div class="cell medium-2 margin-left-1 margin-right-1">';
        $timeSelect .= acym_select($timeSelectOptions, 'acym_woocomerce_time_frame', $selectedtType, 'class="acym__select"');
        $timeSelect .= '</div>';

        $defaultNumber = 1;
        if (!empty($sendingParams) && isset($sendingParams[self::MAILTYPE.'_number'])) {
            $defaultNumber = $sendingParams[self::MAILTYPE.'_number'];
        }
        $inputTime = '<input type="number" min="0" stp="1" name="acym_woocomerce_time_number" class="intext_input" value="'.intval($defaultNumber).'">';

        $orderStatuses = $this->getOrderStatuses();
        $selectedStatus = 'wc-pending';
        if (!empty($sendingParams) && isset($sendingParams[self::MAILTYPE.'_status'])) {
            $selectedStatus = $sendingParams[self::MAILTYPE.'_status'];
        }
        $inputStatus = '<div class="cell medium-2 margin-left-1 margin-right-1">';
        $inputStatus .= acym_select($orderStatuses, 'acym_woocomerce_status', $selectedStatus, 'class="acym__select"');
        $inputStatus .= '</div>';

        $whenSettings = '<div class="cell grid-x acym_vcenter">';
        $whenSettings .= acym_translationSprintf('ACYM_SEND_ORDER_PLACED_STATUS_CURRENTLY', $inputTime, $timeSelect, $inputStatus);
        $whenSettings .= '</div>';

        $specificSettings[] = [
            'whenSettings' => $whenSettings,
            'additionnalSettings' => '',
        ];
    }

    public function saveCampaignSpecificSendSettings($type, &$specialSendings)
    {
        if ($type != self::MAILTYPE) return;

        $inputTime = acym_getVar('int', 'acym_woocomerce_time_number', 0);
        $typeTime = acym_getVar('string', 'acym_woocomerce_time_frame', 'day');
        $status = acym_getVar('string', 'acym_woocomerce_status', '0');

        $specialSendings[] = [
            self::MAILTYPE.'_number' => $inputTime,
            self::MAILTYPE.'_type' => $typeTime,
            self::MAILTYPE.'_status' => $status,
        ];
    }

    public function onAcymSendCampaignSpecial($campaign, &$filters)
    {
        if ($campaign->sending_type != self::MAILTYPE) return;

        $sendingTime = (int)$campaign->sending_params[self::MAILTYPE.'_number'];
        if ($campaign->sending_params[self::MAILTYPE.'_type'] == 'weeks') {
            $sendingTime *= 7;
        } elseif ($campaign->sending_params[self::MAILTYPE.'_type'] == 'months') {
            $sendingTime *= 30;
        }
        $filter = [
            'wooreminder' => [
                'days' => $sendingTime,
                'status' => $campaign->sending_params[self::MAILTYPE.'_status'],
                'payment' => 'any',
            ],

        ];
        $filters[] = $filter;
    }

    public function onAcymDisplayCampaignListingSpecificTabs(&$tabs)
    {
        $tabs['specificListing&type='.self::MAILTYPE] = 'ACYM_WOOCOMMERCE_ABANDONED_CART';
    }

    public function onAcymSpecificListingActive(&$exists, $task)
    {
        if ($task == self::MAILTYPE) {
            $exists = true;
        }
    }

    public function onAcymCampaignDataSpecificListing(&$data, $type)
    {
        if ($type == self::MAILTYPE) {
            $data['typeWorkflowTab'] = 'specificListing&type='.self::MAILTYPE;
            $data['element_to_display'] = acym_translation('ACYM_WOOCOMMERCE_ABANDONED_CART_CAMPAIGN');
            $data['type'] = self::MAILTYPE;
            $campaignController = new CampaignsController();
            $campaignController->prepareEmailsListing($data, $type);
        }
    }

    public function onAcymCampaignAddFiltersSpecificListing(&$filters, $type)
    {
        if ($type == self::MAILTYPE) {
            $filters[] = 'campaign.sending_type = '.acym_escapeDB(self::MAILTYPE);
        }
    }


    public function filterSpecificMailsToSend(&$specialMails, $time)
    {
        $this->filterSpecialMailsDailySend($specialMails, $time, self::MAILTYPE);
    }

    public function getFollowupTriggerBlock(&$blocks)
    {
        $blocks[] = [
            'name' => acym_translation('ACYM_WOOCOMMERCE_PURCHASE'),
            'description' => acym_translation('ACYM_WOOCOMMERCE_FOLLOW_UP_DESC'),
            'icon' => 'acymicon-cart-arrow-down',
            'link' => acym_completeLink('campaigns&task=edit&step=followupCondition&trigger='.self::FOLLOWTRIGGER),
            'level' => 2,
            'alias' => self::FOLLOWTRIGGER,
        ];
    }

    public function getFollowupTriggers(&$triggers)
    {
        $triggers[self::FOLLOWTRIGGER] = acym_translation('ACYM_WOOCOMMERCE_PURCHASE');
    }

    public function getAcymAdditionalConditionFollowup(&$additionalCondition, $trigger, $followup, $statusArray)
    {
        if ($trigger == self::FOLLOWTRIGGER) {
            $woocommerceOrderStatus = $this->getOrderStatuses();
            $multiselectOrderStatus = acym_selectMultiple($woocommerceOrderStatus,
                'followup[condition][order_status]',
                !empty($followup->condition) && $followup->condition['order_status'] ? $followup->condition['order_status'] : [],
                ['class' => 'acym__select']);
            $multiselectOrderStatus = '<span class="cell large-4 medium-6 acym__followup__condition__select__in-text">'.$multiselectOrderStatus.'</span>';
            $statusOrderStatus = '<span class="cell large-1 medium-2 acym__followup__condition__select__in-text">'.acym_select(
                    $statusArray,
                    'followup[condition][order_status_status]',
                    !empty($followup->condition) ? $followup->condition['order_status_status'] : '',
                    'class="acym__select"'
                ).'</span>';;
            $additionalCondition['order_status'] = acym_translationSprintf('ACYM_WOOCOMMERCE_ORDER_STATUS_IN', $statusOrderStatus, $multiselectOrderStatus);


            $ajaxParams = json_encode([
                    'plugin' => 'plgAcymWoocommerce',
                    'trigger' => 'searchProduct',
                ]);
            $parametersProductSelect = [
                'class' => 'acym__select acym_select2_ajax',
                'data-params' => acym_escape($ajaxParams),
                'data-selected' => !empty($followup->condition) && !empty($followup->condition['products']) ? implode(',', $followup->condition['products']) : '',
            ];
            $woocommerceProducts = [];
            $multiselectProducts = acym_selectMultiple(
                $woocommerceProducts,
                'followup[condition][products]',
                !empty($followup->condition) && !empty($followup->condition['products']) ? $followup->condition['products'] : [],
                $parametersProductSelect
            );
            $multiselectProducts = '<span class="cell large-4 medium-6 acym__followup__condition__select__in-text">'.$multiselectProducts.'</span>';
            $statusProducts = '<span class="cell large-1 medium-2 acym__followup__condition__select__in-text">'.acym_select(
                    $statusArray,
                    'followup[condition][products_status]',
                    !empty($followup->condition) ? $followup->condition['products_status'] : '',
                    'class="acym__select"'
                ).'</span>';;
            $additionalCondition['products'] = acym_translationSprintf('ACYM_WOOCOMMERCE_PRODUCT_IN', $statusProducts, $multiselectProducts);

            $ajaxParams = json_encode([
                    'plugin' => 'plgAcymWoocommerce',
                    'trigger' => 'searchCat',
                ]);
            $parametersCategoriesSelect = [
                'class' => 'acym__select acym_select2_ajax',
                'data-params' => acym_escape($ajaxParams),
                'data-selected' => !empty($followup->condition) && !empty($followup->condition['categories']) ? implode(',', $followup->condition['categories']) : '',
            ];
            $woocommerceCategories = [];
            $multiselectCategories = acym_selectMultiple(
                $woocommerceCategories,
                'followup[condition][categories]',
                !empty($followup->condition) && !empty($followup->condition['categories']) ? $followup->condition['categories'] : [],
                $parametersCategoriesSelect
            );
            $multiselectCategories = '<span class="cell large-4 medium-6 acym__followup__condition__select__in-text">'.$multiselectCategories.'</span>';
            $statusCategories = '<span class="cell large-1 medium-2 acym__followup__condition__select__in-text">'.acym_select(
                    $statusArray,
                    'followup[condition][categories_status]',
                    !empty($followup->condition) ? $followup->condition['categories_status'] : '',
                    'class="acym__select"'
                ).'</span>';;
            $additionalCondition['categories'] = acym_translationSprintf('ACYM_WOOCOMMERCE_CATEGORY_IN', $statusCategories, $multiselectCategories);
        }
    }

    public function matchFollowupsConditions(&$followups, $userId, $params)
    {
        foreach ($followups as $key => $followup) {
            if ($followup->trigger != self::FOLLOWTRIGGER) continue;
            //We check the order status
            if (!empty($followup->condition['order_status_status']) && !empty($followup->condition['order_status'])) {
                $status = $followup->condition['order_status_status'] == 'is';
                $inArray = in_array('wc-'.$params['woo_order_status'], $followup->condition['order_status']);
                if (($status && !$inArray) || (!$status && $inArray)) unset($followups[$key]);
            }

            //We check the products
            if (!empty($followup->condition['products_status']) && !empty($followup->condition['products'])) {
                $status = $followup->condition['products_status'] == 'is';
                $inArray = false;
                foreach ($params['woo_order_product_ids'] as $product_id) {
                    if (in_array($product_id, $followup->condition['products'])) {
                        $inArray = true;
                        break;
                    }
                }
                if (($status && !$inArray) || (!$status && $inArray)) unset($followups[$key]);
            }

            //We check the categories
            if (!empty($followup->condition['categories_status']) && !empty($followup->condition['categories'])) {
                $status = $followup->condition['categories_status'] == 'is';
                $inArray = false;
                foreach ($params['woo_order_cat_ids'] as $cat_id) {
                    if (in_array($cat_id, $followup->condition['categories'])) {
                        $inArray = true;
                        break;
                    }
                }
                if (($status && !$inArray) || (!$status && $inArray)) unset($followups[$key]);
            }
        }
    }

    public function getFollowupConditionSummary(&$return, $condition, $trigger, $statusArray)
    {
        if ($trigger == self::FOLLOWTRIGGER) {
            if (empty($condition['order_status_status']) || empty($condition['order_status'])) {
                $return[] = acym_translation('ACYM_EVERY_ORDER_STATUS');
            } else {
                $woocommerceOrderStatus = $this->getOrderStatuses();
                $orderStatusToDisplay = [];
                foreach ($woocommerceOrderStatus as $key => $orderStatus) {
                    if (in_array($key, $condition['order_status'])) $orderStatusToDisplay[] = $orderStatus;
                }
                $return[] = acym_translationSprintf('ACYM_ORDER_STATUS_X_IN_X', strtolower($statusArray[$condition['order_status_status']]), implode(', ', $orderStatusToDisplay));
            }

            if (empty($condition['products_status']) || empty($condition['products'])) {
                $return[] = acym_translation('ACYM_ANY_PRODUCT');
            } else {
                $args = [
                    'post__in' => $condition['products'],
                    'post_type' => 'product',
                ];
                $posts = new WP_Query($args);

                $productsToDisplay = [];
                if ($posts->have_posts()) {
                    foreach ($posts->get_posts() as $post) {
                        $productsToDisplay[] = $post->post_title;
                    }
                }
                $return[] = acym_translationSprintf('ACYM_PRODUCTS_X_IN_X', strtolower($statusArray[$condition['products_status']]), implode(', ', $productsToDisplay));
            }

            if (empty($condition['categories_status']) || empty($condition['categories'])) {
                $return[] = acym_translation('ACYM_EVERY_CATEGORIES');
            } else {
                $cats = $this->getWooCategories($condition['categories']);

                $categoriesToDisplay = [];
                if (!empty($cats)) {
                    foreach ($cats as $cat) {
                        $categoriesToDisplay[] = $cat->name;
                    }
                }
                $return[] = acym_translationSprintf('ACYM_CATEGORIES_X_IN_X', strtolower($statusArray[$condition['categories_status']]), implode(', ', $categoriesToDisplay));
            }
        }
    }

    public function onAcymGetEmailOverrides(&$emailsOverride)
    {
        $wooOverrides = [
            [
                'name' => 'woo-new_order',
                'base_subject' => [
                    '[{site_title}]: New order #{order_number}',
                ],
                'base_body' => '',
                'new_subject' => '[{param1}]: New order #{param2}',
                'new_body' => 'You’ve received the following order from {user_billing_full_name}:
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_WOO_NEW_ORDER_EMAIL_DESC',
                'source' => self::MAIL_OVERRIDE_SOURCE_NAME,
            ],
            [
                'name' => 'woo-customer_completed_order',
                'base_subject' => [
                    'Your {site_title} order is now complete',
                ],
                'base_body' => '',
                'new_subject' => 'Your {param1} order is now complete',
                'new_body' => 'Hi {user_billing_full_name},
			<br>
			We have finished processing your order.
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_WOO_CUSTOMER_COMPLETE_ORDER',
                'source' => self::MAIL_OVERRIDE_SOURCE_NAME,
            ],
            [
                'name' => 'woo-customer_on_hold_order',
                'base_subject' => [
                    'Your {site_title} order has been received!',
                ],
                'base_body' => '',
                'new_subject' => 'Your {param1} order has been received!',
                'new_body' => 'Hi {user_billing_full_name},
			<br>
			Thanks for your order. It’s on-hold until we confirm that payment has been received. In the meantime, here’s a reminder of what you ordered:
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_WOO_CUSTOMER_ON_HOLD_ORDER',
                'source' => self::MAIL_OVERRIDE_SOURCE_NAME,
            ],
            [
                'name' => 'woo-customer_invoice',
                'base_subject' => [
                    'Invoice for order #{order_number} on {site_title}',
                ],
                'base_body' => '',
                'new_subject' => 'Invoice for order #{param1} on {param2}',
                'new_body' => 'Hi {user_billing_full_name},
			<br>
			Here are the details of your order placed on {order_date_created}}:
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_WOO_CUSTOMER_INVOICE',
                'source' => self::MAIL_OVERRIDE_SOURCE_NAME,
            ],
            [
                'name' => 'woo-customer_processing_order',
                'base_subject' => [
                    'Your {site_title} order has been received!',
                ],
                'base_body' => '',
                'new_subject' => 'Your {param1} order has been received!',
                'new_body' => 'Hi {user_billing_full_name},
			<br>
			Just to let you know &mdash; we\'ve received your order #{order_number}, and it is now being processed:
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_WOO_CUSTOMER_PROCESSING_ORDER',
                'source' => self::MAIL_OVERRIDE_SOURCE_NAME,
            ],
            [
                'name' => 'woo-customer_refunded_order',
                'base_subject' => [
                    'Your {site_title} order #{order_number} has been refunded',
                ],
                'base_body' => '',
                'new_subject' => 'Your {param1} order #{param2} has been refunded',
                'new_body' => 'Hi {user_billing_full_name},
			<br>
			Your order on {param1} has been refunded. There are more details below for your reference:
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_WOO_CUSTOMER_REFUNDED_ORDER',
                'source' => self::MAIL_OVERRIDE_SOURCE_NAME,
            ],
            [
                'name' => 'woo-failed_order',
                'base_subject' => [
                    '[{site_title}]: Order #{order_number} has failed',
                ],
                'base_body' => '',
                'new_subject' => '[{param1}]: Order #{param2} has failed',
                'new_body' => 'Payment for order {param2} from {user_billing_full_name} has failed. The order was as follows:
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_WOO_FAILED_ORDER',
                'source' => self::MAIL_OVERRIDE_SOURCE_NAME,
            ],
            [
                'name' => 'woo-cancelled_order',
                'base_subject' => [
                    '[{site_title}]: Order #{order_number} has been cancelled',
                ],
                'base_body' => '',
                'new_subject' => '[{param1}]: Order #{param2} has been cancelled',
                'new_body' => 'Notification to let you know &mdash; order #{param2} belonging to {user_billing_full_name} has been cancelled:
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_WOO_CANCELED_ORDER',
                'source' => self::MAIL_OVERRIDE_SOURCE_NAME,
            ],
            [
                'name' => 'woo-customer_reset_password',
                'base_subject' => [
                    'Password Reset Request for {site_title}',
                ],
                'base_body' => '',
                'new_subject' => 'Password Reset Request for {param1}',
                'new_body' => 'Hi {user_login},
            <br>
            Someone has requested a new password for the following account on {param1}:
            <br>
            Username: {user_login}
            <br>
            If you didn\'t make this request, just ignore this email. If you\'d like to proceed:
            <br>
            <a class="link" href="{link_reset_password}">Click here to reset your password</a>',
                'description' => 'ACYM_OVERRIDE_DESC_RESET_PASSWORD',
                'source' => self::MAIL_OVERRIDE_SOURCE_NAME,
            ],
            [
                'name' => 'woo-customer_new_account',
                'base_subject' => [
                    'Your {site_title} account has been created!',
                ],
                'base_body' => '',
                'new_subject' => 'Your {param1} account has been created!',
                'new_body' => 'Hi {user_login},
            <br>
            Thanks for creating an account on {param1}. Your username is {user_login}. You can access your account area to view orders, change your password, and more at: {my_account_link}
            <br>
            Your password has been automatically generated: {user_password}',
                'description' => 'ACYM_OVERRIDE_DESC_ADMIN_CREATED',
                'source' => self::MAIL_OVERRIDE_SOURCE_NAME,
            ],
            [
                'name' => 'woo-customer_note',
                'base_subject' => [
                    'Note added to your {site_title} order from {order_date}',
                ],
                'base_body' => '',
                'new_subject' => 'Note added to your {param1} order from {param2}',
                'new_body' => 'Hi {user_billing_full_name},
            <br>
            The following note has been added to your order:
            <br>
            <blockquote>{customer_note}</blockquote>
            <br>
            As a reminder, here are your order details:
            <br>
            {woocommerce_email_order_details}
            <br>
            {woocommerce_email_order_meta}
            <br>
            {woocommerce_email_customer_details}',
                'description' => 'ACYM_OVERRIDE_DESC_CUSTOMER_NOTE',
                'source' => self::MAIL_OVERRIDE_SOURCE_NAME,
            ],
        ];

        $emailsOverride = array_merge($emailsOverride, $wooOverrides);
    }

    public function onAcymGetEmailOverridesParams(&$overridesParamsAll)
    {
        $overridesParamsAll['woo-new_order'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_ORDER_NUMBER'),
                'description' => acym_translation('ACYM_ORDER_NUMBER_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-customer_completed_order'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-customer_on_hold_order'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-customer_invoice'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_ORDER_NUMBER'),
                'description' => acym_translation('ACYM_ORDER_NUMBER_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'order_date_created' => [
                'nicename' => acym_translation('ACYM_ORDER_CREATION_DATE'),
                'description' => acym_translation('ACYM_ORDER_CREATION_DATE_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-customer_processing_order'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'order_number' => [
                'nicename' => acym_translation('ACYM_ORDER_NUMBER'),
                'description' => acym_translation('ACYM_ORDER_NUMBER_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-customer_refunded_order'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_ORDER_NUMBER'),
                'description' => acym_translation('ACYM_ORDER_NUMBER_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-failed_order'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_ORDER_NUMBER'),
                'description' => acym_translation('ACYM_ORDER_NUMBER_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-cancelled_order'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_ORDER_NUMBER'),
                'description' => acym_translation('ACYM_ORDER_NUMBER_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-customer_reset_password'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'user_login' => [
                'nicename' => acym_translation('ACYM_USER_NAME'),
                'description' => acym_translation('ACYM_USER_NAME_OVERRIDE_DESC'),
            ],
            'link_reset_password' => [
                'nicename' => acym_translation('ACYM_LINK_RESET_PASSWORD'),
                'description' => acym_translation('ACYM_LINK_RESET_PASSWORD_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-customer_new_account'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'user_login' => [
                'nicename' => acym_translation('ACYM_USER_NAME'),
                'description' => acym_translation('ACYM_USER_NAME_OVERRIDE_DESC'),
            ],
            'my_account_link' => [
                'nicename' => acym_translation('ACYM_LINK_TO_ACCOUNT_FRONT'),
                'description' => acym_translation('ACYM_LINK_TO_ACCOUNT_FRONT_OVERRIDE_DESC'),
            ],
            'user_password' => [
                'nicename' => acym_translation('ACYM_PASSWORD'),
                'description' => acym_translation('ACYM_PASSWORD_OVERRIDE_DESC'),
            ],
        ];

        $overridesParamsAll['woo-customer_note'] = [
            'param1' => [
                'nicename' => acym_translation('ACYM_SITE_NAME'),
                'description' => acym_translation('ACYM_SITE_NAME_OVERRIDE_DESC'),
            ],
            'param2' => [
                'nicename' => acym_translation('ACYM_ORDER_CREATION_DATE'),
                'description' => acym_translation('ACYM_ORDER_CREATION_DATE_OVERRIDE_DESC'),
            ],
            'user_billing_full_name' => [
                'nicename' => acym_translation('ACYM_USER_BILLING_FULL_NAME'),
                'description' => acym_translation('ACYM_USER_BILLING_FULL_NAME_OVERRIDE_DESC'),
            ],
            'customer_note' => [
                'nicename' => acym_translation('ACYM_CUSTOMER_NOTE'),
                'description' => acym_translation('ACYM_CUSTOMER_NOTE_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_DETAILS_OVERRIDE_DESC'),
            ],
            'woocommerce_email_order_meta' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_ORDER_META_OVERRIDE_DESC'),
            ],
            'woocommerce_email_customer_details' => [
                'nicename' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS'),
                'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_CUSTOMER_DETAILS_OVERRIDE_DESC'),
            ],
        ];
    }

    public function onAcymGetEmailOverrideSources(&$sources)
    {
        $sources[self::MAIL_OVERRIDE_SOURCE_NAME] = self::PLUGIN_DISPLAYED_NAME;
    }


    /**
     * @param $args
     *             0: to
     *             1: subject
     *             2: body
     *             3: headers
     *             4: attachments
     * @param $emailTypeClass
     */
    public function onWooCommerceEmailSend($args, $emailTypeClass)
    {
        $overrideClass = new OverrideClass();
        $activeOverrides = $overrideClass->getActiveOverrides('name');

        if (empty($activeOverrides)) return $args;

        if (empty($activeOverrides['woo-'.$emailTypeClass->id])) return $args;

        $override = $activeOverrides['woo-'.$emailTypeClass->id];

        $mailClass = new MailClass();
        $mail = $mailClass->getOneById($override->mail_id);

        if (empty($mail)) return $args;

        $mail = $this->onWooCommerceEmailSendReplaceTags($mail, $emailTypeClass);

        $args[2] = $mail->body;

        $dynamicSubjects = [
            'customer_invoice',
            'customer_refunded_order',
        ];
        if (in_array($emailTypeClass->id, $dynamicSubjects)) {
            $overrideNameKey = array_search($emailTypeClass->id, $dynamicSubjects);
            $overrideName = 'woo-'.$dynamicSubjects[$overrideNameKey];

            $wooOverrides = [];
            $this->onAcymGetEmailOverrides($wooOverrides);

            $key = array_search($overrideName, array_column($wooOverrides, 'name'));
            $mail->subject = $wooOverrides[$key]['base_subject'][0];
            $mail = $this->onWooCommerceEmailSendReplaceTags($mail, $emailTypeClass, 'subject');

            $args[1] = $mail->subject;
        }

        return $args;
    }

    private function onWooCommerceEmailSendReplaceTags($mail, $emailTypeClass, $column = 'body')
    {
        $order = $emailTypeClass->object;

        $dynamicText = [
            '{site_title}' => wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
        ];

        if (get_class($order) != 'WP_User') {
            $dynamicText['{order_number}'] = $order->get_order_number();
            $dynamicText['{user_billing_full_name}'] = $order->get_formatted_billing_full_name();
            $dynamicText['{order_date_created}'] = wc_format_datetime($order->get_date_created());
            $dynamicText['{checkout_payment_url}'] = '<a href="'.esc_url($order->get_checkout_payment_url()).'">';
            if (!empty($emailTypeClass->customer_note)) $dynamicText['{customer_note}'] = wpautop(wptexturize(make_clickable($emailTypeClass->customer_note)));
            //get woocommerce_email_order_details
            ob_start();
            do_action('woocommerce_email_order_details', $order, true, false, '');
            $dynamicText['{woocommerce_email_order_details}'] = ob_get_clean();

            //get woocommerce_email_order_meta
            ob_start();
            do_action('woocommerce_email_order_meta', $order, true, false, '');
            $dynamicText['{woocommerce_email_order_meta}'] = ob_get_clean();

            //get woocommerce_email_customer_details
            ob_start();
            do_action('woocommerce_email_customer_details', $order, true, false, '');
            $dynamicText['{woocommerce_email_customer_details}'] = ob_get_clean();
        } else {
            $dynamicText['{user_login}'] = $emailTypeClass->user_login;
            $dynamicText['{user_password}'] = $emailTypeClass->user_pass;
            $dynamicText['{my_account_link}'] = make_clickable(esc_url(wc_get_page_permalink('myaccount')));
            $dynamicText['{link_reset_password}'] = esc_url(
                add_query_arg(['key' => $emailTypeClass->reset_key, 'id' => $emailTypeClass->user_id], wc_get_endpoint_url('lost-password', '', wc_get_page_permalink('myaccount')))
            );
        }


        $pluginHelper = new PluginHelper();
        $mail->{$column} = $pluginHelper->replaceDText($mail->{$column}, $dynamicText);

        return $mail;
    }
}
