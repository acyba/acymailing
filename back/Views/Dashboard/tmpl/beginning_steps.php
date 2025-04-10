<div class="acym__content cell">
	<div class="cell acym__title acym__dashboard__title"><?php echo acym_translation('ACYM_BEGINNER_STEPS'); ?></div>
	<ul class="beginner-stepper">
		<li class="acym_vcenter gap-1 padding-left-1 padding-vertical-1">
			<i class="small-4 <?php echo $data['listCreated'] ? 'acymicon-circle-check-o' : 'acymicon-circle-o'; ?>"></i>
			<a class="acym__dashboard__card__link" href="<?php echo acym_completeLink('lists&task=settings'); ?>">
                <?php echo acym_translation('ACYM_CREATE_A_LIST'); ?>
			</a>
		</li>
		<span class="separator acym__dashboard__light__separator"></span>

		<li class="acym_vcenter gap-1 padding-left-1 padding-vertical-1">
			<i class="small-4 <?php echo $data['totalSubscribers'] > 1 ? 'acymicon-circle-check-o' : 'acymicon-circle-o'; ?>"></i>
			<a class="acym__dashboard__card__link" href="<?php echo acym_completeLink('users&task=edit'); ?>">
                <?php echo acym_translation('ACYM_FIRST_SUBSCRIBER'); ?>
			</a>
		</li>
		<span class="separator acym__dashboard__light__separator"></span>

		<li class="acym_vcenter gap-1 padding-left-1 padding-vertical-1">
			<i class="small-4 <?php echo $data['campaignCreated'] ? 'acymicon-circle-check-o' : 'acymicon-circle-o'; ?>"></i>
			<a class="acym__dashboard__card__link" href="<?php echo acym_completeLink('campaigns&task=newEmail'); ?>">
                <?php echo acym_translation('ACYM_FIRST_CAMPAIGN'); ?>
			</a>
		</li>
		<span class="separator acym__dashboard__light__separator"></span>

		<li class="acym_vcenter gap-1 padding-left-1 padding-vertical-1">
			<i class="small-4 <?php echo $data['campaignSent'] ? 'acymicon-circle-check-o' : 'acymicon-circle-o'; ?>"></i>
			<a class="acym__dashboard__card__link" href="<?php echo acym_completeLink('queue'); ?>">
                <?php echo acym_translation('ACYM_SEND_FIRST_CAMPAIGN'); ?>
			</a>
		</li>
		<span class="separator acym__dashboard__light__separator"></span>

		<li class="acym_vcenter gap-1 padding-left-1 padding-vertical-1">
			<i class="small-4 <?php echo $data['mailStatsCheckedOnce'] ? 'acymicon-circle-check-o' : 'acymicon-circle-o'; ?>"></i>
			<a class="acym__dashboard__card__link" href="<?php echo acym_completeLink('stats'); ?>">
                <?php echo acym_translation('ACYM_CHECK_STATS'); ?>
			</a>
		</li>
	</ul>
</div>
