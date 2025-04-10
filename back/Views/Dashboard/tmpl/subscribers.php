<div class="acym__content cell">
	<div class="cell acym__title acym__dashboard__title"><?php echo acym_translation('ACYM_SUBSCRIBERS'); ?></div>
	<span class="separator"></span>

	<div style="display: flex;">
		<div class="subscibersWrapperPart horizontalSeparator gap-1">
			<div style="align-items: flex-end;" class="listItemWrapper">
				<p class="acym__color__light-blue"><?php echo $data['subscribers']['all']; ?> </p>
				<p class="acym__color__light-blue"><?php echo $data['subscribers']['active']; ?> </p>
				<p class="acym__color__light-blue"><?php echo $data['subscribers']['inactive']; ?> </p>
				<p class="acym__color__light-blue"><?php echo $data['subscribers']['unconfirmed']; ?></p>
			</div>
			<div class="listItemWrapper">
				<p><?php echo acym_translation('ACYM_USERS'); ?> </p>
				<p><?php echo acym_translation('ACYM_SUBSCRIBED'); ?> </p>
				<p><?php echo acym_translation('ACYM_INACTIVE'); ?> </p>
				<p><?php echo acym_translation('ACYM_NOT_CONFIRMED'); ?></p>
			</div>

		</div>
		<div class="subscibersWrapperPart listItemWrapper">
			<p class="acym__color__light-blue"> <?php echo $data['newSubscribers'] ?> </p>
			<p><?php echo acym_translation('ACYM_NEW_RECENT_SUB'); ?></p>
		</div>
	</div>

</div>
