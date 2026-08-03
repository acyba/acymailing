<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification

if (!empty($data['mailInformation'])) {
    $data['mail'] = $data['mailInformation'];
}
?>
<div class="cell grid-x" id="acym__campaigns__edit_email__attachments">
	<label class="cell"><?php echo acym_escapeHtml(acym_translation('ACYM_ATTACHMENTS')); ?></label>
    <?php if (!empty($data['mail']->attachments)) { ?>
        <?php
        foreach ($data['mail']->attachments as $i => $oneAttach) {
            $onlyFilename = explode('/', $oneAttach['filename']);
            $onlyFilename = end($onlyFilename);

            echo '<div class="acym__listing__row cell grid-x" id="acym__campaigns__attach__del'.acym_escape($i).'">';

            acym_tooltip(
                [
                    'hoveredText' => '<span class="cell acym__campaigns__attachments__already">'.acym_escapeHtml(
                            $onlyFilename.' ('.(round($oneAttach['size'] / 1000, 1)).' Ko)'
                        ).'</span>',
                    'textShownInTooltip' => $oneAttach['filename'],
                    'classContainer' => 'medium-11 cell',
                ]
            );
            $mailId = !empty($data['mail']->mail_id) ? $data['mail']->mail_id : $data['mail']->id;
            echo '<div class="cell medium-1 text-center">
					<a data-id="'.acym_escape($i).'" data-mail="'.intval($mailId).'" class="acym__campaigns__attach__delete">
						<i class="acymicon-delete acym__color__red"></i>
					</a>
				</div>
			</div>';
        }
    }

    for ($i = 0; $i < 10; $i++) {
        ?>
		<div <?php echo $i >= 1 ? 'style="display:none"' : ''; ?>
				class="cell grid-x grid-margin-x acym__campaigns__attach__elements"
				id="acym__campaigns__attach__<?php echo intval($i); ?>">
            <?php $data['uploadFileType']->display('attachments', $i); ?>
			<div class="cell medium-auto"></div>
			<div class="cell medium-1 text-center ">
				<i style="display: none;"
				   id="attachments<?php echo intval($i); ?>suppr"
				   data-id="<?php echo intval($i); ?>"
				   class="acymicon-delete acym__color__red acym__campaigns__attach__remove"></i>
			</div>
		</div>
    <?php } ?>
</div>
<div class="cell">
	<a href="javascript:void(0);" id="acym__campaigns__attach__add"><?php echo acym_escapeHtml(acym_translation('ACYM_ADD_ATTACHMENT')); ?></a>
    <?php echo acym_escapeHtml(acym_translationSprintf('ACYM_MAX_UPLOAD', $data['maxupload'])); ?>
</div>
