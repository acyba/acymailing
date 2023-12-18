<?php

namespace AcyMailing\Helpers;

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Libraries\acymObject;

class ExportHelper extends acymObject
{
    var $eol = "\r\n";
    var $before = '"';
    var $after = '"';

    public function setDownloadHeaders($filename = 'export', $extension = 'csv')
    {
        // Fix for IE catching
        acym_header('Pragma: public');
        acym_header('Expires: 0');
        acym_header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

        // force download dialog
        acym_header('Content-Type: application/force-download');
        acym_header('Content-Type: application/octet-stream');
        acym_header('Content-Type: application/download');

        // Set file name and force the browser to display the save dialog
        acym_header('Content-Disposition: attachment; filename='.$filename.'.'.$extension);
        acym_header('Content-Transfer-Encoding: binary');
    }

    public function exportTemplate($template)
    {
        // 1 - Create a folder with the data to export
        $name = preg_replace('#[^a-z0-9]#Uis', '_', $template->name);
        $name = preg_replace('#_+#s', '_', $name);
        $exportFolder = ACYM_ROOT.ACYM_MEDIA_FOLDER.'tmp'.DS.$name;
        acym_createFolder($exportFolder);

        // 2 - Copy the images in the folder, and change the src in the <img> tags
        $template->body = acym_absoluteURL($template->body);
        $images = [];
        preg_match_all('#<img[^>]* src="([^"]+)"#Uis', $template->body, $images);

        if (!empty($images[1])) {
            $imagesFolder = $exportFolder.DS.'images';
            acym_createFolder($imagesFolder);

            $replace = [];
            foreach ($images[1] as $oneImage) {
                if (isset($replace[$oneImage])) continue;

                $location = str_replace(
                    [ACYM_LIVE, '/'],
                    [ACYM_ROOT, DS],
                    $oneImage
                );
                // Still an http picture? Must be a picture out of the domain... so we just leave it that way
                if (strpos($location, 'http') === 0) continue;

                // We can't find the image? We leave it that way
                if (!file_exists($location)) continue;

                $filename = basename($location);
                while (file_exists($imagesFolder.DS.$filename)) {
                    $filename = rand(0, 99).$filename;
                }

                acym_copyFile($location, $imagesFolder.DS.$filename);
                $replace[$oneImage] = 'images/'.$filename;
            }

            $template->body = str_replace(array_keys($replace), $replace, $template->body);
        }

        // 3 - Copy the template's content
        $structure = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';

        $dataToCopy = [
            'fromname',
            'fromemail',
            'replyname',
            'replyemail',
            'subject',
            'settings',
        ];
        foreach ($dataToCopy as $oneData) {
            if (empty($template->$oneData)) continue;
            $structure .= "\n".'<meta name="'.$oneData.'" content="'.acym_escape($template->$oneData).'" />';
        }

        $structure .= "\n".'<title>'.$template->name.'</title>
</head>
<body>
'.$template->body.'
</body>
</html>';

        acym_writeFile($exportFolder.DS.'template.html', $structure);

        // 4 - Copy the thumbnail
        $thumbnail = acym_getMailThumbnail($template->thumbnail);
        $thumbnail = str_replace(ACYM_LIVE, ACYM_ROOT, $thumbnail);
        acym_copyFile($thumbnail, $exportFolder.DS.'thumbnail.png');

        // 5 - Handle custom stylesheet
        if (!empty($template->stylesheet)) {
            acym_createFolder($exportFolder.DS.'css');
            acym_writeFile($exportFolder.DS.'css'.DS.'custom.css', $template->stylesheet);
        }

        // 6 - Create the zip archive
        $zipFiles = [];
        $folders = acym_getFolders($exportFolder, '.', true, true);
        array_push($folders, $exportFolder);
        foreach ($folders as $folder) {
            $files = acym_getFiles($folder, '.', false, true);
            foreach ($files as $file) {
                // Avoid J3 issue on thumbnail and index.php path, last separator was not DS
                $posSlash = strrpos($file, '/');
                $posASlash = strrpos($file, '\\');
                $pos = ($posSlash < $posASlash) ? $posASlash : $posSlash;
                if (!empty($pos)) $file = substr_replace($file, DS, $pos, 1);

                $data = acym_fileGetContent($file);
                $zipFiles[] = [
                    'name' => str_replace(
                        $exportFolder.DS,
                        '',
                        $file
                    ),
                    'data' => $data,
                ];
            }
        }

        acym_createArchive($exportFolder, $zipFiles);

        // 7 - Remove the temporary folder
        acym_deleteFolder($exportFolder);

        // 8 - Download the zip
        $this->setDownloadHeaders($name, 'zip');
        echo acym_fileGetContent($exportFolder.'.zip');
        acym_deleteFile($exportFolder.'.zip');

        exit;
    }

