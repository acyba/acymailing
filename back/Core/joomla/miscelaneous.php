<?php

use AcyMailing\Classes\PluginClass;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\Component\Scheduler\Administrator\Table\TaskTable;

function acym_getGlobal(string $type): object
{
    $variables = [
        'db' => ['acydb', 'getDbo'],
        'doc' => ['acyDocument', 'getDocument'],
        'app' => ['acyapp', 'getApplication'],
    ];

    global ${$variables[$type][0]};
    if (${$variables[$type][0]} === null) {
        $method = $variables[$type][1];
        ${$variables[$type][0]} = Factory::$method();
    }

    return ${$variables[$type][0]};
}

function acym_addBreadcrumb(string $title, string $link = ''): void
{
    $acyapp = acym_getGlobal('app');
    $pathway = $acyapp->getPathway();
    $pathway->addItem($title, $link);
}

function acym_setPageTitle(string $title): void
{
    if (empty($title)) {
        $title = acym_getCMSConfig('sitename');
    } elseif (acym_getCMSConfig('sitename_pagetitles', 0) == 1) {
        $title = acym_translationSprintf('ACYM_JPAGETITLE', acym_getCMSConfig('sitename'), $title);
    } elseif (acym_getCMSConfig('sitename_pagetitles', 0) == 2) {
        $title = acym_translationSprintf('ACYM_JPAGETITLE', $title, acym_getCMSConfig('sitename'));
    }
    $document = Factory::getDocument();
    $document->setTitle($title);
}

function acym_isLeftMenuNecessary(): bool
{
    return !ACYM_J40 && acym_isAdmin() && !acym_isNoTemplate();
}

function acym_displayLeftMenu(string $name): void
{
    $pluginClass = new PluginClass();
    $nbPluginNotUptodate = count($pluginClass->getNotUptoDatePlugins());

    $addOnsTitle = empty($nbPluginNotUptodate) ? 'ACYM_ADD_ONS' : acym_translationSprintf('ACYM_ADD_ONS_X', $nbPluginNotUptodate);
    $isCollapsed = empty($_COOKIE['menuJoomla']) ? '' : $_COOKIE['menuJoomla'];

    $menus = [
        'dashboard' => ['title' => 'ACYM_DASHBOARD', 'class-i' => 'acymicon-dashboard', 'span-class' => ''],
        'forms' => ['title' => 'ACYM_SUBSCRIPTION_FORMS', 'class-i' => 'acymicon-edit', 'span-class' => 'acym__joomla__left-menu__fa'],
        'users' => ['title' => 'ACYM_SUBSCRIBERS', 'class-i' => 'acymicon-group', 'span-class' => ''],
        'fields' => ['title' => 'ACYM_CUSTOM_FIELDS', 'class-i' => 'acymicon-text-fields', 'span-class' => ''],
        'lists' => ['title' => 'ACYM_LISTS', 'class-i' => 'acymicon-address-book-o', 'span-class' => 'acym__joomla__left-menu__fa'],
    ];
    $menus['campaigns'] = ['title' => 'ACYM_EMAILS', 'class-i' => 'acymicon-email', 'span-class' => ''];
    $menus['mails'] = ['title' => 'ACYM_TEMPLATES', 'class-i' => 'acymicon-pencil', 'span-class' => 'acym__joomla__left-menu__fa'];
    $menus['override'] = ['title' => 'ACYM_EMAILS_OVERRIDE', 'class-i' => 'acymicon-paint-format', 'span-class' => 'acym__joomla__left-menu__fa'];
    $menus['queue'] = ['title' => 'ACYM_QUEUE', 'class-i' => 'acymicon-hourglass-2', 'span-class' => 'acym__joomla__left-menu__fa'];
    $menus['stats'] = ['title' => 'ACYM_STATISTICS', 'class-i' => 'acymicon-bar-chart', 'span-class' => 'acym__joomla__left-menu__fa'];
    $menus['plugins'] = ['title' => $addOnsTitle, 'class-i' => 'acymicon-puzzle-piece', 'span-class' => 'acym__joomla__left-menu__fa'];
    $menus['configuration'] = ['title' => 'ACYM_CONFIGURATION', 'class-i' => 'acymicon-cog', 'span-class' => ''];

    if (!acym_level(ACYM_ESSENTIAL)) {
        $menus['gopro'] = ['title' => 'ACYM_GOPRO', 'class-i' => 'acymicon-star', 'span-class' => ''];
    }


    echo '<div id="acym__joomla__left-menu--show"><i class="acym-logo"></i><i id="acym__joomla__left-menu--burger" class="acymicon-menu"></i></div>
            <div id="acym__joomla__left-menu" class="'.acym_escape($isCollapsed).'">
                <i class="acymicon-close" id="acym__joomla__left-menu--close"></i>';
    foreach ($menus as $oneMenu => $menuOption) {
        if (!acym_isAllowed($oneMenu)) {
            continue;
        }

        $class = $name === $oneMenu ? 'acym__joomla__left-menu--current' : '';
        echo '<a href="'.acym_escapeUrl(acym_completeLink($oneMenu)).'" class="'.acym_escape($class).'"><i class="'.acym_escape($menuOption['class-i']).'"></i>
            <span class="'.acym_escape($menuOption['span-class']).'">'.acym_escapeHtml(acym_translation($menuOption['title'])).'</span>
        </a>';
    }

    echo '<a href="#" id="acym__joomla__left-menu--toggle"><i class="acymicon-keyboard-arrow-left"></i><span>'.acym_escapeHtml(acym_translation('ACYM_COLLAPSE')).'</span></a>';
    echo '</div>';
}

