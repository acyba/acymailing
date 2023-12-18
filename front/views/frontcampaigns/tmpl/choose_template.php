<div class="grid-x grid-margin-x margin-top-3">
    <?php
    $campaignType = empty($data['campaign_type']) ? '' : $data['campaign_type'];
    if (empty($data['allMails']) && empty($data['search']) && empty($data['tag']) && empty($data['status'])) { ?>
		<div class="grid-x cell text-center">
			<div class="medium-4"></div>
			<div class="medium-4 small-12 cell">
				<a
						href="<?php echo acym_completeLink(
                            acym_getVar('cmd', 'ctrl').'&task=edit&step=editEmail&from=-1&type_editor=acyEditor&campaignId='.acym_escape(
                                $data['campaignID']
                            ).'&campaign_type='.$campaignType
                        ); ?>"
						class="button expanded"
						id="acym__templates__choose__create__empty">
                    <?php echo acym_translation('ACYM_CREATE_EMPTY_TEMPLATE'); ?>
				</a>
			</div>
			<div class="medium-4"></div>
		</div>
    <?php } else { ?>
		<div class="cell grid-x margin-bottom-2 grid-margin-x">
			<div class="medium-auto cell">
                <?php echo acym_filterSearch($data['search'], 'mailchoose_search', 'ACYM_SEARCH'); ?>
			</div>
			<div class="grid-x medium-shrink text-center cell acym__templates__choose__type-templates">
				<a
						href="<?php echo acym_completeLink(
                            acym_getVar('cmd', 'ctrl').'&task=edit&step=editEmail&from=-1&type_editor=acyEditor&campaignId='.intval($data['campaignID']).'&campaign_type='.$campaignType
                        ); ?>"
						class="button"
						id="acym__templates__choose__create__empty">
                    <?php echo acym_translation('ACYM_START_FROM_EMPTY_TEMPLATE'); ?>
				</a>
			</div>
		</div>
        <?php if (empty($data['allMails'])) { ?>
			<h1 class="cell acym__listing__empty__search__title text-center"><?php echo acym_translation('ACYM_NO_RESULTS_FOUND'); ?></h1>
        <?php } else { ?>
			<div class="grid-x grid-padding-x grid-padding-y grid-margin-x grid-margin-y medium-up-3 small-up-1 cell">
                <?php
                foreach ($data['allMails'] as $oneTemplate) {
                    ?>
					<div class="cell grid-x acym__templates__oneTpl acym__listing__block">
						<input type="hidden"
							   class="acym__templates__oneTpl__choose"
							   value="<?php echo acym_completeLink(
                                   acym_getVar('cmd', 'ctrl').'&task=edit&step=editEmail&from='.$oneTemplate->id.'&campaignId='.intval($data['campaignID']).'&campaign_type='.$campaignType
                               ); ?>" />
						<div class="cell acym__templates__pic text-center">
							<img src="<?php echo acym_getMailThumbnail($oneTemplate->thumbnail); ?>" alt="<?php echo acym_escape($oneTemplate->name); ?>" />
                            <?php
                            if ($oneTemplate->drag_editor) {
                                echo '<div class="acym__templates__choose__ribbon acyeditor">'.acym_translation('ACYM_DD_EDITOR').'</div>';
                            } else {
                                echo '<div class="acym__templates__choose__ribbon htmleditor">'.acym_translation('ACYM_HTML_EDITOR').'</div>';
                            }
                            ?>
						</div>
						<div class="cell grid-x acym__templates__footer text-center">
							<div class="cell acym__templates__footer__title" title="<?php echo acym_escape($oneTemplate->name); ?>">
                                <?php
                                if (strlen($oneTemplate->name) > 55) {
                                    $oneTemplate->name = substr($oneTemplate->name, 0, 50).'...';
                                }
                                echo acym_escape($oneTemplate->name);
                                ?>
							</div>
							<div class="cell"><?php echo acym_date($oneTemplate->creation_date, 'ACYM_DATE_FORMAT_LC3'); ?></div>
						</div>
					</div>
                <?php } ?>
			</div>
            <?php echo $data['pagination']->display('mailchoose'); ?>
        <?php } ?>
    <?php } ?>
</div>
<?php
