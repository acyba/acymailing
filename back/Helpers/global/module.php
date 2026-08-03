<?php
// context verification

function acym_getModuleFormName(): string
{
    static $i = 1;

    //The static sometimes does not work... but the rand should really do the job, REALLY!
    return 'formAcym'.acym_rand(1000, 9999).$i++;
}

/**
 * $params Is mainly an AcymParameter, but is of unknown type in the subscription module (mod_acym.php)
 */
function acym_initModule(?object $params = null, array $options = []): void
{
    if (acym_isAjax()) {
        return;
    }

    if (!file_exists(ACYM_ROOT.'plugins'.DS.'system'.DS.'modulesanywhere')) {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;
    }

    if (!is_null($params) && method_exists($params, 'get')) {
        $nameCaption = $params->get('nametext');
        $emailCaption = $params->get('emailtext');
        $jsLoading = $params->get('includejs');
        $options['loadJsInModule'] = $jsLoading === 'module';
        $options['defer'] = in_array($jsLoading, ['all', 'defer']);
        $options['async'] = in_array($jsLoading, ['all', 'async']);
    }

    if (empty($nameCaption)) {
        $nameCaption = acym_translation('ACYM_NAME');
    }
    if (empty($emailCaption)) {
        $emailCaption = acym_translation('ACYM_EMAIL');
    }

    $js = 'if (typeof window.acymModule === "undefined") {
            window.acymModule = '.json_encode([
            'NAMECAPTION' => $nameCaption,
            'NAME_MISSING' => acym_translation('ACYM_MISSING_NAME'),
            'EMAILCAPTION' => $emailCaption,
            'VALID_EMAIL' => acym_translation('ACYM_VALID_EMAIL'),
            'VALID_EMAIL_CONFIRMATION' => acym_translation('ACYM_VALID_EMAIL_CONFIRMATION'),
            'CAPTCHA_MISSING' => acym_translation('ACYM_WRONG_CAPTCHA'),
            'NO_LIST_SELECTED' => acym_translation('ACYM_SELECT_LIST'),
            'NO_LIST_SELECTED_UNSUB' => acym_translation('ACYM_SELECT_LIST_UNSUB'),
            'ACCEPT_TERMS' => acym_translation('ACYM_ACCEPT_TERMS'),
        ]).';
            window.acymModule.emailRegex = /^'.acym_getEmailRegex(true).'$/i;
        }
    ';

    $config = acym_config();
    $version = str_replace('.', '', $config->get('version'));

    global $acymEmailMisspelledLoaded;
    $spellChecker = empty($acymEmailMisspelledLoaded) && !empty($config->get('email_spellcheck'));
    if ($spellChecker) $acymEmailMisspelledLoaded = true;

    if (!empty($options['loadJsInModule'])) {
        if ($spellChecker) {
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Option to include the JS inside the module.
            echo '<script type="text/javascript" src="'.acym_escapeUrl(ACYM_JS.'libraries/email-misspelled.min.js?v='.$version).'"></script>';
        }
        // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Option to include the JS inside the module.
        echo '<script type="text/javascript" src="'.acym_escapeUrl(ACYM_JS.'module.min.js?v='.$version).'"></script>';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped, generated above.
        echo '<script type="text/javascript">'.$js.'</script>';
    } else {
        $scriptOptions = ['defer' => !empty($options['defer'])];
        if (!empty($options['async'])) {
            $scriptOptions['async'] = true;
        }

        if ($spellChecker) {
            acym_addScript(false, ACYM_JS.'libraries/email-misspelled.min.js?v='.$version, $scriptOptions);
        }
        $scriptName = acym_addScript(false, ACYM_JS.'module.min.js?v='.$version, $scriptOptions);
        acym_addScript(true, $js, array_merge($scriptOptions, ['dependencies' => ['script_name' => $scriptName]]));
    }

    if ('wordpress' === ACYM_CMS && !in_array(acym_getVar('string', 'action'), ['elementor', 'elementor_ajax'])) {
        if ($spellChecker) {
            wp_enqueue_style(
                'style_email_spellchecker',
                ACYM_CSS.'libraries/email-misspelled.min.css?v='.$version,
                [],
                '{__VERSION__}'
            );
        }
        wp_enqueue_style(
            'style_acymailing_module',
            ACYM_CSS.'module.min.css?v='.$version,
            [],
            '{__VERSION__}'
        );
    } else {
        if ($spellChecker) acym_addStyle(false, ACYM_CSS.'libraries/email-misspelled.min.css?v='.$version);
        acym_addStyle(false, ACYM_CSS.'module.min.css?v='.$version);
    }
}