    public function exportStatsFormattedCSV($mailName, $globalDonutsData, $globaline, $timeLinechart)
    {
        $nbExport = $this->getExportLimit();
        acym_displayErrors();

        if ($timeLinechart == 'month') {
            $timeLinechart = acym_translation('ACYM_MONTHLY_STATS');
        } elseif ($timeLinechart == 'week') {
            $timeLinechart = acym_translation('ACYM_WEEKLY_STATS');
        } else {
            $timeLinechart = acym_translation('ACYM_DAILY_STATS');
        }

        $separator = '","';

        $csvLines = [];
        $csvLines[] = $this->before.$mailName.$this->after;

        $csvLines[] = $this->eol;

        $globalDonutsTitle = [
            acym_translation('ACYM_SUCCESSFULLY_SENT'),
            acym_translation('ACYM_OPEN_RATE'),
            acym_translation('ACYM_CLICK_RATE'),
            acym_translation('ACYM_BOUNCE_RATE'),
            acym_translation('ACYM_UNSUBSCRIBE_RATE'),
        ];

        $csvLines[] = $this->before.implode($separator, $globalDonutsTitle).$this->after;
        $csvLines[] = $this->before.implode($separator, $globalDonutsData).$this->after;

        $csvLines[] = $this->eol;

        $csvLines[] = $this->before.$timeLinechart.$separator.acym_translation('ACYM_OPEN').$separator.acym_translation('ACYM_CLICK').$this->after;

        $i = 0;
        foreach ($globaline as $date => $value) {
            if ($i > $nbExport) break;
            $csvLines[] = $this->before.$date.$separator.$value['open'].$separator.$value['click'].$this->after;
            $i++;
        }

        $this->finishStatsExport($csvLines);
    }

    public function exportStatsFullCSV($query, $columns, $type = 'global')
    {
        $mailsStats = acym_loadObjectList($query);
        $mailClass = new MailClass();
        // We don't want to decode the user's name
        $mailClass->exceptKeysDecode = ['name'];
        $mailsStats = $mailClass->decode($mailsStats);
        $nbExport = $this->getExportLimit();
        acym_displayErrors();

        $separator = '","';
        $csvLines = [];

        $csvLines[] = $this->before.implode($separator, $columns).$this->after;

        $valueNeedNumber = ['click'];

        $i = 0;
        foreach ($mailsStats as $mailStat) {
            if ($i > $nbExport) break;
            $oneLine = [];
            foreach ($columns as $key => $trad) {
                $key = strpos($key, '.') !== false ? explode('.', $key) : $key;
                if (is_array($key)) $key = $key[1];
                $line = in_array($key, $valueNeedNumber) && empty($mailStat->{$key}) ? 0 : $mailStat->{$key};
                if (is_null($line)) {
                    $line = '';
                }
                $oneLine[] = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
            }
            //We delete the double quote so it won't break the CSV
            $csvLines[] = $this->before.implode($separator, $oneLine).$this->after;
            $i++;
        }

        $this->finishStatsExport($csvLines, $type);
    }

    private function finishStatsExport($csvLines, $type = 'global')
    {
        $final = implode($this->eol, $csvLines);

        if (ACYM_CMS === 'wordpress') @ob_get_clean();
        $filename = 'export_stats_'.$type.'_'.date('Y-m-d');
        $this->setDownloadHeaders($filename);
        echo $final;

        return '';
    }

