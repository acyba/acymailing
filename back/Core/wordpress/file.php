<?php

defined('ABSPATH') || die('Restricted Access');

/**
 * @return bool|null|string
 */
function acym_fileGetContent(string $url, int $timeout = 10)
{
    if (strpos($url, '_custom.ini') !== false && !file_exists($url)) {
        return '';
    }

    ob_start();
    $data = '';

    $allowUrlFopen = ini_get('allow_url_fopen');

    if (function_exists('file_get_contents') && (!empty($allowUrlFopen) || strpos($url, 'http') !== 0)) {
        $options = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ];
        if (!empty($timeout)) {
            $options['http']['timeout'] = $timeout;
        }
        $streamContext = stream_context_create($options);

        $data = file_get_contents($url, false, $streamContext);
    }

    if (empty($data) && strpos($url, 'http') === 0 && class_exists('WP_Http') && method_exists('WP_Http', 'request')) {
        $args = ['timeout' => $timeout];
        $request = new WP_Http();
        $data = $request->request($url, $args);
        $data = (empty($data) || !is_array($data) || empty($data['body'])) ? '' : $data['body'];
    }

    if (empty($data) && strpos($url, 'http') !== 0) {
        $data = acym_getInternalFileContents($url);
    }
    $warnings = ob_get_clean();

    if (acym_isDebug()) {
        echo esc_html($warnings);
    }

    return $data;
}

function acym_extractArchive(string $archive, string $destination): bool
{
    if (substr($archive, strlen($archive) - 4) !== '.zip') {
        return false;
    }

    WP_Filesystem();

    return true === unzip_file($archive, $destination);
}

function acym_deleteFolder(string $path, bool $report = true): bool
{
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once ABSPATH.'wp-admin/includes/file.php';
        WP_Filesystem();
    }

    $path = acym_cleanPath($path);
    if (!is_dir($path)) {
        if ($report) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_IS_NOT_A_FOLDER', $path), 'error');
        }

        return false;
    }

    if (!empty($wp_filesystem) && $wp_filesystem->rmdir($path, true)) {
        return true;
    }

    if ($report) {
        acym_enqueueMessage(acym_translationSprintf('ACYM_COULD_NOT_DELETE_FOLDER', $path), 'error');
    }

    return false;
}

function acym_createFolder(string $path = ''): bool
{
    $path = acym_cleanPath($path);
    if (empty($path)) {
        return false;
    }

    if (file_exists($path)) {
        return true;
    }

    return wp_mkdir_p($path);
}

function acym_uploadFile(string $src, string $dest): bool
{
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once ABSPATH.'wp-admin/includes/file.php';
        WP_Filesystem();
    }

    $dest = acym_cleanPath($dest);

    $baseDir = dirname($dest);
    if (!file_exists($baseDir)) {
        acym_createFolder($baseDir);
    }

    if (!is_uploaded_file($src)) {
        acym_enqueueMessage(acym_translation('ACYM_FILE_REJECTED_SAFETY_REASON'), 'error');

        return false;
    }

    if (
        !empty($wp_filesystem)
        && $wp_filesystem->is_writable($baseDir)
        && $wp_filesystem->move($src, $dest, true)
    ) {
        // Short circuit to prevent file permission errors
        if (acym_chmod($dest, FS_CHMOD_FILE)) {
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
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once ABSPATH.'wp-admin/includes/file.php';
        WP_Filesystem();
    }

    if (!empty($path)) {
        $src = acym_cleanPath($path.'/'.$src);
        $dest = acym_cleanPath($path.'/'.$dest);
    }

    if (empty($wp_filesystem) || !$wp_filesystem->is_readable($src)) {
        acym_enqueueMessage(acym_translationSprintf('ACYM_COULD_NOT_FIND_FILE_SOURCE_PERMISSION', $src), 'error');

        return false;
    }

    if (!$wp_filesystem->move($src, $dest, true)) {
        acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_MOVE_FILE'), 'error');

        return false;
    }

    return true;
}

function acym_isWritable(string $path = ''): bool
{
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once ABSPATH.'wp-admin/includes/file.php';
        WP_Filesystem();
    }

    return $wp_filesystem->is_writable($path);
}

function acym_chmod(string $path, int $mode): bool
{
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once ABSPATH.'wp-admin/includes/file.php';
        WP_Filesystem();
    }

    $applied = $wp_filesystem instanceof \WP_Filesystem_Base && $wp_filesystem->chmod($path, $mode);

    clearstatcache(true, $path);
    $currentMode = @fileperms($path);

    if ($applied && $currentMode !== false && ($currentMode & 0777) === $mode) {
        return true;
    }

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- Fallback
    return @chmod($path, $mode);
}

function acym_deleteFile(string $file): bool
{
    if (!file_exists($file)) {
        return true;
    }

    return wp_delete_file($file);
}


function acym_getInternalFileContents(string $path)
{
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once ABSPATH.'wp-admin/includes/file.php';
        WP_Filesystem();
    }

    if ($wp_filesystem instanceof \WP_Filesystem_Base && $wp_filesystem->exists($path)) {
        return $wp_filesystem->get_contents($path);
    }

    return false;
}
