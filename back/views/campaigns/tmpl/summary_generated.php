<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" class="acym__form__campaign__edit">
	<div class="cell grid-x align-center" id="acym__campaign__summary__generated">
		<div class="cell medium-7 grid-x acym__content">
			<h1 class="cell text-center acym__campaign__summary__generated__title "><?php echo $data['mail']->name; ?></h1>
			<div class="cell text-center acym__campaign__summary__generated__sub__title margin-bottom-2 margin-top-1">
                <?php
                if (!empty($data['parent_campaign']->id)) {
                    echo acym_translationSprintf(
                        'ACYM_CAMPAIGN_GENERATED_BY',
                        '<a href="'.acym_completeLink('campaigns&task=edit&step=editEmail&id='.$data['parent_campaign']->id).'">'.$data['parent_mail']->name.'</a>'
                    );
                } else {
                    echo acym_translation('ACYM_AUTO_CAMPAIGN_DELETED');
                }
                ?>
			</div>
            <?php if (!$data['campaign']->sent && !$data['campaign']->waiting_confirmation) { ?>
				<div class="cell grid-x acym__campaign__summary__generated__disabled acym_vcenter margin-bottom-2">
					<div class="cell text-center acym__campaign__summary__generated__disabled__text"><?php echo acym_translation('ACYM_CAMPAIGN_HAS_BEEN_DISABLED'); ?></div>
				</div>
            <?php } ?>
			<div class="cell grid-x acym__campaign__summary__generated__list acym_vcenter margin-bottom-2">
				<div class="cell shrink acym__campaign__summary__generated__list__title margin-right-1"><?php echo acym_translation('ACYM_LISTS_SUMMARY'); ?></div>
				<div class="cell auto grid-x grid-margin-x grid-margin-y">
                    <?php foreach ($data['lists'] as $list) { ?>
						<div class="cell shrink acym__campaign__summary__generated__list__tag"><i class="acymicon-circle"
																								  style="color: <?php echo $list->color; ?>;"></i><?php echo $list->name; ?></div>
                    <?php } ?>
				</div>
			</div>
            <?php
            if ($data['multilingual']) {
                include acym_getView('campaigns', 'summary_languages', true);
            }
            ?>
			<div class="cell grid-x acym__campaign__summary__generated__mail">
				<div class="cell grid-x acym__campaign__summary__generated__mail__one">
					<div class="cell grid-x acym_vcenter">
						<div class="cell shrink acym__campaign__summary__generated__mail__one__subject">
							<span class="acym__campaign__summary__generated__mail__one__bold"><?php echo acym_translation('ACYM_EMAIL_SUBJECT'); ?></span>
							<span class="acym__campaign__summary__email__information-subject"><?php echo $data['mail']->subject; ?></span>
						</div>
						<div class="cell auto acym__campaign__summary__generated__mail__one__preview margin-left-1">
                            <?php echo empty($data['mail']->preheader) ? '' : $data['mail']->preheader; ?>
						</div>
					</div>
					<div class="cell grid-x acym__campaign__summary__generated__mail__one__info margin-top-2">
						<div class="cell auto text-left">
							<span class="acym__campaign__summary__generated__mail__one__bold"><?php echo acym_translation('ACYM_FROM_SUMMARY'); ?></span>
                            <?php echo $data['mail']->from_name.', <a href="mailto:'.$data['mail']->from_email.'">'.$data['mail']->from_email.'</a>'; ?>
						</div>
						<div class="cell auto text-right">
							<span class="acym__campaign__summary__generated__mail__one__bold"><?php echo acym_translation('ACYM_REPLYTO_SUMMARY'); ?></span>
                            <?php echo $data['mail']->reply_to_name.', <a href="mailto:'.$data['mail']->reply_to_email.'">'.$data['mail']->reply_to_email.'</a>'; ?>
						</div>
					</div>
				</div>
				<div class="cell grid-x acym__campaign__summary__generated__mail__one">
					<input type="hidden" class="acym__hidden__mail__content" value="<?php echo acym_escape(acym_absoluteURL($data['mail']->body)); ?>">
					<div style="display: none" class="acym__hidden__mail__stylesheet"><?php echo $data['mail']->stylesheet; ?></div>
					<div class="cell grid-x acym__campaign__summary__generated__mail__preview">
						<i class="acymicon-sort acym__campaign__summary__generated__mail__toogle__preview"></i>
						<div id="acym__wysid__email__preview" class="acym__email__preview grid-x cell margin-top-1"></div>
					</div>
				</div>
			</div>
			<div class="cell text-right grid-margin-y acym__campaign__summary__generated__action grid-margin-x margin-top-2 grid-x">
				<div class="cell medium-shrink text-left">
                    <?php echo acym_backToListing(); ?>
				</div>
				<div class="cell medium-auto align-right grid-margin-x acym_vcenter">
					<div class="cell medium-auto"></div>
                    <?php if ($data['campaign']->sent) { ?>
						<p class="cell text-center acym__campaign__summary__generated__action__text"><?php echo acym_translationSprintf(
                                'ACYM_CAMPAIGN_HAS_BEEN_SENT_ON_X',
                                $data['campaign']->sending_date
                            ); ?></p>
                    <?php } elseif ($data['campaign']->waiting_confirmation) { ?>
						<button type="button" class="cell shrink button acym__button__cancel acy_button_submit" data-task="disableGeneratedCampaign">
                            <?php echo acym_translation('ACYM_DISABLE'); ?> <i class="acymicon-lock"></i>
						</button>
						<button type="button" class="cell shrink button button-send acy_button_submit" data-task="addQueue"><?php echo acym_translation('ACYM_SEND'); ?>
							<i class="acymicon-paper-plane"></i></button>
                    <?php } else { ?>
						<button type="button" class="cell shrink button acy_button_submit" data-task="enableGeneratedCampaign"><?php echo acym_translation('ACYM_ENABLE'); ?> <i
									class="acymicon-unlock"></i></button>
						<button type="button" class="cell shrink disabled button button-send" disabled><?php echo acym_translation('ACYM_SEND'); ?>
							<i class="acymicon-paper-plane"></i></button>
                    <?php } ?>
				</div>
			</div>
		</div>
	</div>
	<input type="hidden" value="<?php echo intval($data['campaign']->id); ?>" name="id" />
    <?php acym_formOptions(true, 'edit', 'summary_generated'); ?>
</form>
