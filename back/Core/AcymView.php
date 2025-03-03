<?php

namespace AcyMailing\Core;

abstract class AcymView extends AcymObject
{
    public string $name = '';
    // Array of workflow steps: task => title
    protected array $steps = [];
    // Step to display
    protected string $step = '';

    public array $tabs = [];

    public function __construct()
    {
        parent::__construct();

        $classname = get_class($this);
        $classname = substr($classname, strrpos($classname, '\\') + 1);
        $this->name = strtolower(substr($classname, 0, - 4));
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
        if (!file_exists($viewFolder.ucfirst($name).DS.'tmpl'.DS.$view.'.php')) {
            $view = 'listing';
        }
        $getCleanView = ($name !== 'archive' && $view !== 'listing') && ($name !== 'frontusers' || $view !== 'profile');
        if (ACYM_CMS === 'wordpress' && $getCleanView) {
            echo ob_get_clean();
        }

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
        $outsideForm = (strpos($name, 'mails') !== false && $view === 'edit') || (strpos($name, 'campaigns') !== false && $view === 'edit_email');
        if ($outsideForm) {
            echo '<form id="acym_form" action="'.acym_completeLink(
                    acym_getVar('cmd', 'ctrl')
                ).'" class="acym__form__mail__edit" method="post" name="acyForm" data-abide novalidate enctype="multipart/form-data">';
        }

        // Open wrapper and display the header
        if (acym_getVar('cmd', 'task') != 'ajaxEncoding') {
            echo '<div id="acym_wrapper" class="'.$name.'_'.$view.' cms_'.ACYM_CMS.' cms_v_'.substr(ACYM_CMSV, 0, 1).'">';
        }

        //if joomla we add the left menu and a div for the content
        if (acym_isLeftMenuNecessary()) {
            echo acym_getLeftMenu($name).'<div id="acym_content">';
        }

        if (!empty($data['header'])) {
            echo $data['header'];
        }

        $remindme = json_decode($this->config->get('remindme', '[]'), true);
        if ($this->config->get('walk_through', 0) == 0) {
            if (acym_isAdmin()) {
                if (!in_array('multilingual', $remindme) && acym_level(ACYM_ESSENTIAL) && $this->config->get('multilingual', '0') === '0') {
                    if (count(acym_getLanguages(false, true)) > 1) {
                        $message = acym_translation('ACYM_MULTILINGUAL_OPTIONS_PROMPT');
                        $message .= ' <a id="acym__multilingual__reminder" href="'.acym_completeLink('configuration&task=multilingual').'">'.acym_translation('ACYM_YES').'</a>';
                        $message .= ' / <a href="#" title="multilingual" class="acym__do__not__remindme__multilingual">'.acym_translation('ACYM_NO').'</a>';
                        acym_display($message, 'info', false);
                    } else {
                        $remindme[] = 'multilingual';
                        $this->config->save(['remindme' => json_encode($remindme)]);
                    }
                }

                $maliciousScan = $this->config->get('malicious_scan', 0);
                if (!empty($maliciousScan)) {
                    $this->config->save(['malicious_scan' => 0]);
                    acym_display(acym_translation('ACYM_NEW_SECURITY_TOOL'), 'info');
                }
            }
        }

        acym_trigger('acym_displayTrackingMessage', [&$message], 'plgAcymWoocommerce');

        if (acym_isAdmin()) {
            acym_trigger('onAcymDisplayPage');
            acym_displayMessages();
        }

        include acym_getView($name, $view);

        // Close the wrapper and the content if CMS = Joomla
        if (acym_isLeftMenuNecessary()) echo '</div>';
        if (acym_getVar('cmd', 'task') != 'ajaxEncoding') {
            echo '</div>';
        }

        if ($outsideForm) {
            echo '</form>';
        }

        if (ACYM_CMS !== 'wordpress' || !acym_isAdmin()) {
            return;
        }

        $remind = json_decode($this->config->get('remindme', '[]'));
        $installationDate = $this->config->get('install_date', time());

        if (!in_array('reviews', $remind) && !in_array($controller->name, ['dashboard', 'language']) && $installationDate < time() - 7 * 86400) {
            echo '<div id="acym__reviews__footer" style="margin: 0 0 30px 30px;">';
            echo acym_translationSprintf(
                'ACYM_REVIEW_FOOTER',
                '<a title="reviews" id="acym__reviews__footer__link" target="_blank" href="https://wordpress.org/support/plugin/acymailing/reviews/?rate=5#new-post"><i class="acymicon-star acym__color__light-blue"></i><i class="acymicon-star acym__color__light-blue"></i><i class="acymicon-star acym__color__light-blue"></i><i class="acymicon-star acym__color__light-blue"></i><i class="acymicon-star acym__color__light-blue"></i></a>'
            );
            echo '</div>';
        }
    }
}
