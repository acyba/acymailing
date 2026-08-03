<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<div id="spam_test_zone" class="cell">
	<h6 class="acym__title acym__title__secondary"><?php echo acym_escapeHtml(acym_translation('ACYM_SAFE_CHECK'));
        acym_info(['textShownInTooltip' => 'ACYM_INTRO_SAFE_CHECK']); ?></h6>
	<p class="margin-bottom-1"><?php echo acym_escapeHtml(acym_translation('ACYM_SAFE_CHECK_DESC')); ?></p>
    <?php
    if (count($data['emails_to_test']) > 1) { ?>
		<div class="margin-bottom-1 cell grid-x">
			<p class="cell">
                <?php echo acym_escapeHtml(acym_translation('ACYM_SELECT_VERSION_TO_TEST')); ?>
			</p>
			<div class="cell">
                <?php acym_select($data['emails_to_test'], 'mail_id_test', $data['id'], ['class' => 'acym__select'], 'id', 'subject', null, false, true); ?>
			</div>
		</div>
    <?php } ?>
	<div class="grid-x align-center">
		<div class="cell">
            <?php
            ?>
		</div>
        <?php
        $classContainer = 'is-hidden';
        if (!empty($data['upgrade'])) {
            $classContainer = 'acym__campaigns__tests__starter';
        }
        if ($data['isDemo']) {
            $classContainer = 'acym__campaigns__tests__demo acym__campaigns__tests__starter';
        }
        ?>
		<div class="cell grid-x <?php echo acym_escape($classContainer); ?>" id="safe_check_results">
			<div class="cell grid-x acym_vcenter" id="check_words">
				<div class="cell small-10"><?php echo acym_escapeHtml(acym_translation('ACYM_TESTS_SAFE_CONTENT')); ?></div>
				<div class="cell small-2 text-center acym_icon_container">
                    <?php
                    if ($data['isDemo']) {
                        echo '<i class="acymicon-check-circle acym_icon_green"></i>';
                    } elseif (empty($data['upgrade'])) {
                        echo '<i></i>';
                    } else {
                        acym_tooltip(
                            [
                                'hoveredText' => '<i class="acymicon-question-circle-o"></i>',
                                'textShownInTooltip' => acym_translation('ACYM_NEED_PRO_VERSION'),
                            ]
                        );
                    }
                    ?>
				</div>
			</div>
			<div class="cell acym_check_results"></div>

			<div class="cell grid-x acym_vcenter" id="check_links">
				<div class="cell small-10"><?php echo acym_escapeHtml(acym_translation('ACYM_TESTS_LINKS')); ?></div>
				<div class="cell small-2 text-center acym_icon_container">
                    <?php
                    if ($data['isDemo']) {
                        echo '<i class="acymicon-check-circle acym_icon_green"></i>';
                    } elseif (empty($data['upgrade'])) {
                        echo '<i></i>';
                    } else {
                        acym_tooltip(
                            [
                                'hoveredText' => '<i class="acymicon-question-circle-o"></i>',
                                'textShownInTooltip' => acym_translation('ACYM_NEED_PRO_VERSION'),
                            ]
                        );
                    }
                    ?>
				</div>
			</div>
			<div class="cell acym_check_results"></div>

            <?php
            $spamTestRow = '';
            $dataModal = '';
            if ($data['isDemo']) {
                $iconSpamTest = '<i class="acymicon-check-circle acym_icon_green"></i>';
            } elseif (acym_level(ACYM_ENTERPRISE)) {
                $iconSpamTest = '<i></i>';
            } else {
                ob_start();
                acym_tooltip(
                    [
                        'hoveredText' => '<i class="acymicon-question-circle-o"></i>',
                        'textShownInTooltip' => acym_translation('ACYM_NEED_ENTERPRISE_VERSION'),
                    ]
                );
                $iconSpamTest = ob_get_clean();
            }
            if (!$data['isDemo']) {
                $spamTestRow = '<div class="cell grid-x acym_vcenter" id="check_spam" data-iframe="spamtestpopup" data-iframe-class="acym__iframe_spamtest">
									<div class="cell small-10">'.acym_escapeHtml(acym_translation('ACYM_TESTS_SPAM')).'</div>
									<div class="cell small-2 text-center acym_icon_container">'.$iconSpamTest.'</div>
								</div>';
            }

            if ($data['isDemo']) {
                $dataModal = '<img src="'.ACYM_IMAGES.'demo/spam_test.png">';
                $spamTestRow = '<div class="cell grid-x acym_vcenter" id="check_spam" data-open="spamtestpopup">
									<div class="cell small-10">'.acym_escapeHtml(acym_translation('ACYM_TESTS_SPAM')).'</div>
									<div class="cell small-2 text-center acym_icon_container">'.$iconSpamTest.'</div>
								</div>';
            }

            acym_modal(
                $spamTestRow,
                $dataModal,
                'spamtestpopup',
                ['data-reveal-larger' => true],
                [],
                false
            );
            ?>
			<div class="cell acym_check_results"></div>
			<div class="cell text-center <?php echo !$data['isDemo'] ? 'is-hidden' : ''; ?>" id="acym_spam_test_details">
				<button type="button" class="button button-secondary"><?php echo acym_escapeHtml(acym_translation('ACYM_DETAILS')); ?></button>
			</div>
		</div>
        <?php
        if (!acym_level(ACYM_ENTERPRISE)) {
            $visibilityClass = '';
            if (acym_level(ACYM_ESSENTIAL)) {
                $visibilityClass = ' is-hidden';
            }
            echo '<div class="cell grid-x grid-margin-x margin-top-2 align-center acym__campaigns__test__pro'.acym_escape($visibilityClass).'">';
            echo '<a href="'.acym_escapeUrl(ACYM_DOCUMENTATION).'main-pages/campaigns/tests" target="_blank" class="button button-secondary cell shrink">';
            echo acym_escapeHtml(acym_translation('ACYM_SEE_MORE'));
            echo '</a>';
            if (acym_level(ACYM_ESSENTIAL)) {
                $visibilityClass = ' is-hidden';
                acym_displayButtonGetProVersion('cell shrink', 'ACYM_GET_ENTERPRISE_VERSION');
            } else {
                acym_displayButtonGetProVersion();
            }
            echo '</div>';
        }
        ?>
	</div>
</div>
