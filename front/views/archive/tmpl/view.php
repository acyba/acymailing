<style>
	html, body, #acym_wrapper, #acyarchiveview, #newsletter_preview_area{
		height: 100% !important;
	}

	#acym__archive__iframe{
		border: none;
		width: 100%;
		height: 90%;
	}

	.archive_view .hideonline{
		display: none !important;
	}

	.archive_view .revealonline{
		display: block !important;
	}


	.contentpane .archive_view .row{
		margin-left: inherit;
	}
</style>
<div id="acyarchiveview">
	<h1 class="contentheading"><?php echo $data['mail']->subject; ?></h1>

	<div class="newsletter_body" style="min-width:80%" id="newsletter_preview_area">
		<input type="hidden" id="acym__archive__email__content" value="<?php echo acym_escape($data['mail']->body); ?>">
		<iframe id="acym__archive__iframe"></iframe>
	</div>

    <?php
    $attachments = json_decode($data['mail']->attachments);

    if (!empty($attachments)) {
        ?>
		<fieldset class="newsletter_attachments">
			<legend><?php echo acym_translation('ACYM_ATTACHMENTS'); ?></legend>
			<table>
                <?php
                foreach ($attachments as $attachment) {
                    $onlyFilename = explode("/", $attachment->filename);

                    $onlyFilename = end($onlyFilename);

                    echo '<tr><td><a href="'.acym_rootURI().$attachment->filename.'" target="_blank">'.$onlyFilename.'</a></td></tr>';
                }
                ?>
			</table>
		</fieldset>
    <?php } ?>
</div>
<script>
    var content = document.getElementById('acym__archive__email__content').value;
    var iframe = document.getElementById('acym__archive__iframe').contentWindow.document;
    iframe.open();
    iframe.writeln(content);
    iframe.close();
</script>
