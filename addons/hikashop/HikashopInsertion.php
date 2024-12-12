<?php

use AcyMailing\Helpers\TabHelper;

trait HikashopInsertion
{
    private $hikaConfig;
    private $currencyClass;
    private $imageHelper;
    private $productClass;
    private $translationHelper;

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
                            '.acym_select(
                        $currencies,
                        'currencyhikashop_coupon',
                        $flatCurrency,
                        [
                            'onchange' => 'updateDynamichikashop_coupon();',
                            'style' => 'width: 80px;',
                        ]
                    ),
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
        $link = $this->finalizeLink($link, $tag);
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
        $format->link = empty($tag->clickable) && empty($tag->clickableimg) ? '' : $link;
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
}
