<?php

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\Component\Content\Site\Helper\RouteHelper;

function acym_getPageLink($menu)
{
    $menuDB = acym_loadObject(
        'SELECT menu.link, menu.id FROM #__menu AS menu
                JOIN #__menu_types AS menu_types ON menu_types.menutype = menu.menutype
                WHERE menu.published = 1 AND menu.link LIKE '.acym_escapeDB('%'.$menu.'%')
    );

    return empty($menuDB) ? '' : acym_frontendLink($menuDB->link.'&Itemid='.$menuDB->id, false);
}

function acym_cmsModal($isIframe, $content, $buttonText, $isButton, $modalTitle, $identifier = null, $width = '800', $height = '400')
{
    if (empty($identifier)) {
        $identifier = 'identifier_'.rand(1000, 9000);
    }

    $params = [
        'title' => $modalTitle,
        'url' => $content,
        'height' => $height.'px',
        'width' => $width.'px',
        'bodyHeight' => '70',
        'modalWidth' => '80',
    ];

    HTMLHelper::_('jquery.framework');
    if (ACYM_J40) {
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->useScript('field.modal-fields');
        acym_addStyle(
            true,
            '
            #'.$identifier.' {
                height: auto;
                border: none;
            }
            
            #'.$identifier.' .modal-dialog {
                margin: 0;
            }'
        );
    } else {
        HTMLHelper::_('script', 'system/modal-fields.js', ['version' => 'auto', 'relative' => true]);
        acym_addStyle(true, '#'.$identifier.' .modal-body { overflow: auto; }');
        $params['footer'] = '<a role="button" class="btn" data-dismiss="modal" aria-hidden="true">'.acym_translation('JLIB_HTML_BEHAVIOR_CLOSE').'</a>';
    }


    $html = '<a 
                class="'.($isButton ? 'btn ' : '').'hasTooltip" 
                data-toggle="modal" 
                role="button" 
                href="#'.$identifier.'" 
                id="button_'.$identifier.'"
                data-bs-toggle="modal"
                data-bs-target="#'.$identifier.'">';
    $html .= acym_translation($buttonText).'</a>';
    $html .= HTMLHelper::_('bootstrap.renderModal', $identifier, $params);

    return $html;
}

function acym_CMSArticleTitle($id)
{
    return acym_loadResult('SELECT title FROM #__content WHERE id = '.intval($id));
}

function acym_getArticleURL($id, $popup, $text)
{
    if (empty($id)) return '';

    $query = 'SELECT article.id, article.alias, article.catid, cat.alias AS catalias, article.language
        FROM #__content AS article 
        LEFT JOIN #__categories AS cat ON cat.id = article.catid 
        WHERE article.id = '.intval($id);
    $article = acym_loadObject($query);

    $category = $article->catid.(empty($article->catalias) ? '' : ':'.$article->catalias);
    $articleid = $article->id.(empty($article->alias) ? '' : ':'.$article->alias);

    if (ACYM_J40) {
        $url = RouteHelper::getArticleRoute($articleid, $category, $article->language);
    } else {
        // Make sure the Joomla link generator class is loaded
        if (!class_exists('ContentHelperRoute')) {
            $contentHelper = JPATH_SITE.DS.'components'.DS.'com_content'.DS.'helpers'.DS.'route.php';
            if (!file_exists($contentHelper)) return '';
            require_once $contentHelper;
        }
        $url = ContentHelperRoute::getArticleRoute($articleid, $category, $article->language);
    }

    if ($popup == 1) {
        $url .= (strpos($url, '?') ? '&' : '?').acym_noTemplate();
        $url = acym_frontModal(acym_route($url), $text, false);
    } else {
        $url = '<a title="'.acym_translation($text, true).'" href="'.acym_escape(acym_route($url)).'" target="_blank">'.acym_translation($text).'</a>';
    }

    return $url;
}

function acym_articleSelectionPage()
{
    return 'index.php?option=com_content&amp;view=articles&amp;layout=modal&amp;tmpl=component&amp;object=content&amp;'.acym_getFormToken();
}

function acym_getPageOverride(string &$ctrl, string $view, bool $forceBackend = false): string
{
    if ($forceBackend || acym_isAdmin()) {
        $app = Factory::getApplication('administrator');
        $folder = JPATH_ADMINISTRATOR;
    } else {
        $app = Factory::getApplication('site');
        $folder = JPATH_SITE;
        if (!file_exists(ACYM_VIEW_FRONT.ucfirst($ctrl))) {
            $ctrl = 'front'.$ctrl;
        }
    }

    return $folder.DS.'templates'.DS.$app->getTemplate().DS.'html'.DS.ACYM_COMPONENT.DS.$ctrl.DS.$view.'.php';
}

function acym_cmsCleanHtml($html)
{
    return $html;
}

function acym_getAlias($name)
{
    return OutputFilter::stringURLSafe($name);
}

function acym_getAllPages()
{
    $menuType = acym_loadResultArray('SELECT menutype FROM #__menu_types');
    if (empty($menuType)) $menuType = [];
    $menuItems = acym_loadObjectList('SELECT id, title FROM #__menu WHERE published = 1 AND menutype IN ("'.implode('","', $menuType).'")');
    $pages = [];
    foreach ($menuItems as $item) {
        $pages[$item->id] = $item->title;
    }

    return $pages;
}

function acym_getArticles($search)
{
    $articles = acym_loadObjectList('SELECT id, title FROM #__content WHERE state = 1 AND title LIKE '.acym_escapeDB('%'.$search.'%'));

    if (empty($articles)) return [];

    $return = [];

    foreach ($articles as $article) {
        $return[] = [$article->id, $article->title];
    }

    return $return;
}

function acym_getArticleById($id)
{
    $article = acym_loadObject('SELECT id, title FROM #__content WHERE state = 1 AND id = '.intval($id));

    if (empty($article)) return [];

    return [
        'id' => $article->id,
        'title' => $article->title,
    ];
}
