<div id="spam_test_zone" class="cell">
	<h6 class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_SAFE_CHECK').acym_info('ACYM_INTRO_SAFE_CHECK'); ?></h6>
	<p class="margin-bottom-1"><?php echo acym_translation('ACYM_SAFE_CHECK_DESC'); ?></p>
    <?php
    if (count($data['emails_to_test']) > 1) { ?>
		<div class="margin-bottom-1 cell grid-x">
			<p class="cell">
                <?php echo acym_translation('ACYM_SELECT_VERSION_TO_TEST'); ?>
			</p>
			<div class="cell">
                <?php echo acym_select($data['emails_to_test'], 'mail_id_test', $data['id'], ['class' => 'acym__select'], 'id', 'subject'); ?>
			</div>
		</div>
    <?php } ?>
	<div class="grid-x align-center">
		<div class="cell">
            <?php
            ?>
		</div>
        <?php
        $icon = empty($data['upgrade'])
            ? '<i></i>'
            : acym_tooltip(
                [
                    'hoveredText' => '<i class="acymicon-question-circle-o"></i>',
                    'textShownInTooltip' => acym_translation('ACYM_NEED_PRO_VERSION'),
                ]
            );
        $iconSpamTest = acym_level(ACYM_ENTERPRISE)
            ? '<i></i>'
            : acym_tooltip(
                [
                    'hoveredText' => '<i class="acymicon-question-circle-o"></i>',
                    'textShownInTooltip' => acym_translation('ACYM_NEED_ENTERPRISE_VERSION'),
                ]
            );
        $classContainer = 'is-hidden';
        if (!empty($data['upgrade'])) $classContainer = 'acym__campaigns__tests__starter';
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $icon = '<i class="acymicon-check-circle acym_icon_green"></i>';
            $iconSpamTest = $icon;
            $classContainer = 'acym__campaigns__tests__demo acym__campaigns__tests__starter';
        }
        //__END__demo_
        ?>
		<div class="cell grid-x <?php echo $classContainer; ?>" id="safe_check_results">
			<div class="cell grid-x acym_vcenter" id="check_words">
				<div class="cell small-10"><?php echo acym_translation('ACYM_TESTS_SAFE_CONTENT'); ?></div>
				<div class="cell small-2 text-center acym_icon_container"><?php echo $icon; ?></div>
			</div>
			<div class="cell acym_check_results"></div>

			<div class="cell grid-x acym_vcenter" id="check_links">
				<div class="cell small-10"><?php echo acym_translation('ACYM_TESTS_LINKS'); ?></div>
				<div class="cell small-2 text-center acym_icon_container"><?php echo $icon; ?></div>
			</div>
			<div class="cell acym_check_results"></div>

            <?php
            $spamTestRow = '';
            $dataModal = '';
            //__START__production_
            if (ACYM_PRODUCTION) {
                $spamTestRow = '<div class="cell grid-x acym_vcenter" id="check_spam" data-iframe="spamtestpopup" data-iframe-class="acym__iframe_spamtest">
													<div class="cell small-10">'.acym_translation('ACYM_TESTS_SPAM').'</div>
													<div class="cell small-2 text-center acym_icon_container">'.$iconSpamTest.'</div>
												</div>';
            }
            //__END__production_

            //__START__demo_
            if (!ACYM_PRODUCTION) {
                $dataModal = '<img src="'.ACYM_IMAGES.'demo/spam_test.png">';
                $spamTestRow = '<div class="cell grid-x acym_vcenter" id="check_spam" data-open="spamtestpopup">
										<div class="cell small-10">'.acym_translation('ACYM_TESTS_SPAM').'</div>
													<div class="cell small-2 text-center acym_icon_container">'.$iconSpamTest.'</div>
												</div>';
            }
            //__END__demo_

            echo acym_modal(
                $spamTestRow,
                $dataModal,
                'spamtestpopup',
                'data-reveal-larger',
                '',
                false
            );
            ?>
			<div class="cell acym_check_results"></div>
			<div class="cell text-center <?php echo ACYM_PRODUCTION ? 'is-hidden' : ''; ?>" id="acym_spam_test_details">
				<button type="button" class="button button-secondary"><?php echo acym_translation('ACYM_DETAILS'); ?></button>
			</div>
		</div>
        <?php
        if (!acym_level(ACYM_ENTERPRISE)) {
            $blocDisplay = '';
            $getProBtn = acym_buttonGetProVersion();
            if (acym_level(ACYM_ESSENTIAL)) {
                $blocDisplay = 'style="display:none;"';
                $getProBtn = acym_buttonGetProVersion('cell shrink', 'ACYM_GET_ENTERPRISE_VERSION');
            }
            echo '<div class="cell grid-x grid-margin-x margin-top-2 align-center acym__campaigns__test__pro" '.$blocDisplay.'>';
            echo '<a href="'.ACYM_DOCUMENTATION.'main-pages/campaigns/tests" target="_blank" class="button button-secondary cell shrink">';
            echo acym_translation('ACYM_SEE_MORE');
            echo '</a>';
            echo $getProBtn;
            echo '</div>';
        }
        ?>
	</div>
</div>
