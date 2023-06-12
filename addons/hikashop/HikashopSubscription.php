<?php

use AcyMailing\Classes\UserClass;

trait HikashopSubscription
{
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
                            [
                                'class' => 'acym__select',
                                'data-toggle-select' => '{"custom":"#acym__config__hikashop__regacy__custom-list-position"}',
                            ],
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
        $autoLists = explode(',', $config->get('hikashop_autolists', ''));
        $listsToSubscribe = array_merge($listData, $autoLists);
        if (!$config->get('hikashop_sub', 0) || acym_isAdmin()) return;
        if (empty($element->user_email) || empty($listsToSubscribe)) return;

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
        $userClass->subscribe($user->id, $listsToSubscribe);
    }
}
