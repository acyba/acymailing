<?php

use AcyMailing\Helpers\UpdatemeHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

/**
 * @param mixed $default
 *
 * @return mixed
 */
function acym_getVar(string $type, string $name, $default = null, string $source = 'default', int $mask = 0)
{
    if (ACYM_J40) {
        if ($mask & ACYM_ALLOWRAW) {
            $type = 'RAW';
        } elseif ($mask & ACYM_ALLOWHTML) {
            $type = 'HTML';
        }

        if (empty($source) || $source === 'default') $source = 'REQUEST';
        $input = Factory::getApplication()->input;
        $sourceInput = $input->__get($source);
        if ($type === 'file') {
            $result = $sourceInput->files->get($name, $default, 'RAW');
        } elseif (acym_isAdmin()) {
            $result = $sourceInput->get($name, $default, $type);
        } else {
            // When the SEF is active, $_REQUEST is empty as Joomla doesn't populate it anymore
            $result = $sourceInput->get($name, $input->get($name, $default, $type), $type);
        }
    } else {
        $result = JRequest::getVar($name, $default, $source, $type, $mask);
    }

    if (is_string($result) && !($mask & ACYM_ALLOWRAW)) {
        return ComponentHelper::filterText($result);
    }

    return $result;
}

function acym_setVar(string $name, $value = null, string $hash = 'method', bool $overwrite = true): void
{
    if (ACYM_J40) {
        if (empty($hash) || $hash === 'method') {
            $hash = 'REQUEST';
        }
        $input = Factory::getApplication()->input;
        $hashInput = $input->__get($hash);
        $hashInput->set($name, $value);
        $input->set($name, $value);
    } else {
        JRequest::setVar($name, $value, $hash, $overwrite);
    }
}

function acym_isAdmin(): bool
{
    $acyapp = acym_getGlobal('app');

    if (ACYM_J40) {
        return $acyapp->isClient('administrator');
    } else {
        return $acyapp->isAdmin();
    }
}

function acym_cmsLoaded(): void
{
    defined('_JEXEC') || die('Restricted access');
}

function acym_isDebug(): bool
{
    return defined('JDEBUG') && JDEBUG;
}

function acym_askLog(bool $current = true, string $message = 'ACYM_NOTALLOWED', string $type = 'error'): void
{
    //If the user is not logged in, we just redirect him to the login page....
    $url = 'index.php?option=com_users&view=login';
    if ($current) {
        $url .= '&return='.base64_encode(acym_currentURL());
    }
    acym_redirect($url, $message, $type);
}

function acym_getDefaultConfigValues(): array
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

function acym_cmsPermission(): string
{
    $user = Factory::getUser();
    if (!$user->authorise('core.admin', ACYM_COMPONENT)) return '';

    $url = 'index.php?option=com_config&view=component&component='.ACYM_COMPONENT.'&return='.urlencode(base64_encode((string)Uri::getInstance()));

    return '
		<div class="cell grid-x margin-bottom-1">
			<label class="cell large-3 medium-5 small-9">'.acym_translation('ACYM_JOOMLA_PERMISSIONS').'</label>
			<div class="cell auto">
				<a class="button button-secondary" href="'.$url.'">'.acym_translation('JTOOLBAR_OPTIONS').'</a>
			</div>
		</div>';
}

function acym_loadJoomlaPlugin(string $family, ?string $name = null): void
{
    PluginHelper::importPlugin($family, $name);
}

function acym_triggerCmsHook(string $method, array $args = [], bool $isAction = true)
{
    if (ACYM_J40) {
        $result = Factory::getApplication()->triggerEvent($method, $args);
    } else {
        global $acydispatcher;
        if ($acydispatcher === null) {
            $acydispatcher = JEventDispatcher::getInstance();
        }

        $result = @$acydispatcher->trigger($method, $args);
    }

    if ($isAction) {
        return $result;
    } else {
        return $result[0] ?? array_shift($args);
    }
}

function acym_getCmsCaptcha(): array
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
    PluginHelper::importPlugin('captcha');
    $results = [];
    foreach ($captchaPlugins as $captchaPlugin) {
        $results[$captchaPlugin->element] = acym_translation($captchaPlugin->name);
    }

    return $results;
}

function acym_loadCaptcha(string $captchaPluginName, string $id): string
{
    PluginHelper::importPlugin('captcha', $captchaPluginName);
    acym_triggerCmsHook('onInit', [$id]);
    $result = acym_triggerCmsHook('onDisplay', [null, $id, 'class=""']);

    return empty($result[0]) ? '' : $result[0];
}

function acym_checkCaptcha(string $captchaPluginName, ?string $response = null): bool
{
    PluginHelper::importPlugin('captcha', $captchaPluginName);
    try {
        $result = acym_triggerCmsHook('onCheckAnswer', [$response]);
    } catch (Exception $e) {
        acym_enqueueMessage($e->getMessage(), 'error');

        return false;
    }

    $isPassing = $result[0] ?? false;

    return (bool)$isPassing;
}
