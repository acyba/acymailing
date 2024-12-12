<?php

use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\EncodingHelper;

$encodingHelper = new EncodingHelper();
$userClass = new UserClass();
$filename = strtolower(acym_getVar('cmd', 'acym_import_filename', ''));
$encoding = acym_getVar('cmd', 'encoding');

$extension = '.'.acym_fileGetExt($filename);
$uploadPath = ACYM_MEDIA.'import'.DS.str_replace(['.', ' '], '_', substr($filename, 0, strpos($filename, $extension))).$extension;

if (!file_exists($uploadPath)) {
    acym_display(acym_translationSprintf('ACYM_FAIL_OPEN', '<b><i>'.acym_escape($uploadPath).'</i></b>'), 'error');

    return;
}

$this->content = file_get_contents($uploadPath);

if (empty($encoding)) {
    $encoding = $encodingHelper->detectEncoding($this->content);
}

$content = $encodingHelper->change($this->content, $encoding, 'UTF-8');


// Get the lines in an array
$content = str_replace(["\r\n", "\r"], "\n", $content);
$this->lines = explode("\n", $content);

// Get the right separator
$this->separator = ',';
$listSeparators = ["\t", ';', ','];
foreach ($listSeparators as $sep) {
    if (strpos($this->lines[0], $sep) !== false) {
        $this->separator = $sep;
        break;
    }
}

$nbPreviewLines = 0;
$i = 0;
$data = [];

while (isset($this->lines[$i])) {
    // If empty line, unset it, else count it as line to import (we will display only the 10 first lines to import)
    if (empty($this->lines[$i])) {
        unset($this->lines[$i]);
        continue;
    } else {
        $nbPreviewLines++;
    }

    // If there is a ", the line could be broken as there could be a return line in a value to import
    if (strpos($this->lines[$i], '"') !== false) {
        $data[$i] = [];
        $j = $i + 1;
        $position = -1;

        // Concatenate 30 lines max
        while ($j < ($i + 30)) {
            // Test if the value is encapsulated by quotes
            $quoteOpened = substr($this->lines[$i], $position + 1, 1) == '"';

            // If encapsulated, search the end of the value
            if ($quoteOpened) {
                $nextQuotePosition = strpos($this->lines[$i], '"', $position + 2);

                // If we didn't find the whole value, then concatenate the current line with the next one
                if ($nextQuotePosition === false) {
                    // If end of import file, error...
                    if (!isset($this->lines[$j])) {
                        break;
                    }

                    // Take the right charset
                    $this->lines[$i] .= "\n".rtrim($this->lines[$j], $this->separator);
                    unset($this->lines[$j]);
                    $j++;
                    continue;
                } else {
                    // Found the entire value, add it to data and move the position
                    $quoteOpened = false;

                    // If the line is completed
                    if (strlen($this->lines[$i]) - 1 == $nextQuotePosition) {
                        $data[$i][] = substr($this->lines[$i], $position + 1);
                        break;
                    }

                    $data[$i][] = substr($this->lines[$i], $position + 1, $nextQuotePosition + 1 - ($position + 1));
                    $position = $nextQuotePosition + 1;
                }
            } else {
                // If not encapsulated by quotes, search the next separator
                $nextSeparatorPosition = strpos($this->lines[$i], $this->separator, $position + 1);
                // If not found, the line is completed
                if ($nextSeparatorPosition === false) {
                    $data[$i][] = substr($this->lines[$i], $position + 1);
                    break;
                } else {
                    // If found the next separator, add the value in $data[$i] and change the position
                    $data[$i][] = substr($this->lines[$i], $position + 1, $nextSeparatorPosition - ($position + 1));
                    $position = $nextSeparatorPosition;
                }
            }
        }

        // We unset some lines, don't forget to change the remaining lines keys
        $this->lines = array_merge($this->lines);
    } else {
        $data[$i] = explode($this->separator, rtrim(trim($this->lines[$i]), $this->separator));
    }

    // We display only the first ten lines
    if ($nbPreviewLines == 10) {
        break;
    }

    if ($nbPreviewLines != 1) {
        $i++;
        continue;
    }

    // If it's the first line, check if there is an header or not
    if (strpos($this->lines[$i], '@')) {
        $noHeader = 1;
    } else {
        $noHeader = 0;
    }

    // Get the column names and count them
    $columnNames = explode($this->separator, $this->lines[$i]);
    $nbColumns = count($columnNames);
    if (!empty($i)) {
        unset($this->lines[$i]);
    }
    ksort($this->lines);
}

