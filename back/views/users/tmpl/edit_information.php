<div class="cell acym__content acym__user__edit__custom__fields margin-y">
    <?php
    if (!empty($data['allFields'])) {
        foreach ($data['allFields'] as $field) {
            if ($field->active === '0') continue;
            echo $field->html;
        }
    }
    ?>
	<div class="cell grid-x margin-top-1">
        <?php echo acym_switch('user[active]', $data['user-information']->active, acym_translation('ACYM_ACTIVE'), []); ?>
	</div>
	<div class="cell grid-x">
        <?php echo acym_switch('user[confirmed]', $data['user-information']->confirmed, acym_translation('ACYM_CONFIRMED'), []); ?>
	</div>
	<div class="cell grid-x">
        <?php
        $label = acym_translation('ACYM_TRACK_THIS_SUBSCRIBER');
        $label .= acym_info('ACYM_TRACK_THIS_SUBSCRIBER_DESC');
        echo acym_switch('user[tracking]', $data['user-information']->tracking, $label, []); ?>
	</div>
    <?php if (!empty($data['user-information']->source)) { ?>
		<div class="cell grid-x margin-top-1">
			<div class="cell medium-6 small-12">
                <?php echo acym_translation('ACYM_DATE_CREATED'); ?> : <b><?php echo !empty($data['user-information']->id) ? acym_date(
                        $data['user-information']->creation_date,
                        'M. j, Y'
                    ) : acym_date(time(), 'M. j, Y'); ?></b>
			</div>
			<div class="cell medium-6 small-12">
                <?php echo acym_translation('ACYM_SOURCE'); ?> : <b><?php echo acym_escape($data['user-information']->source); ?></b>
			</div>
		</div>
    <?php } else { ?>
		<div class="cell margin-top-1">
            <?php echo acym_translation('ACYM_DATE_CREATED'); ?> : <b><?php echo !empty($data['user-information']->id) ? acym_date(
                    $data['user-information']->creation_date,
                    'M. j, Y'
                ) : acym_date(time(), 'M. j, Y'); ?></b>
		</div>
    <?php } ?>
</div>
