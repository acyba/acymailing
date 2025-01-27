<div class="cell grid-x acym__campaign__summary__preview__versions align-center margin-top-2">
    <?php
    $data['versions'] = array_merge([$data['main_version']], $data['versions']);

    foreach ($data['versions'] as $i => $version) {
        $isMain = $version->code == $data['main_version']->code;
        $class = $isMain ? 'version__selected' : '';

        if (empty($version->id) || (empty($data['abtest_mails'][$version->id]->body) && !$isMain)) {
            $class .= ' acym__campaign__summary__preview__versions-one__empty';
        }

        $subject = $isMain ? $data['mailInformation']->subject : $version->subject;
        if (empty($subject)) {
            $subject = acym_translationSprintf('ACYM_VERSION_X_EMPTY', $version->code);
        }
        echo '<div data-acym-version="'.$version->code.'" class="cell cursor-pointer shrink acym__campaign__summary__preview__versions-one '.$class.'">'.$subject.'</div>';

        if (empty($version->id) || empty($data['abtest_mails'][$version->id])) continue;

        echo '<input type="hidden" id="acym__summary-body-'.$version->code.'" value="'.acym_escape(acym_absoluteURL($data['abtest_mails'][$version->id]->body)).'">';
        echo '<input type="hidden" id="acym__summary-subject-'.$version->code.'" value="'.acym_escape($data['abtest_mails'][$version->id]->subject).'">';
        echo '<input type="hidden" id="acym__summary-preview-'.$version->code.'" value="'.acym_escape($data['abtest_mails'][$version->id]->preheader).'">';
    }
    ?>
</div>
