<?php

trait ContactRegistration
{
    public function onRegacyOptionsDisplay($lists)
    {
        if (!$this->installed) {
            return;
        }

        ?>
		<div class="acym__configuration__subscription acym__content acym_area padding-vertical-1 padding-horizontal-2">
			<div class="cell grid-x acym__configuration__showmore-head">
				<div class="acym__title acym__title__secondary cell auto margin-bottom-0">
                    <?php echo acym_escapeHtml(acym_translationSprintf('ACYM_XX_CONTACT_INTEGRATION', ACYM_CMS_TITLE)); ?>
				</div>
				<div class="cell shrink">
                    <?php acym_showMore('acym__configuration__subscription__contact__integration-cms'); ?>
				</div>
			</div>

            <?php
            if (!acym_isPluginActive('acymtriggers')) {
                acym_display(acym_translationSprintf('ACYM_NEEDS_SYSTEM_PLUGIN', 'AcyMailing - Joomla integration'), 'error', false);
            }
            ?>

			<div id="acym__configuration__subscription__contact__integration-cms" style="display:none;">
				<div class="grid-x margin-y">
					<div class="cell grid-x grid-margin-x">
                        <?php
                        acym_switch([
                            'name' => 'config[regacy_contact]',
                            'value' => $this->config->get('regacy_contact', '0'),
                            'label' => acym_translation('ACYM_CREATE_SUBSCRIBER_FOR_CONTACT'),
                            'labelClass' => 'xlarge-3 medium-5 small-9',
                            'toggle' => 'acym__config__regacy__contact',
                        ]);
                        ?>
					</div>
					<div class="cell grid-x margin-y" id="acym__config__regacy__contact">
						<div class="cell grid-x grid-margin-x">
                            <?php
                            acym_switch([
                                'name' => 'config[regacy_contact_forceconf]',
                                'value' => $this->config->get('regacy_contact_forceconf', '0'),
                                'label' => acym_translation('ACYM_SEND_CONF_CONTACT_REGACY'),
                                'labelClass' => 'xlarge-3 medium-5 small-9',
                                'toggle' => 'regforceconf_contact_config',
                            ]);
                            ?>
						</div>
						<div class="cell grid-x grid-margin-x">
                            <?php
                            acym_switch([
                                'name' => 'config[regacy_contact_delete]',
                                'value' => $this->config->get('regacy_contact_delete', '0'),
                                'label' => acym_translation('ACYM_DELETE_SUBSCRIBER_ON_CONTACT_DELETE'),
                                'labelClass' => 'xlarge-3 medium-5 small-9',
                                'toggle' => 'regdelete_contact_config',
                            ]);
                            ?>
						</div>

                        <?php acym_trigger('onRegacyUseExternalPlugins', []); ?>

						<div class="cell xlarge-3 medium-5">
							<label for="acym__config__regacy__contact-autolists">
                                <?php echo acym_escapeHtml(acym_translation('ACYM_AUTO_SUBSCRIBE_TO'));
                                acym_info(['textShownInTooltip' => 'ACYM_AUTO_SUBSCRIBE_TO_DESC']); ?>
							</label>
						</div>
						<div class="cell xlarge-4 medium-7">
                            <?php
                            acym_selectMultiple(
                                $lists,
                                'config[regacy_contact_autolists]',
                                explode(',', $this->config->get('regacy_contact_autolists')),
                                ['class' => 'acym__select', 'id' => 'acym__config__regacy__contact-autolists'],
                                'id',
                                'name',
                                true
                            );
                            ?>
						</div>
						<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					</div>
				</div>
			</div>
		</div>
        <?php
    }
}
