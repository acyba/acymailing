<?php

function acym_getVar($type, $name, $default = null, $source = 'default', $mask = 0)
{
    if (ACYM_J40) {
        if ($mask & ACYM_ALLOWRAW) {
            $type = 'RAW';
        } elseif ($mask & ACYM_ALLOWHTML) {
            $type = 'HTML';
        }

        if (empty($source) || $source === 'default') $source = 'REQUEST';
        $input = JFactory::getApplication()->input;
        $sourceInput = $input->__get($source);
        if (acym_isAdmin()) {
            $result = $sourceInput->get($name, $default, $type);
        } else {
            // When the SEF is active, $_REQUEST is empty as Joomla doesn't populate it anymore
            $result = $sourceInput->get($name, $input->get($name, $default, $type), $type);
        }
    } else {
        $result = JRequest::getVar($name, $default, $source, $type, $mask);
    }

    if (is_string($result) && !($mask & ACYM_ALLOWRAW)) {
        return JComponentHelper::filterText($result);
    }

    return $result;
}

function acym_setVar($name, $value = null, $hash = 'method', $overwrite = true)
{
    if (ACYM_J40) {
        if (empty($hash) || $hash === 'method') $hash = 'REQUEST';
        $input = JFactory::getApplication()->input;
        $hashInput = $input->__get($hash);
        $hashInput->set($name, $value);
        $input->set($name, $value);
    } else {
        JRequest::setVar($name, $value, $hash, $overwrite);
    }
}

function acym_isAdmin()
{
    $acyapp = acym_getGlobal('app');

    if (ACYM_J40) {
        return $acyapp->isClient('administrator');
    } else {
        return $acyapp->isAdmin();
    }
}

function acym_cmsLoaded()
{
    defined('_JEXEC') || die('Restricted access');
}

function acym_isDebug()
{
    return defined('JDEBUG') && JDEBUG;
}

function acym_askLog($current = true, $message = 'ACYM_NOTALLOWED', $type = 'error')
{
    //If the user is not logged in, we just redirect him to the login page....
    $url = 'index.php?option=com_users&view=login';
    if ($current) {
        $url .= '&return='.base64_encode(acym_currentURL());
    }
    acym_redirect($url, $message, $type);
}

function acym_getDefaultConfigValues()
{
    $allPref = [];

    $allPref['from_name'] = acym_getCMSConfig('fromname');
    $allPref['from_email'] = acym_getCMSConfig('mailfrom');
    $allPref['bounce_email'] = acym_getCMSConfig('mailfrom');
    $allPref['sendmail_path'] = acym_getCMSConfig('sendmail');
    $allPref['smtp_port'] = acym_getCMSConfig('smtpport');
    $allPref['smtp_secured'] = acym_getCMSConfig('smtpsecure');
    $allPref['smtp_auth'] = acym_getCMSConfig('smtpauth');
    $allPref['smtp_username'] = acym_getCMSConfig('smtpuser');
    $allPref['smtp_password'] = acym_getCMSConfig('smtppass');
    $allPref['mailer_method'] = acym_getCMSConfig('mailer');
    $smtpinfos = explode(':', acym_getCMSConfig('smtphost'));
    $allPref['smtp_host'] = $smtpinfos[0];
    if (isset($smtpinfos[1])) {
        $allPref['smtp_port'] = $smtpinfos[1];
    }
    if (!in_array($allPref['smtp_secured'], ['tls', 'ssl'])) {
        $allPref['smtp_secured'] = '';
    }
    $allPref['cron_savepath'] = ACYM_LOGS_FOLDER.'report{year}_{month}.log';

    return $allPref;
}

function acym_cmsPermission()
{
    $user = JFactory::getUser();
    if (!$user->authorise('core.admin', ACYM_COMPONENT)) return '';

    $url = 'index.php?option=com_config&view=component&component='.ACYM_COMPONENT.'&return='.urlencode(base64_encode((string)JUri::getInstance()));

    return '
		<div class="cell grid-x margin-bottom-1">
			<label class="cell large-3 medium-5 small-9">'.acym_translation('ACYM_JOOMLA_PERMISSIONS').'</label>
			<div class="cell auto">
				<a class="button button-secondary" href="'.$url.'">'.acym_translation('JTOOLBAR_OPTIONS').'</a>
			</div>
		</div>';
}

