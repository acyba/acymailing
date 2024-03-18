<?php

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;
use Joomla\CMS\Factory;

trait VirtuemartSubscription
{
    private $baseOption = 'virtuemart';

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
                    <?php echo acym_showMore('acym__configuration__subscription__integration-virtuemart'); ?>
				</div>
			</div>

			<div id="acym__configuration__subscription__integration-virtuemart" class="grid-x" style="display:none;">
				<div class="cell grid-x grid-margin-x">
                    <?php
                    $subOptionTxt = acym_translationSprintf('ACYM_SUBSCRIBE_OPTION_ON_XX_CHECKOUT', $this->pluginDescription->name).acym_info(
                            'ACYM_SUBSCRIBE_OPTION_ON_XX_CHECKOUT_DESC'
                        );
                    echo acym_switch(
                        'config[virtuemart_sub]',
                        $this->config->get('virtuemart_sub'),
                        $subOptionTxt,
                        [],
                        'xlarge-3 medium-5 small-9',
                        'auto',
                        '',
                        'acym__config__virtuemart_sub'
                    );
                    ?>
				</div>
				<div class="cell grid-x margin-y" id="acym__config__virtuemart_sub">
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__virtuemart-text">
                            <?php echo acym_translation('ACYM_SUBSCRIBE_CAPTION').acym_info('ACYM_SUBSCRIBE_CAPTION_OPT_DESC'); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
						<input type="text"
							   name="config[virtuemart_text]"
							   id="acym__config__virtuemart-text"
							   value="<?php echo acym_escape($this->config->get('virtuemart_text')); ?>" />
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__virtuemart-lists">
                            <?php echo acym_translation('ACYM_DISPLAYED_LISTS').acym_info('ACYM_DISPLAYED_LISTS_DESC'); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        echo acym_selectMultiple(
                            $lists,
                            'config[virtuemart_lists]',
                            explode(',', $this->config->get('virtuemart_lists', '')),
                            ['class' => 'acym__select', 'id' => 'acym__config__virtuemart-lists'],
                            'id',
                            'name'
                        );
                        ?>
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>

					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__virtuemart-checkedlists">
                            <?php echo acym_translation('ACYM_LISTS_CHECKED_DEFAULT').acym_info('ACYM_LISTS_CHECKED_DEFAULT_DESC'); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        echo acym_selectMultiple(
                            $lists,
                            'config[virtuemart_checkedlists]',
                            explode(',', $this->config->get('virtuemart_checkedlists', '')),
                            ['class' => 'acym__select', 'id' => 'acym__config__virtuemart-checkedlists'],
                            'id',
                            'name'
                        );
                        ?>
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__virtuemart-autolists">
                            <?php echo acym_translation('ACYM_AUTO_SUBSCRIBE_TO').acym_info('ACYM_SUBSCRIBE_OPTION_AUTO_SUBSCRIBE_TO_DESC'); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        echo acym_selectMultiple(
                            $lists,
                            'config[virtuemart_autolists]',
                            explode(',', $this->config->get('virtuemart_autolists', '')),
                            ['class' => 'acym__select', 'id' => 'acym__config__virtuemart-autolists'],
                            'id',
                            'name'
                        );
                        ?>
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__virtuemart-regacy-listsposition">
                            <?php echo acym_escape(acym_translation('ACYM_LISTS_POSITION')); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        echo acym_select(
                            acym_getOptionRegacyPosition(),
                            'config[virtuemart_regacy_listsposition]',
                            $this->config->get('virtuemart_regacy_listsposition', 'password'),
                            [
                                'class' => 'acym__select',
                                'data-toggle-select' => '{"custom":"#acym__config__virtuemart__regacy__custom-list-position"}',
                            ],
                            'value',
                            'text',
                            'acym__config__virtuemart-regacy-listsposition'
                        );
                        ?>
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					<div class="cell grid-x" id="acym__config__virtuemart__regacy__custom-list-position">
						<div class="cell xlarge-3 medium-5"></div>
						<div class="cell xlarge-4 medium-7">
							<input type="text"
								   name="config[virtuemart_regacy_listspositioncustom]"
								   value="<?php echo acym_escape($this->config->get('virtuemart_regacy_listspositioncustom')); ?>" />
						</div>
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					<div class="cell grid-x grid-margin-x">
                        <?php
                        echo acym_switch(
                            'config[virtuemart_save_user]',
                            $this->config->get('virtuemart_save_user', 1),
                            acym_escape(acym_translation('ACYM_SAVE_USER_IF_NO_SUBSCRIPTION')),
                            [],
                            'xlarge-3 medium-5 small-9',
                            'auto',
                            '',
                            'acym__config__virtuemart_save_user'
                        );
                        ?>
					</div>
				</div>
			</div>
		</div>
        <?php
    }

    public function onBeforeSaveConfigFields(&$formData)
    {
        if (empty($formData['virtuemart_lists'])) $formData['virtuemart_lists'] = [];
        if (empty($formData['virtuemart_checkedlists'])) $formData['virtuemart_checkedlists'] = [];
        if (empty($formData['virtuemart_autolists'])) $formData['virtuemart_autolists'] = [];
    }

    public function onRegacyAddComponent(&$components)
    {
        $config = acym_config();
        if (!$config->get('virtuemart_sub', 0) || acym_isAdmin()) return;

        $components['com_virtuemart'] = [
            'view' => ['user', 'cart', 'shop.registration', 'account.billing', 'checkout.index', 'editaddresscart', 'editaddresscheckout', 'askquestion'],
            'lengthafter' => 500,
            'valueClass' => 'controls',
            'baseOption' => $this->baseOption,
        ];
    }

    public function onRegacyAfterRoute()
    {
        acym_session();

        if (!isset($_SESSION['acym_virtuemart_user_email'])) {
            $email = acym_getVar('string', 'email', '');

            if (!empty($email)) {
                $_SESSION['acym_virtuemart_user_email'] = $email;
            }
        }

        // We are updating the user information from VM
        $option = acym_getVar('string', 'option', '');
        $acySource = acym_getVar('string', 'acy_source', '');
        $task = acym_getVar('cmd', 'task', '');
        if ($option !== 'com_virtuemart' || $acySource !== 'virtuemart registration form' || $task === 'updateCartNoMethods') {
            return;
        }

        $vmPath = ACYM_ROOT.'administrator'.DS.'components'.DS.'com_virtuemart'.DS;
        if (!class_exists('VmConfig') && file_exists($vmPath.'helpers'.DS.'config.php')) {
            include_once $vmPath.'helpers'.DS.'config.php';
        }

        if (!class_exists('VmConfig') || !method_exists('VmConfig', 'loadConfig')) {
            return;
        }

        VmConfig::loadConfig();

        $vmPathPublic = ACYM_ROOT.'components'.DS.'com_virtuemart'.DS;
        if (!class_exists('shopFunctionsF') && file_exists($vmPathPublic.'helpers'.DS.'shopfunctionsf.php')) {
            include_once $vmPathPublic.'helpers'.DS.'shopfunctionsf.php';
            if (!class_exists('shopFunctionsF')) {
                return;
            }
        }

        if (method_exists('shopFunctionsF', 'checkCaptcha')) {
            $captcha = shopFunctionsF::checkCaptcha();
            if ($captcha === true) {
                $this->updateVM();
            }
        } else {
            $this->updateVM();
        }
    }

    private function updateVM()
    {
        $config = acym_config();
        if (!$config->get('virtuemart_sub', 0) || acym_isAdmin()) return;

        $email = $_SESSION['acym_virtuemart_user_email'] ?? null;
        if (empty($email)) {
            $user = Factory::getUser();
            if (!empty($user)) $email = $user->get('email');
        }

        if (empty($email)) {
            unset($_SESSION['acym_virtuemart_user_email']);

            return;
        }

        $autoListsRaw = $config->get('virtuemart_autolists', '');
        $autoLists = explode(',', $autoListsRaw);
        acym_arrayToInteger($autoLists);

        $visibleLists = acym_getVar('string', 'virtuemart_visible_lists');
        $visibleLists = explode(',', $visibleLists);
        acym_arrayToInteger($visibleLists);

        $visibleListsChecked = acym_getVar('array', 'virtuemart_visible_lists_checked', []);
        acym_arrayToInteger($visibleListsChecked);

        // Get existing AcyMailing user or create one
        $userClass = new UserClass();
        $user = $userClass->getOneByEmail($email);
        if (empty($user)) {

            if (!$config->get('virtuemart_save_user', 1) && empty($autoListsRaw) && empty($visibleListsChecked)) {
                unset($_SESSION['acym_virtuemart_user_email']);

                return;
            }

            $user = new stdClass();
            $user->email = $email;

            $userName = acym_getVar('string', 'name', '');
            if (empty($userName)) {
                $userNameArray = [];
                $userNameArray[] = acym_getVar('string', 'first_name', '');
                $userNameArray[] = acym_getVar('string', 'middle_name', '');
                $userNameArray[] = acym_getVar('string', 'last_name', '');
                $userName = trim(implode(' ', $userNameArray));
            }
            if (!empty($userName)) $user->name = $userName;

            $user->source = 'virtuemart';
            $user->id = $userClass->save($user);
        }

        if (empty($user->id)) {
            unset($_SESSION['acym_virtuemart_user_email']);

            return;
        }

        // Handle user subscription
        $currentSubscription = $userClass->getSubscriptionStatus($user->id);

        $listsClass = new ListClass();
        $allLists = $listsClass->getAll();

        // Handle the unsubscription
        if (!empty($visibleLists)) {
            $currentlySubscribedLists = [];
            foreach ($currentSubscription as $oneSubscription) {
                if ($oneSubscription->status == 1) $currentlySubscribedLists[] = $oneSubscription->list_id;
            }
            $unsubscribeLists = array_intersect($currentlySubscribedLists, array_diff($visibleLists, $visibleListsChecked));
            $userClass->unsubscribe($user->id, $unsubscribeLists);
        }

        // Handle the subscription
        $listsToSubscribe = [];
        foreach ($allLists as $oneList) {
            if (!$oneList->active) continue;
            if (!empty($currentSubscription[$oneList->id]) && $currentSubscription[$oneList->id]->status == 1) continue;

            if (in_array($oneList->id, $visibleListsChecked) || (in_array($oneList->id, $autoLists) && !in_array(
                        $oneList->id,
                        $visibleLists
                    ) && empty($currentSubscription[$oneList->id]))) {
                $listsToSubscribe[] = $oneList->id;
            }
        }

        if (!empty($listsToSubscribe)) $userClass->subscribe($user->id, $listsToSubscribe);
        unset($_SESSION['acym_virtuemart_user_email']);
    }

    /**
     * Only for connected users that are already subscribed to a list when regacy is added to a VM form
     * VirtueMart automatically submits the form using ajax when a field is changed, but we don't want to subscribe/unsubscribe the user to/from the lists directly when he checks
     * the checkbox: it would send the confirmation/welcome/goodbye emails
     *
     * @param array $currentSubscription
     */
    public function onRegacyPrepareCheckedLists(&$currentSubscription)
    {
        // Task used by VirtueMart when the form is refreshed using ajax
        $task = acym_getVar('cmd', 'task', '');
        if ($task != 'updateCartNoMethods') return;

        // If the user unchecks the checkbox, it isn't saved with the ajax call, but since the form is refreshed it automatically re-checks the checkbox

        $visibleLists = acym_getVar('string', 'virtuemart_visible_lists');
        $visibleLists = explode(',', $visibleLists);
        acym_arrayToInteger($visibleLists);
        if (empty($visibleLists)) return;

        $visibleListsChecked = acym_getVar('array', 'virtuemart_visible_lists_checked', []);
        acym_arrayToInteger($visibleListsChecked);

        // apply the lists status in $currentSubscription based on what the user checked, to not change the checkbox on form refresh
        foreach ($visibleLists as $oneListId) {
            if (empty($currentSubscription[$oneListId])) {
                $currentSubscription[$oneListId] = (object)[
                    'status' => in_array($oneListId, $visibleListsChecked) ? '1' : '0',
                    'list_id' => $oneListId,
                ];
            } else {
                $currentSubscription[$oneListId]->status = in_array($oneListId, $visibleListsChecked) ? '1' : '0';
            }
        }
    }
}
