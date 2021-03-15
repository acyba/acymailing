<div class="cell grid-x acym__campaign__summary__preview__languages align-center margin-top-2">
    <?php
    $data['languages'] = array_merge([$data['main_language']], $data['languages']);

    foreach ($data['languages'] as $i => $language) {
        $class = $language->code == $data['main_language']->code ? 'language__selected' : '';

        if (empty($data['multilingual_mails'][$language->code]->body) && $language->code != $data['main_language']->code) {
            $class .= ' acym__campaign__summary__preview__languages-one__empty';
        }

        $flag = acym_tooltip(
            '<img acym-data-lang="'.$language->code.'" src="'.acym_getFlagByCode($language->code).'" alt="'.$language->code.' flag">',
            $language->name
        );
        echo '<div data-acym-lang="'.$language->code.'" class="cell shrink acym__campaign__summary__preview__languages-one '.$class.'">'.$flag.'</div>';

        if (empty($data['multilingual_mails'][$language->code])) continue;

        echo '<input type="hidden" id="acym__summary-body-'.$language->code.'" value="'.acym_escape(acym_absoluteURL($data['multilingual_mails'][$language->code]->body)).'">';
        echo '<input type="hidden" id="acym__summary-subject-'.$language->code.'" value="'.acym_escape($data['multilingual_mails'][$language->code]->subject).'">';
        echo '<input type="hidden" id="acym__summary-preview-'.$language->code.'" value="'.acym_escape($data['multilingual_mails'][$language->code]->preheader).'">';
    }
    ?>
</div>
