<?php

use AcyMailing\Helpers\ImageHelper;

/**
 * Function to return the number of bytes of a val like 2M
 */
function acym_bytes($val)
{
    $val = trim($val);
    if (empty($val)) {
        return 0;
    }
    $last = strtolower($val[strlen($val) - 1]);
    switch ($last) {
        case 'g':
            $val = intval($val) * 1073741824;
            break;
        case 'm':
            $val = intval($val) * 1048576;
            break;
        case 'k':
            $val = intval($val) * 1024;
            break;
    }

    return (int)$val;
}

//Create a dir and an index.html file inside
function acym_createDir($dir, $report = true, $secured = false)
{
    if (is_dir($dir)) return true;

    $indexhtml = '<html><body bgcolor="#FFFFFF"></body></html>';

    //Create the directory with an index file inside
    try {
        $status = acym_createFolder($dir);
    } catch (Exception $e) {
        $status = false;
    }

    if (!$status) {
        if ($report) {
            acym_display('Could not create the directory '.$dir, 'error');
        }

        return false;
    }

    try {
        $status = acym_writeFile($dir.DS.'index.html', $indexhtml);
    } catch (Exception $e) {
        $status = false;
    }

    if (!$status) {
        if ($report) {
            acym_display('Could not create the file '.$dir.DS.'index.html', 'error');
        }
    }

    if ($secured) {
        try {
            $htaccess = 'Order deny,allow'."\r\n".'Deny from all';
            $status = acym_writeFile($dir.DS.'.htaccess', $htaccess);
        } catch (Exception $e) {
            $status = false;
        }

        if (!$status) {
            if ($report) {
                acym_display('Could not create the file '.$dir.DS.'.htaccess', 'error');
            }
        }
    }

    return $status;
}

function acym_importFile($file, $uploadPath, $onlyPict, $maxwidth = '')
{
    // Check the token... no import without token!
    acym_checkToken();

    $config = acym_config();
    $additionalMsg = '';

    if ($file['error'] > 0) {
        $file['error'] = intval($file['error']);
        if ($file['error'] > 8) {
            $file['error'] = 0;
        }

        $phpFileUploadErrors = [
            0 => 'Unknown error',
            1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk',
            8 => 'A PHP extension stopped the file upload',
        ];

        acym_enqueueMessage(acym_translation_sprintf('ACYM_ERROR_UPLOADING_FILE_X', $phpFileUploadErrors[$file['error']]), 'error');

        return false;
    }

    acym_createDir($uploadPath, true);

    if (!is_writable($uploadPath)) {
        @chmod($uploadPath, '0755');
        if (!is_writable($uploadPath)) {
            acym_display(acym_translation_sprintf('ACYM_WRITABLE_FOLDER', $uploadPath), 'error');

            return false;
        }
    }

    if ($onlyPict) {
        $allowedExtensions = ['png', 'jpeg', 'jpg', 'gif', 'ico', 'bmp'];
    } else {
        $allowedExtensions = explode(',', $config->get('allowed_files'));
    }

    // We don't allow to upload anything else than a picture extension
    if (!preg_match('#\.('.implode('|', $allowedExtensions).')$#Ui', $file['name'], $extension)) {
        $ext = substr($file['name'], strrpos($file['name'], '.') + 1);
        acym_display(
            acym_translation_sprintf(
                'ACYM_ACCEPTED_TYPE',
                acym_escape($ext),
                implode(', ', $allowedExtensions)
            ),
            'error'
        );

        return false;
    }

    // We will never allow some files to be uploaded...
    // This should never happen... only if there is an hack tentative so no need to translate this error message
    if (preg_match('#\.(php.?|.?htm.?|pl|py|jsp|asp|sh|cgi)#Ui', $file['name'])) {
        acym_display(
            'This extension name is blocked by the system regardless your configuration for security reasons',
            'error'
        );

        return false;
    }

    // We remove all dots or space from the file name to avoid the double extension security issue and the fact some mail clients don't like spaces
    $file['name'] = preg_replace(
            '#[^a-z0-9]#i',
            '_',
            strtolower(substr($file['name'], 0, strrpos($file['name'], '.')))
        ).'.'.$extension[1];

    if ($onlyPict) {
        // Extra security: we make sure we only have a valid picture format
        $imageSize = @getimagesize($file['tmp_name']);
        if (empty($imageSize)) {
            acym_display(acym_translation('ACYM_INVALID_IMAGE'), 'error');

            return false;
        }
    }

    if (file_exists($uploadPath.DS.$file['name'])) {
        $i = 1;
        $nameFile = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file['name']);
        $ext = substr($file['name'], strrpos($file['name'], '.') + 1);
        while (file_exists($uploadPath.DS.$nameFile.'_'.$i.'.'.$ext)) {
            $i++;
        }

        $file['name'] = $nameFile.'_'.$i.'.'.$ext;
        $additionalMsg = '<br />'.acym_translation_sprintf('ACYM_FILE_RENAMED', $file['name']);
        if ($onlyPict) {
            $additionalMsg .= '<br /><a style="color: blue; cursor: pointer;" onclick="confirmBox(\'rename\', \''.$file['name'].'\', \''.$nameFile.'.'.$ext.'\')">'.acym_translation(
                    'ACYM_RENAME_OR_REPLACE'
                ).'</a>';
        }
    }

    if (!acym_uploadFile($file['tmp_name'], rtrim($uploadPath, DS).DS.$file['name'])) {
        if (!move_uploaded_file($file['tmp_name'], rtrim($uploadPath, DS).DS.$file['name'])) {
            acym_display(
                acym_translation_sprintf(
                    'ACYM_FAIL_UPLOAD',
                    '<b><i>'.acym_escape($file['tmp_name']).'</i></b>',
                    '<b><i>'.acym_escape(rtrim($uploadPath, DS).DS.$file['name']).'</i></b>'
                ),
                'error'
            );

            return false;
        }
    }

    if (!empty($maxwidth) || ($onlyPict && $imageSize[0] > 1000)) {
        $imageHelper = new ImageHelper();
        if ($imageHelper->available()) {
            $imageHelper->maxHeight = 9999;
            if (empty($maxwidth)) {
                $imageHelper->maxWidth = 700;
            } else {
                $imageHelper->maxWidth = $maxwidth;
            }
            $message = 'ACYM_IMAGE_RESIZED';
            $imageHelper->destination = $uploadPath;
            $thumb = $imageHelper->generateThumbnail(rtrim($uploadPath, DS).DS.$file['name']);
            $resize = acym_moveFile($thumb['file'], $uploadPath.DS.$file['name']);
            if ($thumb) {
                $additionalMsg .= '<br />'.acym_translation($message);
            }
        }
    }
    acym_enqueueMessage(acym_translation('ACYM_SUCCESS_FILE_UPLOAD').$additionalMsg, 'success');

    return $file['name'];
}

