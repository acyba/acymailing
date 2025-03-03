<?php

namespace AcyMailing\Helpers;

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
    public function display(array $steps, string $currentStep, bool $editionMode = true, bool $needTabs = false, string $linkParameters = '', string $idName = 'id'): string
    {
        $ctrl = acym_getVar('cmd', 'ctrl');
        $id = acym_getVar('int', $idName, 0);

        $workflow = [];
        $disableTabs = false;
        foreach ($steps as $task => $title) {
            $title = acym_translation($title);

            $class = 'step';
            if ($disableTabs) $class .= ' disabled_step';
            if ($currentStep === $task) $class .= ' current_step';

            if (!$disableTabs) {
                if ($editionMode) {
                    $link = $ctrl.'&task=edit&step='.$task.'&'.$idName.'='.$id;
                } else {
                    $link = $ctrl.'&task='.$task;
                }
                $title = '<a href="'.acym_completeLink($link.$linkParameters).'">'.$title.'</a>';
            }

            $workflow[] = '<li class="'.$class.'">'.$title.'</li>';
            $workflow[] = '<li class="step_separator '.($needTabs ? '' : 'acymicon-keyboard-arrow-right').'"></li>';

            if ($task === $this->disabledAfter) {
                $disableTabs = true;
            }
        }

        array_pop($workflow);

        $result = '<ul id="workflow">';
        $result .= implode('', $workflow);
        $result .= '</ul>';

        return $result;
    }

    public function displayNew(array $steps, string $currentStep, bool $edition = true, string $linkParameters = '', string $idName = 'id'): string
    {
        $ctrl = acym_getVar('cmd', 'ctrl');
        $id = acym_getVar('int', $idName, 0);

        $workflow = [];
        $disableTabs = false;
        foreach ($steps as $task => $title) {
            if ($disableTabs) {
                continue;
            }
            $title = acym_translation($title);

            $classCurrentStep = $currentStep === $task ? 'acym__workflow__step__active' : '';
            $classDisabled = $disableTabs ? 'acym__workflow__step__disabled' : '';

            $label = $title;
            if (!$disableTabs) {
                if ($edition) {
                    $link = $ctrl.'&task=edit&step='.$task.'&'.$idName.'='.$id;
                } else {
                    $link = $ctrl.'&task='.$task;
                }
                $label = '<a href="'.acym_completeLink($link.$linkParameters).'">'.$title.'</a>';
            }

            $step = '<div class="acym__workflow__step '.$classDisabled.' '.$classCurrentStep.'">'.$label.'</div>';

            $workflow[] = $step;

            if ($task === $this->disabledAfter) {
                $disableTabs = true;
            }
        }

        $result = '<div class="acym__workflow">';
        $result .= implode('', $workflow);
        $result .= '</div>';

        return $result;
    }

    public function displayTabs(array $steps, string $currentStep, array $options = []): string
    {
        $ctrl = acym_getVar('cmd', 'ctrl');

        $mailTypes = ['mailbox_action'];

        $workflow = [];
        foreach ($steps as $task => $title) {
            if (in_array($task, $mailTypes)) {
                $searchSettings = [
                    'offset' => 0,
                    'mailsPerPage' => 1,
                    'key' => '',
                ];
                $mailClass = new \AcyMailing\Classes\MailClass();
                $existingMailbox = $mailClass->getMailsByType($task, $searchSettings);
                if (empty($existingMailbox['mails'])) {
                    continue;
                }
            }

            $title = acym_translation($title);

            $linkAttribute = $currentStep === $task ? 'aria-selected="true"' : '';

            if (!empty($options['query'])) {
                $link = $ctrl.$options['query'].$task;
            } else {
                $link = $ctrl.'&task='.$task;
            }

            if (!empty($options['disableTabs']) && in_array($task, $options['disableTabs'])) {
                $link = '';
                $linkAttribute = 'aria-disabled="true"';
            }

            $hrefAttribute = $link ? 'href="'.acym_completeLink($link).'"' : '';

            $title = '<a class="acym_tab acym__color__medium-gray" '.$linkAttribute.' '.$hrefAttribute.'>'.$title.'</a>';

            $workflow[] = '<li class="tabs-title">'.$title.'</li>';
        }

        $result = '<ul class="tabs" id="workflow">';
        $result .= implode('', $workflow);
        $result .= '</ul>';

        return $result;
    }
}
