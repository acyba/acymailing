<div class="acym_front_page <?php echo $data['paramsCMS']['suffix']; ?>">
    <?php
    if (!empty($data['paramsCMS']['show_page_heading'])) {
        echo '<h1 class="contentheading '.$data['paramsCMS']['suffix'].'"> '.$data['paramsCMS']['page_heading'].'</h1>';
    }
    ?>
	<div class="acym__front__archive ">
		<form method="post" action="<?php
        echo $data['actionUrl']; ?>" id="acym_form" class="acym__archive__form">
			<h1 class="acym__front__archive__title"><?php echo acym_translation('ACYM_NEWSLETTERS'); ?></h1>
			<div id="acym__front__archive__search" class="grid-x">
                <?php
                if (!empty($data['paramsCMS']['widget_id'])) {
                    echo '<input type="text" name="acym_search['.$data['paramsCMS']['widget_id'].']" value="'.acym_escape($data['search']).'">';
                } else {
                    ?>
					<input type="text" name="acym_search" value="<?php echo acym_escape($data['search']); ?>">
                <?php }
                $disableSearch = '';
                if (isset($data['disableButtons']) && $data['disableButtons']) {
                    $disableSearch = 'disabled';
                }
                ?>
				<button class="button btn btn-primary subbutton" <?php echo $disableSearch; ?>><?php echo acym_translation('ACYM_SEARCH'); ?></button>
			</div>

            <?php
            foreach ($data['newsletters'] as $oneNewsletter) {
                $archiveURL = acym_frontendLink('archive&task=view&id='.$oneNewsletter->id.'&'.acym_noTemplate());

                if ($data['popup']) {
                    $iframeClass = 'acym__modal__iframe';
                    if (empty($data['userId'])) $iframeClass .= ' acym__front__not_connected_user';
                    echo acym_frontModal($archiveURL, $oneNewsletter->subject, false, $oneNewsletter->id, $iframeClass);
                } else {
                    echo '<p class="acym__front__archive__raw"><a href="'.$archiveURL.'" target="_blank">'.$oneNewsletter->subject.'</a></p>';
                }
                echo '<p class="acym__front__archive__newsletter_sending-date">';
                echo acym_translation('ACYM_SENDING_DATE').' : '.acym_date($oneNewsletter->sending_date, 'd M Y');
                echo '</p>';
            }

            echo $data['pagination']->display('archive', '', true);
            acym_formOptions(true, 'listing', '', '', false);
            ?>

			<input type="hidden" name="acym_front_page" id="acym__front__archive__next-page" value="1">
		</form>
	</div>
</div>
