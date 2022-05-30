<?php

use AcyMailing\Classes\AutomationClass;
use AcyMailing\Classes\FollowupClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\TabHelper;
use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Types\DelayType;

class plgAcymHikashop extends acymPlugin
{
    const FOLLOWTRIGGER = 'hikashop_purchase';

    var $hikaConfig;
    var $currencyClass;
    var $imageHelper;
    var $productClass;
    var $translationHelper;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->installed = acym_isExtensionActive('com_hikashop');

        $this->pluginDescription->name = 'HikaShop';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.ico';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'price' => ['ACYM_PRICE', true],
                'image' => ['ACYM_IMAGE', true],
                'shortdesc' => ['ACYM_SHORT_DESCRIPTION', true],
                'desc' => ['ACYM_DESCRIPTION', false],
                'readmore' => ['ACYM_READ_MORE', false],
            ];

            $this->initCustomView();

            $frontData = [
                'all' => 'ACYM_ALL_ELEMENTS',
                'hide' => 'ACYM_DONT_SHOW',
            ];
            if (acym_isExtensionActive('com_hikamarket')) {
                $frontData['user'] = 'ACYM_DISPLAY_OWN_USER_PRODUCTS';
            }

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->replaceOptions, $this->elementOptions),
                ],
                'front' => [
                    'type' => 'select',
                    'label' => 'ACYM_FRONT_ACCESS',
                    'value' => 'all',
                    'data' => $frontData,
                ],
                'vat' => [
                    'type' => 'switch',
                    'label' => 'ACYM_PRICE_WITH_TAX',
                    'value' => 1,
                ],
                'stock' => [
                    'type' => 'switch',
                    'label' => 'ACYM_ONLY_IN_STOCK',
                    'value' => 0,
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
        $format->title = '{product_name}';
        $format->afterTitle = '';
        $format->afterArticle = acym_translation('ACYM_PRICE').': {finalPrice}';
        $format->imagePath = '{pictHTML}';
        $format->description = '{product_description}';
        $format->link = '{link}';
        $format->customFields = [];
        $customView = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';
    }

    public function initReplaceOptionsCustomView()
    {
        $this->replaceOptions = [
            'link' => ['ACYM_LINK'],
            'pictHTML' => ['ACYM_IMAGE'],
            'finalPrice' => ['ACYM_PRICE'],
        ];
    }

    public function initElementOptionsCustomView()
    {
        $this->elementOptions = [];
        $query = 'SELECT b.*, a.*
                    FROM #__hikashop_product AS a
                    LEFT JOIN #__hikashop_file AS b ON a.product_id = b.file_ref_id AND file_type = "product"';
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

        acym_loadLanguageFile('com_hikashop', JPATH_SITE);

        $this->categories = acym_loadObjectList(
            "SELECT category_id AS id, category_parent_id AS parent_id, category_name AS title 
			FROM `#__hikashop_category` 
			WHERE category_type = 'product'",
            'id'
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
                'title' => 'ACYM_PRICE',
                'type' => 'select',
                'name' => 'price_type',
                'options' => [
                    'full' => 'ACYM_APPLY_DISCOUNTS',
                    'no_discount' => 'ACYM_NO_DISCOUNT',
                ],
                'default' => 'full',
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
                    'product_id' => 'ACYM_ID',
                    'product_created' => 'ACYM_DATE_CREATED',
                    'product_modified' => 'ACYM_MODIFICATION_DATE',
                    'product_name' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
            ],
        ];

        // Add parameter for the HikaMarket vendor
        if (acym_isExtensionActive('com_hikamarket') && !acym_isAdmin() && $this->getParam('front', 'all') === 'user') {
            $vendorId = acym_loadResult(
                'SELECT v.vendor_id FROM #__hikashop_user AS u JOIN #__hikamarket_vendor AS v ON u.user_id = v.vendor_admin_id WHERE u.user_cms_id = '.acym_currentUserId()
            );
            if (empty($vendorId)) {
                $vendorId = '-1';
            }
            $extraOption = [
                'title' => '',
                'type' => 'custom',
                'name' => 'hikamarketuser',
                'output' => '',
                'js' => 'otherinfo += "| vendorid:'.(int)$vendorId.'";',
            ];
            $catOptions[] = $extraOption;
        }

        $this->autoContentOptions($catOptions);

        $this->autoCampaignOptions($catOptions);

        $catOptions = array_merge($displayOptions, $catOptions);

        echo $this->displaySelectionZone($this->getCategoryListing());
        echo $this->pluginHelper->displayOptions($catOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();
        $identifier = 'hikashop_abandonedcart';
        $tabHelper->startTab(acym_translation('HIKA_ABANDONED_CART'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $methods = acym_loadObjectList('SELECT payment_id, payment_name FROM #__hikashop_payment', 'payment_id');

        $paymentMethods = ['' => 'ALL_PAYMENT_METHODS'];
        foreach ($methods as $method) {
            $paymentMethods[$method->payment_id] = $method->payment_name;
        }

        $abandonedOptions = [
            [
                'title' => 'PAYMENT_METHOD',
                'type' => 'select',
                'name' => 'paymentcart',
                'options' => $paymentMethods,
            ],
            [
                'title' => 'ACYM_DATE_CREATED',
                'type' => 'intextfield',
                'isNumber' => 1,
                'name' => 'nbdayscart',
                'text' => 'DAYS_AFTER_ORDERING',
                'default' => 1,
            ],
        ];

        $abandonedOptions = array_merge($displayOptions, $abandonedOptions);

        echo $this->pluginHelper->displayOptions($abandonedOptions, $identifier, 'simple', $this->defaultValues);

        $tabHelper->endTab();
        $identifier = 'hikashop_coupon';
        $tabHelper->startTab(acym_translation('ACYM_COUPON'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $query = "SELECT `product_id`, CONCAT(product_name, ' ( ', product_code, ' )') AS `title` 
                            FROM #__hikashop_product 
                            WHERE `product_type`='main' AND `product_published` = 1  
                            ORDER BY `product_code` ASC";
        $results = acym_loadObjectList($query);

        $products = [0 => 'ACYM_NONE'];
        foreach ($results as $result) {
            $products[$result->product_id] = $result->title;
        }

        $parent = acym_loadResult('SELECT category_id FROM #__hikashop_category WHERE category_parent_id = 0');

        $query = 'SELECT a.category_id, a.category_name  
                    FROM #__hikashop_category AS a 
                    WHERE a.category_type = "tax" 
                        AND a.category_published = 1 
                        AND a.category_parent_id != '.intval($parent).' 
                    ORDER BY a.category_ordering ASC';

        $results = acym_loadObjectList($query);

        $taxes = [0 => 'ACYM_NONE'];
        foreach ($results as $result) {
            $taxes[$result->category_id] = $result->category_name;
        }

        $query = 'SELECT currency_id AS value, CONCAT(currency_symbol, " ", currency_code) AS text FROM #__hikashop_currency WHERE currency_published = 1';
        $currencies = acym_loadObjectList($query);

        $flatValue = 0;
        $flatCurrency = null;
        if (!empty($this->defaultValues->flat)) $flatValue = $this->defaultValues->flat;
        if (!empty($this->defaultValues->currency)) $flatCurrency = $this->defaultValues->currency;
        $couponOptions = [
            [
                'title' => 'DISCOUNT_CODE',
                'type' => 'text',
                'name' => 'code',
                'default' => '[name][key][value]',
                'class' => 'acym_plugin__larger_text_field',
                'large' => true,
            ],
            [
                'title' => 'DISCOUNT_FLAT_AMOUNT',
                'type' => 'custom',
                'name' => 'flat',
                'output' => '<input type="number" name="flathikashop_coupon" id="flat" onchange="updateDynamichikashop_coupon();" value="'.$flatValue.'" class="acym_plugin_text_field" style="display: inline-block;" />
                            '.acym_select($currencies, 'currencyhikashop_coupon', $flatCurrency, 'onchange="updateDynamichikashop_coupon();" style="width: 80px;"'),
                'js' => 'otherinfo += "| flat:" + jQuery(\'input[name="flathikashop_coupon"]\').val();
                        otherinfo += "| currency:" + jQuery(\'[name="currencyhikashop_coupon"]\').val();',
            ],
            [
                'title' => 'DISCOUNT_PERCENT_AMOUNT',
                'type' => 'number',
                'name' => 'percent',
                'default' => '0',
            ],
            [
                'title' => 'DISCOUNT_START_DATE',
                'type' => 'date',
                'name' => 'start',
                'default' => '',
                'relativeDate' => '+',
            ],
            [
                'title' => 'DISCOUNT_END_DATE',
                'type' => 'date',
                'name' => 'end',
                'default' => '',
                'relativeDate' => '+',
            ],
            [
                'title' => 'MINIMUM_ORDER_VALUE',
                'type' => 'number',
                'name' => 'min',
                'default' => '0',
            ],
            [
                'title' => 'DISCOUNT_QUOTA',
                'type' => 'number',
                'name' => 'quota',
                'default' => '3',
            ],
            [
                'title' => 'DISCOUNT_QUOTA_PER_USER',
                'type' => 'number',
                'name' => 'quota_user',
                'default' => '',
            ],
            [
                'title' => 'PRODUCT',
                'type' => 'select',
                'name' => 'product',
                'options' => $products,
                'default' => '0',
            ],
            [
                'title' => 'TAXATION_CATEGORY',
                'type' => 'select',
                'name' => 'tax',
                'options' => $taxes,
                'default' => '0',
            ],
        ];

        echo $this->pluginHelper->displayOptions($couponOptions, $identifier, 'simple', $this->defaultValues);

        $tabHelper->endTab();

        $tabHelper->display('plugin');
    }

    public function prepareListing()
    {
        $this->querySelect = 'SELECT a.* ';
        $this->query = 'FROM #__hikashop_product AS a ';
        $this->filters = [];
        $this->searchFields = ['a.product_id', 'a.product_name', 'a.product_code'];
        $this->pageInfo->order = 'a.product_id';
        $this->elementIdTable = 'a';
        $this->elementIdColumn = 'product_id';

        if ($this->getParam('stock', '1') === '1') {
            $this->filters[] = '(a.product_quantity = -1 OR a.product_quantity > 0)';
        }

        parent::prepareListing();

        //if a category is selected
        if (!empty($this->pageInfo->filter_cat)) {
            $this->query .= 'JOIN #__hikashop_product_category AS b ON a.product_id = b.product_id';


            $this->categories = acym_loadObjectList(
                "SELECT category_id AS id, category_parent_id AS parent_id, category_name AS title 
				FROM `#__hikashop_category` 
				WHERE category_type = 'product'",
                'id'
            );
            $category = intval($this->pageInfo->filter_cat);
            $categories = $this->getSubCategories($category);
            acym_arrayToInteger($categories);

            $this->filters[] = 'b.category_id IN ('.implode(', ', $categories).')';
        }

        // Hikamarket: only display product from the vendor
        $currentUserId = acym_currentUserId();
        if (!acym_isAdmin() && acym_isExtensionActive('com_hikamarket') && $this->getParam('front', 'all') === 'user') {
            $this->query .= ' JOIN #__hikamarket_vendor AS hv ON a.product_vendor_id = hv.vendor_id ';
            $this->query .= ' JOIN #__hikashop_user as hu ON hv.vendor_admin_id = hu.user_id AND hu.user_cms_id = '.(int)$currentUserId;
        }

        $listingOptions = [
            'header' => [
                'product_name' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '7',
                ],
                'product_created' => [
                    'label' => 'ACYM_DATE_CREATED',
                    'size' => '4',
                    'type' => 'date',
                ],
                'product_id' => [
                    'label' => 'ACYM_ID',
                    'size' => '1',
                    'class' => 'text-center',
                ],
            ],
            'id' => 'product_id',
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
        $this->readmore = empty($email->template->readmore)
            ? acym_translation('ACYM_READ_MORE')
            : '<img src="'.ACYM_LIVE.$email->template->readmore.'" alt="'.acym_translation(
                'ACYM_READ_MORE',
                true
            ).'" />';

        if (!include_once(rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php')) return;

        $this->hikaConfig = hikashop_config();
        $this->productClass = hikashop_get('class.product');
        $this->imageHelper = hikashop_get('helper.image');
        $this->currencyClass = hikashop_get('class.currency');
        $this->translationHelper = hikashop_get('helper.translation');
    }

    public function generateByCategory(&$email)
    {
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $this->tags = [];

        if (empty($tags)) return $this->generateCampaignResult;

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            $query = 'SELECT DISTINCT b.`product_id` FROM #__hikashop_product_category AS a 
                    LEFT JOIN #__hikashop_product AS b ON a.product_id = b.product_id';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $this->categories = acym_loadObjectList(
                    'SELECT category_id AS id, category_parent_id AS parent_id, category_name AS title 
					FROM `#__hikashop_category` 
					WHERE category_type = "product"',
                    'id'
                );
                $categories = [];
                foreach ($selectedArea as $oneSelectedCat) {
                    $categories = array_merge($categories, $this->getSubCategories($oneSelectedCat));
                }
                acym_arrayToInteger($categories);

                $where[] = 'a.category_id IN ('.implode(',', $categories).')';
            }

            $where[] = 'b.`product_published` = 1';

            if ($this->getParam('stock', '1') === '1') {
                $this->filters[] = '(b.product_quantity = -1 OR b.product_quantity > 0)';
            }
            if (!empty($parameter->min_publish)) {
                $parameter->min_publish = acym_replaceDate($parameter->min_publish);
                $where[] = 'b.`product_created` >= '.acym_escapeDB($parameter->min_publish);
            }

            if (!empty($parameter->onlynew)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $where[] = 'b.`product_created` > '.acym_escapeDB($lastGenerated);
                }
            }

            if (acym_isExtensionActive('com_hikamarket') && !empty($parameter->vendorid)) {
                $where[] = 'b.product_vendor_id = '.(int)$parameter->vendorid;
            }

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'b');
        }

        return $this->generateCampaignResult;
    }

    public function replaceIndividualContent($tag)
    {
        // Get product data
        $query = 'SELECT b.*, a.*
                    FROM #__hikashop_product AS a
                    LEFT JOIN #__hikashop_file AS b ON a.product_id = b.file_ref_id AND file_type = "product"
                    WHERE a.product_id = '.intval($tag->id).'
                    ORDER BY b.file_ordering ASC, b.file_id ASC';

        $product = $this->initIndividualContent($tag, $query);

        if (empty($product)) return '';

        if ($product->product_type == 'variant') {
            $query = 'SELECT * 
                        FROM #__hikashop_variant AS a 
                        LEFT JOIN #__hikashop__characteristic AS b ON a.variant_characteristic_id = b.characteristic_id 
                        WHERE a.variant_product_id = '.intval($tag->id).' 
                        ORDER BY a.ordering';
            $product->characteristics = acym_loadObjectList($query);

            $query = 'SELECT b.*, a.*
                        FROM #__hikashop_product AS a
                        LEFT JOIN #__hikashop_file AS b ON a.product_id = b.file_ref_id AND file_type = "product"
                        WHERE a.product_id = '.intval($product->product_parent_id).'
                        ORDER BY b.file_ordering ASC, b.file_id ASC';
            $parentProduct = acym_loadObject($query);

            $this->productClass->checkVariant($product, $parentProduct);
        }

        if ($this->translationHelper->isMulti(true, false)) {
            $this->pluginHelper->translateItem($product, $tag, 'hikashop_product');
        }

        $varFields = $this->getCustomLayoutVars($product);


        // Prepare the price
        $main_currency = $currency_id = (int)$this->hikaConfig->get('main_currency', 1);
        $zone_id = explode(',', $this->hikaConfig->get('main_tax_zone', 0));
        $zone_id = count($zone_id) ? array_shift($zone_id) : 0;

        $ids = [$product->product_id];
        $discount_before_tax = (int)$this->hikaConfig->get('discount_before_tax', 0);
        $this->currencyClass->getPrices($product, $ids, $currency_id, $main_currency, $zone_id, $discount_before_tax);

        $finalPrice = '';
        // Tests on $tag->type are for retro compatibility since 2/2/21
        if ((empty($tag->type) && $tag->price_type === 'full') || (!empty($tag->type) && $tag->price === 'full')) {
            $priceSource = $this->getParam('vat', '1') === '1' ? 'price_value_with_tax' : 'price_value';
            $finalPrice = @$this->currencyClass->format(
                $product->prices[0]->$priceSource,
                $product->prices[0]->price_currency_id
            );

            if (!empty($product->discount)) {
                $priceSource = $this->getParam('vat', '1') === '1' ? 'price_value_without_discount_with_tax' : 'price_value_without_discount';
                $oldPrice = '<span style="text-decoration: line-through;">';
                $oldPrice .= $this->currencyClass->format(
                    $product->prices[0]->$priceSource,
                    $product->prices[0]->price_currency_id
                );
                $oldPrice .= '</span> ';
                $finalPrice = $oldPrice.$finalPrice;
            }
        } elseif (empty($tag->type) || $tag->price === 'no_discount') {
            $vatActive = $this->getParam('vat', '1') === '1';

            $priceSource = $vatActive ? 'price_value_without_discount_with_tax' : 'price_value_without_discount';
            if (empty($product->prices[0]->$priceSource)) $priceSource = $vatActive ? 'price_value_with_tax' : 'price_value';

            $finalPrice = $this->currencyClass->format(
                $product->prices[0]->$priceSource,
                $product->prices[0]->price_currency_id
            );
        }
        $varFields['{finalPrice}'] = $finalPrice;


        // Prepare the link
        $link = 'index.php?option=com_hikashop&ctrl=product&task=show&cid='.$product->product_id;
        if (!empty($product->product_canonical)) {
            $link = $product->product_canonical;
        }
        $link = $this->finalizeLink($link);
        $varFields['{link}'] = $link;


        $title = '';
        $varFields['{title}'] = $product->product_name;
        if (!empty($tag->type) || in_array('title', $tag->display)) $title = $varFields['{title}'];
        if ((!empty($tag->type) || in_array('price', $tag->display)) && !empty($finalPrice)) {
            $title .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$finalPrice;
        }


        // Prepare the main picture
        $imagePath = '';
        if (!empty($product->file_path)) {
            $img = $this->imageHelper->getThumbnail($product->file_path, null);
            if ($img->success) {
                $imagePath = $img->url;
            } else {
                $imagePath = $this->imageHelper->display($product->file_path, false, $product->product_name);
            }
        }
        $imagePath = ltrim($imagePath, './');
        if (strpos($imagePath, acym_rootURI()) !== 0) {
            $imagePath = acym_mainURL($imagePath).$imagePath;
        }
        // For retro compatibility
        $varFields['{pictHTML}'] = $imagePath;
        $varFields['{picthtml}'] = '<img alt="'.acym_escape($product->product_name.' '.acym_translation('ACYM_FEATURED_IMAGE')).'" src="'.$imagePath.'">';

        if (empty($tag->type) && !in_array('image', $tag->display)) $imagePath = '';


        // Prepare the main content
        $contentText = '';
        $varFields['{desc}'] = $product->product_description;
        $cutPosition = strpos($varFields['{desc}'], '<hr id="system-readmore"');
        if ($cutPosition === false) {
            if (empty($tag->type)) {
                $varFields['{shortdesc}'] = $varFields['{desc}'];
            } else {
                $varFields['{shortdesc}'] = substr($varFields['{desc}'], 0, 100).'...';
            }
        } else {
            $varFields['{shortdesc}'] = substr($varFields['{desc}'], 0, $cutPosition);
        }

        if (empty($tag->type)) {
            if (in_array('shortdesc', $tag->display)) $contentText .= $varFields['{shortdesc}'];
            if (in_array('desc', $tag->display)) $contentText .= $varFields['{desc}'];
        } elseif ($tag->type !== 'title') {
            // Retro compat
            if ($tag->type === 'full') {
                $contentText = $varFields['{desc}'];
            } else {
                $contentText = $varFields['{shortdesc}'];
            }
        }


        $afterArticle = '';
        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'">';
        $varFields['{readmore}'] .= '<span class="acymailing_readmore">'.acym_escape(acym_translation('ACYM_READ_MORE')).'</span></a>';
        if (empty($tag->type) && in_array('readmore', $tag->display)) $afterArticle .= $varFields['{readmore}'];


        $format = new stdClass();
        $format->tag = $tag;
        $format->title = $title;
        $format->afterTitle = '';
        $format->afterArticle = $afterArticle;
        $format->imagePath = $imagePath;
        $format->description = $contentText;
        $format->link = empty($tag->clickable) ? '' : $link;
        $format->customFields = [];
        $result = '<div class="acym_product acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }

    public function replaceUserInformation(&$email, &$user, $send = true)
    {
        if (!include_once(rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php')) return;

        $this->hikaConfig = hikashop_config();

        $this->replaceAbandonedCarts($email, $user);
        $this->replaceCoupons($email, $user, $send);
    }

    public function replaceAbandonedCarts(&$email, &$user)
    {
        $tags = $this->pluginHelper->extractTags($email, 'hikashop_abandonedcart');
        if (empty($tags)) {
            return;
        }

        $tagsReplaced = [];
        foreach ($tags as $i => $oneTag) {
            if (isset($tagsReplaced[$i])) continue;

            $tagsReplaced[$i] = $this->replaceAbandonedCart($oneTag, $user);
        }

        $this->pluginHelper->replaceTags($email, $tagsReplaced, true);

        $this->replaceOne($email);
    }

    public function replaceAbandonedCart($oneTag, $user)
    {
        if (empty($user->cms_id)) return '';

        $delay = 0;
        if (!empty($oneTag->nbdayscart)) {
            $delay = ($oneTag->nbdayscart * 86400);
        }

        $senddate = time() - intval($delay);

        $createdstatus = $this->hikaConfig->get('order_created_status', 'created');

        $myquery = 'SELECT c.product_id
					FROM #__hikashop_order AS a
					LEFT JOIN #__hikashop_order AS b
						ON a.order_user_id = b.order_user_id
						AND b.order_id > a.order_id
					JOIN #__hikashop_order_product AS c
						ON a.order_id = c.order_id
					JOIN #__hikashop_user AS hikauser
						ON a.order_user_id = hikauser.user_id ';

        if (!empty($oneTag->paymentcart)) {
            $myquery .= 'JOIN #__hikashop_payment AS payment
                            ON payment.payment_type = a.order_payment_method
                            AND payment.payment_id = '.intval($oneTag->paymentcart);
        }

        $myquery .= ' WHERE hikauser.user_cms_id = '.intval($user->cms_id).' AND a.order_status = '.acym_escapeDB($createdstatus).' AND b.order_id IS NULL ';
        $myquery .= ' AND FROM_UNIXTIME(a.order_created,"%Y %d %m") = FROM_UNIXTIME('.$senddate.',"%Y %d %m")';

        return $this->finalizeCategoryFormat($myquery, $oneTag);
    }

    public function replaceCoupons(&$email, &$user, $send = true)
    {
        $tags = $this->pluginHelper->extractTags($email, 'hikashop_coupon');
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
                $tagsReplaced[$i] = $this->generateCoupon($oneTag, $user, $i);
            }
        }

        $this->pluginHelper->replaceTags($email, $tagsReplaced, true);
    }

    public function generateCoupon($tag, $user, $raw)
    {
        if (empty($tag->code)) {
            $code = '[name][key][value]';
        } else {
            $code = $tag->code;
        }

        $minimum_order = $tag->min;
        $quota = $tag->quota;
        $start = $tag->start;
        $end = $tag->end;
        $percent_amount = $tag->percent;
        $flat_amount = $tag->flat;
        $currency_id = $tag->currency;
        $product_id = $tag->product;
        $tax_id = $tag->tax;

        $quotaPerUser = empty($tag->quota_user) ? 0 : $tag->quota_user;

        $key = acym_generateKey(5);

        if ($percent_amount > 0) {
            $value = $percent_amount;
        } else {
            $value = $flat_amount;
        }

        $value = str_replace(',', '.', $value);

        if ($start) {
            $start = acym_replaceDate($start);
            $start = hikashop_getTime($start);
        }

        if ($end) {
            $end = acym_replaceDate($end);
            $end = hikashop_getTime($end);
        }

        $clean_name = strtoupper($user->name);
        $space = strpos($clean_name, ' ');
        if (!empty($space)) {
            $clean_name = substr($clean_name, 0, $space);
        }

        $code = str_replace(
            [
                '[name]',
                '[clean_name]',
                '[subid]',
                '[email]',
                '[key]',
                '[flat]',
                '[percent]',
                '[value]',
                '[prodid]',
            ],
            [
                $user->name,
                $clean_name,
                $user->id,
                $user->email,
                $key,
                $flat_amount,
                $percent_amount,
                $value,
                $product_id,
            ],
            $code
        );

        $query = 'INSERT IGNORE INTO #__hikashop_discount (
            `discount_code`,
            `discount_percent_amount`,
            `discount_flat_amount`,
            `discount_type`,
            `discount_start`,
            `discount_end`,
            `discount_minimum_order`,
            `discount_quota`,
            `discount_currency_id`,
            `discount_product_id`,
            `discount_tax_id`,
            `discount_published`,
            `discount_quota_per_user`
		) VALUES (
		    '.acym_escapeDB($code).',
		    '.acym_escapeDB($percent_amount).',
		    '.acym_escapeDB($flat_amount).',
		    "coupon",
		    '.acym_escapeDB($start).',
		    '.acym_escapeDB($end).',
		    '.acym_escapeDB($minimum_order).',
		    '.acym_escapeDB($quota).',
		    '.acym_escapeDB($currency_id).',
		    '.acym_escapeDB($product_id).',
		    '.acym_escapeDB($tax_id).',
		    1,
		    '.acym_escapeDB($quotaPerUser).'
        )';

        acym_query($query);

        return $code;
    }

    /**
     * Function called with ajax to search in products
     */
    public function searchProduct()
    {
        $ids = $this->getIdsSelectAjax();

        if (!empty($ids)) {
            $value = '';
            $elements = acym_loadObjectList('SELECT `product_name` AS name, `product_id` AS id FROM #__hikashop_product WHERE `product_id` IN ("'.implode('","', $ids).'")');

            $value = [];
            if (!empty($elements)) {
                foreach ($elements as $element) {
                    $value[] = [
                        'text' => $element->name,
                        'value' => $element->id,
                    ];
                }
            }
            echo json_encode($value);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $elements = acym_loadObjectList(
            'SELECT `product_id`, `product_name` FROM `#__hikashop_product` WHERE `product_name` LIKE '.acym_escapeDB('%'.$search.'%').' ORDER BY `product_name`'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->product_id, $oneElement->product_name];
        }

        echo json_encode($return);
        exit;
    }

    public function searchCat()
    {
        $ids = $this->getIdsSelectAjax();

        if (!empty($ids)) {
            $cats = acym_loadObjectList(
                'SELECT `category_id` AS id, `category_name` AS name 
				FROM #__hikashop_category 
				WHERE `category_type` = "product" 
					AND `category_id` IN ("'.implode('","', $ids).'") 
				ORDER BY `category_name`'
            );

            $value = [];
            if (!empty($cats)) {
                foreach ($cats as $cat) {
                    $value[] = ['text' => $cat->name, 'value' => $cat->id];
                }
            }
            echo json_encode($value);
            exit;
        }

        $search = acym_getVar('string', 'search', '');
        $cats = acym_loadObjectList(
            'SELECT `category_id` AS id, `category_name` AS name 
			FROM #__hikashop_category 
			WHERE `category_type` = "product" 
				AND `category_name` LIKE '.acym_escapeDB('%'.$search.'%').' 
			ORDER BY `category_name`'
        );
        $categories = [];
        foreach ($cats as $oneCat) {
            $categories[] = [$oneCat->id, $oneCat->name];
        }

        echo json_encode($categories);
        exit;
    }

    public function searchVendor()
    {
        $ids = $this->getIdsSelectAjax();
        if (!empty($ids)) {
            $value = '';
            $elements = acym_loadObjectList('SELECT `vendor_name` AS name, `vendor_id` AS id FROM #__hikamarket_vendor WHERE `vendor_id` IN ("'.implode('","', $ids).'")');

            $value = [];
            if (!empty($elements)) {
                foreach ($elements as $element) {
                    $value[] = [
                        'text' => $element->name,
                        'value' => $element->id,
                    ];
                }
            }
            echo json_encode($value);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $elements = acym_loadObjectList(
            'SELECT `vendor_id`, `vendor_name` FROM `#__hikamarket_vendor` WHERE `vendor_name` LIKE '.acym_escapeDB('%'.$search.'%').' ORDER BY `vendor_name`'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->vendor_id, $oneElement->vendor_name];
        }

        echo json_encode($return);
        exit;
    }

    public function onAcymDeclareConditions(&$conditions)
    {
        $categories = [
            'any' => acym_translation('ACYM_ANY_CATEGORY'),
        ];
        $cats = acym_loadObjectList('SELECT `category_id`, `category_name` FROM #__hikashop_category WHERE `category_type` = "product" ORDER BY `category_name`');
        foreach ($cats as $oneCat) {
            $categories[$oneCat->category_id] = $oneCat->category_name;
        }

        $conditions['user']['hikapurchased'] = new stdClass();
        $conditions['user']['hikapurchased']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'HikaShop', acym_translation('ACYM_PURCHASED'));
        $conditions['user']['hikapurchased']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['hikapurchased']->option .= '<div class="cell acym_vcenter shrink">'.acym_translation('ACYM_BOUGHT').'</div>';

        $conditions['user']['hikapurchased']->option .= '<div class="intext_select_automation cell">';
        $ajaxParams = json_encode([
            'plugin' => __CLASS__,
            'trigger' => 'searchProduct',
        ]);
        $conditions['user']['hikapurchased']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][hikapurchased][product]',
            null,
            'class="acym__select acym_select2_ajax" data-placeholder="'.acym_translation('ACYM_AT_LEAST_ONE_PRODUCT', true).'" data-params="'.acym_escape($ajaxParams).'"'
        );
        $conditions['user']['hikapurchased']->option .= '</div>';

        $conditions['user']['hikapurchased']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['hikapurchased']->option .= acym_select(
            $categories,
            'acym_condition[conditions][__numor__][__numand__][hikapurchased][category]',
            'any',
            'class="acym__select"'
        );
        $conditions['user']['hikapurchased']->option .= '</div>';

        // Filter on vendor
        if (acym_isExtensionActive('com_hikamarket')) {
            $conditions['user']['hikapurchased']->option .= '<div class="cell acym_vcenter shrink">'.acym_translation('ACYM_FROM').'</div>';
            $conditions['user']['hikapurchased']->option .= '<div class="intext_select_automation cell">';
            $ajaxParams = json_encode([
                'plugin' => __CLASS__,
                'trigger' => 'searchVendor',
            ]);
            $conditions['user']['hikapurchased']->option .= acym_select(
                [],
                'acym_condition[conditions][__numor__][__numand__][hikapurchased][vendor]',
                null,
                'class="acym__select acym_select2_ajax" data-placeholder="'.acym_translation('ACYM_ANY_VENDOR', true).'" data-params="'.acym_escape($ajaxParams).'"'
            );
            $conditions['user']['hikapurchased']->option .= '</div>';
        }

        $conditions['user']['hikapurchased']->option .= '</div>';

        $conditions['user']['hikapurchased']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['hikapurchased']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][hikapurchased][datemin]', '', 'cell shrink');
        $conditions['user']['hikapurchased']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['hikapurchased']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_DATE_CREATED').'</span>';
        $conditions['user']['hikapurchased']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['hikapurchased']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][hikapurchased][datemax]', '', 'cell shrink');
        $conditions['user']['hikapurchased']->option .= '</div>';


        $orderStatuses = acym_loadObjectList('SELECT `orderstatus_id` AS value, `orderstatus_name` AS text FROM #__hikashop_orderstatus ORDER BY `orderstatus_name`');

        $paymentMethods = ['any' => acym_translation('ACYM_ANY_PAYMENT_METHOD')];
        $payments = acym_loadObjectList('SELECT `payment_id`, `payment_name` FROM #__hikashop_payment ORDER BY `payment_name`');
        foreach ($payments as $oneMethod) {
            $paymentMethods[$oneMethod->payment_id] = $oneMethod->payment_name;
        }

        $delayType = new DelayType();
        $conditions['user']['hikareminder'] = new stdClass();
        $conditions['user']['hikareminder']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'HikaShop', acym_translation('ACYM_REMINDER'));
        $conditions['user']['hikareminder']->option = '<div class="cell">';
        $conditions['user']['hikareminder']->option .= acym_translationSprintf(
            'ACYM_ORDER_WITH_STATUS',
            $delayType->display('acym_condition[conditions][__numor__][__numand__][hikareminder][days]', 1, 1, '__numor____numand__'),
            '<div class="intext_select_automation cell margin-right-1">'.acym_select(
                $orderStatuses,
                'acym_condition[conditions][__numor__][__numand__][hikareminder][status]',
                null,
                'class="acym__select"'
            ).'</div>'
        );
        $conditions['user']['hikareminder']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['hikareminder']->option .= acym_select(
            $paymentMethods,
            'acym_condition[conditions][__numor__][__numand__][hikareminder][payment]',
            'any',
            'class="acym__select"'
        );
        $conditions['user']['hikareminder']->option .= '</div>';
        $conditions['user']['hikareminder']->option .= '</div>';

        $conditions['user']['hikawishlist'] = new stdClass();
        $conditions['user']['hikawishlist']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'HikaShop', acym_translation('ACYM_WISHLIST'));
        $conditions['user']['hikawishlist']->option = '<div class="intext_select_automation cell">';
        $conditions['user']['hikawishlist']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][hikawishlist][product]',
            null,
            'class="acym__select acym_select2_ajax" data-placeholder="'.acym_translation('ACYM_ANY_PRODUCT', true).'"  data-params="'.acym_escape($ajaxParams).'"'
        );
        $conditions['user']['hikawishlist']->option .= '</div>';
        $conditions['user']['hikawishlist']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['hikawishlist']->option .= acym_select(
            $categories,
            'acym_condition[conditions][__numor__][__numand__][hikawishlist][category]',
            'any',
            'class="acym__select"'
        );
        $conditions['user']['hikawishlist']->option .= '</div>';
    }

    public function onAcymProcessCondition_hikapurchased(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_hikapurchased($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_hikapurchased(&$query, $options, $num)
    {
        $query->join['hikapurchased_hika_user'.$num] = '#__hikashop_user AS hika_user'.$num.' ON user.email = hika_user'.$num.'.user_email';
        $query->join['hikapurchased_order'.$num] = '#__hikashop_order AS order'.$num.' ON hika_user'.$num.'.user_id = order'.$num.'.order_user_id';

        $query->where[] = 'order'.$num.'.order_user_id != 0';
        $query->where[] = 'order'.$num.'.order_type = "sale"';
        $query->where[] = 'order'.$num.'.order_status = "confirmed"';

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $query->where[] = 'order'.$num.'.order_created > '.acym_escapeDB($options['datemin']);
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $query->where[] = 'order'.$num.'.order_created < '.acym_escapeDB($options['datemax']);
            }
        }

        if (!empty($options['product'])) {
            $query->join['hikapurchased_order_product'.$num] = '#__hikashop_order_product AS hikaop'.$num.' ON order'.$num.'.order_id = hikaop'.$num.'.order_id';
            $query->where[] = 'hikaop'.$num.'.product_id = '.intval($options['product']);
        } elseif (!empty($options['category']) && $options['category'] != 'any') {
            $query->join['hikapurchased_order_product'.$num] = '#__hikashop_order_product AS hikaop'.$num.' ON order'.$num.'.order_id = hikaop'.$num.'.order_id';
            $query->join['hikapurchased_order_cat'.$num] = '#__hikashop_product_category AS hikapc'.$num.' ON hikaop'.$num.'.product_id = hikapc'.$num.'.product_id';
            $query->where[] = 'hikapc'.$num.'.category_id = '.intval($options['category']);
        }

        // Filter on the vendor (Hikamarket)
        // Don't applly if there is a filter on a product
        if (acym_isExtensionActive('com_hikamarket') && empty($options['product']) && !empty($options['vendor'])) {
            if (empty($query->join['hikapurchased_order_product'.$num])) {
                $query->join['hikapurchased_order_product'.$num] = '#__hikashop_order_product AS hikaop'.$num.' ON order'.$num.'.order_id = hikaop'.$num.'.order_id';
            }
            $query->join['hikapurchased_product'.$num] = '#__hikashop_product AS hikap'.$num.' ON hikaop'.$num.'.product_id = hikap'.$num.'.product_id';
            $query->where[] = 'hikap'.$num.'.product_vendor_id = '.(int)$options['vendor'];
        }
    }

    public function onAcymProcessCondition_hikareminder(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_hikareminder($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_hikareminder(&$query, $options, $num)
    {
        if (!include_once rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php') return;

        $options['days'] = intval($options['days']);

        if (version_compare(HIKASHOP_VERSION, '4.0.0', '>=')) {
            $orderStatuses = acym_loadObjectList('SELECT `orderstatus_id` AS id,  `orderstatus_namekey` AS name FROM #__hikashop_orderstatus', 'id');
            $orderStatus = $orderStatuses[$options['status']]->name;
        } else {
            $orderStatus = $options['status'];
        }
        $query->join['hikareminder_hika_user'.$num] = '#__hikashop_user AS hika_user'.$num.' ON user.email = hika_user'.$num.'.user_email';
        $query->join['hikareminder_order'.$num] = '#__hikashop_order AS order'.$num.' ON order'.$num.'.order_user_id = hika_user'.$num.'.user_id';

        $query->where[] = 'order'.$num.'.order_user_id != 0';
        $query->where[] = 'order'.$num.'.order_type = "sale"';
        $query->where[] = 'order'.$num.'.order_status = '.acym_escapeDB($orderStatus);

        $query->where[] = 'FROM_UNIXTIME(order'.$num.'.order_created, "%Y-%m-%d") = '.acym_escapeDB(date('Y-m-d', time() - $options['days']));

        if (!empty($options['payment']) && $options['payment'] != 'any') {
            $query->where[] = 'order'.$num.'.order_payment_id = '.intval($options['payment']);
        }
    }

    public function onAcymProcessCondition_hikawishlist(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_hikawishlist($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_hikawishlist(&$query, $options, $num)
    {
        if (!include_once rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php') return;

        $query->join['hikawishlist_hika_user'.$num] = '#__hikashop_user AS hika_user'.$num.' ON user.email = hika_user'.$num.'.user_email';
        $query->join['hikawishlist_cart'.$num] = '#__hikashop_cart AS hika_cart'.$num.' ON hika_user'.$num.'.user_id = hika_cart'.$num.'.user_id';

        $query->where[] = 'hika_cart'.$num.'.cart_type = "wishlist"';

        if (!empty($options['product'])) {
            $query->join['hikawishlist_cart_product'.$num] = '#__hikashop_cart_product AS hika_cart_product'.$num.' ON hika_cart'.$num.'.cart_id = hika_cart_product'.$num.'.cart_id';
            $query->where[] = 'hika_cart_product'.$num.'.product_id = '.intval($options['product']);
        } elseif (!empty($options['category']) && $options['category'] !== 'any') {
            $query->join['hikawishlist_cart_product'.$num] = '#__hikashop_cart_product AS hika_cart_product'.$num.' ON hika_cart'.$num.'.cart_id = hika_cart_product'.$num.'.cart_id';
            $query->join['hikawishlist_cat'.$num] = '#__hikashop_product_category AS hikapc'.$num.' ON hika_cart_product'.$num.'.product_id = hikapc'.$num.'.product_id';
            $query->where[] = 'hikapc'.$num.'.category_id = '.intval($options['category']);
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        if (!empty($automationCondition['hikapurchased'])) {
            if (empty($automationCondition['hikapurchased']['product'])) {
                $product = acym_translation('ACYM_AT_LEAST_ONE_PRODUCT');
            } else {
                $product = acym_loadResult('SELECT `product_name` FROM #__hikashop_product WHERE `product_id` = '.intval($automationCondition['hikapurchased']['product']));
            }

            $cats = acym_loadObjectList('SELECT `category_id`, `category_name` FROM #__hikashop_category WHERE `category_type` = "product"', 'category_id');
            $category = empty($cats[$automationCondition['hikapurchased']['category']]) ? acym_translation(
                'ACYM_ANY_CATEGORY'
            ) : $cats[$automationCondition['hikapurchased']['category']]->category_name;

            $finalText = acym_translationSprintf('ACYM_CONDITION_PURCHASED', $product, $category);

            if (acym_isExtensionActive('com_hikamarket') && empty($automationCondition['hikapurchased']['product'])) {
                $finalText .= ' '.acym_translation('ACYM_FROM').' ';
                if (empty($automationCondition['hikapurchased']['vendor'])) {
                    $finalText .= acym_translation('ACYM_ANY_VENDOR');
                } else {
                    $vendorName = acym_loadResult('SELECT vendor_name FROM #__hikamarket_vendor WHERE vendor_id = '.(int)$automationCondition['hikapurchased']['vendor']);
                    $finalText .= $vendorName;
                }
            }

            $dates = [];
            if (!empty($automationCondition['hikapurchased']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['hikapurchased']['datemin'], true);
            }

            if (!empty($automationCondition['hikapurchased']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['hikapurchased']['datemax'], true);
            }

            if (!empty($dates)) {
                $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $automationCondition = $finalText;
        }

        if (!empty($automationCondition['hikareminder'])) {

            $orderStatuses = acym_loadObjectList('SELECT `orderstatus_id`, `orderstatus_name` FROM #__hikashop_orderstatus', 'orderstatus_id');
            $paymentMethods = acym_loadObjectList('SELECT `payment_id`, `payment_name` FROM #__hikashop_payment', 'payment_id');

            $delayType = new DelayType();
            $delay = $delayType->get((int)$automationCondition['hikareminder']['days'], 1);

            $paymentName = @$paymentMethods[$automationCondition['hikareminder']['payment']]->payment_name;
            if (empty($paymentName)) $paymentName = 'ACYM_ANY_PAYMENT_METHOD';
            $automationCondition = acym_translationSprintf(
                'ACYM_CONDITION_HIKASHOP_REMINDER',
                acym_translation($paymentName),
                $delay->value,
                strtolower($delay->typeText),
                $orderStatuses[$automationCondition['hikareminder']['status']]->orderstatus_name
            );
        }

        if (!empty($automationCondition['hikawishlist'])) {
            if (!(empty($automationCondition['hikawishlist']['product']))) {
                $product = acym_loadResult('SELECT `product_name` FROM #__hikashop_product WHERE product_id = '.acym_escapeDB($automationCondition['hikawishlist']['product']));
            }
            if (!(empty($automationCondition['hikawishlist']['category'])) && $automationCondition['hikawishlist']['category'] !== 'any') {
                $cats = acym_loadObjectList('SELECT `category_id`, `category_name` FROM #__hikashop_category WHERE `category_type` = "product"', 'category_id');
            }
            $category = empty($cats[$automationCondition['hikawishlist']['category']]) ? acym_translation(
                'ACYM_ANY_CATEGORY'
            ) : $cats[$automationCondition['hikawishlist']['category']]->category_name;

            $automationCondition = acym_translationSprintf(
                'ACYM_WISH_LISTED',
                !empty($product) ? $product : acym_translation('ACYM_ANY_PRODUCT'),
                $category
            );
        }
    }

    public function onAcymDeclareFilters(&$filters)
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_hikapurchased(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_hikapurchased($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_hikapurchased(&$query, $options, $num)
    {
        $this->processConditionFilter_hikapurchased($query, $options, $num);
    }

    public function onAcymProcessFilterCount_hikareminder(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_hikareminder($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_hikareminder(&$query, $options, $num)
    {
        $this->processConditionFilter_hikareminder($query, $options, $num);
    }

    public function onAcymProcessFilterCount_hikawishlist(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_hikawishlist($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_hikawishlist(&$query, $options, $num)
    {
        $this->processConditionFilter_hikawishlist($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }

    // Add trigger configuration for Hikashop order status change and wishlist
    public function onAcymDeclareTriggers(&$triggers, &$defaultValues)
    {
        if (!include_once rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php') return;

        $statusClass = hikashop_get('type.categorysub');
        $statusClass->type = 'status';
        $statusClass->load();

        if (empty($statusClass->categories)) return;

        $triggers['user']['hikashoporder'] = new stdClass();
        $triggers['user']['hikashoporder']->name = acym_translationSprintf('ACYM_ORDER_STATUS_CHANGED', 'HikaShop', '');

        $triggers['user']['hikashopNewOrder'] = new stdClass();
        $triggers['user']['hikashopNewOrder']->name = acym_translationSprintf('ACYM_X_NEW_ORDER', 'HikaShop');
        $triggers['user']['hikashopNewOrder']->option = '<input type="hidden" name="[triggers][user][hikashopNewOrder][hidden]" value="">';

        $triggers['user']['hikashopWishlistUpdated'] = new stdClass();
        $triggers['user']['hikashopWishlistUpdated']->name = acym_translationSprintf('ACYM_X_ADD_TO_WISHLIST', 'HikaShop');
        $triggers['user']['hikashopWishlistUpdated']->option = '<input type="hidden" name="[triggers][user][hikashopWishlistUpdated][hidden]" value="">';

        $cats = [];
        foreach ($statusClass->categories as $category) {
            if (empty($category->value)) {
                $val = str_replace(' ', '_', strtoupper($category->category_name));
                $category->value = acym_translation($val);
                if ($val == $category->value) {
                    $category->value = $category->category_name;
                }
            }
            $cats[$category->value] = $category->value;
        }

        $selectedValue = empty($defaultValues['hikashoporder']['status']) ? [] : $defaultValues['hikashoporder']['status'];
        $triggers['user']['hikashoporder']->option = acym_selectMultiple($cats, '[triggers][user][hikashoporder][status]', $selectedValue, ['data-class' => 'acym__select']);
    }

    public function onAcymExecuteTrigger(&$step, &$execute, &$data)
    {
        if (empty($data['userId'])) return;

        $triggers = $step->triggers;

        if (!empty($triggers['hikashoporder']) && !empty($data['order'])) {
            // Check order status in allowed statuses in the trigger
            if (!empty($triggers['hikashoporder']) && in_array($data['order']->order_status, $triggers['hikashoporder']['status'])) {
                $execute = true;
            }
        }
        if (!empty($triggers['hikashopNewOrder']) && !empty($data['order'])) {
            if ($data['order']->order_status == 'confirmed') {
                $execute = true;
            }
        }
        if (!empty($triggers['hikashopWishlistUpdated'])) {
            if ($data['cart_type'] === 'wishlist') {
                $execute = true;
            }
        }
    }

    // Trigger on Hikashop status change
    public function onAfterOrderUpdate(&$order)
    {
        if (empty($order->order_id) || empty($order->order_status)) return;

        // Get Hikashop user from the order
        if (empty($order->order_user_id)) {
            $class = hikashop_get('class.order');
            $old = $class->get($order->order_id);
            if (empty($old)) return;
            $order->order_user_id = $old->order_user_id;
        }
        $hikaUserClass = hikashop_get('class.user');
        $hikaUser = $hikaUserClass->get($order->order_user_id);
        if (empty($hikaUser)) return;

        // Trigger the automation
        $userClass = new UserClass();
        $user = $userClass->getOneByEmail(!empty($hikaUser->email) ? $hikaUser->email : $hikaUser->user_email);
        if (empty($user->id)) return;

        //We get the order status id
        $orderStatus = acym_loadResult('SELECT `orderstatus_id` FROM #__hikashop_orderstatus WHERE orderstatus_namekey = '.acym_escapeDB($order->order_status));

        //We get the products ids
        $productIds = acym_loadResultArray('SELECT product_id FROM #__hikashop_order_product WHERE order_id = '.intval($order->order_id));

        //We get the categories ids
        acym_arrayToInteger($productIds);
        $categoriesIds = empty($productIds) ? [] : acym_loadResultArray('SELECT category_id FROM #__hikashop_product_category WHERE product_id IN ('.implode(',', $productIds).')');

        $params = [
            'hika_order_status' => $orderStatus,
            'hika_order_product_ids' => $productIds,
            'hika_order_cat_ids' => $categoriesIds,
        ];

        $followupClass = new FollowupClass();
        $followupClass->addFollowupEmailsQueue(self::FOLLOWTRIGGER, $user->id, $params);

        $automationClass = new AutomationClass();

        $automationClass->trigger('hikashopNewOrder', [
            'userId' => $user->id,
            'order' => $order,
        ]);
        $automationClass->trigger('hikashoporder', [
            'userId' => $user->id,
            'order' => $order,
        ]);
    }

    public function onAfterCartSave(&$element)
    {
        // Get Hikashop user from the cart
        if (empty($element->user_id)) {
            $class = hikashop_get('class.cart');
            $old = $class->get($element->cart_id);
            if (empty($old)) return;
            $element->user_id = $old->user_id;
        }

        $hikaUserClass = hikashop_get('class.user');
        $hikaUser = $hikaUserClass->get($element->user_id);
        if (empty($hikaUser)) return;

        $userClass = new UserClass();
        $user = $userClass->getOneByEmail(!empty($hikaUser->email) ? $hikaUser->email : $hikaUser->user_email);
        if (empty($user->id)) return;

        $automationClass = new AutomationClass();
        $automationClass->trigger('hikashopWishlistUpdated', [
            'userId' => $user->id,
            'cart_type' => $element->cart_type,
        ]);
    }

    // Build Hikashop trigger display for the summary
    public function onAcymDeclareSummary_triggers(&$automation)
    {
        if (!empty($automation->triggers['hikashoporder']['status'])) {
            //$return = acym_translation('ACYM_HIKASHOP_ORDER_STATUS_TO').' ';
            $status = implode(', ', $automation->triggers['hikashoporder']['status']);
            $automation->triggers['hikashoporder'] = acym_translationSprintf('ACYM_ORDER_STATUS_CHANGED', 'HikaShop', $status);
        }
        if (isset($automation->triggers['hikashopNewOrder'])) {
            $automation->triggers['hikashopNewOrder'] = acym_translationSprintf('ACYM_X_NEW_ORDER', 'HikaShop');
        }
        if (isset($automation->triggers['hikashopWishlistUpdated'])) {
            $automation->triggers['hikashopWishlistUpdated'] = acym_translationSprintf('ACYM_X_ADD_TO_WISHLIST', 'HikaShop');
        }
    }

    public function getFollowupTriggerBlock(&$blocks)
    {
        $blocks[] = [
            'name' => acym_translation('ACYM_HIKASHOP_PURCHASE'),
            'description' => acym_translation('ACYM_HIKASHOP_FOLLOW_UP_DESC'),
            'icon' => 'acymicon-cart-arrow-down',
            'link' => acym_completeLink('campaigns&task=edit&step=followupCondition&trigger='.self::FOLLOWTRIGGER),
            'level' => 2,
            'alias' => self::FOLLOWTRIGGER,
        ];
    }

    public function getFollowupTriggers(&$triggers)
    {
        $triggers[self::FOLLOWTRIGGER] = acym_translation('ACYM_HIKASHOP_PURCHASE');
    }

    public function getAcymAdditionalConditionFollowup(&$additionalCondition, $trigger, $followup, $statusArray)
    {
        if ($trigger == self::FOLLOWTRIGGER) {
            $orderStatus = acym_loadObjectList('SELECT `orderstatus_id` AS value, `orderstatus_name` AS text FROM #__hikashop_orderstatus ORDER BY `orderstatus_name`');
            $multiselectOrderStatus = acym_selectMultiple(
                $orderStatus,
                'followup[condition][order_status]',
                !empty($followup->condition) && !empty($followup->condition['order_status']) ? $followup->condition['order_status'] : [],
                ['class' => 'acym__select']
            );
            $multiselectOrderStatus = '<span class="cell large-4 medium-6 acym__followup__condition__select__in-text">'.$multiselectOrderStatus.'</span>';
            $statusOrderStatus = '<span class="cell large-1 medium-2 acym__followup__condition__select__in-text">'.acym_select(
                    $statusArray,
                    'followup[condition][order_status_status]',
                    !empty($followup->condition) ? $followup->condition['order_status_status'] : '',
                    'class="acym__select"'
                ).'</span>';
            $additionalCondition['order_status'] = acym_translationSprintf('ACYM_WOOCOMMERCE_ORDER_STATUS_IN', $statusOrderStatus, $multiselectOrderStatus);


            $ajaxParams = json_encode([
                'plugin' => __CLASS__,
                'trigger' => 'searchProduct',
            ]);
            $parametersProductSelect = [
                'class' => 'acym__select acym_select2_ajax',
                'data-params' => acym_escape($ajaxParams),
                'data-selected' => !empty($followup->condition) && !empty($followup->condition['products']) ? implode(',', $followup->condition['products']) : '',
            ];
            $multiselectProducts = acym_selectMultiple(
                [],
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
                ).'</span>';
            $additionalCondition['products'] = acym_translationSprintf('ACYM_WOOCOMMERCE_PRODUCT_IN', $statusProducts, $multiselectProducts);

            $ajaxParams = json_encode([
                'plugin' => __CLASS__,
                'trigger' => 'searchCat',
            ]);
            $parametersCategoriesSelect = [
                'class' => 'acym__select acym_select2_ajax',
                'data-params' => acym_escape($ajaxParams),
                'data-selected' => !empty($followup->condition) && !empty($followup->condition['categories']) ? implode(',', $followup->condition['categories']) : '',
            ];
            $multiselectCategories = acym_selectMultiple(
                [],
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
                ).'</span>';
            $additionalCondition['categories'] = acym_translationSprintf('ACYM_WOOCOMMERCE_CATEGORY_IN', $statusCategories, $multiselectCategories);
        }
    }

    public function getFollowupConditionSummary(&$return, $condition, $trigger, $statusArray)
    {
        if ($trigger !== self::FOLLOWTRIGGER) return;

        if (empty($condition['order_status_status']) || empty($condition['order_status'])) {
            $return[] = acym_translation('ACYM_EVERY_ORDER_STATUS');
        } else {
            acym_arrayToInteger($condition['order_status']);
            $orderStatusToDisplay = acym_loadResultArray(
                'SELECT `orderstatus_name` 
                FROM #__hikashop_orderstatus 
                WHERE `orderstatus_id` IN ('.implode(', ', $condition['order_status']).') 
                ORDER BY `orderstatus_name`'
            );
            $return[] = acym_translationSprintf('ACYM_ORDER_STATUS_X_IN_X', strtolower($statusArray[$condition['order_status_status']]), implode(', ', $orderStatusToDisplay));
        }

        if (empty($condition['products_status']) || empty($condition['products'])) {
            $return[] = acym_translation('ACYM_ANY_PRODUCT');
        } else {
            acym_arrayToInteger($condition['products']);
            $productsToDisplay = acym_loadResultArray(
                'SELECT `product_name` 
                FROM #__hikashop_product 
                WHERE `product_id` IN ('.implode(', ', $condition['products']).') 
                ORDER BY `product_name`'
            );
            $return[] = acym_translationSprintf('ACYM_PRODUCTS_X_IN_X', strtolower($statusArray[$condition['products_status']]), implode(', ', $productsToDisplay));
        }

        if (empty($condition['categories_status']) || empty($condition['categories'])) {
            $return[] = acym_translation('ACYM_EVERY_CATEGORIES');
        } else {
            acym_arrayToInteger($condition['categories']);
            $categoriesToDisplay = acym_loadResultArray(
                'SELECT `category_name` 
                FROM #__hikashop_category 
                WHERE `category_type` = "product" AND `category_id` IN ('.implode(', ', $condition['categories']).') 
                ORDER BY `category_name`'
            );
            $return[] = acym_translationSprintf('ACYM_CATEGORIES_X_IN_X', strtolower($statusArray[$condition['categories_status']]), implode(', ', $categoriesToDisplay));
        }
    }

    public function matchFollowupsConditions(&$followups, $userId, $params)
    {
        foreach ($followups as $key => $followup) {
            if ($followup->trigger != self::FOLLOWTRIGGER) continue;
            //We check the order status
            if (!empty($followup->condition['order_status_status']) && !empty($followup->condition['order_status'])) {
                $status = $followup->condition['order_status_status'] == 'is';
                $inArray = in_array($params['hika_order_status'], $followup->condition['order_status']);
                if (($status && !$inArray) || (!$status && $inArray)) unset($followups[$key]);
            }

            //We check the products
            if (!empty($followup->condition['products_status']) && !empty($followup->condition['products'])) {
                $status = $followup->condition['products_status'] == 'is';
                $matchedProducts = array_intersect($params['hika_order_product_ids'], $followup->condition['products']);
                $inArray = !empty($matchedProducts);
                if (($status && !$inArray) || (!$status && $inArray)) unset($followups[$key]);
            }

            //We check the categories
            if (!empty($followup->condition['categories_status']) && !empty($followup->condition['categories'])) {
                $status = $followup->condition['categories_status'] == 'is';
                $matchedCategories = array_intersect($params['hika_order_cat_ids'], $followup->condition['categories']);
                $inArray = !empty($matchedCategories);
                if (($status && !$inArray) || (!$status && $inArray)) unset($followups[$key]);
            }
        }
    }

    public function onRegacyOptionsDisplay($lists)
    {
        if (!$this->installed) return;

        ?>
		<div class="acym__configuration__subscription acym__content acym_area padding-vertical-1 padding-horizontal-2">
			<div class="cell grid-x acym__configuration__showmore-head">
				<div class="acym__title acym__title__secondary cell auto margin-bottom-0">
                    <?php echo acym_escape(acym_translationSprintf('ACYM_XX_INTEGRATION', $this->pluginDescription->name)); ?>
				</div>
				<div class="cell shrink">
                    <?php echo acym_showMore('acym__configuration__subscription__integration-hikashop'); ?>
				</div>
			</div>

			<div id="acym__configuration__subscription__integration-hikashop" class="grid-x" style="display:none;">
				<div class="cell grid-x grid-margin-x">
                    <?php
                    $subOptionTxt = acym_translationSprintf('ACYM_SUBSCRIBE_OPTION_ON_XX_CHECKOUT', $this->pluginDescription->name).acym_info(
                            'ACYM_SUBSCRIBE_OPTION_ON_XX_CHECKOUT_DESC'
                        );
                    echo acym_switch(
                        'config[hikashop_sub]',
                        $this->config->get('hikashop_sub'),
                        $subOptionTxt,
                        [],
                        'xlarge-3 medium-5 small-9',
                        'auto',
                        '',
                        'acym__config__hikashop_sub'
                    );
                    ?>
				</div>
				<div class="cell grid-x margin-y" id="acym__config__hikashop_sub">
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__hikashop-text">
                            <?php echo acym_translation('ACYM_SUBSCRIBE_CAPTION').acym_info('ACYM_SUBSCRIBE_CAPTION_OPT_DESC'); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
						<input type="text"
							   name="config[hikashop_text]"
							   id="acym__config__hikashop-text"
							   value="<?php echo acym_escape($this->config->get('hikashop_text')); ?>" />
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__hikashop-lists">
                            <?php echo acym_translation('ACYM_DISPLAYED_LISTS').acym_info('ACYM_DISPLAYED_LISTS_DESC'); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        echo acym_selectMultiple(
                            $lists,
                            'config[hikashop_lists]',
                            explode(',', $this->config->get('hikashop_lists', '')),
                            ['class' => 'acym__select', 'id' => 'acym__config__hikashop-lists'],
                            'id',
                            'name'
                        );
                        ?>
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>

					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__hikashop-checkedlists">
                            <?php echo acym_translation('ACYM_LISTS_CHECKED_DEFAULT').acym_info('ACYM_LISTS_CHECKED_DEFAULT_DESC'); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        echo acym_selectMultiple(
                            $lists,
                            'config[hikashop_checkedlists]',
                            explode(',', $this->config->get('hikashop_checkedlists', '')),
                            ['class' => 'acym__select', 'id' => 'acym__config__hikashop-checkedlists'],
                            'id',
                            'name'
                        );
                        ?>
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__hikashop-autolists">
                            <?php echo acym_translation('ACYM_AUTO_SUBSCRIBE_TO').acym_info('ACYM_SUBSCRIBE_OPTION_AUTO_SUBSCRIBE_TO_DESC'); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        echo acym_selectMultiple(
                            $lists,
                            'config[hikashop_autolists]',
                            explode(',', $this->config->get('hikashop_autolists', '')),
                            ['class' => 'acym__select', 'id' => 'acym__config__hikashop-autolists'],
                            'id',
                            'name'
                        );
                        ?>
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__hikashop-regacy-listsposition">
                            <?php echo acym_escape(acym_translation('ACYM_LISTS_POSITION')); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        echo acym_select(
                            acym_getOptionRegacyPosition(),
                            'config[hikashop_regacy_listsposition]',
                            $this->config->get('hikashop_regacy_listsposition', 'password'),
                            'class="acym__select" data-toggle-select="'.acym_escape('{"custom":"#acym__config__hikashop__regacy__custom-list-position"}').'"',
                            'value',
                            'text',
                            'acym__config__hikashop-regacy-listsposition'
                        );
                        ?>
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					<div class="cell grid-x" id="acym__config__hikashop__regacy__custom-list-position">
						<div class="cell xlarge-3 medium-5"></div>
						<div class="cell xlarge-4 medium-7">
							<input type="text"
								   name="config[hikashop_regacy_listspositioncustom]"
								   value="<?php echo acym_escape($this->config->get('hikashop_regacy_listspositioncustom')); ?>" />
						</div>
					</div>
				</div>
			</div>
		</div>
        <?php
    }

    public function onBeforeSaveConfigFields(&$formData)
    {
        if (empty($formData['hikashop_lists'])) $formData['hikashop_lists'] = [];
        if (empty($formData['hikashop_checkedlists'])) $formData['hikashop_checkedlists'] = [];
        if (empty($formData['hikashop_autolists'])) $formData['hikashop_autolists'] = [];
    }

    public function onRegacyAddComponent(&$components)
    {
        $config = acym_config();
        if (!$config->get('hikashop_sub', 0) || acym_isAdmin()) return;

        $components['com_hikashop'] = [
            'view' => ['checkout', 'user'],
            'email' => ['data[register][email]'],
            'password' => ['data[register][password2]'],
            'lengthafter' => 500,
            'containerClass' => 'hkform-group control-group',
            'labelClass' => 'hkc-sm-4 hkcontrol-label',
            'valueClass' => 'controls',
            'baseOption' => 'hikashop',
        ];
    }

    public function onAfterHikashopUserCreate($formData, $listData, $element)
    {
        $config = acym_config();
        if (!$config->get('hikashop_sub', 0) || acym_isAdmin()) return;
        if (empty($element->user_email) || empty($listData)) return;

        // Get existing AcyMailing user or create one
        $userClass = new UserClass();

        $user = $userClass->getOneByEmail($element->user_email);
        if (empty($user)) {
            $user = new stdClass();
            $user->email = $element->user_email;
            $userName = [];
            if (!empty($formData['address']['address_firstname'])) $userName[] = $formData['address']['address_firstname'];
            if (!empty($formData['address']['address_middle_name'])) $userName[] = $formData['address']['address_middle_name'];
            if (!empty($formData['address']['address_lastname'])) $userName[] = $formData['address']['address_lastname'];
            if (!empty($userName)) $user->name = implode(' ', $userName);
            $user->source = 'hikashop';
            $user->id = $userClass->save($user);
        }

        if (empty($user->id)) return;

        // Subscribe the user
        $autoLists = explode(',', $config->get('hikashop_autolists', ''));
        $listsToSubscribe = array_merge($listData, $autoLists);
        if (empty($listsToSubscribe)) return;
        $userClass->subscribe($user->id, $listsToSubscribe);
    }
}
