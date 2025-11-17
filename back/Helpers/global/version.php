<?php

/**
 * Return TRUE if the level is handled by the current application
 * Return FALSE if the level is not enough
 */
function acym_level(int $neededLevel): bool
{
    $levels = [
        'Starter' => ACYM_STARTER,
        'Essential' => ACYM_ESSENTIAL,
        'Enterprise' => ACYM_ENTERPRISE,
    ];

    $config = acym_config();
    $currentLevel = $config->get('level');

    $expirationDate = $config->get('expirationdate', 'not-set');

    if ($config->get('isTrial', 0) == 1 && ($expirationDate !== 'not-set' && $expirationDate < time())) {
        $currentLevel = 'Starter';
    }

    $currentLevelNumber = $levels[$currentLevel] ?? 0;

    return $currentLevelNumber >= $neededLevel;
}

function acym_upgradeTo(string $version, string $utmMedium): void
{
    $link = ACYM_ACYMAILING_WEBSITE.'pricing?utm_source=acymailing_plugin&utm_medium='.$utmMedium.'&utm_campaign=purchase';
    $text = $version === 'essential' ? 'AcyMailing Essential' : 'AcyMailing Enterprise';
    echo '<div class="acym__upgrade cell grid-x text-center align-center">
            <h2 class="acym__listing__empty__title cell">'.acym_translationSprintf('ACYM_USE_THIS_FEATURE', '<span class="acym__color__blue">'.$text.'</span>').'</h2>
            <a target="_blank" href="'.$link.'" class="cell medium-6 large-shrink button acym__button__upgrade">'.acym_translation('ACYM_UPGRADE_NOW_SIMPLE').'</a>
          </div>';
}

function acym_existsAcyMailing59(): bool
{
    $allTables = acym_getTables();

    if (!in_array(acym_getPrefix().'acymailing_config', $allTables)) {
        return false;
    }

    $version = acym_loadResult('SELECT `value` FROM #__acymailing_config WHERE `namekey` = "version"');

    return version_compare($version, '5.9.0', '>=');
}

function acym_buttonGetProVersion(string $class = 'cell shrink', string $text = 'ACYM_UPGRADE_NOW_SIMPLE'): string
{
    return '<a href="'.ACYM_ACYMAILING_WEBSITE.'pricing" target="_blank" class="button acym__button__upgrade '.$class.'">'.acym_translation($text).'</a>';
}
