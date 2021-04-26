<?php

function acym_translationExists($key)
{
    return $key !== acym_translation($key);
}

function acym_loadLanguage($lang = null)
{
    acym_loadLanguageFile(ACYM_LANGUAGE_FILE, ACYM_ROOT, $lang, true);
    acym_loadLanguageFile(ACYM_LANGUAGE_FILE.'_custom', ACYM_ROOT, $lang, true);
}

function acym_isMultilingual()
{
    if (!acym_level(ACYM_ESSENTIAL)) return false;

    $config = acym_config();
    $mainLanguage = $config->get('multilingual_default');
    $languages = $config->get('multilingual_languages');

    if ($config->get('multilingual', '0') === '0') return false;
    if (empty($mainLanguage)) return false;

    return !empty($languages);
}

function acym_getMultilingualLanguages()
{
    $allLanguages = acym_getLanguages();

    $config = acym_config();
    $languageCodes = array_merge([$config->get('multilingual_default')], explode(',', $config->get('multilingual_languages')));

    $languages = [];

    foreach ($languageCodes as $languageCode) {
        if (empty($allLanguages[$languageCode])) continue;

        $languages[$languageCode] = $allLanguages[$languageCode];
    }

    return $languages;
}

function acym_displayLanguageRadio($languages, $name, $translation, $info, $default = '')
{
    $config = acym_config();
    $defaultLanguage = $config->get('multilingual_default');

    if (is_array($translation)) $translation = json_encode($translation);
    if (is_array($default)) $default = json_encode($default);

    $return = '<div class="cell grid-x grid-margin-x acym__multilingual__selection">';
    $return .= '<input type="hidden" id="acym__multilingual__selection__translation" name="'.$name.'" value="'.acym_escape($translation).'">';
    $return .= '<input type="hidden" id="acym__multilingual__selection__translation__default" name="" value="'.acym_escape($default).'">';
    $return .= '<input type="hidden" id="acym__multilingual__selection__main-language" value="'.acym_escape($defaultLanguage).'">';
    $return .= '<h4 class="cell shrink acym__title">'.acym_translation('ACYM_LANGUAGE').acym_info($info).'</h4>';

    foreach ($languages as $code => $language) {
        $class = $defaultLanguage == $code ? 'acym__multilingual__selection__one__selected' : '';
        $return .= '<div class="cell shrink acym__multilingual__selection__one '.$class.'" data-acym-code="'.$code.'" data-acym-tooltip="'.$language->name.'">';
        $return .= '<img src="'.acym_getFlagByCode($code).'" alt="'.$code.' flag">';
        $return .= '</div>';
    }

    $return .= '</div>';

    return $return;
}
