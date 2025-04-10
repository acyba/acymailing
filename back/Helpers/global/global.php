<?php

use AcyMailing\Classes\ConfigurationClass;

function acydump($arg, $ajax = false, array $options = [])
{
    $indent = $options['indent'] ?? true;
    $htmlentities = $options['htmlentities'] ?? true;

    ob_start();
    if (is_object($arg) && isset($arg->config)) {
        $safeArg = clone $arg;
        unset($safeArg->config);
        var_dump($safeArg);
    } else {
        var_dump($arg);
    }
    $result = ob_get_clean();

    if ($ajax) {
        if (($options['clear_file'] ?? false) === true) {
            file_put_contents(ACYM_ROOT.'acydebug.txt', '');
        }

        file_put_contents(ACYM_ROOT.'acydebug.txt', $result, FILE_APPEND);
    } else {
        $style = $indent ? 'margin-left: 220px;' : '';
        echo '<pre style="'.$style.'">';
        echo $htmlentities ? htmlentities($result) : $result;
        echo '</pre>';
    }
}

function acym_debug(bool $file = false, bool $indent = true)
{
    $debug = debug_backtrace();
    $takenPath = [];
    foreach ($debug as $step) {
        if (empty($step['file']) || empty($step['line'])) continue;
        $takenPath[] = $step['file'].' => '.$step['line'];
    }
    acydump(implode("\n", $takenPath), $file, ['indent' => $indent]);
}

function acym_config(bool $reload = false): ConfigurationClass
{
    static $configClass = null;
    if ($configClass === null || $reload) {
        $configClass = new ConfigurationClass();
        $configClass->load();
    }

    return $configClass;
}

/**
 * @param mixed  $messages The messages displayed, either a string or an array or strings
 * @param string $type     Type of the message: success, error, warning, info
 * @param bool   $close    Allow or not to close the message zone
 */
function acym_display($messages, $type = 'success', $close = true)
{
    if (empty($messages)) return;
    if (!is_array($messages)) $messages = [$messages];
    $config = acym_config();
    $remindme = json_decode($config->get('remindme', '[]'), true);
    foreach ($messages as $id => $message) {
        if (strpos($message, 'acym__do__not__remindme') !== false) {
            preg_match('/title="(.*)"/Ui', $message, $matches);
            if (in_array($matches[1], $remindme)) continue;
        }

        echo '<div class="acym__message grid-x acym__message__'.$type.'">';

        if (is_array($message)) $message = implode('</div><div>', $message);

        echo '<div class="cell auto"><div>'.$message.'</div></div>';

        if ($close && strpos($message, 'acym__do__not__remindme') === false) {
            echo '<i data-id="'.acym_escape($id).'" class="cell shrink acym__message__close acymicon-close"></i>';
        }
        echo '</div>';
    }
}

function acym_increasePerf()
{
    // Increase the max exec time to be able to handle long processes such as the send process
    $maxExecutionTime = ini_get('max_execution_time');
    if ($maxExecutionTime < 600) {
        @ini_set('max_execution_time', 600);
    }

    // This is for big regex, the default value is 100 000
    @ini_set('pcre.backtrack_limit', 1000000);
}

function acym_session()
{
    if (empty(session_id()) || session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
}

function acym_getCID(string $field = ''): int
{
    $oneResult = acym_getVar('array', 'cid', [], '');
    $oneResult = intval(reset($oneResult));
    if (!empty($oneResult) || empty($field)) {
        return $oneResult;
    }

    $oneResult = acym_getVar('int', $field, 0, '');

    return intval($oneResult);
}

function acym_header($header, $replace = true)
{
    if (headers_sent()) return;
    header($header, $replace);
}

function acym_getSocialMedias()
{
    return json_decode(ACYM_SOCIAL_MEDIA, true);
}

function acym_isAcyCheckerInstalled()
{
    $installed = ACYM_CMS === 'joomla' && acym_isExtensionActive('com_acychecker');
    $installed = $installed || (ACYM_CMS === 'wordpress' && acym_isExtensionActive('acychecker/acychecker.php'));

    return $installed;
}

function acym_getErrorLogFilename(string $prefix = ''): string
{
    if (!empty($prefix)) {
        $prefix .= '_';
    }

    return $prefix.'errors.log';
}

function acym_logError(string $message, string $prefix = '', int $maxLines = 0)
{
    $reportPath = acym_getLogPath(acym_getErrorLogFilename($prefix), true);

    if ($maxLines > 0 && file_exists($reportPath)) {
        $lines = file($reportPath);
        if (!empty($lines)) {
            $lines = array_slice($lines, -$maxLines);
            file_put_contents($reportPath, implode("\n", $lines));
        }
    }

    $lr = "\r\n";
    file_put_contents(
        $reportPath,
        $lr.acym_getDate(time()).': '.$message,
        FILE_APPEND
    );
}

function acym_isLogFileErrorExist($prefix = ''): bool
{
    $reportPath = acym_getLogPath(acym_getErrorLogFilename($prefix));

    return file_exists($reportPath);
}

function acym_getJsonData(): array
{
    $rawData = file_get_contents('php://input');
    $decodedData = @json_decode($rawData, true);

    return empty($decodedData) ? [] : $decodedData;
}

function displayFreeTrialMessage()
{
    if (!acym_isAdmin()) {
        return;
    }

    $config = acym_config();

    if ($config->get('isTrial', 0) != 1) {
        return;
    }

    $expirationDate = $config->get('expirationdate', 0);
    $href = ACYM_ACYMAILING_WEBSITE.'account/license';
    if ($config->get('expirationdate', 0) > time()) {
        $days = ceil(($expirationDate - time()) / 86400);
        $buttonFullAccess = '<a target="_blank" class="button button-secondary margin-left-1 shrink margin-bottom-0" href="'.$href.'">'.acym_translation(
                'ACYM_GET_FULL_ACCESS'
            ).'</a>';
        $message = '<span class="shrink">'.acym_translationSprintf('ACYM_FREE_TRIAL_EXPIRATION_X_DAYS', $days).'</span>';
        $type = 'warning';
    } else {
        $buttonFullAccess = '<a target="_blank" class="button acym__button__upgrade margin-left-1 shrink margin-bottom-0" href="'.$href.'">'.acym_translation(
                'ACYM_GET_FULL_ACCESS'
            ).'</a>';
        $message = '<span class="shrink">'.acym_translation('ACYM_FREE_TRIAL_ENDED').'</span>';
        $type = 'error';
    }
    acym_enqueueMessage('<div class="cell grid-x acym_vcenter">'.$message.$buttonFullAccess.'</div>', $type, false);
}


function acym_removeDashboardNotification(string $name): void
{
    $config = acym_config();
    $existingNotifications = json_decode($config->get('dashboard_notif', '[]'), true) ?? [];

    foreach ($existingNotifications as $key => $existingNotification) {
        if (is_array($existingNotification) && $existingNotification['name'] === $name) {
            unset($existingNotifications[$key]);
        }
    }

    $config->save(['dashboard_notif' => json_encode($existingNotifications)], false);
}
