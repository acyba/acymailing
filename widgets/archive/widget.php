<?php

class acym_archive_widget extends WP_Widget
{
    public function __construct()
    {
        require_once rtrim(dirname(dirname(__DIR__)), DS).DS.'back'.DS.'helpers'.DS.'helper.php';

        parent::__construct(
            'acym_archive_widget',
            acym_translation_sprintf('ACYM_MENU', acym_translation('ACYM_MENU_ARCHIVE')),
            ['description' => acym_translation('ACYM_MENU_ARCHIVE_DESC')]
        );
    }

    //Widget Configuration
    public function form($instance)
    {
        require_once rtrim(dirname(dirname(__DIR__)), DS).DS.'back'.DS.'helpers'.DS.'helper.php';

        $params = [
            'title' => 'See all newsletters',
            'nbNewsletters' => '5',
            'lists' => '',
            'popup' => '1',
        ];

        $listClass = acym_get('class.list');
        $lists = $listClass->getAllWIthoutManagement();
        foreach ($lists as $i => $oneList) {
            if ($oneList->active == 0) {
                unset($lists[$i]);
            }
        }

        foreach ($params as $keyParam => &$valueParam) {
            if (isset($instance[$keyParam])) {
                $valueParam = $instance[$keyParam];
            }

            if (is_array($valueParam)) {
                $valueParam = implode(',', $valueParam);
            }

            $valueParam = esc_attr($valueParam);
        }

        echo '<div class="acyblock">';
        echo '<p><label class="acyWPconfig" for="'.$this->get_field_id('title').'">'.acym_translation('ACYM_TITLE').'</label>
			<input type="text" class="widefat" id="'.$this->get_field_id('title').'" name="'.$this->get_field_name('title').'" value="'.$params['title'].'" /></p>';

        echo '<p><label class="acyWPconfig"> '.acym_translation('ACYM_WIDGET_ARCHIVE_CHOICE').'</label>
           '.acym_translation_sprintf('ACYM_LAST_X_NEWSLETTERS', '<input class="tiny-text" type="number" min="1"  max="20" name="'.$this->get_field_name('nbNewsletters').'" value="'.$params['nbNewsletters'].'">').'</p>';

        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_LISTS_ARCHIVE').'">'.acym_translation('ACYM_LISTS').'</label>';
        echo acym_selectMultiple($lists, $this->get_field_name('lists'), explode(',', $params['lists']), ['class' => 'acym_simple_select2', 'id' => $this->get_field_id('lists')], 'id', 'name');

        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_ARCHIVE_POPUP_DESC').'">'.acym_translation('ACYM_ARCHIVE_POPUP').'</label>';
        echo acym_boolean($this->get_field_name('popup'), $params['popup'], $this->get_field_id('popup')).'</p>';

        echo '</div>';
    }

    // Widget's output
    public function widget($args, $instance)
    {
        require_once rtrim(dirname(dirname(__DIR__)), DS).DS.'back'.DS.'helpers'.DS.'helper.php';
        if (!acym_isElementorEdition()) acym_loadAssets('archive', 'listing');

        echo $args['before_widget'];

        $title = apply_filters('widget_title', $instance['title']);
        if (!empty($title)) {
            echo $args['before_title'].$title.$args['after_title'];
        }

        acym_displayMessages();
        acym_setVar('page', 'front');

        $viewParams = [
            'nbNewsletters' => isset($instance['nbNewsletters']) ? $instance['nbNewsletters'] : '',
            'listsSent' => isset($instance['lists']) ? $instance['lists'] : [],
            'popup' => isset($instance['popup']) ? $instance['popup'] : '1',
            'paramsCMS' => [],
        ];

        $archiveController = acym_get('controller_front.archive');
        $archiveController->showArchive($viewParams);

        echo $args['after_widget'];
    }
}
