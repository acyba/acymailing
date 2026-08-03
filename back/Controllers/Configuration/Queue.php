<?php

namespace AcyMailing\Controllers\Configuration;

trait Queue
{
    public function deletereport(): void
    {
        acym_checkToken();

        $path = trim(html_entity_decode($this->config->get('cron_savepath')));
        if (!preg_match('#^[a-z0-9/_\-{}]*\.log$#i', $path)) {
            acym_enqueueMessage(acym_translation('ACYM_WRONG_LOG_NAME'), 'error');

            return;
        }

        $path = str_replace(['{year}', '{month}'], [gmdate('Y'), gmdate('m')], $this->config->get('cron_savepath'));
        $reportPath = acym_cleanPath(ACYM_ROOT.$path);

        if (is_file($reportPath)) {
            $result = acym_deleteFile($reportPath);
            if ($result) {
                acym_enqueueMessage(acym_translation('ACYM_SUCC_DELETE_LOG'), 'success');
            } else {
                acym_enqueueMessage(acym_translation('ACYM_ERROR_DELETE_LOG'), 'error');
            }
        } else {
            acym_enqueueMessage(acym_translation('ACYM_EXIST_LOG'), 'info');
        }

        $this->listing();
    }

    public function seereport(): void
    {
        acym_noCache();

        $path = trim(html_entity_decode($this->config->get('cron_savepath')));
        if (!preg_match('#^[a-z0-9/_\-{}]*\.log$#i', $path)) {
            acym_display(acym_translation('ACYM_WRONG_LOG_NAME'), 'error');

            return;
        }

        $path = str_replace(['{year}', '{month}'], [gmdate('Y'), gmdate('m')], $path);
        $reportPath = acym_cleanPath(ACYM_ROOT.$path);

        if (file_exists($reportPath) && !is_dir($reportPath)) {
            try {
                $lines = 5000;
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Necessary tail to save on resources.
                $f = fopen($reportPath, 'rb');
                fseek($f, -1, SEEK_END);
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Necessary tail to save on resources.
                if (fread($f, 1) != "\n") {
                    $lines -= 1;
                }

                $report = '';
                while (ftell($f) > 0 && $lines >= 0) {
                    $seek = min(ftell($f), 4096);
                    fseek($f, -$seek, SEEK_CUR);
                    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Necessary tail to save on resources.
                    $report = ($chunk = fread($f, $seek)).$report; // Get the line
                    fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
                    $lines -= substr_count($chunk, "\n"); // Move to previous line
                }

                while ($lines++ < 0) {
                    $report = substr($report, strpos($report, "\n") + 1);
                }
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Necessary tail to save on resources.
                fclose($f);
            } catch (\Exception $e) {
                $report = '';
            }
        }

        if (empty($report)) {
            $report = acym_translation('ACYM_EMPTY_LOG');
        }

        echo nl2br(acym_escapeHtml($report));
        exit;
    }
}
