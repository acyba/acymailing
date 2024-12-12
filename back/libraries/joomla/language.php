<?php

use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Installer\Installer;

global $acymLanguages;

function acym_translation($key, $jsSafe = false, $interpretBackSlashes = true, $textdomain = 'acymailing')
{
    $translation = Text::_($key, false, $interpretBackSlashes);

    if ($jsSafe) {
        $translation = str_replace('"', '\"', $translation);
    }

    return $translation;
}

function acym_setLanguage($lang)
{
    if (ACYM_J40) {
        $previousLanguage = acym_getLanguageTag();
        new Language($lang);

        return $previousLanguage;
    }

    $acyapp = acym_getGlobal('app');
    $acylanguage = $acyapp->getLanguage();

    return $acylanguage->setLanguage($lang);
}

function acym_translationSprintf()
{
    $args = func_get_args();

    return call_user_func_array(['Joomla\CMS\Language\Text', 'sprintf'], $args);
}

function acym_getLanguages($uppercaseLangCode = false, $published = false)
{
    $result = [];

    $path = acym_getLanguagePath(ACYM_ROOT);
    $dirs = acym_getFolders($path);

    $languages = acym_loadObjectList('SELECT * FROM #__languages', 'lang_code');

    foreach ($dirs as $dir) {
        if (strlen($dir) != 5 || $dir == 'xx-XX') {
            continue;
        }
        if ($published && (empty($languages[$dir]) || $languages[$dir]->published != 1)) {
            continue;
        }

        $xmlFiles = acym_getFiles($path.DS.$dir, '^([-_A-Za-z]*)\.xml$');
        $xmlFile = reset($xmlFiles);
        if (empty($xmlFile)) {
            $data = [];
        } else {
            $data = Installer::parseXMLInstallFile(ACYM_LANGUAGE.$dir.DS.$xmlFile);
        }

        $lang = new stdClass();
        $lang->sef = empty($languages[$dir]) ? null : $languages[$dir]->sef;
        $lang->language = $uppercaseLangCode ? $dir : strtolower($dir);
        $lang->name = empty($languages[$dir]->title_native) ? (empty($data['name']) ? $dir : $data['name']) : $languages[$dir]->title_native;
        $lang->exists = file_exists(ACYM_LANGUAGE.$dir.DS.$dir.'.'.ACYM_COMPONENT.'.ini');
        $lang->content = empty($languages[$dir]) ? false : $languages[$dir]->published == 1;

        $result[$dir] = $lang;
    }

    return $result;
}

function acym_getLanguageTag($simple = false)
{
    if (ACYM_J40) {
        $acylanguage = Factory::getLanguage();
    } else {
        $acyapp = acym_getGlobal('app');
        $acylanguage = $acyapp->getLanguage();
    }

    $langCode = $acylanguage->getTag();

    return $simple ? substr($langCode, 0, 2) : $langCode;
}

function acym_loadLanguageFile($extension = 'joomla', $basePath = JPATH_SITE, $lang = null, $reload = false, $default = true)
{
    if (ACYM_J40) {
        $acylanguage = Factory::getLanguage();
    } else {
        $acyapp = acym_getGlobal('app');
        $acylanguage = $acyapp->getLanguage();
    }
    $acylanguage->load($extension, $basePath, $lang, $reload, $default);
}

function acym_getLanguagePath($basePath = ACYM_BASE, $language = null)
{
    return LanguageHelper::getLanguagePath(rtrim($basePath, DS), $language);
}

function acym_languageOption($emailLanguage, $name)
{
    $languages = acym_getLanguages(true, true);
    if (count($languages) < 2) return '';

    $default = new stdClass();
    $default->language = '';
    $default->name = acym_translation('ACYM_DEFAULT');
    array_unshift($languages, $default);

    return acym_select(
        $languages,
        $name,
        $emailLanguage,
        ['class' => 'acym__select'],
        'language',
        'name'
    );
}

function acym_getCmsUserLanguage($userId = null)
{
    if ($userId === null) $userId = acym_currentUserId();
    if (empty($userId)) return '';

    $user = Factory::getUser($userId);

    return $user->getParam('language', $user->getParam('admin_language', ''));
}

function acym_getTranslationTools()
{
    return [];
}
