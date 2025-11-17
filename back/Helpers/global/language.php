<?php

function acym_translationExists(string $key): bool
{
    return $key !== acym_translation($key);
}

function acym_loadLanguage(?string $lang = null): void
{
    acym_loadLanguageFile(ACYM_LANGUAGE_FILE, ACYM_ROOT, $lang, true);
    acym_loadLanguageFile(ACYM_LANGUAGE_FILE.'_custom', ACYM_ROOT, $lang, true);
}

function acym_isMultilingual(): bool
{
    if (!acym_level(ACYM_ESSENTIAL)) {
        return false;
    }

    $config = acym_config();
    $mainLanguage = $config->get('multilingual_default');
    $languages = $config->get('multilingual_languages');
    $isMultilingual = !empty($config->get('multilingual', '0'));

    if (!$isMultilingual || empty($mainLanguage) || empty($languages)) {
        return false;
    }

    return true;
}

function acym_getMultilingualLanguages(): array
{
    $allLanguages = acym_getLanguages();

    $config = acym_config();
    $languageCodes = array_merge(
        [
            $config->get('multilingual_default'),
        ],
        explode(',', $config->get('multilingual_languages'))
    );

    $languages = [];

    foreach ($languageCodes as $languageCode) {
        if (empty($allLanguages[$languageCode])) {
            continue;
        }

        $languages[$languageCode] = $allLanguages[$languageCode];
    }

    return $languages;
}

function acym_displayLanguageRadio(array $languages, string $name, $translation, string $info, $default = '', string $type = ''): string
{
    $config = acym_config();
    $defaultLanguage = $config->get('multilingual_default');

    if (is_array($translation)) $translation = json_encode($translation);
    if (is_array($default)) $default = json_encode($default);

    $return = '<div class="cell grid-x grid-margin-x acym__multilingual__selection" id="acym__multilingual__selection-'.acym_escape($type).'">';
    $return .= '<input type="hidden" class="acym__multilingual__selection__translation" name="'.acym_escape($name).'" value="'.acym_escape($translation).'">';
    $return .= '<input type="hidden" class="acym__multilingual__selection__translation__default" value="'.acym_escape($default).'">';
    $return .= '<input type="hidden" class="acym__multilingual__selection__main-language" value="'.acym_escape($defaultLanguage).'">';
    $return .= '<h4 class="cell shrink acym__title">'.acym_escape(acym_translation('ACYM_LANGUAGE')).acym_info(['textShownInTooltip' => $info]).'</h4>';

    foreach ($languages as $code => $language) {
        $class = $defaultLanguage === $code ? 'acym__multilingual__selection__one__selected' : '';
        $return .= '<div class="cell shrink acym__multilingual__selection__one '.acym_escape($class).'" 
                        data-acym-code="'.acym_escape($code).'" 
                        data-acym-tooltip="'.acym_escape($language->name).'">';
        $return .= '<img src="'.acym_escapeUrl(acym_getFlagByCode($code)).'" alt="'.acym_escape($code).' flag">';
        $return .= '</div>';
    }

    $return .= '</div>';

    return $return;
}

/**
 * Display the according translation
 * Works like sprintf(), but accepts an array as an argument, instead of a list of arguments.
 */
function acym_translationVsprintf(string $key, array $messageData, bool $isKey = true): string
{
    if ($isKey) {
        return vsprintf(acym_translation($key), $messageData);
    } else {
        return vsprintf($key, $messageData);
    }
}
