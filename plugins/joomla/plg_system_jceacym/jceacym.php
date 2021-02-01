<?php

defined('_JEXEC') or die('Restricted access');

class plgSystemJceacym extends JPlugin
{
    public function onBeforeWfEditorRender(&$settings)
    {
        if (empty($_REQUEST['option']) || $_REQUEST['option'] != 'com_acym') {
            return;
        }

        // We're in a newsletter context, no other CSS file should be applied.
        if (!empty($_REQUEST['acycssfile'])) {
            $settings['content_css'] = $_REQUEST['acycssfile'];
        }
    }
}
