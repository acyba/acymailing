<?php

use AcyMailing\Classes\PluginClass;

function acym_getGlobal($type)
{
    $variables = [
        'db' => ['acydb', 'getDBO'],
        'doc' => ['acyDocument', 'getDocument'],
        'app' => ['acyapp', 'getApplication'],
    ];

    global ${$variables[$type][0]};
    if (${$variables[$type][0]} === null) {
        $method = $variables[$type][1];
        ${$variables[$type][0]} = JFactory::$method();
    }

    return ${$variables[$type][0]};
}

function acym_addBreadcrumb($title, $link = '')
{
    $acyapp = acym_getGlobal('app');
    $pathway = $acyapp->getPathway();
    $pathway->addItem($title, $link);
}

function acym_setPageTitle($title)
{
    if (empty($title)) {
        $title = acym_getCMSConfig('sitename');
    } elseif (acym_getCMSConfig('sitename_pagetitles', 0) == 1) {
        $title = acym_translationSprintf('ACYM_JPAGETITLE', acym_getCMSConfig('sitename'), $title);
    } elseif (acym_getCMSConfig('sitename_pagetitles', 0) == 2) {
        $title = acym_translationSprintf('ACYM_JPAGETITLE', $title, acym_getCMSConfig('sitename'));
    }
    $document = JFactory::getDocument();
    $document->setTitle($title);
}

function acym_isLeftMenuNecessary()
{
    return (!ACYM_J40 && acym_isAdmin() && !acym_isNoTemplate());
}

function acym_getLeftMenu($name)
{
    $pluginClass = new PluginClass();
    $nbPluginNotUptodate = $pluginClass->getNotUptoDatePlugins();

    $addOnsTitle = empty($nbPluginNotUptodate) ? 'ACYM_ADD_ONS' : acym_translationSprintf('ACYM_ADD_ONS_X', $nbPluginNotUptodate);
    $isCollapsed = empty($_COOKIE['menuJoomla']) ? '' : $_COOKIE['menuJoomla'];

    $menus = [
        'dashboard' => ['title' => 'ACYM_DASHBOARD', 'class-i' => 'acymicon-dashboard', 'span-class' => ''],
        'forms' => ['title' => 'ACYM_SUBSCRIPTION_FORMS', 'class-i' => 'acymicon-edit', 'span-class' => 'acym__joomla__left-menu__fa'],
        'users' => ['title' => 'ACYM_SUBSCRIBERS', 'class-i' => 'acymicon-group', 'span-class' => ''],
        'fields' => ['title' => 'ACYM_CUSTOM_FIELDS', 'class-i' => 'acymicon-text_fields', 'span-class' => ''],
        'lists' => ['title' => 'ACYM_LISTS', 'class-i' => 'acymicon-address-book-o', 'span-class' => 'acym__joomla__left-menu__fa'],
    ];
    $menus['campaigns'] = ['title' => 'ACYM_EMAILS', 'class-i' => 'acymicon-email', 'span-class' => ''];
    $menus['mails'] = ['title' => 'ACYM_TEMPLATES', 'class-i' => 'acymicon-pencil', 'span-class' => 'acym__joomla__left-menu__fa'];
    $menus['override'] = ['title' => 'ACYM_EMAILS_OVERRIDE', 'class-i' => 'acymicon-paint-format', 'span-class' => 'acym__joomla__left-menu__fa'];
    $menus['queue'] = ['title' => 'ACYM_QUEUE', 'class-i' => 'acymicon-hourglass-2', 'span-class' => 'acym__joomla__left-menu__fa'];
    $menus['stats'] = ['title' => 'ACYM_STATISTICS', 'class-i' => 'acymicon-bar-chart', 'span-class' => 'acym__joomla__left-menu__fa'];
    $menus['plugins'] = ['title' => $addOnsTitle, 'class-i' => 'acymicon-plug', 'span-class' => 'acym__joomla__left-menu__fa'];
    $menus['configuration'] = ['title' => 'ACYM_CONFIGURATION', 'class-i' => 'acymicon-settings', 'span-class' => ''];

    if (!acym_level(ACYM_ESSENTIAL)) {
        $menus['gopro'] = ['title' => 'ACYM_GOPRO', 'class-i' => 'acymicon-star', 'span-class' => ''];
    }


    $leftMenu = '<div id="acym__joomla__left-menu--show"><i class="acym-logo"></i><i id="acym__joomla__left-menu--burger" class="acymicon-menu"></i></div>
                    <div id="acym__joomla__left-menu" class="'.$isCollapsed.'">
                        <i class="acymicon-close" id="acym__joomla__left-menu--close"></i>';
    foreach ($menus as $oneMenu => $menuOption) {
        if (!acym_isAllowed($oneMenu)) continue;

        $class = $name == $oneMenu ? 'acym__joomla__left-menu--current' : '';
        $leftMenu .= '<a href="'.acym_completeLink(
                $oneMenu
            ).'" class="'.$class.'"><i class="'.$menuOption['class-i'].'"></i><span class="'.$menuOption['span-class'].'">'.acym_translation($menuOption['title']).'</span></a>';
    }

    $leftMenu .= '<a href="#" id="acym__joomla__left-menu--toggle"><i class="acymicon-keyboard_arrow_left"></i><span>'.acym_translation('ACYM_COLLAPSE').'</span></a>';

    $leftMenu .= '</div>';

    return $leftMenu;
}

function acym_isPluginActive($plugin, $family = 'system')
{
    $plugin = JPluginHelper::getPlugin($family, $plugin);

    return !empty($plugin);
}

function acym_menuOnly($link)
{
    $menu = JFactory::getApplication('site')->getMenu()->getActive();
    if (empty($menu) || $menu->link !== $link) {
        acym_redirect(acym_rootURI(), 'ACYM_UNAUTHORIZED_ACCESS', 'error');
    }
}

function acym_disableCmsEditor()
{
}
