<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<div class="acym_front_page <?php echo acym_escape($data['paramsCMS']['suffix'] ?? ''); ?>">
    <?php
    if (!empty($data['paramsCMS']['show_page_heading'])) {
        echo '<h1 class="contentheading '.acym_escape($data['paramsCMS']['suffix'] ?? '').'">'.acym_escapeHtml($data['paramsCMS']['page_heading'] ?? '').'</h1>';
    }
    ?>
	<div class="acym__front__archive">
		<form method="post" action="<?php
        echo acym_escapeUrl($data['actionUrl']); ?>" id="acym_form" class="acym__archive__form">
			<h1 class="acym__front__archive__title"><?php echo acym_escapeHtml(acym_translation('ACYM_NEWSLETTERS')); ?></h1>
			<div id="acym__front__archive__search" class="grid-x">
                <?php
                if (!empty($data['paramsCMS']['widget_id'])) {
                    echo '<input type="text" name="acym_search['.acym_escape($data['paramsCMS']['widget_id']).']" value="'.acym_escape($data['search']).'">';
                } else {
                    ?>
					<input type="text" name="acym_search" value="<?php echo acym_escape($data['search']); ?>">
                <?php }
                $disableSearch = '';
                if (isset($data['disableButtons']) && $data['disableButtons']) {
                    $disableSearch = 'disabled';
                }
                ?>
				<button class="button btn btn-primary subbutton" <?php echo acym_escape($disableSearch); ?>><?php echo acym_escapeHtml(acym_translation('ACYM_SEARCH')); ?></button>
			</div>

            <?php
            if (empty($data['newsletters'])) {
                echo acym_escapeHtml(acym_translation('ACYM_NOTHING_FOR_SEARCH'));
            } else {
                foreach ($data['newsletters'] as $oneNewsletter) {
                    $archiveURL = acym_frontendLink('archive&task=view&id='.$oneNewsletter->id.'&'.acym_noTemplate());

                    if ($data['popup']) {
                        $iframeClass = 'acym__modal__iframe';
                        if (empty($data['userId'])) $iframeClass .= ' acym__front__not_connected_user';
                        echo acym_escapeHtmlWithAllowedTags(
                            acym_frontModal($archiveURL, $oneNewsletter->subject, false, $oneNewsletter->id, $iframeClass),
                            [
                                'link' => [
                                    'rel' => true,
                                    'href' => true,
                                    'type' => true,
                                ],
                                'a' => [
                                    'class' => true,
                                    'data-acym-modal' => true,
                                    'href' => true,
                                ],
                                'div' => [
                                    'class' => true,
                                    'id' => true,
                                    'style' => true,
                                ],
                                'span' => [
                                ],
                                'iframe' => [
                                    'class' => true,
                                    'src' => true,
                                ],
                            ]
                        );
                    } else {
                        echo '<p class="acym__front__archive__raw"><a href="'.acym_escapeUrl($archiveURL).'" target="_blank">'.acym_escapeHtml($oneNewsletter->subject).'</a></p>';
                    }
                    echo '<p class="acym__front__archive__newsletter_sending-date">';
                    echo acym_escapeHtml(acym_translation('ACYM_SENDING_DATE').' : '.acym_date($oneNewsletter->sending_date, 'd M Y'));
                    echo '</p>';
                }

                $data['pagination']->display('archive', '', true);
            }

            acym_formOptions(true, 'listing', '', '', false);
            ?>

			<input type="hidden" name="acym_front_page" id="acym__front__archive__next-page" value="1">
		</form>
	</div>
</div>
