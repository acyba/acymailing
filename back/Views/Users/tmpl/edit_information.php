<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<div class="cell acym__content acym__user__edit__custom__fields margin-y">
    <?php
    if (!empty($data['allFields'])) {
        foreach ($data['allFields'] as $field) {
            if (intval($field->active) === 0) continue;
            echo $field->html;
        }
    }
    ?>
	<div class="cell grid-x margin-top-1">
        <?php acym_switch(['name' => 'user[active]', 'value' => $data['user-information']->active, 'label' => acym_translation('ACYM_ACTIVE')]); ?>
	</div>
    <?php if ($this->config->get('require_confirmation', '0') == '1') { ?>
		<div class="cell grid-x">
            <?php acym_switch(['name' => 'user[confirmed]', 'value' => $data['user-information']->confirmed, 'label' => acym_translation('ACYM_CONFIRMED')]); ?>
		</div>
    <?php } ?>
	<div class="cell grid-x">
        <?php
        acym_switch([
            'name' => 'user[tracking]',
            'value' => $data['user-information']->tracking,
            'label' => acym_translation('ACYM_TRACK_THIS_SUBSCRIBER'),
            'tip' => ['textShownInTooltip' => 'ACYM_TRACK_THIS_SUBSCRIBER_DESC'],
        ]); ?>
	</div>
    <?php if (!empty($data['user-information']->source)) { ?>
		<div class="cell grid-x margin-top-1">
			<div class="cell medium-6 small-12">
                <?php
                echo acym_escapeHtml(acym_translation('ACYM_DATE_CREATED')).' : <b>';
                echo acym_escapeHtml(
                    acym_date(
                        empty($data['user-information']->id) ? time() : $data['user-information']->creation_date,
                        acym_getDateTimeFormat('ACYM_DATE_FORMAT_LC3')
                    )
                );
                echo '</b>';
                ?>
			</div>
			<div class="cell medium-6 small-12">
                <?php echo acym_escapeHtml(acym_translation('ACYM_SOURCE')); ?> : <b><?php echo acym_escapeHtml($data['user-information']->source); ?></b>
			</div>
		</div>
    <?php } else { ?>
		<div class="cell margin-top-1">
            <?php echo acym_escapeHtml(acym_translation('ACYM_DATE_CREATED')); ?> : <b>
                <?php
                echo acym_escapeHtml(
                    acym_date(
                        empty($data['user-information']->id) ? time() : $data['user-information']->creation_date,
                        acym_getDateTimeFormat('ACYM_DATE_FORMAT_LC3')
                    )
                );
                ?>
			</b>
		</div>
    <?php } ?>
</div>