function acym_inputFile($name, $value = '', $id = '', $class = '', $attributes = '')
{
    $return = '<div class="cell acym__input__file '.$class.' grid-x"><input '.$attributes.' style="display: none" id="'.$id.'" type="file" name="'.$name.'"><button type="button" class=" acym__button__file button button-secondary cell shrink">'.acym_translation('ACYM_CHOOSE_FILE').'</button><span class="cell shrink margin-left-2">';
    $return .= empty($value) ? acym_translation('ACYM_NO_FILE_CHOSEN') : $value;
    $return .= '</span></div>';

    return $return;
}

function acym_getFilesFolder($ignoreVariables = false)
{
    $config = acym_config();
    $uploadFolder = $config->get('uploadfolder', ACYM_UPLOAD_FOLDER);
    if ($ignoreVariables) $uploadFolder = str_replace(['{userid}', '{groupname}'], '', $uploadFolder);
    $uploadFolder = trim($uploadFolder, '/');

    if (strpos($uploadFolder, '{userid}') !== false) {
        $uploadFolder = str_replace('{userid}', acym_currentUserId(), $uploadFolder);
    }

    $uploadFolder = acym_replaceGroupTags($uploadFolder);

    return $uploadFolder;
}

/**
 * Find all sub folders...
 * Return an array of all folders ordered properly
 */
function acym_generateArborescence($folders)
{
    //Recursive algorythm to catch subfolders
    $folderList = [];
    foreach ($folders as $folder) {
        $folderPath = acym_cleanPath(ACYM_ROOT.trim(str_replace('/', DS, trim($folder)), DS));
        if (!file_exists($folderPath)) {
            acym_createDir($folderPath);
        }
        $subFolders = acym_listFolderTree($folderPath, '', 15);
        $folderList[$folder] = [];
        foreach ($subFolders as $oneFolder) {
            $subFolder = str_replace(ACYM_ROOT, '', $oneFolder['relname']);
            $subFolder = str_replace(DS, '/', $subFolder);
            $folderList[$folder][$subFolder] = ltrim($subFolder, '/');
        }
        $folderList[$folder] = array_unique($folderList[$folder]);
    }

    return $folderList;
}