$this->lines = $data;
$nbLines = count($this->lines);

?>
<div class="table-scroll">
	<table cellspacing="10" cellpadding="10" id="importdata" class="unstriped">
        <?php
        if ($noHeader || !isset($this->lines[1])) {
            $firstValueLine = $columnNames;
        } else {
            $firstValueLine = $this->lines[1];
            foreach ($firstValueLine as &$oneValue) {
                $oneValue = trim($oneValue, '\'" ');
            }
        }

        // Possible values for columns imported as new custom field
        $fieldAssignment = [];

        $fieldAssignment[] = acym_selectOption('0', 'ACYM_IGNORED');
        $separator = acym_selectOption('3', '----------------------');
        $separator->disable = true;
        $fieldAssignment[] = $separator;


        // This variable will be used to re-assign columns when we change the encoding option
        $fields = $userClass->getAllColumnsUserAndCustomField();
        if (acym_isAdmin()) {
            $fields['listids'] = 'listids';
            $fields['listname'] = 'listname';
        }

        $cleanFields = [];
        foreach ($fields as $value => $label) {
            if (in_array($value, ['id', 'automation'])) continue;
            if (is_numeric($value)) $value = 'cf_'.$value;
            $fieldAssignment[] = acym_selectOption($value, $label);
            $cleanFields[$value] = strtolower($label);
        }

        // Add first line to assign the imported columns, also add a button to ignore all unassigned columns if there are at least 6 columns
        echo '<tr>';

        // Don't auto-assign two times the same column
        $alreadyFound = [];

        foreach ($columnNames as $key => $oneColumn) {
            $columnNames[$key] = strtolower(trim($columnNames[$key], '\'" '));

            $customValue = '';

            // If we assigned the column then changed the charset option (reloading the page), keep the assigned value
            $selectedField = acym_getVar('cmd', 'fieldAssignment'.$key, '');

            // Auto-detect the column assignment
            if (empty($selectedField) && $selectedField !== 0) {
                // If the column name matches one of the AcyMailing fields, take this field
                if (isset($cleanFields[$columnNames[$key]])) {
                    $selectedField = $columnNames[$key];
                } elseif (in_array($columnNames[$key], $cleanFields)) {
                    $selectedField = array_search(strtolower($columnNames[$key]), $cleanFields);
                } else {
                    $selectedField = '0';
                }

                // Detect if it's the mail field, if there are only two columns and it isn't the email one, it is the name column
                if (!$selectedField && !empty($firstValueLine)) {
                    if (isset($firstValueLine[$key]) && strpos($firstValueLine[$key], '@')) {
                        $selectedField = 'email';
                    } elseif ($nbColumns == 2) {
                        $selectedField = 'name';
                    }
                }
                if (in_array($selectedField, $alreadyFound)) {
                    $selectedField = '0';
                }
            } elseif ($selectedField == 2) {
                // If custom field, fill in the input with the right value
                $customValue = acym_getVar('cmd', 'newcustom'.$key);
            }

            $alreadyFound[] = $selectedField;

            echo '<td valign="top">'.acym_select(
                    $fieldAssignment,
                    'fieldAssignment'.$key,
                    $selectedField,
                    ['class' => 'fieldAssignment']
                ).'<br />';
        }
        echo '</tr>';

        // If the first imported line is the header, display a line to show it...
        if (!$noHeader) {
            foreach ($columnNames as $key => $oneColumn) {
                $columnNames[$key] = htmlspecialchars($columnNames[$key], ENT_COMPAT | ENT_IGNORE, 'UTF-8');
            }
            echo '<tr class="acym__users__import__generic__column_name"><td><b>'.implode('</b></td><td><b>', $columnNames).'</b></td></tr>';
        }

        // For each imported line, display it so that the user can preview with the right encoding...
        for ($i = 1 - $noHeader ; $i < 11 - $noHeader && $i < $nbLines ; $i++) {
            $values = $this->lines[$i];

            echo '<tr>';
            foreach ($values as &$oneValue) {
                $oneValue = htmlspecialchars(trim($oneValue, '\'" '), ENT_COMPAT | ENT_IGNORE, 'UTF-8');
                echo '<td>'.htmlspecialchars_decode($oneValue).'</td>';
            }
            echo '</tr>';
        }
        ?>
	</table>
</div>
