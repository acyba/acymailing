<?php

class FileController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        $this->setDefaultTask('select');
    }

    public function select()
    {
        $uploadFolderBase = acym_getFilesFolder();
        $currentFolder = acym_getVar('string', 'currentFolder', $uploadFolderBase);
        if (strpos($currentFolder, $uploadFolderBase) !== 0) $currentFolder = $uploadFolderBase;

        $uploadFolder = trim(str_replace('/', DS, trim($currentFolder)), DS);
        $uploadPath = acym_cleanPath(ACYM_ROOT.$uploadFolder);
        $map = acym_getVar('string', 'id');
        acym_setVar('layout', 'select');

        $folders = acym_generateArborescence([$uploadFolderBase]);


        $uploadedFile = acym_getVar('array', 'uploadedFile', [], 'files');
        if (!empty($uploadedFile) && !empty($uploadedFile['name'])) {
            $uploaded = acym_importFile($uploadedFile, $uploadPath, false);
            if ($uploaded) {
                // TODO : Select file and close popup
            }
        }

        // TODO : delete file


        $allowedExtensions = explode(',', $this->config->get('allowed_files'));
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'ico', 'bmp', 'svg'];
        $displayType = acym_getVar('string', 'displayType', 'icons');

        $files = [];
        if (file_exists($uploadPath)) {
            $files = acym_getFiles($uploadPath);
        }

        $data = [
            'files' => $files,
            'uploadFolder' => $uploadFolder,
            'map' => $map,
            'displayType' => $displayType,
            'imageExtensions' => $imageExtensions,
            'allowedExtensions' => $allowedExtensions,
            'folders' => $folders,
        ];

        parent::display($data);
    }
}
