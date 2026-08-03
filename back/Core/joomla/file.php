<?php

use Joomla\Archive\Archive;
use Joomla\CMS\Http\HttpFactory;

/**
 * Returns the url content or false if couldn't get it
 *
 * @return bool|null|string
 */
function acym_fileGetContent(string $url, int $timeout = 10)
{
    ob_start();
    // use the Joomla way first
    $data = '';

    if (!ACYM_J40) {
        if (function_exists('file_get_contents')) {
            if (!empty($timeout)) {
                ini_set('default_socket_timeout', $timeout);
            }
            $streamContext = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            $data = @file_get_contents($url, false, $streamContext);
        }
    }

    if (empty($data)) {
        $http = HttpFactory::getHttp();
        try {
            $response = $http->get($url, [], $timeout);
        } catch (RuntimeException $e) {
            $response = null;
        }

        if ($response !== null && $response->code === 200) {
            $data = $response->body;
        }
    }

    if (empty($data) && function_exists('curl_exec') && filter_var($url, FILTER_VALIDATE_URL)) {
        $conn = curl_init($url);
        curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($conn, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($timeout)) {
            curl_setopt($conn, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($conn, CURLOPT_CONNECTTIMEOUT, $timeout);
        }

        $data = curl_exec($conn);
        if ($data === false) {
            echo curl_error($conn);
        }
    }

    if (empty($data) && function_exists('fopen') && function_exists('stream_get_contents')) {
        $handle = fopen($url, 'r');
        if (!empty($handle)) {
            if (!empty($timeout)) {
                stream_set_timeout($handle, $timeout);
            }
            $data = stream_get_contents($handle);
        }
    }
    $warnings = ob_get_clean();

    if (acym_isDebug()) {
        echo $warnings;
    }

    return $data;
}

function acym_extractArchive(string $archive, string $destination): bool
{
    if (ACYM_J40) {
        $archiveManager = new Archive();

        return $archiveManager->extract($archive, $destination);
    } else {
        return JArchive::extract($archive, $destination);
    }
}

function acym_deleteFolder(string $path, bool $report = true): bool
{
    $path = acym_cleanPath($path);
    if (!is_dir($path)) {
        if ($report) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_IS_NOT_A_FOLDER', $path), 'error');
        }

        return false;
    }

    $files = acym_getFiles($path, '.', false, false, [], []);
    if (!empty($files)) {
        foreach ($files as $oneFile) {
            if (!acym_deleteFile($path.DS.$oneFile)) {
                return false;
            }
        }
    }

    $folders = acym_getFolders($path, '.', false, false, []);
    if (!empty($folders)) {
        foreach ($folders as $oneFolder) {
            if (!acym_deleteFolder($path.DS.$oneFolder, $report)) {
                return false;
            }
        }
    }

    if (@rmdir($path)) {
        return true;
    } else {
        if ($report) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_COULD_NOT_DELETE_FOLDER', $path), 'error');
        }

        return false;
    }
}

function acym_createFolder(string $path = ''): bool
{
    $path = acym_cleanPath($path);
    if (file_exists($path)) {
        return true;
    }

    $origmask = @umask(0);
    $ret = @mkdir($path, 0755, true);
    @umask($origmask);

    return $ret;
}

function acym_uploadFile(string $src, string $dest): bool
{
    $dest = acym_cleanPath($dest);

    $baseDir = dirname($dest);
    if (!file_exists($baseDir)) {
        acym_createFolder($baseDir);
    }

    if (is_writeable($baseDir) && move_uploaded_file($src, $dest)) {
        // Short circuit to prevent file permission errors
        if (acym_chmod($dest, 0644)) {
            return true;
        } else {
            acym_enqueueMessage(acym_translation('ACYM_FILE_REJECTED_SAFETY_REASON'), 'error');
        }
    } else {
        acym_enqueueMessage(acym_translationSprintf('ACYM_COULD_NOT_UPLOAD_FILE_PERMISSION', $baseDir), 'error');
    }

    return false;
}

function acym_moveFile(string $src, string $dest, string $path = ''): bool
{
    if (!empty($path)) {
        $src = acym_cleanPath($path.'/'.$src);
        $dest = acym_cleanPath($path.'/'.$dest);
    }

    if (!is_readable($src)) {
        acym_enqueueMessage(acym_translationSprintf('ACYM_COULD_NOT_FIND_FILE_SOURCE_PERMISSION', $src), 'error');

        return false;
    }

    if (!@rename($src, $dest)) {
        acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_MOVE_FILE'), 'error');

        return false;
    }

    return true;
}

function acym_isWritable(string $path = ''): bool
{
    return is_writable($path);
}

function acym_chmod(string $path, int $mode): bool
{
    return @chmod($path, $mode);
}

function acym_deleteFile(string $file): bool
{
    if (!file_exists($file)) {
        return true;
    }

    return unlink($file);
}

function acym_getInternalFileContents(string $path)
{
    $handle = fopen($path, 'r');
    stream_set_timeout($handle, 10);

    return stream_get_contents($handle);
}
