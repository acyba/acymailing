<?php

global $acyWPLangCodes;
$acyWPLangCodes = [
    'af' => 'af-ZA',
    'ar' => 'ar-AA',
    'as' => 'as-AS', // Not sure
    'az' => 'az-AZ', // Not sure
    'bo' => 'bo-BO', // Not sure
    'ca' => 'ca-ES',
    'cy' => 'cy-GB',
    'el' => 'el-GR',
    'eo' => 'eo-XX',
    'et' => 'et-EE',
    'eu' => 'eu-ES',
    'fi' => 'fi-FI',
    'gd' => 'gd-GD', // Not sure
    'gu' => 'gu-GU', // Not sure
    'hr' => 'hr-HR',
    'hy' => 'hy-AM',
    'ja' => 'ja-JP',
    'kk' => 'kk-KK', // Not sure
    'km' => 'km-KH',
    'lo' => 'lo-LO', // Not sure
    'lv' => 'lv-LV',
    'mn' => 'mn-MN', // Not sure
    'mr' => 'mr-MR', // Not sure
    'ps' => 'ps-PS', // Not sure
    'sq' => 'sq-AL',
    'te' => 'te-TE',
    'th' => 'th-TH',
    'tl' => 'tl-TL', // Not sure
    'uk' => 'uk-UA',
    'ur' => 'ur-PK', // Not sure
    'vi' => 'vi-VN',
];

global $acymLanguages;

/**
 * Display the translation based on a key
 *
 * @param string $key                  The translation key used in AcyMailing language files
 * @param bool   $jsSafe               Whether or not the result should be escaped
 * @param bool   $interpretBackSlashes Interpret or not the \ like \t \n etc...
 *
 * @return string
 */
function acym_translation($key, $jsSafe = false, $interpretBackSlashes = true)
{
    // Return the key passed by default as it may be a random text instead of a key
    $translation = $key;

    global $acymLanguages;
    acym_getLanguageTag();
    if (!isset($acymLanguages[$acymLanguages['currentLanguage']])) acym_loadLanguage();

    $acymailingEnglishText = '';
    foreach ($acymLanguages[ACYM_DEFAULT_LANGUAGE] as $fileContent) {
        if (isset($fileContent[$key])) {
            $acymailingEnglishText = $fileContent[$key];
            break;
        }
    }

    global $customTranslation;
    acym_getCustomTranslation();

    if (!empty($customTranslation) && isset($customTranslation[$key])) {
        $translation = $customTranslation[$key];
    } elseif (!empty($acymailingEnglishText)) {
        // If there is an english translation, get the wordpress translation. By default it returns the text passed, so the english translation
        $translation = __($acymailingEnglishText, 'acymailing');

        // If there is no translation on the WordPress side, and we're not in english, take the AcyMailing community translation
        if ($translation === $acymailingEnglishText && $acymLanguages['currentLanguage'] != ACYM_DEFAULT_LANGUAGE) {
            foreach ($acymLanguages[$acymLanguages['currentLanguage']] as $fileContent) {
                if (isset($fileContent[$key])) {
                    // If a translation is found for the specified key, take it
                    $translation = $fileContent[$key];
                    break;
                }
            }
        }
    }

    // Escape the quotes if we're in a javascript context
    if ($jsSafe) {
        $translation = str_replace('"', '\"', $translation);
    } elseif ($interpretBackSlashes && strpos($translation, '\\') !== false) {
        $translation = str_replace(['\\\\', '\t', '\n'], ["\\", "\t", "\n"], $translation);
    }

    return $translation;
}

/**
 * Display the according translation
 */
function acym_translation_sprintf()
{
    $args = func_get_args();
    $args[0] = acym_translation($args[0]);

    return call_user_func_array('sprintf', $args);
}

function acym_getLanguages($installed = false)
{
    global $acyWPLangCodes;

    $result = [];

    require_once ABSPATH.'wp-admin/includes/translation-install.php';
    $wplanguages = wp_get_available_translations();
    $languages = get_available_languages();
    foreach ($languages as $oneLang) {
        $wpLangCode = $oneLang;
        if (!empty($acyWPLangCodes[$oneLang])) $oneLang = $acyWPLangCodes[$oneLang];
        $langTag = str_replace('_', '-', $oneLang);

        $lang = new stdClass();
        $lang->sef = empty($wplanguages[$oneLang]['iso'][1]) ? null : $wplanguages[$oneLang]['iso'][1];
        $lang->language = strtolower($langTag);
        $lang->name = empty($wplanguages[$wpLangCode]) ? $langTag : $wplanguages[$wpLangCode]['native_name'];
        $lang->exists = file_exists(ACYM_LANGUAGE.$langTag.'.'.ACYM_LANGUAGE_FILE.'.ini');
        $lang->content = true;

        $result[$langTag] = $lang;
    }

    if (!in_array('en-US', array_keys($result))) {
        $lang = new stdClass();
        $lang->sef = 'en';
        $lang->language = 'en-us';
        $lang->name = 'English (United States)';
        $lang->exists = file_exists(ACYM_LANGUAGE.'en-US.'.ACYM_LANGUAGE_FILE.'.ini');
        $lang->content = true;

        $result['en-US'] = $lang;
    }

    return $result;
}

