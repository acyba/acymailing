<div class="grid-x grid-margin-x cell">
	<div class="cell large-6 xlarge-auto">
        <?php echo acym_filterSearch($data['search'], 'users_search', 'ACYM_SEARCH'); ?>
	</div>
	<div class="cell large-3 xlarge-auto margin-bottom-1">
        <?php
        echo acym_select(
            $data['lists'],
            'users_list',
            $data['list'],
            ['class' => 'acym__select'],
            'id',
            'name'
        );
        ?>
	</div>
	<div class="cell large-3 xlarge-auto margin-bottom-1">
        <?php
        if (!empty($data['list'])) {
            echo acym_select(
                $data['list_statuses'],
                'list_status',
                $data['list_status'],
                ['class' => 'acym__select']
            );
        }
        ?>
	</div>
	<div class="cell medium-6 large-shrink">
		<button data-task="import" class="button button-secondary expanded acy_button_submit">
            <?php echo acym_translation('ACYM_IMPORT'); ?>
		</button>
	</div>
	<div class="cell medium-6 large-shrink">
		<button type="submit" data-task="export" class="button expanded button-secondary acy_button_submit">
            <?php echo acym_translation('ACYM_EXPORT'); ?> (<span id="acym__users__listing__number_to_export" data-default="<?php echo strtolower(acym_translation('ACYM_ALL')); ?>"><?php echo strtolower(acym_translation('ACYM_ALL')); ?></span>)
		</button>
	</div>
	<div class="cell medium-6 large-shrink">
        <?php
        $entityHelper = acym_get('helper.entitySelect');

        echo acym_modal(
            acym_translation('ACYM_ADD_TO_LIST').' (<span id="acym__users__listing__number_to_add_to_list">0</span>)',
            $entityHelper->entitySelect('list', ['join' => ''], $entityHelper->getColumnsForList(), ['text' => acym_translation('ACYM_SUBSCRIBE_USERS_TO_THESE_LISTS'), 'action' => 'addToList']),
            null,
            '',
            'class="button button-secondary disabled expanded" id="acym__users__listing__button--add-to-list"'
        );
        ?>
	</div>
	<div class="cell medium-6 large-shrink">
		<button data-task="edit" class="button expanded acy_button_submit">
            <?php echo acym_translation('ACYM_CREATE'); ?>
		</button>
	</div>
</div>
