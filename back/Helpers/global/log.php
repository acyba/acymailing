<?php

function acym_getLogPath(string $filename = '', bool $create = false): string
{
    $reportPath = ACYM_LOGS_FOLDER.$filename;
    $reportPath = acym_cleanPath(ACYM_ROOT.trim(html_entity_decode($reportPath)));

    if ($create) {
        acym_createDir(dirname($reportPath), true, true);
    }

    return $reportPath;
}
