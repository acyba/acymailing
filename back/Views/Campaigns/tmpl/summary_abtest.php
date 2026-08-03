<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
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
        echo '<div data-acym-version="'.acym_escape($version->code).'" class="cell cursor-pointer shrink acym__campaign__summary__preview__versions-one '.acym_escape(
                $class
            ).'">'.acym_escapeHtml($subject).'</div>';

        if (empty($version->id) || empty($data['abtest_mails'][$version->id])) continue;

        echo '<input type="hidden" id="acym__summary-body-'.acym_escape($version->code).'" value="'.acym_escape(acym_absoluteURL($data['abtest_mails'][$version->id]->body)).'">';
        echo '<input type="hidden" id="acym__summary-subject-'.acym_escape($version->code).'" value="'.acym_escape($data['abtest_mails'][$version->id]->subject).'">';
        echo '<input type="hidden" id="acym__summary-preview-'.acym_escape($version->code).'" value="'.acym_escape($data['abtest_mails'][$version->id]->preheader).'">';
    }
    ?>
</div>
