<?php

include_once dirname(__DIR__).DS.'widgets'.DS.'profile'.DS.'widget.php';
include_once dirname(__DIR__).DS.'widgets'.DS.'subscriptionform'.DS.'widget.php';
include_once dirname(__DIR__).DS.'widgets'.DS.'archive'.DS.'widget.php';

class acyWidget extends acyHook
{
    public function __construct()
    {
        add_action('widgets_init', [$this, 'loadWidgets']);
    }

    public function loadWidgets()
    {
        register_widget('acym_profile_widget');
        register_widget('acym_subscriptionform_widget');
        register_widget('acym_archive_widget');
    }
}

new acyWidget();
