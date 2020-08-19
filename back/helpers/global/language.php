<?php

function acym_translationExists($key)
{
    return $key !== acym_translation($key);
}

function acym_loadLanguage()
{
    acym_loadLanguageFile(ACYM_LANGUAGE_FILE, ACYM_ROOT, null, true);
    acym_loadLanguageFile(ACYM_LANGUAGE_FILE.'_custom', ACYM_ROOT, null, true);
}

function acym_isMultilingual()
{
    if (!acym_level(1)) return false;

    $config = acym_config();
    $mainLanguage = $config->get('multilingual_default');
    $languages = explode(',', $config->get('multilingual_languages'));

    if ($config->get('multilingual', '0') === '0') return false;
    if (empty($mainLanguage)) return false;

    return count($languages) !== 0;
}