    public function exportCSV($query, $fieldsToExport, $customFieldsToExport, $specialFieldsToExport, $separator = ',', $charset = 'UTF-8', $exportFile = null, $flagToRemove = 0)
    {
        $nbExport = $this->getExportLimit();
        acym_displayErrors();
        $encodingClass = new EncodingHelper();
        $excelSecure = $this->config->get('export_excelsecurity', 0);

        if (!in_array($separator, [',', ';'])) $separator = ',';
        $separator = '"'.$separator.'"';

        $allFieldsToExport = array_merge($fieldsToExport, $customFieldsToExport);

        foreach ($allFieldsToExport as $key => $field) {
            $allFieldsToExport[$key] = $encodingClass->change($field, 'UTF-8', $charset);
        }

        if (in_array('subscribe_date', $specialFieldsToExport)) {
            $listClass = new ListClass();
            $lists = $listClass->getAll();
            foreach ($lists as $oneList) {
                if ($oneList->type === $listClass::LIST_TYPE_FRONT) {
                    continue;
                }

                $allFieldsToExport[] = acym_translation('ACYM_SUBSCRIPTION_DATE').' '.acym_translation($oneList->name);
            }
        }

        // This first line shows the column headers ("name", "email", etc...)
        $firstLine = $this->before.implode($separator, $allFieldsToExport).$this->after.$this->eol;

        if (empty($exportFile)) {
            @ob_get_clean();
            $filename = 'export_'.date('Y-m-d');
            $this->setDownloadHeaders($filename);
            echo $firstLine;
        } else {
            // Make sure the folder exists
            preg_match('#^(.+/)[^/]+$#', $exportFile, $folder);
            if (!empty($folder[1]) && !file_exists($folder[1])) acym_createDir($folder[1]);

            $fp = fopen($exportFile, 'w');
            if (false === $fp) {
                if ($flagToRemove !== 0) {
                    $automationHelper = new AutomationHelper();
                    $automationHelper->removeFlag($flagToRemove);
                }

                return acym_translationSprintf('ACYM_FAIL_SAVE_FILE', $exportFile);
            }

            $error = fwrite($fp, $firstLine);
            if (false === $error) {
                if ($flagToRemove !== 0) {
                    $automationHelper = new AutomationHelper();
                    $automationHelper->removeFlag($flagToRemove);
                }

                return acym_translationSprintf('ACYM_UNWRITABLE_FILE', $exportFile);
            }
        }

        $start = 0;
        do {
            try {
                $users = acym_loadObjectList($query.' LIMIT '.intval($start).', '.intval($nbExport), 'id');
            } catch (\Exception $e) {
                $users = false;
            }
            $start += $nbExport;

            if ($users === false) {
                $errorLine = $this->eol.$this->eol.'Error: '.(isset($e) ? $e->getMessage() : acym_getDBError());

                if (empty($exportFile)) {
                    echo $errorLine;
                } else {
                    $error = fwrite($fp, $errorLine);
                    if (false === $error) {
                        if ($flagToRemove !== 0) {
                            $automationHelper = new AutomationHelper();
                            $automationHelper->removeFlag($flagToRemove);
                        }

                        return acym_translationSprintf('ACYM_UNWRITABLE_FILE', $exportFile);
                    }
                }
            }

            // There is no other user to export, end here
            if (empty($users)) break;

            foreach ($users as $userID => $oneUser) {
                unset($oneUser->id);

                $data = get_object_vars($oneUser);

                if (!empty($customFieldsToExport)) {
                    $fieldIDs = array_keys($customFieldsToExport);
                    acym_arrayToInteger($fieldIDs);

                    $userCustomFields = acym_loadObjectList(
                        'SELECT `field_id`, `value` 
                        FROM #__acym_user_has_field 
                        WHERE user_id = '.intval($userID).' AND field_id IN ('.implode(',', $fieldIDs).')',
                        'field_id'
                    );

                    foreach ($customFieldsToExport as $fieldID => $fieldName) {
                        if (empty($userCustomFields[$fieldID])) {
                            $data[] = '';
                        } else {
                            $data[] = acym_triggerCmsHook(
                                'onUserExportFieldData',
                                [$userCustomFields[$fieldID]->value, $fieldID, $fieldName],
                                false
                            );
                        }
                    }
                    unset($userCustomFields);
                }

                if (in_array('subscribe_date', $specialFieldsToExport)) {
                    $userSubscriptions = acym_loadObjectList(
                        'SELECT `list_id`, `subscription_date` 
                        FROM #__acym_user_has_list 
                        WHERE user_id = '.intval($userID),
                        'list_id'
                    );
                    foreach ($lists as $oneList) {
                        if ($oneList->type === $listClass::LIST_TYPE_FRONT) {
                            continue;
                        }

                        $data[] = empty($userSubscriptions[$oneList->id]) ? '' : $userSubscriptions[$oneList->id]->subscription_date;
                    }
                    unset($userSubscriptions);
                }

                foreach ($data as &$oneData) {
                    if (is_null($oneData)) {
                        continue;
                    }

                    // Not using empty() because 0 and '0' are valid values
                    if ($excelSecure == 1) {
                        $firstCharacter = substr($oneData, 0, 1);
                        if (in_array($firstCharacter, ['=', '+', '-', '@'])) {
                            $oneData = '	'.$oneData;
                        }
                    }

                    $oneData = htmlspecialchars($oneData);
                }

                $dataexport = implode($separator, $data);
                unset($data);

                $oneLine = $this->before.$encodingClass->change($dataexport, 'UTF-8', $charset).$this->after.$this->eol;
                if (empty($exportFile)) {
                    echo $oneLine;
                } else {
                    $error = fwrite($fp, $oneLine);
                    if (false === $error) {
                        if ($flagToRemove !== 0) {
                            $automationHelper = new AutomationHelper();
                            $automationHelper->removeFlag($flagToRemove);
                        }

                        return acym_translationSprintf('ACYM_UNWRITABLE_FILE', $exportFile);
                    }
                }
            }

            unset($users);
        } while (true);

        if ($flagToRemove !== 0) {
            $automationHelper = new AutomationHelper();
            $automationHelper->removeFlag($flagToRemove);
        }

        if (!empty($exportFile)) fclose($fp);

        return '';
    }

