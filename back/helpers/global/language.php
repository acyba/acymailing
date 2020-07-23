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