function acym_isPluginActive(string $plugin, string $family = 'system'): bool
{
    $plugin = PluginHelper::getPlugin($family, $plugin);

    return !empty($plugin);
}

function acym_disableCmsEditor(): void
{
}

function acym_scheduleTask(array $options): ?int
{
    if (!ACYM_J40) {
        return null;
    }

    $model = Factory::getApplication()
                    ->bootComponent('com_scheduler')
                    ->getMVCFactory()
                    ->createModel('Task', 'Administrator', ['ignore_request' => true]);
    $table = $model->getTable();

    $cronKey = '';
    if (!empty($options['config']['cron_security']) && !empty($options['config']['cron_key'])) {
        $cronKey = '&cronKey='.$options['config']['cron_key'];
    }

    $cronUrl = acym_frontendLink('cron&task=cron'.$cronKey);

    if (empty($options['taskId']) || !$table->load($options['taskId'])) {
        $table->title = $options['title'];
        $table->type = $options['type'];

        $table->params = json_encode(
            (object)[
                'individual_log' => false,
                'log_file' => '',
                'notifications' => (object)[
                    'success_mail' => '0',
                    'failure_mail' => '1',
                    'notification_failure_groups' => [
                        ACYM_ADMIN_GROUP,
                    ],
                    'fatal_failure_mail' => '1',
                    'notification_fatal_groups' => [
                        ACYM_ADMIN_GROUP,
                    ],
                    'orphan_mail' => '0',
                ],
                'url' => $cronUrl,
                'timeout' => 600,
                'auth' => 0,
                'authType' => 'Bearer',
                'authKey' => '',
            ]
        );
    } else {
        $params = json_decode($table->params, true);
        $params['url'] = $cronUrl;
        $params['notifications'] = (object)$params['notifications'];
        $table->params = json_encode((object)$params);
    }

    $table->state = 1;
    $table->next_execution = date('Y-m-d H:i:s', time() - date('Z'));
    $table->execution_rules = json_encode(
        (object)[
            'rule-type' => 'interval-minutes',
            'interval-minutes' => $options['frequencyInMinutes'],
            'exec-day' => date('d', time() - date('Z')),
            'exec-time' => date('H:i:s', time() - date('Z')),
        ]
    );
    $table->cron_rules = json_encode(
        (object)[
            'type' => 'interval',
            'exp' => 'PT'.$options['frequencyInMinutes'].'M',
        ]
    );

    if (!$table->check() || !$table->store()) {
        acym_logError($table->getError());

        return null;
    }

    return (int)$table->id;
}

function acym_deleteScheduledTask(array $options): bool
{
    if (empty($options['taskId']) || $options['taskId'] <= 0) {
        return false;
    }

    $model = Factory::getApplication()
                    ->bootComponent('com_scheduler')
                    ->getMVCFactory()
                    ->createModel('Task', 'Administrator', ['ignore_request' => true]);
    $task = $model->getItem($options['taskId']);

    if (empty($task) || $task->type !== $options['type']) {
        return false;
    }

    $table = $model->getTable();

    return (bool)$table->delete($options['taskId']);
}

function acym_rand(int $min, int $max): int
{
    try {
        return random_int($min, $max);
    } catch (\Exception $e) {
        return mt_rand($min, $max);
    }
}
