<?php if ('joomla' === ACYM_CMS) { ?>
	<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-bottom-2">
		<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_FRONTEND_EDITION'); ?></div>
        <?php
        if (!acym_level(ACYM_ENTERPRISE)) {
            $data['version'] = 'enterprise';
            include acym_getView('dashboard', 'upgrade', true);
        }
        ?>
	</div>
<?php } ?>


<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-bottom-2">
	<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_UNSUBSCRIBE_PAGE'); ?></div>
	<div class="grid-x grid-margin-x margin-y">
		<div class="cell grid-x margin-top-1 acym_vcenter">
            <?php
            echo acym_switch(
                'config[unsubscribe_page]',
                $this->config->get('unsubscribe_page', 1),
                acym_translation('ACYM_REDIRECT_ON_UNSUBSCRIBE_PAGE'),
                [],
                'xlarge-3 medium-5 small-9',
                'auto',
                '',
                'unsubpage_header'
            );
            ?>
		</div>
		<div class="cell grid-x margin-top-1" id="unsubpage_header">
			<div class="cell grid-x">
                <?php
                echo acym_switch(
                    'config[unsubpage_header]',
                    $this->config->get('unsubpage_header', 0),
                    acym_translation('ACYM_UNSUBSCRIBE_PAGE_HEADER'),
                    [],
                    'xlarge-3 medium-5 small-9'
                );
                ?>
			</div>
		</div>
	</div>
</div>
