<?php

namespace AcyMailing\Helpers;

use AcyMailing\Libraries\acymObject;

class WorkflowHelper extends acymObject
{
    // Disable all the steps after this one
    var $disabledAfter = null;

    /**
     * Call this helper in an element with the class acym__content
     * The steps are defined in the view.html.php
     * Put acym_formOptions(true, 'edit', 'CURRENT STEP') at the end of each step
     *
     * @param array   $steps       => array('task' => 'ACYM_TITLE_TAB'). It is set in the view.html.php
     * @param string  $currentStep => The current step, you shouldn't have anything to do for this
     * @param boolean $edition     => are we in editing mode or in creation mode?
     * @param bool    $needTabs
     * @param string  $linkParameters
     *
     * @return string
     */
    public function display($steps, $currentStep, $edition = true, $needTabs = false, $linkParameters = '', $idName = 'id'): string
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
                if ($edition) {
                    $link = $ctrl.'&task=edit&step='.$task.'&'.$idName.'='.$id;
                } else {
                    $link = $ctrl.'&task='.$task;
                }
                $title = '<a href="'.acym_completeLink($link.$linkParameters).'">'.$title.'</a>';
            }

            $workflow[] = '<li class="'.$class.'">'.$title.'</li>';
            $workflow[] = '<li class="step_separator '.($needTabs ? '' : 'acymicon-keyboard_arrow_right').'"></li>';

            if ($task == $this->disabledAfter) {
                $disableTabs = true;
            }
        }

        array_pop($workflow);

        $result = '<ul id="workflow">';
        $result .= implode('', $workflow);
        $result .= '</ul>';

        return $result;
    }

    public function displayTabs($steps, $currentStep, $options = [])
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

            $linkAttribute = $currentStep == $task ? 'aria-selected="true"' : '';

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
