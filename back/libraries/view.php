<?php

namespace AcyMailing\Libraries;

class acymView extends acymObject
{
    var $name = '';
    // Array of workflow steps: task => title
    var $steps = [];
    // Step to display
    var $step = '';
    // Are we creating or editing an element
    var $edition = false;

    public function __construct()
    {
        parent::__construct();

        $classname = get_class($this);
        $classname = substr($classname, strrpos($classname, '\\') + 1);
        $viewpos = strpos($classname, 'View');
        $this->name = strtolower(substr($classname, $viewpos + 4));
        $this->step = acym_getVar('string', 'nextstep', '');
        if (empty($this->step)) {
            $this->step = acym_getVar('string', 'step', '');
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLayout()
    {
        return acym_getVar('cmd', 'layout', acym_getVar('cmd', 'task', 'listing'));
    }

    public function setLayout($value)
    {
        acym_setVar('layout', $value);
    }

    public function display($controller, $data = [])
    {
        $name = $this->getName();
        $view = $this->getLayout();
        if (method_exists($this, $view)) $this->$view();

        $viewFolder = acym_isAdmin() ? ACYM_VIEW : ACYM_VIEW_FRONT;
        if (!file_exists($viewFolder.$name.DS.'tmpl'.DS.$view.'.php')) $view = 'listing';
        if (ACYM_CMS === 'wordpress' && $name !== 'archive' && $view !== 'listing') echo ob_get_clean();

        // Load the needed scripts and styles
        if (ACYM_CMS !== 'wordpress' || ($name === 'frontusers' && ($view === 'unsubscribe' || $view === 'unsubscribepage')) || !defined(
                'DOING_AJAX'
            ) || !DOING_AJAX || ($name === 'archive' && $view === 'view')) {
            acym_loadAssets($name, $view);
            $controller->loadScripts($view);
        }

        // Display enqueued messages
        if (!empty($_SESSION['acynotif'])) {
            echo implode('', $_SESSION['acynotif']);
            $_SESSION['acynotif'] = [];
        }

        // On pages with the editor, we need to put the wrapper inside the form
        $outsideForm = (strpos($name, 'mails') !== false && $view == 'edit') || (strpos($name, 'campaigns') !== false && $view == 'edit_email');
        if ($outsideForm) echo '<form id="acym_form" action="'.acym_completeLink(
                acym_getVar('cmd', 'ctrl')
            ).'" class="acym__form__mail__edit" method="post" name="acyForm" data-abide novalidate>';

        // Open wrapper and display the header
        if (acym_getVar('cmd', 'task') != 'ajaxEncoding') echo '<div id="acym_wrapper" class="'.$name.'_'.$view.'">';

        //if joomla we add the left menu and a div for the content
        if (acym_isLeftMenuNecessary()) echo acym_getLeftMenu($name).'<div id="acym_content">';

        if (!empty($data['header'])) echo $data['header'];

        $remindme = json_decode($this->config->get('remindme', '[]'), true);
        if (acym_isAdmin() && !in_array('multilingual', $remindme) && acym_level(1) && $this->config->get('multilingual', '0') === '0' && $this->config->get(
                'walk_through',
                0
            ) == 0) {
            if (count(acym_getLanguages(true)) > 1) {
                $message = acym_translation('ACYM_MULTILINGUAL_OPTIONS_PROMPT');
                $message .= ' <a id="acym__multilingual__reminder" href="'.acym_completeLink('configuration&task=multilingual').'">'.acym_translation('ACYM_YES').'</a>';
                $message .= ' / <a href="#" class="acym__do__not__remindme" title="multilingual">'.acym_translation('ACYM_NO').'</a>';
                acym_display($message, 'info', false);
            } else {
                $remindme[] = 'multilingual';
                $this->config->save(['remindme' => json_encode($remindme)]);
            }
        }

        acym_trigger('acym_displayTrackingMessage', [&$message], 'plgAcymWoocommerce');

        if (acym_isAdmin()) acym_displayMessages();

        include acym_getView($name, $view);

        // Close the wrapper and the content if CMS = Joomla
        if (acym_isLeftMenuNecessary()) echo '</div>';
        if (acym_getVar('cmd', 'task') != 'ajaxEncoding') echo '</div>';

        if ($outsideForm) echo '</form>';

        $remind = json_decode($this->config->get('remindme', '[]'));
        if (ACYM_CMS == 'wordpress' && !in_array('reviews', $remind) && acym_isAdmin() && $controller->name != 'language') {
            echo '<div id="acym__reviews__footer" style="margin: 0 0 30px 30px;">';
            echo acym_translationSprintf(
                'ACYM_REVIEW_FOOTER',
                '<a title="reviews" id="acym__reviews__footer__link" target="_blank" href="https://wordpress.org/support/plugin/acymailing/reviews/?rate=5#new-post"><i class="acymicon-star acym__color__light-blue"></i><i class="acymicon-star acym__color__light-blue"></i><i class="acymicon-star acym__color__light-blue"></i><i class="acymicon-star acym__color__light-blue"></i><i class="acymicon-star acym__color__light-blue"></i></a>'
            );
            echo '</div>';
        }
    }
}
