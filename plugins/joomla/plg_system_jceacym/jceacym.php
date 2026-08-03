<?php

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;

defined('_JEXEC') or die('Restricted access');

class plgSystemJceacym extends CMSPlugin
{
    public function onBeforeWfEditorRender(&$settings)
    {
        if (empty($_REQUEST['option']) || $_REQUEST['option'] !== 'com_acym') {
            return;
        }

        // We're in a newsletter context, no other CSS file should be applied.
        if (!empty(acym_getVar('string', 'acycssfile', ''))) {
            $settings['content_css'] = acym_getVar('string', 'acycssfile', '');
        }
    }

    public function onAfterInitialise()
    {
        $app = Factory::getApplication();
        if ($app->input->getCmd('option') !== 'com_media') return;
        if (!$app->input->getWord('asset') || $app->input->getWord('tmpl') !== 'component') return;
        if (!$this->isEditorEnabled()) return;

        $params = ComponentHelper::getParams('com_jce');
        if (!empty($params) && (bool)$params->get('replace_media_manager', 1) === true) {
            // Prevent JCE redirection
            if ($app->input->getCmd('author') === 'acymailing') {
                $jversion = preg_replace('#[^0-9\.]#i', '', JVERSION);
                if (version_compare($jversion, '4.0.0', '>=')) {
                    $session = Joomla\CMS\Factory::getApplication()->getSession();
                    $session->set('acyJCERedirectionPrevented', true);
                } else {
                    $session = JFactory::getSession();
                    $session->set('acyJCERedirectionPrevented', true);
                }

                $params->set('replace_media_manager', 0);
            }
        }
    }

    public function onAfterRender()
    {
        $jversion = preg_replace('#[^0-9\.]#i', '', JVERSION);

        if (version_compare($jversion, '4.0.0', '>=')) {
            $result = Joomla\CMS\Factory::getApplication()->getSession()->get('acyJCERedirectionPrevented', false);
        } else {
            $result = JFactory::getSession()->get('acyJCERedirectionPrevented', false);
        }

        if (empty($result)) {
            return;
        }

        if (version_compare($jversion, '4.0.0', '>=')) {
            $session = Joomla\CMS\Factory::getApplication()->getSession();
            $session->remove('acyJCERedirectionPrevented');
        } else {
            $session = JFactory::getSession();
            $session->clear('acyJCERedirectionPrevented');
        }

        $params = ComponentHelper::getParams('com_jce');
        if (!empty($params)) {
            // Re-set the JCE option value
            $params->set('replace_media_manager', 1);
        }
    }

    private function isEditorEnabled()
    {
        if (!PluginHelper::getPlugin('editors', 'jce')) {
            return false;
        }

        $config = Factory::getConfig();
        $user = Factory::getUser();

        if ($user->getParam('editor', $config->get('editor')) !== 'jce') {
            return false;
        }

        return true;
    }
}