function acym_makeSafeFile($file)
{
    $file = rtrim($file, '.');
    $regex = ['#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#', '#^\.#'];

    return trim(preg_replace($regex, '', $file));
}

function acym_deleteFolder($path, $report = true)
{
    $path = acym_cleanPath($path);
    if (!is_dir($path)) {
        if ($report) acym_enqueueMessage(acym_translation_sprintf('ACYM_IS_NOT_A_FOLDER', $path), 'error');

        return false;
    }

    $files = acym_getFiles($path, '.', false, false, [], []);
    if (!empty($files)) {
        foreach ($files as $oneFile) {
            if (!acym_deleteFile($path.DS.$oneFile, $report)) {
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
        $ret = true;
    } else {
        if ($report) acym_enqueueMessage(acym_translation_sprintf('ACYM_COULD_NOT_DELETE_FOLDER', $path), 'error');
        $ret = false;
    }

    return $ret;
}

function acym_createFolder($path = '', $mode = 0755)
{
    $path = acym_cleanPath($path);
    if (file_exists($path)) {
        return true;
    }

    $origmask = @umask(0);
    $ret = @mkdir($path, $mode, true);
    @umask($origmask);

    return $ret;
}

function acym_getFolders($path, $filter = '.', $recurse = false, $full = false, $exclude = ['.svn', 'CVS', '.DS_Store', '__MACOSX'], $excludefilter = ['^\..*'])
{
    $path = acym_cleanPath($path);

    if (!is_dir($path)) {
        acym_enqueueMessage(acym_translation_sprintf('ACYM_IS_NOT_A_FOLDER', $path), 'error');

        return [];
    }

    if (count($excludefilter)) {
        $excludefilter_string = '/('.implode('|', $excludefilter).')/';
    } else {
        $excludefilter_string = '';
    }

    $arr = acym_getItems($path, $filter, $recurse, $full, $exclude, $excludefilter_string, false);
    asort($arr);

    return array_values($arr);
}

function acym_getFiles($path, $filter = '.', $recurse = false, $full = false, $exclude = ['.svn', 'CVS', '.DS_Store', '__MACOSX'], $excludefilter = ['^\..*', '.*~'], $naturalSort = false)
{
    $path = acym_cleanPath($path);

    if (!is_dir($path)) {
        acym_enqueueMessage(acym_translation_sprintf('ACYM_IS_NOT_A_FOLDER', $path), 'error');

        return false;
    }

    if (count($excludefilter)) {
        $excludefilter_string = '/('.implode('|', $excludefilter).')/';
    } else {
        $excludefilter_string = '';
    }

    $arr = acym_getItems($path, $filter, $recurse, $full, $exclude, $excludefilter_string, true);

    if ($naturalSort) {
        natsort($arr);
    } else {
        asort($arr);
    }

    return array_values($arr);
}

function acym_getItems($path, $filter, $recurse, $full, $exclude, $excludefilter_string, $findfiles)
{
    $arr = [];

    if (!($handle = @opendir($path))) {
        return $arr;
    }

    while (($file = readdir($handle)) !== false) {
        if ($file == '.' || $file == '..' || in_array($file, $exclude) || (!empty($excludefilter_string) && preg_match(
                    $excludefilter_string,
                    $file
                ))) {
            continue;
        }
        $fullpath = $path.'/'.$file;

        $isDir = is_dir($fullpath);

        if (($isDir xor $findfiles) && preg_match("/$filter/", $file)) {
            if ($full) {
                $arr[] = $fullpath;
            } else {
                $arr[] = $file;
            }
        }

        if ($isDir && $recurse) {
            if (is_int($recurse)) {
                $arr = array_merge(
                    $arr,
                    acym_getItems(
                        $fullpath,
                        $filter,
                        $recurse - 1,
                        $full,
                        $exclude,
                        $excludefilter_string,
                        $findfiles
                    )
                );
            } else {
                $arr = array_merge(
                    $arr,
                    acym_getItems(
                        $fullpath,
                        $filter,
                        $recurse,
                        $full,
                        $exclude,
                        $excludefilter_string,
                        $findfiles
                    )
                );
            }
        }
    }

    closedir($handle);

    return $arr;
}

function acym_copyFolder($src, $dest, $path = '', $force = false, $use_streams = false)
{

    if ($path) {
        $src = acym_cleanPath($path.'/'.$src);
        $dest = acym_cleanPath($path.'/'.$dest);
    }

    $src = rtrim($src, DIRECTORY_SEPARATOR);
    $dest = rtrim($dest, DIRECTORY_SEPARATOR);

    if (!file_exists($src)) {
        acym_enqueueMessage(acym_translation_sprintf('ACYM_FOLDER_DOES_NOT_EXIST', $src), 'error');

        return false;
    }

    if (file_exists($dest) && !$force) {
        acym_enqueueMessage(acym_translation_sprintf('ACYM_FOLDER_ALREADY_EXIST', $dest), 'error');

        return true;
    }

    if (!acym_createFolder($dest)) {
        acym_enqueueMessage(acym_translation('ACYM_CANNOT_CREATE_DESTINATION_FOLDER'), 'error');

        return false;
    }

    if (!($dh = @opendir($src))) {
        acym_enqueueMessage(acym_translation('ACYM_CANNOT_OPEN_SOURCE_FOLDER'), 'error');

        return false;
    }

    while (($file = readdir($dh)) !== false) {
        $sfid = $src.'/'.$file;
        $dfid = $dest.'/'.$file;

        switch (filetype($sfid)) {
            case 'dir':
                if ($file != '.' && $file != '..') {
                    $ret = acym_copyFolder($sfid, $dfid, null, $force, $use_streams);

                    if ($ret !== true) {
                        return $ret;
                    }
                }
                break;

            case 'file':
                if (!@copy($sfid, $dfid)) {
                    acym_enqueueMessage(acym_translation_sprintf('ACYM_COPY_FILE_FAILED_PERMISSION', $sfid), 'error');

                    return false;
                }
                break;
        }
    }

    return true;
}

function acym_listFolderTree($path, $filter, $maxLevel = 3, $level = 0, $parent = 0)
{
    $dirs = [];

    if ($level == 0) {
        $GLOBALS['acym_folder_tree_index'] = 0;
    }

    if ($level < $maxLevel) {
        $folders = acym_getFolders($path, $filter);

        foreach ($folders as $name) {
            $id = ++$GLOBALS['acym_folder_tree_index'];
            $fullName = acym_cleanPath($path.'/'.$name);
            $dirs[] = [
                'id' => $id,
                'parent' => $parent,
                'name' => $name,
                'fullname' => $fullName,
                'relname' => str_replace(ACYM_ROOT, '', $fullName),
            ];
            $dirs2 = acym_listFolderTree($fullName, $filter, $maxLevel, $level + 1, $id);
            $dirs = array_merge($dirs, $dirs2);
        }
    }

    return $dirs;
}

function acym_deleteFile($file, $report = true)
{
    $file = acym_cleanPath($file);
    if (!is_file($file)) {
        if ($report) acym_enqueueMessage(acym_translation_sprintf('ACYM_IS_NOT_A_FILE', $file), 'error');

        return false;
    }

    @chmod($file, 0777);

    if (!@unlink($file)) {
        $filename = basename($file);
        if ($report) acym_enqueueMessage(acym_translation_sprintf('ACYM_FAILED_DELETE', $filename), 'error');

        return false;
    }

    return true;
}

function acym_writeFile($file, $buffer, $use_streams = false)
{
    if (!file_exists(dirname($file)) && acym_createFolder(dirname($file)) == false) {
        return false;
    }

    $file = acym_cleanPath($file);

    return is_int(file_put_contents($file, $buffer));
}

function acym_moveFile($src, $dest, $path = '', $use_streams = false)
{
    if (!empty($path)) {
        $src = acym_cleanPath($path.'/'.$src);
        $dest = acym_cleanPath($path.'/'.$dest);
    }

    if (!is_readable($src)) {
        acym_enqueueMessage(acym_translation_sprintf('ACYM_COULD_NOT_FIND_FILE_SOURCE_PERMISSION', $src), 'error');

        return false;
    }

    if (!@rename($src, $dest)) {
        acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_MOVE_FILE'), 'error');

        return false;
    }

    return true;
}

function acym_uploadFile($src, $dest)
{
    $dest = acym_cleanPath($dest);

    $baseDir = dirname($dest);
    if (!file_exists($baseDir)) {
        acym_createFolder($baseDir);
    }

    if (is_writeable($baseDir) && move_uploaded_file($src, $dest)) {
        // Short circuit to prevent file permission errors
        if (@chmod($dest, octdec('0644'))) {
            return true;
        } else {
            acym_enqueueMessage(acym_translation('ACYM_FILE_REJECTED_SAFETY_REASON'), 'error');
        }
    } else {
        acym_enqueueMessage(acym_translation_sprintf('ACYM_COULD_NOT_UPLOAD_FILE_PERMISSION', $baseDir), 'error');
    }

    return false;
}

function acym_copyFile($src, $dest, $path = null, $use_streams = false)
{
    if ($path) {
        $src = acym_cleanPath($path.'/'.$src);
        $dest = acym_cleanPath($path.'/'.$dest);
    }

    if (!is_readable($src)) {
        acym_enqueueMessage(acym_translation_sprintf('ACYM_COULD_NOT_FIND_FILE_SOURCE_PERMISSION', $src), 'error');

        return false;
    }

    if (!@copy($src, $dest)) {
        acym_enqueueMessage(acym_translation_sprintf('ACYM_COULD_NOT_COPY_FILE_X_TO_X', $src, $dest), 'error');

        return false;
    }

    return true;
}

function acym_fileGetExt($file)
{
    $endPos = strpos($file, '?');
    if (false !== $endPos) {
        $file = substr($file, 0, $endPos);
    }

    $dot = strrpos($file, '.');
    if (false === $dot) return '';

    return substr($file, $dot + 1);
}

function acym_cleanPath($path, $ds = DIRECTORY_SEPARATOR)
{
    $path = trim($path);

    if (empty($path)) {
        $path = ACYM_ROOT;
    } elseif (($ds == '\\') && substr($path, 0, 2) == '\\\\') {
        $path = "\\".preg_replace('#[/\\\\]+#', $ds, $path);
    } else {
        $path = preg_replace('#[/\\\\]+#', $ds, $path);
    }

    return $path;
}

function acym_createArchive($name, $files)
{
    $contents = [];
    $ctrldir = [];

    $timearray = getdate();
    $dostime = (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) | ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
    $dtime = dechex($dostime);
    $hexdtime = chr(hexdec($dtime[6].$dtime[7])).chr(hexdec($dtime[4].$dtime[5])).chr(hexdec($dtime[2].$dtime[3])).chr(
            hexdec($dtime[0].$dtime[1])
        );

    foreach ($files as $file) {
        $data = $file['data'];
        $filename = str_replace('\\', '/', $file['name']);

        $fr = "\x50\x4b\x03\x04\x14\x00\x00\x00\x08\x00".$hexdtime;

        $unc_len = strlen($data);
        $crc = crc32($data);
        $zdata = gzcompress($data);
        $zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2);
        $c_len = strlen($zdata);

        $fr .= pack('V', $crc).pack('V', $c_len).pack('V', $unc_len).pack('v', strlen($filename)).pack(
                'v',
                0
            ).$filename.$zdata;

        $old_offset = strlen(implode('', $contents));
        $contents[] = $fr;

        $cdrec = "\x50\x4b\x01\x02\x00\x00\x14\x00\x00\x00\x08\x00".$hexdtime;
        $cdrec .= pack('V', $crc).pack('V', $c_len).pack('V', $unc_len).pack('v', strlen($filename)).pack('v', 0).pack(
                'v',
                0
            ).pack('v', 0).pack('v', 0).pack('V', 32).pack('V', $old_offset).$filename;

        $ctrldir[] = $cdrec;
    }

    $data = implode('', $contents);
    $dir = implode('', $ctrldir);
    $buffer = $data.$dir."\x50\x4b\x05\x06\x00\x00\x00\x00".pack('v', count($ctrldir)).pack('v', count($ctrldir)).pack(
            'V',
            strlen($dir)
        ).pack('V', strlen($data))."\x00\x00";

    return acym_writeFile($name.'.zip', $buffer);
}

function acym_loaderLogo()
{
    return '<div class="cell shrink acym_loader_logo">'.acym_getSvg(ACYM_IMAGES.'loader.svg').'</div>';
}
