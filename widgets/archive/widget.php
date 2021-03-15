<?php

use AcyMailing\Classes\ListClass;
use AcyMailing\FrontControllers\ArchiveController;

class acym_archive_widget extends WP_Widget
{
    public function __construct()
    {
        require_once rtrim(dirname(dirname(__DIR__)), DS).DS.'back'.DS.'helpers'.DS.'helper.php';

        parent::__construct(
            'acym_archive_widget',
            acym_translationSprintf('ACYM_MENU', acym_translation('ACYM_MENU_ARCHIVE')),
            ['description' => acym_translation('ACYM_MENU_ARCHIVE_DESC')]
        );
    }

    //Widget Configuration
    public function form($instance)
    {
        require_once rtrim(dirname(dirname(__DIR__)), DS).DS.'back'.DS.'helpers'.DS.'helper.php';

        $params = [
            'title' => 'See all newsletters',
            'nbNewslettersPerPage' => '10',
            'lists' => '',
            'popup' => '1',
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

        echo '<p><label class="acyWPconfig"> '.acym_translation('ACYM_WIDGET_ARCHIVE_NUMBER_PER_PAGE').'</label>
           '.acym_translationSprintf(
                'ACYM_X_NEWSLETTERS_PER_PAGE',
                '<input class="tiny-text" type="number" min="1"  max="20" name="'.$this->get_field_name('nbNewslettersPerPage').'" value="'.$params['nbNewslettersPerPage'].'">'
            ).'</p>';

        echo '<p><label class="acyWPconfig" title="'.acym_translation('ACYM_LISTS_ARCHIVE').'">'.acym_translation('ACYM_LISTS').'</label>';
        echo acym_selectMultiple(
            $lists,
            $this->get_field_name('lists'),
            explode(',', $params['lists']),
            ['class' => 'acym_simple_select2', 'id' => $this->get_field_id('lists')],
            'id',
            'name'
        );

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

        if (!isset($instance['title'])) $instance['title'] = '';
        $title = apply_filters('widget_title', $instance['title']);
        if (!empty($title)) {
            echo $args['before_title'].$title.$args['after_title'];
        }

        acym_displayMessages();
        acym_setVar('page', 'front');
        $searchs = acym_getVar('array', 'acym_search', []);
        $search = '';
        if (!empty($searchs[$args['widget_id']])) $search = $searchs[$args['widget_id']];

        $viewParams = [
            'listsSent' => isset($instance['lists']) ? $instance['lists'] : [],
            'popup' => isset($instance['popup']) ? $instance['popup'] : '1',
            'nbNewslettersPerPage' => isset($instance['nbNewslettersPerPage']) ? $instance['nbNewslettersPerPage'] : '10',
            'paramsCMS' => ['widget_id' => $args['widget_id']],
            'search' => $search,
        ];

        $archiveController = new ArchiveController();
        $archiveController->showArchive($viewParams);

        echo $args['after_widget'];
    }
}