function acym_getLanguageTag($simple = false)
{
    if (acym_isAdmin()) {
        $currentLocale = get_user_locale(acym_currentUserId());
    } else {
        $currentLocale = get_locale();
    }

    $currentLocale = convertWPLocaleToAcyLocale($currentLocale);

    global $acymLanguages;
    if (!isset($acymLanguages['currentLanguage']) || $acymLanguages['currentLanguage'] !== $currentLocale) {
        $acymLanguages['currentLanguage'] = $currentLocale;
    }

    return $simple ? substr($acymLanguages['currentLanguage'], 0, 2) : $acymLanguages['currentLanguage'];
}

function acym_getCustomTranslation()
{
    global $customTranslation;
    if (isset($customTranslation)) return;
    $customTranslation = [];
    $currentLanguage = acym_getLanguageTag();
    $filePath = ACYM_LANGUAGE.$currentLanguage.'.'.ACYM_LANGUAGE_FILE.'_custom.ini';
    if (file_exists($filePath)) {
        $data = acym_fileGetContent($filePath);
        $data = str_replace('"_QQ_"', '"', $data);
        $separate = explode("\n", $data);
        foreach ($separate as $raw) {
            if (strpos($raw, '=') === false) continue;

            $keyval = explode('=', $raw);
            $key = array_shift($keyval);

            $customTranslation[$key] = trim(implode('=', $keyval), "\"\r\n\t ");
        }
    }
}

function acym_loadLanguageFile($extension, $basePath = null, $lang = null, $reload = false, $default = true)
{
    global $acymLanguages;
    $currentLanguage = acym_getLanguageTag();
    if (isset($acymLanguages[$currentLanguage][$extension]) && !$reload) return;

    $base = ACYM_LANGUAGE;
    $language = $currentLanguage;

    if (!file_exists($base.$language.'.'.$extension.'.ini')) {
        $language = ACYM_DEFAULT_LANGUAGE;
        if (!file_exists($base.$language.'.'.$extension.'.ini')) {
            $base = ACYM_FOLDER.'language'.DS;
            $language = $currentLanguage;
            if (!file_exists($base.$language.'.'.$extension.'.ini')) {
                $language = ACYM_DEFAULT_LANGUAGE;
                if (!file_exists($base.$language.'.'.$extension.'.ini')) return;
            }
        }
    }

    $data = acym_fileGetContent($base.$language.'.'.$extension.'.ini');
    $data = str_replace('"_QQ_"', '"', $data);
    $separate = explode("\n", $data);
    $storeExtension = $extension === ACYM_LANGUAGE_FILE.'_custom' ? ACYM_LANGUAGE_FILE : $extension;
    foreach ($separate as $raw) {
        if (strpos($raw, '=') === false) continue;

        $keyval = explode('=', $raw);
        $key = array_shift($keyval);

        $acymLanguages[$acymLanguages['currentLanguage']][$storeExtension][$key] = trim(implode('=', $keyval), "\"\r\n\t ");
    }

    if (!empty($acymLanguages[ACYM_DEFAULT_LANGUAGE])) return;

    $data = acym_fileGetContent($base.ACYM_DEFAULT_LANGUAGE.'.'.$extension.'.ini');
    $data = str_replace('"_QQ_"', '"', $data);
    $separate = explode("\n", $data);

    foreach ($separate as $raw) {
        if (strpos($raw, '=') === false) continue;

        $keyval = explode('=', $raw);
        $key = array_shift($keyval);

        $acymLanguages[ACYM_DEFAULT_LANGUAGE][$extension][$key] = trim(implode('=', $keyval), "\"\r\n\t ");
    }
}

function acym_getLanguagePath($basePath, $language = null)
{
    return rtrim(ACYM_LANGUAGE, DS);
}

function acym_languageOption($emailLanguage, $name)
{
    return '';
}

function convertWPLocaleToAcyLocale($locale)
{
    if (strpos($locale, '-') !== false) return $locale;

    global $acyWPLangCodes;
    if (!empty($acyWPLangCodes[$locale])) return $acyWPLangCodes[$locale];

    if (strpos($locale, '_') === false) {
        return $locale.'-'.strtoupper($locale);
    } else {
        return str_replace('_', '-', $locale);
    }
}

function acym_getCmsUserLanguage($userId = null)
{
    if ($userId === null) $userId = acym_currentUserId();
    if (empty($userId)) return '';

    return convertWPLocaleToAcyLocale(get_user_locale($userId));
}

function acym_getTranslationTools()
{
    $options = [
        (object)['value' => 'no', 'text' => 'ACYM_NO'],
    ];

    $polylangOption = (object)['value' => 'polylang', 'text' => 'Polylang'];
    if (!acym_isExtensionActive('polylang/polylang.php')) $polylangOption->disable = true;
    $options[] = $polylangOption;

    $wpmlOption = (object)['value' => 'wpml', 'text' => 'WPML'];
    if (!acym_isExtensionActive('sitepress-multilingual-cms/sitepress.php')) $wpmlOption->disable = true;
    $options[] = $wpmlOption;

    return $options;
}
