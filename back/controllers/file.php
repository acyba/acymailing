<?php

namespace AcyMailing\Controllers;

use AcyMailing\Libraries\acymController;
use AcyMailing\Types\FileTreeType;

class FileController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        $this->setDefaultTask('select');
    }

    public function select()
    {
        acym_setVar('layout', 'select');

        $warnings = '';
        $uploadFolderBase = acym_getFilesFolder();
        $currentFolder = acym_getVar('string', 'currentFolder', $uploadFolderBase);
        if (strpos($currentFolder, $uploadFolderBase) !== 0 || strpos($currentFolder, '..') !== false) {
            $currentFolder = $uploadFolderBase;
        }

        $uploadFolder = trim(str_replace('/', DS, trim($currentFolder)), DS);
        $uploadPath = acym_cleanPath(ACYM_ROOT.$uploadFolder);
        $folders = acym_generateArborescence([$uploadFolderBase]);

        $uploadedFile = acym_getVar('array', 'uploadedFile', [], 'files');
        $selectedFile = '';
        if (!empty($uploadedFile['name'])) {
            ob_start();
            $uploaded = acym_importFile($uploadedFile, $uploadPath, false);
            $warnings = ob_get_clean();
            if ($uploaded) {
                $selectedFile = $uploaded;
            }
        }

        $files = [];
        if (file_exists($uploadPath)) {
            $files = acym_getFiles($uploadPath);
        }

        $data = [
            'files' => $files,
            'uploadFolder' => $uploadFolder,
            'map' => acym_getVar('string', 'id'),
            'displayType' => acym_getVar('string', 'displayType', 'icons'),
            'imageExtensions' => acym_getImageFileExtensions(),
            'allowedExtensions' => explode(',', $this->config->get('allowed_files')),
            'folders' => $folders,
            'fileTreeType' => new FileTreeType(),
            'selectedFile' => $selectedFile,
            'warnings' => $warnings,
        ];

        parent::display($data);
    }
}