    private function getExportLimit()
    {
        // Getting X users per batch based on the memory limit
        $serverLimit = acym_bytes(ini_get('memory_limit'));
        if ($serverLimit > 150000000) {
            return 50000;
        } elseif ($serverLimit > 80000000) {
            return 15000;
        } else {
            return 5000;
        }
    }

    public function getExportChangesFileName($year, $month, $extension = true)
    {
        $filename = 'export_changes_'.$year.'_'.$month;

        if ($extension) $filename .= '.csv';

        return $filename;
    }

    public function getExportChangesFilePath()
    {
        $filename = $this->getExportChangesFileName(acym_date('now', 'Y'), acym_date('now', 'm'));

        return acym_getLogPath($filename);
    }

    public function generateExportChangesFilePathConfigChanges()
    {
        $filename = $this->getExportChangesFileName(acym_date('now', 'Y'), acym_date('now', 'm'), false);

        $i = 1;

        $newFilename = $filename.'_'.$i.'.csv';

        while (file_exists(acym_getLogPath($newFilename))) {
            $i++;
            $newFilename = $filename.'_'.$i.'.csv';
        }

        return acym_getLogPath($newFilename);
    }

    public function exportChanges($user, $fieldsToExport, $fieldName, $newValue, $oldValue)
    {
        if (empty($user) || empty($fieldName)) return;

        $exportFullPath = $this->getExportChangesFilePath();

        $separator = '","';
        $content = '';

        if (!file_exists($exportFullPath)) {
            $columnsHeader = [];
            foreach ($fieldsToExport as $field) {
                $columnsHeader[] = $field;
            }
            $columnsHeader = array_merge(
                $columnsHeader,
                [
                    acym_translation('ACYM_FIELD_MODIFIED'),
                    acym_translation('ACYM_NEW_VALUE'),
                    acym_translation('ACYM_OLD_VALUE'),
                    acym_translation('ACYM_DATE'),
                ]
            );
            $content .= $this->before.implode($separator, $columnsHeader).$this->after;
        }


        $columns = [];

        foreach ($fieldsToExport as $field) {
            $columns[] = empty($user[$field]) ? '' : $user[$field];
        }

        $columns = array_merge(
            $columns,
            [
                $fieldName,
                $newValue,
                $oldValue,
                acym_date(),
            ]
        );

        $content .= $this->eol;
        $content .= $this->before.implode($separator, $columns).$this->after;

        file_put_contents(
            $exportFullPath,
            $content,
            FILE_APPEND
        );
    }

    public function cleanExportChangesFile()
    {
        $exportFolder = acym_getLogPath('');
        $files = scandir($exportFolder);
        if (empty($files)) return;

        $filenameToSearch = $this->getExportChangesFileName(acym_date('2 month ago', 'Y'), acym_date('2 month ago', 'm'), false);
        foreach ($files as $file) {
            if (strpos($file, $filenameToSearch) === false) continue;
            @unlink(acym_getLogPath($file));
        }
    }
}
