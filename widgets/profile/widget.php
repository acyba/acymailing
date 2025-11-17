<?php

use AcyMailing\Classes\ListClass;
use AcyMailing\FrontControllers\FrontusersController;
use AcyMailing\Core\AcymParameter;

class acym_profile_widget extends WP_Widget
{
    public function __construct()
    {
        $this->loadAcyMailing();

        parent::__construct(
            'acym_profile_widget',
            acym_translationSprintf('ACYM_MENU', acym_translation('ACYM_MENU_PROFILE')),
            ['description' => acym_translation('ACYM_MENU_PROFILE_DESC')]
        );
    }

    // Configuration
    public function form($instance)
    {
        $this->loadAcyMailing();

        acym_addStyle(false, ACYM_CSS.'widget.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'widget.min.css'));

        $listClass = new ListClass();
        $lists = $listClass->getAllWithoutManagement();
        foreach ($lists as $i => $oneList) {
            if ($oneList->active == 0) {
                unset($lists[$i]);
            }
        }

        $params = [
            'title' => 'Your profile',
            'lists' => 'All',
            'listschecked' => 'None',
            'dropdown' => false,
            'hiddenlists' => 'None',
            'fields' => '1',
            'introtext' => '',
            'posttext' => '',
            'source' => 'profile __i__',
        ];

        foreach ($params as $oneParam => &$value) {
            if (!empty($instance)) {
                $value = $instance[$oneParam] ?? '';
            }

            if (is_array($value)) {
                $value = implode(',', $value);
            }

            if (in_array($oneParam, ['dropdown'])) {
                $value = (bool)$value;
            } else {
                $value = esc_attr($value);
            }
        }

        if (!isset($instance['hiddenlists']) && !empty($params['lists'])) {
            $params['hiddenlists'] = '';
        }

        echo '<div class="acym_toggle_zone">
                <div class="acyblock" id="mainopt_profilewidget">
                    <div class="acym_toggle_div_title">
                        <h3>'.acym_translation('ACYM_MAIN_OPTIONS').'</h3>
                    </div>
                    <div class="acym_toggle_div" style="display: none;">';

        echo '<p><label class="acyWPconfig" for="'.$this->get_field_id('title').'">'.acym_translation('ACYM_TITLE').'</label>
			<input type="text" class="widefat" id="'.$this->get_field_id('title').'" name="'.$this->get_field_name('title').'" value="'.$params['title'].'" /></p>';


        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_VISIBLE_LISTS_DESC').'">'.acym_translation('ACYM_VISIBLE_LISTS').'</label>';
        echo acym_displayParam('lists', $params['lists'], $this->get_field_name('lists')).'</p>';

        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_DROPDOWN_LISTS_DESC').'">'.acym_translation('ACYM_DROPDOWN_LISTS').'</label>';
        echo acym_boolean($this->get_field_name('dropdown'), $params['dropdown'], $this->get_field_id('dropdown')).'</p>';

        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_LISTS_CHECKED_DEFAULT_DESC').'">'.acym_translation('ACYM_LISTS_CHECKED_DEFAULT').'</label>';
        echo acym_displayParam('lists', $params['listschecked'], $this->get_field_name('listschecked')).'</p>';

        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_AUTO_SUBSCRIBE_TO_DESC').'">'.acym_translation('ACYM_AUTO_SUBSCRIBE_TO').'</label>';
        echo acym_displayParam('lists', $params['hiddenlists'], $this->get_field_name('hiddenlists')).'</p>';

        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_FIELDS_TO_DISPLAY_DESC').'">'.acym_translation('ACYM_FIELDS_TO_DISPLAY').'</label>';
        echo acym_displayParam('fields', $params['fields'], $this->get_field_name('fields')).'</p>';

        echo '</div>
            </div>
            <div class="acyblock" id="advopt_profilewidget">
                <div class="acym_toggle_div_title">
                    <h3>'.acym_translation('ACYM_ADVANCED_OPTIONS').'</h3>
                </div>
                <div class="acym_toggle_div" style="display: none;">';

        echo '<p><label class="acyWPconfig" for="'.$this->get_field_id('introtext').'" title="'.acym_translation('ACYM_INTRO_TEXT_DESC').'">'.acym_translation('ACYM_INTRO_TEXT').'</label>
			<textarea class="widefat" id="'.$this->get_field_id('introtext').'" name="'.$this->get_field_name('introtext').'" >'.$params['introtext'].'</textarea></p>';

        echo '<p><label class="acyWPconfig" for="'.$this->get_field_id('posttext').'" title="'.acym_translation('ACYM_POST_TEXT_DESC').'">'.acym_translation('ACYM_POST_TEXT').'</label>
			<textarea class="widefat" id="'.$this->get_field_id('posttext').'" name="'.$this->get_field_name('posttext').'" >'.$params['posttext'].'</textarea></p>';

        echo '<p><label class="acyWPconfig" for="'.$this->get_field_id('source').'" title="'.acym_translation('ACYM_SOURCE_DESC').'">'.acym_translation('ACYM_SOURCE').'</label>
			<input type="text" class="widefat" id="'.$this->get_field_id('source').'" name="'.$this->get_field_name('source').'" value="'.$params['source'].'" /></p>';

        echo '</div></div></div>';
    }

    // Widget's output
    public function widget($args, $instance)
    {
        $this->loadAcyMailing();

        if (!acym_isElementorEdition()) {
            acym_loadAssets('frontusers', 'profile');
        }

        echo $args['before_widget'];

        if (!isset($instance['title'])) $instance['title'] = '';
        $title = apply_filters('widget_title', $instance['title'], $instance, $args['widget_id']);
        if (!empty($title)) {
            echo $args['before_title'].$title.$args['after_title'];
        }

        acym_setVar('page', ACYM_COMPONENT.'_front');
        $params = new AcymParameter($instance);
        acym_initModule($params);

        $userController = new FrontusersController();
        $data = $userController->prepareParams((object)$instance);

        if (empty($data['user']->language)) {
            $cmsUserLanguage = acym_getCmsUserLanguage();
            $data['user']->language = empty($cmsUserLanguage) ? acym_getLanguageTag() : $cmsUserLanguage;
        }

        acym_setVar('layout', 'profile');
        $userController->display($data);

        echo $args['after_widget'];
    }

    private function loadAcyMailing(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        require_once rtrim(dirname(dirname(__DIR__)), $ds).$ds.'back'.$ds.'Core'.$ds.'init.php';
    }
}
