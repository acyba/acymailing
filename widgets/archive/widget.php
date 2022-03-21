<?php

use AcyMailing\Classes\ListClass;
use AcyMailing\FrontControllers\ArchiveController;

class acym_archive_widget extends WP_Widget
{
    public function __construct()
    {
        $ds = DIRECTORY_SEPARATOR;
        require_once rtrim(dirname(dirname(__DIR__)), $ds).$ds.'back'.$ds.'helpers'.$ds.'helper.php';

        parent::__construct(
            'acym_archive_widget',
            acym_translationSprintf('ACYM_MENU', acym_translation('ACYM_MENU_ARCHIVE')),
            ['description' => acym_translation('ACYM_MENU_ARCHIVE_DESC')]
        );
    }

    //Widget Configuration
    public function form($instance)
    {
        $ds = DIRECTORY_SEPARATOR;
        require_once rtrim(dirname(dirname(__DIR__)), $ds).$ds.'back'.$ds.'helpers'.$ds.'helper.php';

        $params = [
            'title' => 'See all newsletters',
            'lists' => '',
            'popup' => '1',
            'displayUserListOnly' => '1',
        ];

        $listClass = new ListClass();
        $lists = $listClass->getAllWithoutManagement();
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

        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_LISTS_ARCHIVE').'">'.acym_translation('ACYM_LISTS').'</label>';
        echo acym_selectMultiple(
            $lists,
            $this->get_field_name('lists'),
            explode(',', $params['lists']),
            [
                'style' => 'width:100%;',
                'id' => $this->get_field_id('lists'),
            ],
            'id',
            'name'
        );

        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_ARCHIVE_POPUP_DESC').'">'.acym_translation('ACYM_ARCHIVE_POPUP').'</label>';
        echo acym_boolean($this->get_field_name('popup'), $params['popup'], $this->get_field_id('popup')).'</p>';

        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_ARCHIVE_ONLY_USER_LIST_DESC').'">'.acym_translation('ACYM_ARCHIVE_ONLY_USER_LIST').'</label>';
        echo acym_boolean($this->get_field_name('displayUserListOnly'), $params['displayUserListOnly'], $this->get_field_id('displayUserListOnly')).'</p>';

        echo '</div>';
    }

    // Widget's output
    public function widget($args, $instance)
    {
        $ds = DIRECTORY_SEPARATOR;
        require_once rtrim(dirname(dirname(__DIR__)), $ds).$ds.'back'.$ds.'helpers'.$ds.'helper.php';
        if (!acym_isElementorEdition()) acym_loadAssets('archive', 'listing');

        echo $args['before_widget'];

        if (!isset($instance['title'])) $instance['title'] = '';
        $title = apply_filters('widget_title', $instance['title'], $instance, $args['widget_id']);
        if (!empty($title)) {
            echo $args['before_title'].$title.$args['after_title'];
        }

        acym_setVar('page', 'front');
        $searchs = acym_getVar('array', 'acym_search', []);
        $search = '';
        if (!empty($searchs[$args['widget_id']])) $search = $searchs[$args['widget_id']];

        $viewParams = [
            'listsSent' => isset($instance['lists']) ? $instance['lists'] : [],
            'popup' => isset($instance['popup']) ? $instance['popup'] : '1',
            'displayUserListOnly' => isset($instance['displayUserListOnly']) ? $instance['displayUserListOnly'] : '1',
            'paramsCMS' => ['widget_id' => $args['widget_id'], 'suffix' => ''],
            'search' => $search,
        ];

        $archiveController = new ArchiveController();
        $archiveController->showArchive($viewParams);

        echo $args['after_widget'];
    }
}
