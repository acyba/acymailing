<?php
// context verification

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

function acym_displayLanguageRadio(array $languages, string $name, $translation, string $info, $default = '', string $type = ''): void
{
    $config = acym_config();
    $defaultLanguage = $config->get('multilingual_default');

    if (is_array($translation)) {
        $translation = json_encode($translation);
    }
    if (is_array($default)) {
        $default = json_encode($default);
    }
    ?>

	<div class="cell grid-x grid-margin-x acym__multilingual__selection" id="acym__multilingual__selection-<?php echo acym_escape($type); ?>">
		<input type="hidden" class="acym__multilingual__selection__translation" name="<?php echo acym_escape($name); ?>" value="<?php echo acym_escape($translation ?? ''); ?>">
		<input type="hidden" class="acym__multilingual__selection__translation__default" value="<?php echo acym_escape($default ?? ''); ?>">
		<input type="hidden" class="acym__multilingual__selection__main-language" value="<?php echo acym_escape($defaultLanguage); ?>">
		<h4 class="cell shrink acym__title">
            <?php echo acym_escapeHtml(acym_translation('ACYM_LANGUAGE')); ?>
            <?php acym_info(['textShownInTooltip' => $info]); ?>
		</h4>

        <?php foreach ($languages as $code => $language) { ?>
			<div class="cell shrink acym__multilingual__selection__one <?php echo acym_escape($defaultLanguage === $code ? 'acym__multilingual__selection__one__selected' : ''); ?>"
			     data-acym-code="<?php echo acym_escape($code); ?>"
			     data-acym-tooltip="<?php echo acym_escape($language->name); ?>">
				<img src="<?php echo acym_escapeUrl(acym_getFlagByCode($code)); ?>" alt="<?php echo acym_escape($code); ?> flag">
			</div>
        <?php } ?>
	</div>
    <?php
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
