<?php
if (!empty($data['mailInformation'])) $data['mail'] = $data['mailInformation'];
?>
<div class="cell grid-x" id="acym__campaigns__edit_email__attachments">
	<label class="cell"><?php echo acym_translation('ACYM_ATTACHMENTS'); ?></label>
    <?php if (!empty($data['mail']->attachments)) { ?>
        <?php
        foreach ($data['mail']->attachments as $i => $oneAttach) {
            $onlyFilename = explode("/", $oneAttach->filename);

            $onlyFilename = end($onlyFilename);

            if (strlen($onlyFilename) > 40) {
                $onlyFilename = substr($onlyFilename, 0, 15)."...".substr($onlyFilename, strlen($onlyFilename) - 15);
            }

            echo '<div class="acym__listing__row cell grid-x" id="acym__campaigns__attach__del'.$i.'">';

            echo acym_tooltip(
                '<span class="cell acym__campaigns__attachments__already">'.$onlyFilename.' ('.(round($oneAttach->size / 1000, 1)).' Ko)</span>',
                $oneAttach->filename,
                'medium-11 cell'
            );
            $mailId = !empty($data['mail']->mail_id) ? $data['mail']->mail_id : $data['mail']->id;
            echo '<div class="cell medium-1 text-center"><a data-id="'.$i.'" data-mail="'.$mailId.'" class="acym__campaigns__attach__delete"><i class="acymicon-trash-o acym__color__red"></i></a></div>';
            echo '</div>';
        }
    }

    $uploadfileType = $data['uploadFileType'];
    for ($i = 0 ; $i < 10 ; $i++) {
        $result = '<div '.($i >= 1 ? 'style="display:none"' : '').' class="cell grid-x grid-margin-x acym__campaigns__attach__elements" id="acym__campaigns__attach__'.$i.'">';
        $result .= $uploadfileType->display('attachments', $i);
        $result .= '<div class="cell medium-auto"></div><div class="cell medium-1 text-center "><i style="display: none;" id="attachments'.$i.'suppr" data-id="'.$i.'" class="acymicon-trash-o acym__color__red acym__campaigns__attach__remove"></i></div>';
        $result .= '</div>';
        echo $result;
    }
    ?>
</div>
<div class="cell">
	<a href="javascript:void(0);" id="acym__campaigns__attach__add"><?php echo acym_translation('ACYM_ADD_ATTACHMENT'); ?></a>
    <?php echo acym_translationSprintf('ACYM_MAX_UPLOAD', $data['maxupload']); ?>
</div>
