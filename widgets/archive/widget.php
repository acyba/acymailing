<?php

defined('ABSPATH') || die('Restricted Access');

use AcyMailing\Classes\ListClass;
use AcyMailing\FrontControllers\ArchiveController;

class acym_archive_widget extends WP_Widget
{
    public function __construct()
    {
        $this->loadAcyMailing();

        parent::__construct(
            'acym_archive_widget',
            acym_translationSprintf('ACYM_MENU', acym_translation('ACYM_MENU_ARCHIVE')),
            ['description' => acym_translation('ACYM_MENU_ARCHIVE_DESC')]
        );
    }

    //Widget Configuration
    public function form($instance)
    {
        $this->loadAcyMailing();

        $params = [
            'title' => 'See all newsletters',
            'lists' => '',
            'popup' => true,
            'displayUserListOnly' => true,
            'nbNewslettersPerPage' => '10',
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

            if (in_array($keyParam, ['popup', 'displayUserListOnly'])) {
                $valueParam = (bool)$valueParam;
            } else {
                $valueParam = esc_attr($valueParam);
            }
        }

        echo '<div class="acyblock">';
        echo '<p><label class="acyWPconfig" for="'.esc_attr($this->get_field_id('title')).'">'.esc_html(acym_translation('ACYM_TITLE')).'</label>
			<input type="text" class="widefat" id="'.esc_attr($this->get_field_id('title')).'" name="'.esc_attr($this->get_field_name('title')).'" value="'.esc_attr(
                $params['title']
            ).'" /></p>';
        echo '<p><label class="acyWPconfig"> '.esc_html(acym_translation('ACYM_WIDGET_CAMPAIGN_NUMBER_PER_PAGE')).'</label>
           '.wp_kses(
                acym_translationSprintf(
                    'ACYM_X_NEWSLETTERS_PER_PAGE',
                    acym_select(
                        [
                            '5' => '5',
                            '10' => '10',
                            '15' => '15',
                            '20' => '20',
                            '30' => '30',
                            '50' => '50',
                            '100' => '100',
                            '200' => '200',
                        ],
                        $this->get_field_name('nbNewslettersPerPage'),
                        $params['nbNewslettersPerPage']
                    )
                ),
                [
                    'select' => [
                        'name' => true,
                        'id' => true,
                    ],
                    'option' => [
                        'value' => true,
                        'selected' => true,
                    ],
                ]
            ).'</p>';
        echo '<p><label class="acyWPconfig" title="'.esc_attr(acym_translation('ACYM_LISTS_ARCHIVE')).'">'.esc_html(acym_translation('ACYM_LISTS')).'</label>';
        acym_selectMultiple(
            $lists,
            $this->get_field_name('lists'),
            explode(',', $params['lists']),
            [
                'style' => 'width:100%;',
                'id' => $this->get_field_id('lists'),
            ],
            'id',
            'name',
            true
        );

        echo '<p><label class="acyWPconfig" title="'.esc_attr(acym_translation('ACYM_ARCHIVE_POPUP_DESC')).'">'.esc_html(acym_translation('ACYM_ARCHIVE_POPUP')).'</label>';
        acym_boolean($this->get_field_name('popup'), $params['popup'], $this->get_field_id('popup')).'</p>';

        echo '<p><label class="acyWPconfig" title="'.esc_attr(acym_translation('ACYM_ARCHIVE_ONLY_USER_LIST_DESC')).'">'.esc_html(
                acym_translation('ACYM_ARCHIVE_ONLY_USER_LIST')
            ).'</label>';
        acym_boolean($this->get_field_name('displayUserListOnly'), $params['displayUserListOnly'], $this->get_field_id('displayUserListOnly')).'</p>';

        echo '</div>';
    }

    // Widget's output
    public function widget($args, $instance)
    {
        $this->loadAcyMailing();

        if (!acym_isElementorEdition()) {
            acym_loadAssets('archive', 'listing');
        }

        echo wp_kses_post($args['before_widget']);

        if (!isset($instance['title'])) $instance['title'] = '';
        $title = apply_filters('widget_title', $instance['title'], $instance, $args['widget_id']);
        if (!empty($title)) {
            echo wp_kses_post($args['before_title']);
            echo esc_html($title);
            echo wp_kses_post($args['after_title']);
        }

        acym_setVar('page', 'front');
        $searchs = acym_getVar('array', 'acym_search', []);
        $search = '';
        if (!empty($searchs[$args['widget_id']])) $search = $searchs[$args['widget_id']];

        $viewParams = [
            'listsSent' => $instance['lists'] ?? [],
            'popup' => $instance['popup'] ?? '1',
            'displayUserListOnly' => $instance['displayUserListOnly'] ?? '1',
            'paramsCMS' => ['widget_id' => $args['widget_id'], 'suffix' => ''],
            'search' => $search,
            'nbNewslettersPerPage' => $instance['nbNewslettersPerPage'] ?? '',
        ];

        $archiveController = new ArchiveController();
        $archiveController->showArchive($viewParams);

        echo wp_kses_post($args['after_widget']);
    }

    private function loadAcyMailing(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        require_once rtrim(dirname(dirname(__DIR__)), $ds).$ds.'back'.$ds.'Core'.$ds.'init.php';
    }
}
