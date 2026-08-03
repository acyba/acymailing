<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<div id="archive_view">
    <?php
    if (empty($data['receiver']->id)) {
        echo '<p class="acym_front_message_warning">'.acym_escapeHtml(acym_translation('ACYM_FRONT_ARCHIVE_NOT_CONNECTED')).'</p>';
    }
    ?>
	<h1 class="contentheading"><?php echo acym_escapeHtml($data['mail']->subject); ?></h1>

	<input type="hidden" id="archive_view__content" value="<?php echo acym_escape($data['mail']->body); ?>" />
	<div style="min-width:80%" id="archive_view__preview"><?php echo $data['mail']->body; ?></div>

    <?php
    $attachments = json_decode(!empty($data['mail']->attachments) ? $data['mail']->attachments : '[]', true);

    if (!empty($attachments)) {
        ?>
		<fieldset class="newsletter_attachments">
			<legend><?php echo acym_escapeHtml(acym_translation('ACYM_ATTACHMENTS')); ?></legend>
			<table>
                <?php
                foreach ($attachments as $attachment) {
                    $onlyFilename = explode('/', $attachment['filename']);
                    $onlyFilename = end($onlyFilename);

                    echo '<tr><td><a href="'.acym_escapeUrl(acym_rootURI().$attachment['filename']).'" target="_blank">'.acym_escapeHtml($onlyFilename).'</a></td></tr>';
                }
                ?>
			</table>
		</fieldset>
    <?php } ?>
</div>
