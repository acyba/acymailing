<?php

namespace AcyMailing\WpInit;

class Deactivate
{
    public function __construct()
    {
        if (strpos(acym_currentURL(), 'plugins.php') !== false) {
            add_action('admin_footer', [$this, 'add_deactivation_feedback_dialog_box']);
        }
    }

    public function add_deactivation_feedback_dialog_box()
    {
        include acym_getPartial('modal', 'deactivate');
    }
}
