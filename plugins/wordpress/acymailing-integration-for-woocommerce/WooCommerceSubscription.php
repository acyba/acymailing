<?php

use AcyMailing\Classes\UserClass;

trait WooCommerceSubscription
{
    public function onRegacyOptionsDisplay($lists)
    {
        if (!$this->installed) return;
        ?>
		<div class="acym__configuration__subscription acym__content acym_area padding-vertical-1 padding-horizontal-2">
			<div class="cell grid-x acym__configuration__showmore-head">
				<div class="acym__title acym__title__secondary cell auto margin-bottom-0">
                    <?php echo esc_attr(acym_translationSprintf('ACYM_XX_INTEGRATION', 'WooCommerce')); ?>
				</div>
				<div class="cell shrink">
                    <?php
                    echo wp_kses(
                        acym_showMore('acym__configuration__subscription__integration-woocommerce'),
                        [
                            'div' => ['class' => [], 'data-toggle-showmore' => []],
                            'label' => [],
                            'i' => ['class' => []],
                        ]
                    );
                    ?>
				</div>
			</div>

			<div id="acym__configuration__subscription__integration-woocommerce" class="grid-x margin-y" style="display:none;">
				<div class="cell grid-x grid-margin-x">
                    <?php
                    $subOptionTxt = acym_translationSprintf('ACYM_SUBSCRIBE_OPTION_ON_XX_CHECKOUT', 'WooCommerce');
                    $subOptionTxt .= acym_info(['textShownInTooltip' => 'ACYM_SUBSCRIBE_OPTION_ON_XX_CHECKOUT_DESC']);

                    echo wp_kses(
                        acym_switch(
                            'config[woocommerce_sub]',
                            $this->config->get('woocommerce_sub'),
                            $subOptionTxt,
                            [],
                            'xlarge-3 medium-5 small-9',
                            'auto',
                            '',
                            'acym__config__woocommerce_sub'
                        ),
                        [
                            'div' => ['class' => [], 'data-toggle-showmore' => []],
                            'label' => ['for' => [], 'class' => [], 'data-acym-tooltip' => []],
                            'i' => ['class' => []],
                            'input' => [
                                'type' => [],
                                'name' => [],
                                'id' => [],
                                'value' => [],
                                'checked' => [],
                                'disabled' => [],
                                'class' => [],
                                'data-switch' => [],
                                'data-toggle-switch' => [],
                                'data-toggle-switch-open' => [],
                                'v-model' => [],
                            ],
                            'span' => ['class' => [], 'aria-hidden' => []],
                        ]
                    );
                    ?>
				</div>
				<div class="cell grid-x margin-y" id="acym__config__woocommerce_sub">
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__woocommerce-text">
                            <?php
                            echo wp_kses(
                                acym_translation('ACYM_SUBSCRIBE_CAPTION').acym_info(['textShownInTooltip' => 'ACYM_SUBSCRIBE_CAPTION_OPT_DESC']),
                                [
                                    'span' => ['class' => []],
                                    'a' => ['href' => [], 'title' => [], 'target' => [], 'class' => []],
                                ]
                            );
                            ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
						<input type="text"
							   name="config[woocommerce_text]"
							   id="acym__config__woocommerce-text"
							   value="<?php echo esc_attr($this->config->get('woocommerce_text')); ?>" />
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__woocommerce-autolists">
                            <?php
                            echo wp_kses(
                                acym_translation('ACYM_AUTO_SUBSCRIBE_TO').acym_info(['textShownInTooltip' => 'ACYM_SUBSCRIBE_OPTION_AUTO_SUBSCRIBE_TO_DESC']),
                                [
                                    'span' => ['class' => []],
                                    'a' => ['href' => [], 'title' => [], 'target' => [], 'class' => []],
                                ]
                            );
                            ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        echo wp_kses(
                            acym_selectMultiple(
                                $lists,
                                'config[woocommerce_autolists]',
                                explode(',', $this->config->get('woocommerce_autolists', '')),
                                ['class' => 'acym__select', 'id' => 'acym__config__woocommerce-autolists'],
                                'id',
                                'name'
                            ),
                            [
                                'select' => ['name' => [], 'id' => [], 'class' => [], 'multiple' => []],
                                'option' => ['value' => [], 'selected' => [], 'disabled' => [], 'data-hidden' => []],
                                'optgroup' => ['label' => []],
                            ]
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


        $userName = [];
        if (!empty($posted_data['billing_first_name'])) $userName[] = $posted_data['billing_first_name'];
        if (!empty($posted_data['billing_last_name'])) $userName[] = $posted_data['billing_last_name'];
        $name = implode(' ', $userName);

        $this->subscribeFromCheckout($posted_data['billing_email'], $name);
    }

    /**
     * Subscribe user when the WooCommerce gutenberg checkout is processed
     *
     * @param $order : WooCommerce order
     */
    public function subscribeUserOnCheckoutWCApi($order, $request)
    {
        $config = acym_config();
        if (!$config->get('woocommerce_sub', 0)) return;

        $body = json_decode($request->get_body(), true);
        if (empty($body['billing_address']['email']) || empty($body['extensions'][self::WC_ACY_SUBSCRIBE_KEY]['is-subscribing'])) {
            return;
        }

        $userName = [];
        if (!empty($body['billing_address']['first_name'])) $userName[] = $body['billing_address']['first_name'];
        if (!empty($body['billing_address']['last_name'])) $userName[] = $body['billing_address']['last_name'];
        $name = implode(' ', $userName);

        $this->subscribeFromCheckout($body['billing_address']['email'], $name);
    }

    private function subscribeFromCheckout($email, $name = '')
    {
        $config = acym_config();
        $userClass = new UserClass();
        $user = $userClass->getOneByEmail($email);
        if (empty($user)) {
            $user = new stdClass();
            $user->email = $email;
            if (!empty($name)) {
                $user->name = $name;
            }
            $user->source = 'woocommerce';
            $user->id = $userClass->save($user);
        }

        if (empty($user->id)) return;

        // Subscribe the user
        $listsToSubscribe = $config->get('woocommerce_autolists', '');
        if (empty($listsToSubscribe)) return;
        $hiddenLists = explode(',', $listsToSubscribe);
        $userClass->subscribe([$user->id], $hiddenLists);
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
}