function acym_checkVersion($ajax = false)
{
    // Get any error correctly
    ob_start();
    $config = acym_config();
    $url = ACYM_UPDATEURL.'loadUserInformation';

    $paramsForLicenseCheck = [
        'component' => 'acymailing', // Know which product to look at
        'level' => strtolower($config->get('level', 'starter')), // Know which version to look at
        'domain' => rtrim(ACYM_LIVE, '/'), // Tell the user if the automatic features are available for the current installation
        'version' => $config->get('version'), // Tell the user if a newer version is available
        'cms' => ACYM_CMS, // We may delay some new Acy versions depending on the CMS
        'cmsv' => ACYM_CMSV, // Acy isn't available for some versions
    ];


    foreach ($paramsForLicenseCheck as $param => $value) {
        $url .= '&'.$param.'='.urlencode($value);
    }

    $userInformation = acym_fileGetContent($url, 30);
    $warnings = ob_get_clean();
    $result = (!empty($warnings) && acym_isDebug()) ? $warnings : '';

    // Could not load the user information
    if (empty($userInformation) || $userInformation === false) {
        $config->save(['lastlicensecheck' => time()]);
        if ($ajax) {
            acym_sendAjaxResponse(
                '',
                [
                    'content' => '<br/><span style="color:#C10000;">'.acym_translation('ACYM_ERROR_LOAD_FROM_ACYBA').'</span><br/>'.$result,
                    'lastcheck' => acym_date(time(), 'Y/m/d H:i'),
                ],
                false
            );
        } else {
            return '';
        }
    }

    $decodedInformation = json_decode($userInformation, true);

    $newConfig = new stdClass();

    $newConfig->latestversion = $decodedInformation['latestversion'];
    $newConfig->expirationdate = $decodedInformation['expiration'];
    $newConfig->lastlicensecheck = time();
    $config->save($newConfig);

    //check for plugins
    acym_checkPluginsVersion();

    return $newConfig->lastlicensecheck;
}

function acym_triggerCmsHook($method, $args = [], $isAction = true)
{
    if (ACYM_J40) {
        $result = JFactory::getApplication()->triggerEvent($method, $args);
    } else {
        global $acydispatcher;
        if ($acydispatcher === null) {
            $acydispatcher = JDispatcher::getInstance();
        }

        $result = @$acydispatcher->trigger($method, $args);
    }

    if ($isAction) {
        return $result;
    } else {
        return isset($result[0]) ? $result[0] : array_shift($args);
    }
}

function acym_getCmsCaptcha()
{
    $captchaPlugins = acym_loadObjectList(
        'SELECT `element`, `name` 
        FROM #__extensions 
        WHERE `type` = "plugin" 
            AND `folder` = "captcha" 
            AND `enabled` = 1
        ORDER BY `name`'
    );

    // Import plugins to translate their name
    JPluginHelper::importPlugin('captcha');
    $results = [];
    foreach ($captchaPlugins as $captchaPlugin) {
        $results[$captchaPlugin->element] = acym_translation($captchaPlugin->name);
    }

    return $results;
}

function acym_loadCaptcha($captchaPluginName, $id)
{
    JPluginHelper::importPlugin('captcha', $captchaPluginName);
    acym_triggerCmsHook('onInit', [$id]);
    $result = acym_triggerCmsHook('onDisplay', [null, $id, 'class=""']);

    return empty($result[0]) ? '' : $result[0];
}

function acym_checkCaptcha($captchaPluginName)
{
    JPluginHelper::importPlugin('captcha', $captchaPluginName);
    try {
        $result = acym_triggerCmsHook('onCheckAnswer', [null]);
    } catch (Exception $e) {
        acym_enqueueMessage($e->getMessage(), 'error');

        return false;
    }

    return isset($result[0]) ? $result[0] : false;
}
