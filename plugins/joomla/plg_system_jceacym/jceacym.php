<?php

defined('_JEXEC') or die('Restricted access');

class plgSystemJceacym extends JPlugin
{
    public function onBeforeWfEditorRender(&$settings)
    {
        if (empty($_REQUEST['option']) || $_REQUEST['option'] !== 'com_acym') {
            return;
        }

        // We're in a newsletter context, no other CSS file should be applied.
        if (!empty($_REQUEST['acycssfile'])) {
            $settings['content_css'] = $_REQUEST['acycssfile'];
        }
    }

    public function onAfterInitialise()
    {
        $app = JFactory::getApplication();
        if ($app->input->getCmd('option') !== 'com_media') return;
        if (!$app->input->getWord('asset') || $app->input->getWord('tmpl') !== 'component') return;
        if (!$this->isEditorEnabled()) return;

        $params = JComponentHelper::getParams('com_jce');
        if (!empty($params) && (bool)$params->get('replace_media_manager', 1) === true) {
            // Prevent JCE redirection
            if ($app->input->getCmd('author') === 'acymailing') {
                $sessionID = session_id();
                if (empty($sessionID)) @session_start();
                $_SESSION['acyJCERedirectionPrevented'] = true;

                $params->set('replace_media_manager', 0);
            }
        }
    }

    public function onAfterRender()
    {
        $sessionID = session_id();
        if (empty($sessionID)) @session_start();
        if (empty($_SESSION['acyJCERedirectionPrevented'])) return;

        unset($_SESSION['acyJCERedirectionPrevented']);

        $params = JComponentHelper::getParams('com_jce');
        if (!empty($params)) {
            // Re-set the JCE option value
            $params->set('replace_media_manager', 1);
        }
    }

    private function isEditorEnabled()
    {
        if (!JPluginHelper::getPlugin('editors', 'jce')) {
            return false;
        }

        $config = JFactory::getConfig();
        $user = JFactory::getUser();

        if ($user->getParam('editor', $config->get('editor')) !== 'jce') {
            return false;
        }

        return true;
    }
}
