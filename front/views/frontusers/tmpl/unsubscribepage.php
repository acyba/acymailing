<form action="<?php echo acym_frontendLink('frontusers'); ?>"
	  name="unsubscribepage"
	  onsubmit="this.querySelector('#acym__save');"
	  class="acym_front_page acym_front_page__unsubscribe">
	<fieldset>
		<legend><?php echo acym_translation('ACYM_YOUR_NEWSLETTER_SUBSCRIPTIONS'); ?></legend>
		<h2 class="margin-top-2 acym_front_page__unsubscribe__title"><?php echo acym_translation('ACYM_HERE_LISTS_YOU_ARE_SUBSCRIBED_TO'); ?></h2>
		<div class="acym_front_page__unsubscribe__lists__container">
            <?php
            if (empty($data['subscriptions'])) {
                echo acym_translation('ACYM_NO_DATA_TO_DISPLAY');
                echo '</div>';
            } else {
            echo '<ul>';
            foreach ($data['subscriptions'] as $list) {
                if (empty($list->visible)) continue;
                echo '<li><input style="display: inline-block" id="list__'.$list->id.'" type="checkbox" name="lists['.$list->id.']" '.(empty($list->status) ? '' : 'checked').'><label style="display: inline-block" for="list__'.$list->id.'">'.$list->name.'</label></li>';
            }
            echo '</ul>';
            ?>
		</div>
		<div class="acym_front_page__unsubscribe__lists__actions">
			<h5 class="margin-top-1 margin-bottom-1 acym_front_page__unsubscribe__sub-title"><?php echo acym_translation('ACYM_CLICK_HERE_TO_MAKE_CHANGES'); ?></h5>
			<button type="button" class="button margin-right-1" id="acym__save" onclick="return acymSubmitForm('saveSubscriptions');"><?php echo acym_translation(
                    'ACYM_UPDATE'
                ); ?></button>
			<button type="button" class="button button-secondary" id="acym__unsub__all" onclick="return acymSubmitForm('unsubscribeAll');"><?php echo acym_translation(
                    'ACYM_UNSUBSCRIBE_ALL'
                ); ?></button>
		</div>
        <?php
        } ?>
	</fieldset>
    <?php acym_formOptions(); ?>
	<input type="hidden" name="user_id" value="<?php echo $data['user']->id; ?>">
	<input type="hidden" name="action" value="acymailing_frontrouter">
</form>
<?php if ('wordpress' == ACYM_CMS) exit; ?>
