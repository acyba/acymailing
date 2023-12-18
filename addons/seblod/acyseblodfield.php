<?php
/**
 * @copyright      Copyright (C) 2009-{__YEAR__} ACYBA SAS - All rights reserved.
 * @license        GNU/GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') || die('Restricted access');

class AcyplgContentCCK extends plgContentCCK
{

    var $acyDisplays = [];

    public function __construct()
    {
    }

    public function onContentPrepare($context, &$article, &$params, $limitstart = 0)
    {
        if (strpos($article->text, '/cck') === false) {
            return true;
        }
        $this->_prepare($context, $article, $params, $limitstart);
    }

    public function _prepare($context, &$article, &$params, $page = 0)
    {
        $property = 'text';
        preg_match('#::cck::(.*)::/cck::#U', $article->$property, $matches);
        if (!@$matches[1]) {
            return;
        }

        $query = 'SELECT a.id, a.pk, a.pkb, a.cck, a.storage_location, a.store_id, b.id as type_id, b.indexed,
						b.options_content, b.options_intro, c.template as content_template, c.params as content_params, d.template as intro_template,
						d.params as intro_params, f.app as folder_app
				FROM #__cck_core AS a
				LEFT JOIN #__cck_core_types AS b ON b.name = a.cck
				LEFT JOIN #__template_styles AS c ON c.id = b.template_content
				LEFT JOIN #__template_styles AS d ON d.id = b.template_intro
				LEFT JOIN #__cck_core_folders AS f ON f.id = b.folder
				WHERE a.id = '.intval($matches[1]);
        $cck = JCckDatabase::loadObject($query);
        $contentType = (string)$cck->cck;
        $article->id = (int)$cck->pk;
        if (!$contentType) {
            return;
        }

        JPluginHelper::importPlugin('cck_storage_location');
        if ($context == 'text') {
            $client = 'intro';
        } else {
            if ($cck->storage_location != '') {
                $properties = ['contexts'];
                $properties = JCck::callFunc('plgCCK_Storage_Location'.$cck->storage_location, 'getStaticProperties', $properties);
                $client = (in_array($context, $properties['contexts'])) ? 'content' : 'intro';
            } else {
                $client = 'intro';
            }
        }

        // Fields
        $app = JFactory::getApplication();

        $user = JFactory::getUser();
        $authorizedViewLevels = $user->getAuthorisedViewLevels();
        acym_arrayToInteger($authorizedViewLevels);
        $access = implode(',', $user->getAuthorisedViewLevels());
        foreach ($this->acyDisplays as $i => $oneDisplay) {
            $this->acyDisplays[$i] = acym_escapeDB($this->acyDisplays[$i]);
        }
        if ($client == 'intro' && $this->cache) {
            if (isset($this->loaded[$contentType.'_'.$client.'_fields'])) {
                $fields = $this->loaded[$contentType.'_'.$client.'_fields'];
            } else {
                $query = 'SELECT cc.*, c.label as label2, c.variation, c.link, c.link_options, c.markup_class, c.typo, c.typo_label, c.typo_options, c.access, c.position'.' FROM #__cck_core_type_field AS c'.' LEFT JOIN #__cck_core_types AS sc ON sc.id = c.typeid'.' LEFT JOIN #__cck_core_fields AS cc ON cc.id = c.fieldid'//we modify the code here, adding a condition at the end of the 'WHERE'
                    .' WHERE sc.name = '.acym_escapeDB($contentType).' AND sc.published = 1 AND c.access IN ('.$access.') AND cc.name IN ('.implode(
                        ',',
                        $this->acyDisplays
                    ).')'.' ORDER BY c.ordering ASC';
                $fields = JCckDatabase::loadObjectList($query, 'name');    //#
                if (!count($fields) && $client == 'intro') {
                    $client = 'content';
                    $query = 'SELECT cc.*, c.label as label2, c.variation, c.link, c.link_options, c.markup_class, c.typo, c.typo_label, c.typo_options, c.access, c.position'.' FROM #__cck_core_type_field AS c'.' LEFT JOIN #__cck_core_types AS sc ON sc.id = c.typeid'.' LEFT JOIN #__cck_core_fields AS cc ON cc.id = c.fieldid'.' WHERE sc.name = '.acym_escapeDB(
                            $contentType
                        ).' AND sc.published = 1 AND c.access IN ('.$access.') AND cc.name IN ('.implode(',', $this->acyDisplays).')'.' ORDER BY c.ordering ASC';
                    $fields = JCckDatabase::loadObjectList($query, 'name');    //#
                }
                $this->loaded[$contentType.'_'.$client.'_fields'] = $fields;
            }
        } else {
            $query = 'SELECT cc.*, c.label as label2, c.variation, c.link, c.link_options, c.markup_class, c.typo, c.typo_label, c.typo_options, c.access, c.position'.' FROM #__cck_core_type_field AS c'.' LEFT JOIN #__cck_core_types AS sc ON sc.id = c.typeid'.' LEFT JOIN #__cck_core_fields AS cc ON cc.id = c.fieldid'.' WHERE sc.name = '.acym_escapeDB(
                    $contentType
                ).' AND sc.published = 1 AND c.access IN ('.$access.') AND cc.name IN ('.implode(',', $this->acyDisplays).')'.' ORDER BY c.ordering ASC';
            $fields = JCckDatabase::loadObjectList($query, 'name');    //#
            if (!count($fields) && $client == 'intro') {
                $client = 'content';
                $query = 'SELECT cc.*, c.label as label2, c.variation, c.link, c.link_options, c.markup_class, c.typo, c.typo_label, c.typo_options, c.access, c.position'.' FROM #__cck_core_type_field AS c'.' LEFT JOIN #__cck_core_types AS sc ON sc.id = c.typeid'.' LEFT JOIN #__cck_core_fields AS cc ON cc.id = c.fieldid'.' WHERE sc.name = '.acym_escapeDB(
                        $contentType
                    ).' AND sc.published = 1 AND c.access IN ('.$access.') AND cc.name IN ('.implode(',', $this->acyDisplays).')'.' ORDER BY c.ordering ASC';
                $fields = JCckDatabase::loadObjectList($query, 'name');    //#
            }
        }
        foreach ($fields as $oneField) {
            if ($oneField->display == 1 || $oneField->display < 0) {
                $oneField->label = '<br />';
                $oneField->display = 3;
            }
        }
        if (!isset($this->loaded[$contentType.'_'.$client.'_options'])) {
            acym_loadLanguageFile('pkg_app_cck_'.$cck->folder_app, JPATH_SITE, null, false, false);
            $registry = new JRegistry;
            $registry->loadString($cck->{'options_'.$client});
            $this->loaded[$contentType.'_'.$client.'_options'] = $registry->toArray();
            if (isset($this->loaded[$contentType.'_'.$client.'_options']['sef'])) {
                if ($this->loaded[$contentType.'_'.$client.'_options']['sef'] == '') {
                    $this->loaded[$contentType.'_'.$client.'_options']['sef'] = JCck::getConfig_Param('sef', '2');
                }
            }
        }

        // Template
        $tpl['home'] = $app->getTemplate();
        $tpl['folder'] = $cck->{$client.'_template'};
        $cckArticleParams = $cck->{$client.'_params'};
        if (!is_array($cckArticleParams)) {
            $cckArticleParams = json_decode($cckArticleParams, true);
        }
        $tpl['params'] = $cckArticleParams;
        if (file_exists(JPATH_SITE.'/templates/'.$tpl['home'].'/html/tpl_'.$tpl['folder'])) {
            $tpl['folder'] = 'tpl_'.$tpl['folder'];
            $tpl['root'] = JPATH_SITE.'/templates/'.$tpl['home'].'/html';
        } else {
            $tpl['root'] = JPATH_SITE.'/templates';
        }
        $tpl['path'] = $tpl['root'].'/'.$tpl['folder'];
        if (!$tpl['folder'] || !file_exists($tpl['path'].'/index.php')) {
            $article->$property = str_replace(
                $article->$property,
                'Template Style does not exist. Open the Content Type & save it again. (Intro + Content views)',
                $article->$property
            );

            return;
        }

        $article_params = null;
        @parent::_render($context, $article, $article_params, $tpl, $contentType, $fields, $property, $client, $cck, '');
    }
}
