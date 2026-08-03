<?php

namespace AcyMailing\Helpers;

use AcyMailing\Classes\MailClass;
use AcyMailing\Core\AcymObject;

class WorkflowHelper extends AcymObject
{
    // Disable all the steps after this one
    public string $disabledAfter = '';

    /**
     * Call this helper in an element with the class acym__content
     * The steps are defined in the Main view class
     * Put acym_formOptions(true, 'edit', 'CURRENT STEP') at the end of each step
     */
    public function display(
        array  $steps,
        string $currentStep,
        bool   $editionMode = true,
        bool   $needTabs = false,
        string $linkParameters = '',
        string $idName = 'id'
    ): void {
        $ctrl = acym_getVar('cmd', 'ctrl');
        $id = acym_getVar('int', $idName, 0);

        echo '<ul id="workflow">';

        $disableTabs = false;
        $step = 0;
        foreach ($steps as $task => $title) {
            $class = 'step';
            if ($disableTabs) {
                $class .= ' disabled_step';
            }
            if ($currentStep === $task) {
                $class .= ' current_step';
            }

            if ($step !== 0) {
                echo '<li class="step_separator '.acym_escape($needTabs ? '' : 'acymicon-keyboard-arrow-right').'"></li>';
            }
            echo '<li class="'.acym_escape($class).'">';

            if (!$disableTabs) {
                $link = $editionMode ? $ctrl.'&task=edit&step='.$task.'&'.$idName.'='.$id : $ctrl.'&task='.$task;
                echo '<a href="'.acym_escapeUrl(acym_completeLink($link.$linkParameters)).'">';
            }
            echo acym_escapeHtml(acym_translation($title));
            if (!$disableTabs) {
                echo '</a>';
            }
            echo '</li>';

            if ($task === $this->disabledAfter) {
                $disableTabs = true;
            }

            $step++;
        }

        echo '</ul>';
    }

    public function displayNew(array $steps, string $currentStep, bool $edition = true, string $linkParameters = '', string $idName = 'id'): void
    {
        $ctrl = acym_getVar('cmd', 'ctrl');
        $id = acym_getVar('int', $idName, 0);

        echo '<div class="acym__workflow">';

        $disableTabs = false;
        foreach ($steps as $task => $title) {
            if ($disableTabs) {
                continue;
            }

            $classCurrentStep = $currentStep === $task ? 'acym__workflow__step__active' : '';
            $classDisabled = $disableTabs ? 'acym__workflow__step__disabled' : '';

            echo '<div class="acym__workflow__step '.acym_escape($classDisabled.' '.$classCurrentStep).'">';
            if (!$disableTabs) {
                $link = $edition ? $ctrl.'&task=edit&step='.$task.'&'.$idName.'='.$id : $ctrl.'&task='.$task;
                echo '<a href="'.acym_escapeUrl(acym_completeLink($link.$linkParameters)).'">';
            }
            echo acym_escapeHtml(acym_translation($title));
            if (!$disableTabs) {
                echo '</a>';
            }
            echo '</div>';

            if ($task === $this->disabledAfter) {
                $disableTabs = true;
            }
        }

        echo '</div>';
    }

    public function displayTabs(array $steps, string $currentStep, array $options = []): void
    {
        $ctrl = acym_getVar('cmd', 'ctrl');

        echo '<ul class="tabs" id="workflow">';

        $mailClass = new MailClass();
        $mailTypes = ['mailbox_action'];

        foreach ($steps as $task => $title) {
            if (in_array($task, $mailTypes)) {
                $searchSettings = [
                    'offset' => 0,
                    'mailsPerPage' => 1,
                    'key' => '',
                ];
                $existingMailbox = $mailClass->getMailsByType($task, $searchSettings);

                if (empty($existingMailbox['mails'])) {
                    continue;
                }
            }

            if (!empty($options['query'])) {
                $link = $ctrl.$options['query'].$task;
            } else {
                $link = $ctrl.'&task='.$task;
            }

            if (!empty($options['disableTabs']) && in_array($task, $options['disableTabs'])) {
                $link = '';
            }

            echo '<li class="tabs-title"><a class="acym_tab acym__color__medium-gray"';
            if (!empty($link)) {
                echo ' href="'.acym_escapeUrl(acym_completeLink($link)).'"';
                if ($currentStep === $task) {
                    echo ' aria-selected="true"';
                }
            } else {
                echo ' aria-disabled="true"';
            }
            echo '>';
            echo acym_escapeHtml(acym_translation($title));
            echo '</a></li>';
        }

        echo '</ul>';
    }
}
