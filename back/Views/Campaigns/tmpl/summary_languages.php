<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<div class="cell grid-x acym__campaign__summary__preview__languages align-center margin-top-2">
    <?php
    $data['languages'] = array_merge([$data['main_language']], $data['languages']);

    foreach ($data['languages'] as $i => $language) {
        $class = $language->code == $data['main_language']->code ? 'language__selected' : '';

        if (empty($data['multilingual_mails'][$language->code]->body) && $language->code != $data['main_language']->code) {
            $class .= ' acym__campaign__summary__preview__languages-one__empty';
        }

        echo '<div data-acym-lang="'.acym_escape($language->code).'" class="cell shrink acym__campaign__summary__preview__languages-one '.acym_escape($class).'">';
        acym_tooltip(
            [
                'hoveredText' => '<img acym-data-lang="'.acym_escape($language->code).'" 
                						src="'.acym_escapeUrl(acym_getFlagByCode($language->code)).'" 
                						alt="'.acym_escape($language->code).' flag">',
                'textShownInTooltip' => $language->name,
            ]
        );
        echo '</div>';

        if (empty($data['multilingual_mails'][$language->code])) {
            continue;
        }

        echo '<input type="hidden" id="acym__summary-body-'.acym_escape($language->code).'" value="'.acym_escape(
                acym_absoluteURL($data['multilingual_mails'][$language->code]->body)
            ).'">';
        echo '<input type="hidden" id="acym__summary-subject-'.acym_escape($language->code).'" value="'.acym_escape($data['multilingual_mails'][$language->code]->subject).'">';
        echo '<input type="hidden" id="acym__summary-preview-'.acym_escape($language->code).'" value="'.acym_escape($data['multilingual_mails'][$language->code]->preheader).'">';
    }
    ?>
</div>
