<?php

function acym_getModuleFormName()
{
    static $i = 1;

    //The static sometimes does not work... but the rand should really do the job, REALLY!
    return 'formAcym'.rand(1000, 9999).$i++;
}

function acym_initModule($params = null, $options = [])
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

    $js = "
        if(typeof acymModule === 'undefined'){
            var acymModule = [];
			acymModule['emailRegex'] = /^".acym_getEmailRegex(true)."$/i;
			acymModule['NAMECAPTION'] = '".str_replace("'", "\'", $nameCaption)."';
			acymModule['NAME_MISSING'] = '".str_replace("'", "\'", acym_translation('ACYM_MISSING_NAME'))."';
			acymModule['EMAILCAPTION'] = '".str_replace("'", "\'", $emailCaption)."';
			acymModule['VALID_EMAIL'] = '".str_replace("'", "\'", acym_translation('ACYM_VALID_EMAIL'))."';
			acymModule['VALID_EMAIL_CONFIRMATION'] = '".str_replace("'", "\'", acym_translation('ACYM_VALID_EMAIL_CONFIRMATION'))."';
			acymModule['CAPTCHA_MISSING'] = '".str_replace("'", "\'", acym_translation('ACYM_WRONG_CAPTCHA'))."';
			acymModule['NO_LIST_SELECTED'] = '".str_replace("'", "\'", acym_translation('ACYM_SELECT_LIST'))."';
			acymModule['NO_LIST_SELECTED_UNSUB'] = '".str_replace("'", "\'", acym_translation('ACYM_SELECT_LIST_UNSUB'))."';
            acymModule['ACCEPT_TERMS'] = '".str_replace("'", "\'", acym_translation('ACYM_ACCEPT_TERMS'))."';
        }
		";

    $config = acym_config();
    $version = str_replace('.', '', $config->get('version'));

    global $acymEmailMisspelledLoaded;
    $spellChecker = empty($acymEmailMisspelledLoaded) && !empty($config->get('email_spellcheck'));
    if ($spellChecker) $acymEmailMisspelledLoaded = true;

    if (!empty($options['loadJsInModule'])) {
        if ($spellChecker) echo '<script type="text/javascript" src="'.ACYM_JS.'libraries/email-misspelled.min.js?v='.$version.'"></script>';
        echo '<script type="text/javascript" src="'.ACYM_JS.'module.min.js?v='.$version.'"></script>';
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
        if ($spellChecker) wp_enqueue_style('style_email_spellchecker', ACYM_CSS.'libraries/email-misspelled.min.css?v='.$version);
        wp_enqueue_style('style_acymailing_module', ACYM_CSS.'module.min.css?v='.$version);
    } else {
        if ($spellChecker) acym_addStyle(false, ACYM_CSS.'libraries/email-misspelled.min.css?v='.$version);
        acym_addStyle(false, ACYM_CSS.'module.min.css?v='.$version);
    }
}
