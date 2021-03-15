<?php

namespace AcyMailing\Libraries;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\PluginClass;
use AcyMailing\Helpers\PluginHelper;

class acymPlugin extends acymObject
{
    var $pluginHelper;
    var $cms = 'all';
    var $name = '';

    var $installed = true;
    var $pluginsPath = '';

    var $rootCategoryId = 1;
    var $categories;
    var $catvalues = [];
    var $cats = [];

    var $tagvalues = [];

    var $tags = [];
    var $pageInfo;
    var $searchFields = [];
    var $querySelect = '';
    var $query = '';
    var $filters = [];
    var $elementIdTable = '';
    var $elementIdColumn = '';

    var $pluginDescription;
    var $generateCampaignResult;

    var $defaultValues;
    var $emailLanguage = '';
    var $campaignId = 0;
    var $campaignType = '';

    var $settings;
    var $savedSettings;

    var $displayOptions = [];
    var $replaceOptions = [];
    var $elementOptions = [];
    var $customOptions = [];
    var $sendingPlugins = [];
    private $subCategories;
    var $errors = [];

    var $logFilename = '';

    public function __construct()
    {
        parent::__construct();

        $this->elementOptions = ['wrappedText' => [acym_translation('ACYM_WRAPPED_TEXT')]];

        $this->pluginHelper = new PluginHelper();
        $this->pluginsPath = acym_getPluginsPath(__FILE__, __DIR__);
        $this->pageInfo = new \stdClass();

        $this->pluginDescription = new \stdClass();
        $this->pluginDescription->plugin = get_class($this);

        $this->name = strtolower(substr($this->pluginDescription->plugin, 7));

        $this->generateCampaignResult = new \stdClass();
        $this->generateCampaignResult->status = true;
        $this->generateCampaignResult->message = '';

        $this->campaignId = acym_getVar('int', 'campaignId', 0);
        $this->campaignType = acym_getVar('string', 'campaign_type', '');

        $pluginClass = new PluginClass();
        $this->savedSettings = $pluginClass->getSettings($this->name);

        $this->sendingPlugins = [
            'wp_mail_smtp' => 'WP Mail SMTP',
        ];

        $this->logFilename = (empty($this->name) ? get_class($this) : $this->name).'.txt';
    }

    protected function displaySelectionZone($zoneContent)
    {
        $output = '<p class="acym__wysid__right__toolbar__p acym__wysid__right__toolbar__p__open acym__title">';
        $output .= acym_translation('ACYM_CONTENT_TO_INSERT').'<i class="acymicon-keyboard_arrow_up"></i>';
        $output .= '</p>';
        $output .= '<div class="acym__wysid__right__toolbar__design--show acym__wysid__right__toolbar__design acym__wysid__context__modal__container">';
        $output .= $zoneContent;
        $output .= '</div>';

        return $output;
    }

    public function displayListing()
    {
        echo $this->prepareListing();
    }

    public function prepareListing()
    {
        $this->pageInfo->limit = 10;
        $this->pageInfo->page = acym_getVar('int', 'pagination_page_ajax', 1);
        $this->pageInfo->start = ($this->pageInfo->page - 1) * $this->pageInfo->limit;
        $this->pageInfo->search = acym_getVar('string', 'plugin_search', '');
        $this->pageInfo->filter_cat = acym_getVar('int', 'plugin_category', 0);
        $this->pageInfo->orderdir = 'DESC';
        $this->pageInfo->loadMore = acym_getVar('boolean', 'loadMore', false);

        if (!empty($this->pageInfo->search) && !empty($this->searchFields)) {
            $searchVal = '%'.acym_getEscaped($this->pageInfo->search, true).'%';
            $this->filters[] = implode(' LIKE '.acym_escapeDB($searchVal).' OR ', $this->searchFields).' LIKE '.acym_escapeDB($searchVal);
        }

        return '';
    }

    public function getElements()
    {
        $conditions = '';
        $ordering = '';
        if (!empty($this->filters)) $conditions = ' WHERE ('.implode(') AND (', $this->filters).')';
        if (!empty($this->pageInfo->order)) $ordering = ' ORDER BY '.acym_secureDBColumn($this->pageInfo->order).' '.acym_secureDBColumn($this->pageInfo->orderdir);

        $rows = acym_loadObjectList($this->querySelect.$this->query.$conditions.$ordering, '', $this->pageInfo->start, $this->pageInfo->limit);
        $this->pageInfo->total = acym_loadResult('SELECT COUNT(*) '.$this->query.$conditions.$ordering);

        if (!empty($this->defaultValues->id) && $this->defaultValues->defaultPluginTab === $this->name) {
            $found = false;
            foreach ($rows as $oneRow) {
                if ($oneRow->{$this->elementIdColumn} === $this->defaultValues->id) $found = true;
            }

            if (!$found) {
                $this->filters[] = $this->elementIdTable.'.'.$this->elementIdColumn.' = '.intval($this->defaultValues->id);
                $row = acym_loadObject($this->querySelect.$this->query.$conditions);
                if (!empty($row)) $rows[] = $row;
            }
        }

        return $rows;
    }

    protected function getFilteringZone($categoryFilter = true)
    {
        $result = '<div class="grid-x" id="plugin_listing_filters">
                    <div class="cell medium-6">
                        <input type="text" name="plugin_search" placeholder="'.acym_escape(acym_translation('ACYM_SEARCH')).'"/>
                    </div>
                    <div class="cell medium-6 grid-x">
                        <div class="cell hide-for-small-only medium-auto"></div>
                        <div class="cell medium-shrink">';

        if ($categoryFilter) $result .= $this->getCategoryFilter();

        $result .= '</div>
                    </div>
                </div>';

        return $result;
    }

    protected function getCategoryFilter()
    {
        $filter_cat = acym_getVar('int', 'plugin_category', 0);

        $this->cats = [];
        $this->subCategories = [];
        if (!empty($this->categories)) {
            foreach ($this->categories as $oneCat) {
                $this->cats[$oneCat->parent_id][] = $oneCat;
            }
        }
        $this->catvalues = [];
        $this->catvalues[] = acym_selectOption(0, 'ACYM_ALL');
        $this->handleChildrenCategories($this->rootCategoryId);
        foreach ($this->categories as $oneCat) {
            $this->subCategories[$oneCat->id] = $this->getSubCats($oneCat->id);
        }

        return acym_select($this->catvalues, 'plugin_category', intval($filter_cat), 'class="plugin_category_select"', 'value', 'text');
    }

    private function getSubCats($categoryId)
    {
        $result = [$categoryId];
        if (empty($this->cats[$categoryId])) return $result;

        foreach ($this->cats[$categoryId] as $oneSubCategory) {
            $result = array_merge($result, $this->getSubCats($oneSubCategory->id));
        }

        return $result;
    }

    protected function handleChildrenCategories($parent_id, $level = 0)
    {
        if (empty($this->cats[$parent_id])) return;

        foreach ($this->cats[$parent_id] as $cat) {
            $this->catvalues[] = acym_selectOption($cat->id, str_repeat(' - - ', $level).$cat->title);
            $this->handleChildrenCategories($cat->id, $level + 1);
        }
    }

    protected function getSubCategories($categoryId)
    {
        $this->getCategoryFilter();

        return $this->subCategories[$categoryId];
    }

    protected function autoCampaignOptions(&$options)
    {
        if (empty($this->campaignId) && empty($this->campaignType)) {
            return;
        } elseif (!empty($this->campaignId) && (empty($this->campaignType) || $this->campaignType != 'auto')) {
            $campaignClass = new CampaignClass();
            $campaign = $campaignClass->getOneById($this->campaignId);
            if ($campaign->sending_type !== 'auto') return;
        } elseif (empty($this->campaignId) && !empty($this->campaignType) && $this->campaignType != 'auto') return;

        $options[] = [
            'title' => 'ACYM_DOCUMENTATION',
            'type' => 'custom',
            'name' => 'documentation',
            'output' => '<a target="_blank" href="'.ACYM_DOCUMENTATION.'main-pages/campaigns/automatic-campaigns#dont-send-twice-the-same-content"><i class="acymicon-book"></i></a>',
            'js' => '',
            'section' => 'ACYM_AUTO_CAMPAIGNS_OPTIONS',
        ];

        $options[] = [
            'title' => 'ACYM_ONLY_NEWLY_CREATED',
            'type' => 'boolean',
            'name' => 'onlynew',
            'default' => true,
            'tooltip' => 'ACYM_ONLY_NEWLY_CREATED_DESC',
            'section' => 'ACYM_AUTO_CAMPAIGNS_OPTIONS',
        ];

        $options[] = [
            'title' => 'ACYM_MIN_NB_ELEMENTS',
            'type' => 'number',
            'name' => 'min',
            'default' => 0,
            'tooltip' => 'ACYM_MIN_NB_ELEMENTS_DESC',
            'section' => 'ACYM_AUTO_CAMPAIGNS_OPTIONS',
        ];
    }

    protected function getElementsListing($options)
    {
        if ($this->pageInfo->loadMore) {
            return $this->getInnerListing($options);
        }
        $listing = '<div id="plugin_listing" class="acym__popup__listing">';
        $listing .= '<input type="hidden" name="plugin" value="'.acym_escape(get_class($this)).'" />';

        // Column names
        $listing .= '<div class="cell grid-x hide-for-small-only plugin_listing_headers">';
        foreach ($options['header'] as $oneColumn) {
            $class = empty($oneColumn['class']) ? '' : ' '.$oneColumn['class'];
            $listing .= '<div class="cell plugin_listing_headers__title medium-'.$oneColumn['size'].$class.'">'.acym_translation($oneColumn['label']).'</div>';
        }
        $listing .= '</div>';

        $listing .= $this->getInnerListing($options);

        $listing .= '<input type="hidden" value="1" id="acym_pagination__ajax__load-more" name="acym_pagination__ajax__load-more">';
        $listing .= '</div>';

        return $listing;
    }

    private function getInnerListing($options)
    {
        $listing = '';
        // Actual listing
        if (empty($options['rows']) && $this->pageInfo->loadMore) {
            return '<h3 class="cell acym__listing__empty__load-more text-center">'.acym_translation('ACYM_NO_MORE_RESULTS').'</h3>';
        } elseif (empty($options['rows'])) {
            $listing .= '<h1 class="cell acym__listing__empty__search__modal text-center">'.acym_translation('ACYM_NO_RESULTS_FOUND').'</h1>';
        } else {
            $selected = explode(',', acym_getVar('string', 'selected', ''));
            if (!empty($this->defaultValues->id)) $selected = [$this->defaultValues->id];

            foreach ($options['rows'] as $row) {
                $class = 'cell grid-x acym__row__no-listing acym__listing__row__popup';
                if (in_array($row->{$options['id']}, $selected)) $class .= ' selected_row';

                $listing .= '<div class="'.$class.'" data-id="'.intval($row->{$options['id']}).'" onclick="applyContent'.acym_escape($this->name).'('.intval(
                        $row->{$options['id']}
                    ).', this);">';

                foreach ($options['header'] as $column => $oneColumn) {
                    $value = $row->$column;

                    if (!empty($oneColumn['type']) && $oneColumn['type'] == 'date') {
                        if (!is_numeric($value) && $value != '0000-00-00 00:00:00') $value = strtotime($value);
                        $tooltip = acym_date($value, acym_translation('ACYM_DATE_FORMAT_LC2'));
                        $value = acym_tooltip(acym_date($value, acym_translation('ACYM_DATE_FORMAT_LC5')), $tooltip);
                    }

                    $class = empty($oneColumn['class']) ? '' : ' '.$oneColumn['class'];
                    $listing .= '<div class="cell medium-'.$oneColumn['size'].$class.'">'.$value.'</div>';
                }

                $listing .= '</div>';
            }
        }

        return $listing;
    }

    protected function getCategoryListing()
    {
        $listing = '';
        if (empty($this->catvalues)) {
            $listing .= '<h1 class="cell acym__listing__empty__search__modal text-center">'.acym_translation('ACYM_NO_RESULTS_FOUND').'</h1>';

            return $listing;
        }

        $listing .= '<div class="acym__popup__listing padding-0">';
        $selected = [];
        if (!empty($this->defaultValues->id) && strpos($this->defaultValues->id, '-')) {
            $selected = explode('-', $this->defaultValues->id);
        }
        foreach ($this->catvalues as $oneCat) {
            if (empty($oneCat->value)) continue;

            $class = 'cell grid-x acym__row__no-listing acym__listing__row__popup';
            if (in_array($oneCat->value, $selected)) $class .= ' selected_row';
            $listing .= '<div class="'.$class.'" data-id="'.intval($oneCat->value).'" onclick="applyContentauto'.acym_escape($this->name).'('.intval($oneCat->value).', this);">
                        <div class="cell medium-5">'.acym_escape($oneCat->text).'</div>
                    </div>';
        }
        $listing .= '</div>';

        return $listing;
    }

    protected function getTagListing()
    {
        $listing = '';
        if (empty($this->tagvalues)) {
            $listing .= '<h1 class="cell acym__listing__empty__search__modal text-center">'.acym_translation('ACYM_NO_RESULTS_FOUND').'</h1>';

            return $listing;
        }

        $listing .= '<div class="acym__popup__listing padding-0">';
        $selected = [];
        if (!empty($this->defaultValues->id) && strpos($this->defaultValues->id, '-')) {
            $selected = explode('-', $this->defaultValues->id);
        }
        foreach ($this->tagvalues as $oneTag) {
            if (empty($oneTag->term_id)) continue;

            $class = 'cell grid-x acym__row__no-listing acym__listing__row__popup';
            if (in_array($oneTag->term_id, $selected)) $class .= ' selected_row';
            $listing .= '<div class="'.$class.'" data-id="'.intval($oneTag->term_id).'" onclick="applyContent'.acym_escape($this->name).'_tags('.intval($oneTag->term_id).', this);">
                        <div class="cell medium-5">'.acym_escape($oneTag->name).'</div>
                    </div>';
        }
        $listing .= '</div>';

        return $listing;
    }

    protected function replaceMultiple(&$email)
    {
        $this->generateByCategory($email);
        if (empty($this->tags)) return;
        $this->pluginHelper->replaceTags($email, $this->tags, true);
    }

    protected function handleOrderBy(&$query, $parameter, $table = null)
    {
        if (empty($parameter->order)) return;

        $ordering = explode(',', $parameter->order);
        if ($ordering[0] == 'rand') {
            $query .= ' ORDER BY rand()';
        } else {
            $table = null === $table ? '' : $table.'.';
            $query .= ' ORDER BY '.$table.'`'.acym_secureDBColumn(trim($ordering[0])).'` '.acym_secureDBColumn(trim($ordering[1]));
        }
    }

    protected function handleMax(&$query, $parameter)
    {
        if (empty($parameter->max)) $parameter->max = 20;
        $query .= ' LIMIT '.intval($parameter->max);
    }

    protected function getLastGenerated($mailId)
    {
        $campaignClass = new CampaignClass();

        return $campaignClass->getLastGenerated($mailId);
    }

    /**
     * Returns the individual elements tags based on a query result
     *
     * @param $elements
     * @param $parameter
     * @param $table
     *
     * @return string
     */
    protected function finalizeCategoryFormat($query, $parameter, $table = null)
    {
        $this->handleOrderBy($query, $parameter, $table);
        $this->handleMax($query, $parameter);

        $elements = acym_loadResultArray($query);

        if (!empty($parameter->min) && count($elements) < $parameter->min) {
            $this->generateCampaignResult->status = false;
            $this->generateCampaignResult->message = acym_translationSprintf(
                'ACYM_GENERATE_CAMPAIGN_NOT_ENOUGH_CONTENT',
                $this->pluginDescription->name,
                count($elements),
                $parameter->min
            );
        }

        if (empty($elements)) return '';

        $elements = $this->groupByCategory($elements);

        $customLayout = ACYM_CUSTOM_PLUGIN_LAYOUT.$this->name.'_auto.php';
        if (file_exists($customLayout)) {
            ob_start();
            require $customLayout;

            return ob_get_clean();
        }

        $arrayElements = [];
        unset($parameter->id);
        foreach ($elements as $oneElementId) {
            $args = [];
            $args[] = $this->name.':'.$oneElementId;
            foreach ($parameter as $oneParam => $val) {
                if (is_bool($val)) {
                    $args[] = $oneParam;
                } else {
                    $args[] = $oneParam.':'.$val;
                }
            }
            $arrayElements[] = '{'.implode('| ', $args).'}';
        }

        return $this->pluginHelper->getFormattedResult($arrayElements, $parameter);
    }

    /**
     * This method can be overridden in the add-on when a group by category option is added
     *
     * @param array $elements List of element ids
     *
     * @return array
     */
    protected function groupByCategory($elements)
    {
        return $elements;
    }

    protected function getSelectedArea($parameter)
    {
        $allcats = explode('-', $parameter->id);
        $selectedArea = [];
        foreach ($allcats as $oneCat) {
            if (empty($oneCat)) continue;
            $selectedArea[] = intval($oneCat);
        }

        return $selectedArea;
    }

    protected function replaceOne(&$email)
    {
        $tags = $this->pluginHelper->extractTags($email, $this->name);
        if (empty($tags)) return;

        if (false === $this->loadLibraries($email)) return;
        $this->emailLanguage = $email->links_language;
        $translationTool = $this->config->get('translate_content', 'no');

        $tagsReplaced = [];
        foreach ($tags as $i => $oneTag) {
            if (isset($tagsReplaced[$i])) continue;

            if (!empty($this->emailLanguage) && $translationTool !== 'no' && acym_isMultilingual()) {
                $oneTag->id = $this->getTranslationId($oneTag->id, $translationTool);
            }
            $tagsReplaced[$i] = $this->replaceIndividualContent($oneTag, $email);
        }

        $this->pluginHelper->replaceTags($email, $tagsReplaced, true);
    }

    protected function loadLibraries($email)
    {
        return true;
    }

    protected function initIndividualContent(&$tag, $query)
    {
        $element = acym_loadObject($query);

        if (empty($element)) {
            if (acym_isAdmin()) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_CONTENT_NOT_FOUND', $tag->id), 'notice');
            }

            return false;
        }

        if (empty($tag->display)) {
            $tag->display = [];
        } else {
            $tag->display = explode(',', $tag->display);
        }

        return $element;
    }

    protected function getCustomLayoutVars($element)
    {
        $varFields = [];
        $varFields['{picthtml}'] = '';
        foreach ($element as $fieldName => $oneField) {
            $varFields['{'.$fieldName.'}'] = $oneField;
        }

        return $varFields;
    }

    /**
     * Handles the custom layouts and the pictures management
     *
     * @param string $name    Name of the plugin used as identifier
     * @param string $result  What will be inserted in the email
     * @param object $options Selected options when inserting dcontent
     * @param array  $data    Data used as shortcodes in custom layouts
     *
     * @return string
     */
    protected function finalizeElementFormat($result, $options, $data)
    {
        $customLayoutPath = ACYM_CUSTOM_PLUGIN_LAYOUT.$this->name.'.html';
        //Check if the template exists...
        if (file_exists($customLayoutPath)) {
            $data['{wrappedText}'] = $this->pluginHelper->wrappedText;
            $viewContent = acym_fileGetContent($customLayoutPath);
            $viewContentReplace = str_replace(array_keys($data), $data, $viewContent);
            if ($viewContent !== $viewContentReplace) $result = $viewContentReplace;
        }

        return $this->pluginHelper->managePicts($options, $result);
    }

    protected function filtersFromConditions(&$filters)
    {
        $newFilters = [];

        $this->onAcymDeclareConditions($newFilters);
        foreach ($newFilters as $oneType) {
            foreach ($oneType as $oneFilterName => $oneFilter) {
                if (!empty($oneFilter->option)) $oneFilter->option = str_replace(['acym_condition', '[conditions]'], ['acym_action', '[filters]'], $oneFilter->option);
                $filters[$oneFilterName] = $oneFilter;
            }
        }
    }

    protected function getElementTags($type, $id)
    {
        $tags = acym_loadObjectList(
            'SELECT tags.`title`, tags.`alias`, tags.`id` 
                FROM #__tags AS tags 
                JOIN #__contentitem_tag_map AS map ON tags.`id` = map.`tag_id` 
                WHERE map.`type_alias` = '.acym_escapeDB($type).'
                    AND map.`content_item_id` = '.intval($id)
        );

        if (empty($tags)) return [];

        $displayTags = [];
        foreach ($tags as $oneTag) {
            $displayTags[] = '<a href="index.php?option=com_tags&view=tag&id='.$oneTag->id.':'.$oneTag->alias.'" target="_blank">'.$oneTag->title.'</a>';
        }

        return $displayTags;
    }

    protected function getFormattedValue(&$fieldValues)
    {
        $field = $fieldValues[0];

        if (!empty($field->fieldparams) && !is_array($field->fieldparams)) {
            $field->fieldparams = json_decode($field->fieldparams, true);
        }

        switch ($field->type) {
            case 'calendar':
                $format = $field->fieldparams['showtime'] == '1' ? 'Y-m-d H:i' : 'Y-m-d';
                $field->value = acym_date(strtotime($field->value), $format);
                break;

            case 'checkboxes':
            case 'list':
            case 'radio':
                $values = [];
                foreach ($fieldValues as $oneValue) {
                    foreach ($field->fieldparams['options'] as $oneOPT) {
                        if ($oneOPT['value'] == $oneValue->value) {
                            $values[] = $oneOPT['name'];
                            break;
                        }
                    }
                }

                $field->value = implode(',', $values);
                break;

            case 'dpcalendar':
                if (empty($field->value)) {
                    $field->value = '';
                    break;
                }

                $title = acym_loadResult('SELECT `title` FROM #__categories WHERE `id` = '.intval($field->value));
                if (empty($title)) {
                    $field->value = '';
                } else {
                    $date = '#year='.acym_date('now', 'Y').'&month='.acym_date('now', 'm').'&day='.acym_date('now', 'd').'&view=month';
                    $field->value = '<a target="_blank" href="index.php?option=com_dpcalendar&view=calendar&id='.intval($field->value).$date.'">'.$title.'</a>';
                }
                break;

            case 'imagelist':
                if (!empty($field->fieldparams['directory']) && strlen($field->fieldparams['directory']) > 1) {
                    $field->value = '/'.$field->value;
                } else {
                    $field->fieldparams['directory'] = '';
                }
                $field->value = '<img src="images/'.$field->fieldparams['directory'].$field->value.'" />';
                break;

            case 'media':
                $field->value = '<img src="'.$field->value.'" alt="" />';
                break;

            case 'repeatable':
                if (!empty($field->value)) {
                    $values = json_decode($field->value);
                    $formattedValues = [];
                    foreach ($values as $oneSetOfValues) {
                        $formattedSet = [];
                        foreach ($oneSetOfValues as $oneLabel => $oneValue) {
                            if (empty($oneValue)) continue;

                            foreach ($field->fieldparams['fields'] as $oneField) {
                                if ($oneField['fieldname'] !== $oneLabel) continue;

                                if ($oneField['fieldtype'] === 'media') {
                                    $oneValue = '<img src="'.$oneValue.'" alt="" />';
                                }

                                $formattedSet[] = $oneValue;
                                break;
                            }
                        }

                        if (empty($formattedSet)) continue;
                        $formattedValues[] = implode(', ', $formattedSet);
                    }

                    $field->value = empty($formattedSet) ? '' : '<ul><li>'.implode('</li><li>', $formattedValues).'</li></ul>';
                }
                break;

            case 'sql':
                if (empty($field->options)) {
                    $field->options = acym_loadObjectList($field->fieldparams['query'], 'value');
                }

                $field->value = $field->options[$field->value]->text;
                break;

            case 'url':
                $field->value = '<a target="_blank" href="'.$field->value.'">'.$field->value.'</a>';
                break;

            case 'user':
                $field->value = acym_currentUserName($field->value);
                break;

            case 'usergrouplist':
                if (empty($field->usergroups)) {
                    $field->usergroups = acym_loadObjectList('SELECT id, title FROM #__usergroups', 'id');
                }

                if (empty($field->usergroups[$field->value])) {
                    $field->value = '';
                    break;
                }
                $field->value = $field->usergroups[$field->value]->title;
                break;
        }

        return $field->value;
    }

    protected function getLanguage($elementLanguage = null, $onlyValue = false)
    {
        $value = $this->emailLanguage;
        if (!empty($elementLanguage) && $elementLanguage !== '*') $value = $elementLanguage;

        if (empty($value)) return '';

        if ($onlyValue) return $value;

        if (ACYM_CMS == 'joomla' && acym_isPluginActive('languagefilter')) {
            return '&lang='.substr($value, 0, strpos($value, '-'));
        } else {
            return '&language='.$value;
        }
    }

    protected function finalizeLink($link)
    {
        if (acym_isPluginActive('languagefilter')) {
            if (strpos($link, 'lang=') === false && !empty($this->emailLanguage)) {
                $link .= strpos($link, '?') === false ? '?' : '&';
                $link .= 'lang='.substr($this->emailLanguage, 0, strpos($this->emailLanguage, '-'));
            }
        } else {
            if (strpos($link, 'language=') === false && !empty($this->emailLanguage)) {
                $link .= strpos($link, '?') === false ? '?' : '&';
                $link .= 'language='.$this->emailLanguage;
            }
        }

        return acym_frontendLink($link, false);
    }

    protected function handleCustomFields($tag, &$customFields)
    {
        if (empty($tag->custom)) return;

        $tag->custom = explode(',', $tag->custom);
        acym_arrayToInteger($tag->custom);

        $rawfields = acym_loadObjectList(
            'SELECT `field`.*, `values`.`value` 
                FROM #__fields_values AS `values` 
                JOIN #__fields AS `field` ON `values`.`field_id` = `field`.`id` 
                WHERE `values`.`item_id` = '.intval($tag->id).' AND `field`.`id` IN ('.implode(', ', $tag->custom).')'
        );

        $fields = [];
        foreach ($rawfields as $field) {
            $fields[$field->id][] = $field;
        }

        foreach ($fields as $fieldValues) {
            $value = $this->getFormattedValue($fieldValues);

            if (empty($value)) continue;

            $customFields[] = [
                $value,
                $fieldValues[0]->title,
            ];
        }
    }

    protected function getIntro($text)
    {
        $pageBreak = null;
        $possibleBreaks = [
            '<!--more-->',
            '<hr id="system-readmore" />',
            '<!--nextpage-->',
        ];

        foreach ($possibleBreaks as $oneBreak) {
            if (strpos($text, $oneBreak) !== false) {
                $pageBreak = $oneBreak;
                break;
            }
        }

        if (empty($pageBreak)) return $text;

        $split = explode($pageBreak, $text, 2);

        return array_shift($split);
    }

    public function displayCustomViewEditor(&$output)
    {
        $plugin = new \stdClass();
        $plugin->folder_name = $this->name;
        $plugin->settings = $this->settings;
        $this->generateSettings($plugin);

        $output .= '<p class="acym__wysid__right__toolbar__p acym__wysid__right__toolbar__p__open acym__title">';
        $output .= acym_translation('ACYM_ADDON_SETTINGS');
        $output .= '<i class="acymicon-keyboard_arrow_up"></i></p>';
        $output .= '<div class="acym__wysid__right__toolbar__design--show acym__wysid__right__toolbar__design acym__wysid__context__modal__container">';
        $output .= $plugin->settings['custom_view'];
        $output .= '</div>';
    }

    public function generateSettings(&$plugin)
    {
        if (empty($plugin->settings)) return false;

        if (array_key_exists('not_installed', $plugin->settings)) {
            $plugin->settings = 'not_installed';

            return true;
        }

        foreach ($plugin->settings as $key => $field) {
            $text = '';
            if (empty($field['type'])) {
                $plugin->settings[$key] = $text;
                continue;
            }

            $id = $plugin->folder_name.'_'.$key;
            $name = $plugin->folder_name.'['.$key.']';
            if (!empty($field['label'])) $field['label'] = acym_translation($field['label']);
            if (!empty($field['info'])) {
                $field['label'] .= acym_info(
                    acym_translation($field['info']),
                    '',
                    '',
                    'wysid_tooltip',
                    !empty($field['info_warning'])
                );
            }

            if ($field['type'] == 'checkbox') {
                $classLabel = 'shrink';
                $text .= '<label for="'.$id.'" class="cell '.$classLabel.'">'.$field['label'].'</label>';
                $text .= '<input id="'.$id.'" class="cell shrink" type="checkbox" name="'.$name.'" '.(empty($field['value']) ? '' : 'checked').'>';
            } elseif ($field['type'] == 'switch') {
                $text .= acym_switch(
                    $name,
                    $field['value'],
                    $field['label'],
                    [],
                    'large-7'
                );
            } elseif ($field['type'] == 'select') {
                $text .= '<label class="cell shrink">'.$field['label'].'</label>';
                $text .= acym_select(
                    $field['data'],
                    $name,
                    $field['value'],
                    ['class' => 'acym__select'],
                    'value',
                    'text',
                    false,
                    true
                );
            } elseif ($field['type'] == 'multiple_select') {
                $text .= '<label class="cell shrink">'.$field['label'].'</label>';
                $text .= acym_selectMultiple(
                    $field['data'],
                    $name,
                    empty($field['value']) ? [] : $field['value'],
                    ['class' => 'acym__select']
                );
            } elseif ($field['type'] == 'text') {
                $text .= '<label class="cell shrink">'.$field['label'].'</label>';
                $text .= '<input type="text" name="'.$name.'" value="'.acym_escape($field['value']).'" class="cell shrink">';
            } elseif ($field['type'] == 'number') {
                $text .= '<label class="cell shrink">'.$field['label'].'</label>';
                $text .= '<input type="number" name="'.$name.'" value="'.acym_escape($field['value']).'" class="cell large-2 medium-5">';
                if (!empty($field['post_text'])) $text .= '<span class="cell shrink">'.strtolower($field['post_text']).'</span>';
            } elseif ($field['type'] == 'radio') {
                $text .= '<p class="cell">'.$field['label'].'</p>';
                $text .= acym_radio(
                    $field['data'],
                    $name,
                    $field['value']
                );
            } elseif ($field['type'] == 'date') {
                $text .= '<label class="cell shrink">'.$field['label'].'</label>';
                $text .= acym_dateField(
                    $name,
                    $field['value'],
                    'cell shrink'
                );
            } elseif ($field['type'] == 'custom_view') {
                $idCustomView = 'acym__plugins__installed__custom-view__'.$this->name;
                $ctrl = acym_getVar('string', 'ctrl', '');
                $classTooltip = $ctrl == 'dynamics' ? '' : 'wysid_tooltip';
                $text .= '<label class="cell">'.acym_translation('ACYM_CUSTOM_VIEW').acym_info('ACYM_CUSTOM_VIEW_DESC', '', '', $classTooltip).'</label>';
                if (empty($field['tags'])) $field['tags'] = [];
                $modalContent = '<div id="'.$idCustomView.'" class="cell grid-x acym__plugins__installed__custom-view" acym-data-tags="'.acym_escape(json_encode($field['tags'])).'">
                                    <h2 class="cell text-center acym__title__primary__color">'.acym_translationSprintf('ACYM_CUSTOM_VIEW_FOR_X', $this->pluginDescription->name).'</h2>
                                    <div class="cell grid-x acym__plugins__installed__custom-view__edit-container">
                                        <div class="acym__plugins__installed__custom-view__editor-loader grid-x cell align-center acym_vcenter" v-if="loading">'.acym_loaderLogo().'</div>
                                        <vue-prism-editor :emitEvents="true" class="cell acym__plugins__installed__custom-view__code cell auto" v-model="code" :language="language" lineNumbers="true"></vue-prism-editor>
                                        <div class="cell grid-x medium-3 margin-left-1 acym__plugins__installed__custom-view__tags">
                                            <h3 class="acym__title acym__title__secondary cell text-center">'.acym_translation('ACYM_DYNAMIC_CONTENT').acym_info(
                        'ACYM_DYNAMIC_CONTENT_DESC'
                    ).'</h3>
                                            <div class="cell acym__plugins__installed__custom-view__tag" v-for="(trad, tag) in tags" :key="tag" @click.prevent="insertTag(tag)">{{ trad }}</div>
                                        </div>
                                    </div>
                                    <div class="cell grid-x acym__plugins__installed__custom-view__actions acym_vcenter margin-top-1 padding-bottom-1">
                                        <div class="cell auto grid-x">
                                            <button type="button" class="cell shrink button-secondary button" @click="resetView">'.acym_translation('ACYM_RESET_VIEW').'</button>
                                            <div class="cell shrink margin-right-2 acym_vcenter margin-left-1">
                                                <i v-if="deleting" class="acymicon-spin acymicon-circle-o-notch" style="margin-bottom: 0; line-height: 26px;"></i>
                                                <span v-if="deleted">{{ messageDeleted }}</span>
                                            </div>
                                        </div>
                                        <div class="cell auto align-right grid-x">
                                            <div class="cell shrink margin-right-2 acym_vcenter">
                                                <i v-if="saving" class="acymicon-spin acymicon-circle-o-notch" style="margin-bottom: 0; line-height: 26px;"></i>
                                                <span v-if="saved">{{ messageSaved }}</span>
                                            </div>
                                            <button @click="save()" class="cell shrink button" type="button">'.acym_translation('ACYM_SAVE').'</button>
                                        </div>
                                    </div>
                                </div>';
                $text .= acym_modal(
                    acym_translation('ACYM_EDIT_CUSTOM_VIEW'),
                    $modalContent,
                    null,
                    'acym-data-plugins-id="'.$idCustomView.'" acym-data-plugin-class="'.get_class($this).'" acym-data-plugin-folder="'.$this->name.'"',
                    'class="cell button"'
                );
            } elseif ($field['type'] == 'custom') {
                $text .= $field['content'];
            }
            $plugin->settings[$key] = $text;
        }

        return true;
    }

    /**
     * @param string $css This attribute is the name of the file in the folder css of the plugin OR it can be raw CSS
     * @param bool   $raw
     */
    public function loadCSS($css, $raw = false, $path = null)
    {
        if (!$raw) {
            if (empty($path)) $path = ACYM_DYNAMICS_URL.$this->name;
            $css = $path.DS.'css'.DS.$css.'.css';
        }
        acym_addStyle($raw, $css);
    }

    /**
     * @param string $js This attribute is the name of the file in the folder js of the plugin OR it can be raw Javascript
     * @param bool   $raw
     */
    public function loadJavascript($js, $raw = false, $path = null)
    {
        if (!$raw) {
            if (empty($path)) $path = ACYM_DYNAMICS_URL.$this->name;
            $js = $path.DS.'js'.DS.$js.'.js';
        }
        acym_addScript($raw, $js);
    }

    public function includeView($view, $data = [], $path = null)
    {
        if (empty($path)) {
            $path = ACYM_ADDONS_FOLDER_PATH.$this->name.DS.'views'.DS.$view.'.php';
        } else {
            $path = $path.DS.'views'.DS.$view.'.php';
        }

        if (!file_exists($path)) return false;

        ob_start();
        include $path;

        return ob_get_clean();
    }

    //this function will parse and show all settings
    public function onAcymAddSettings(&$plugins)
    {
        foreach ($plugins as $key => $plugin) {
            if ($plugin->folder_name === $this->name) {
                if (!empty($plugin->settings)) {
                    foreach ($plugin->settings as $keySettings => $value) {
                        $this->settings[$keySettings]['value'] = $plugin->settings[$keySettings]['value'];
                    }
                }
                $plugins[$key]->settings = $this->settings;

                $this->generateSettings($plugins[$key]);
                break;
            }
        }
    }

    protected function getParam($name, $default = '')
    {
        if (empty($this->savedSettings) || !isset($this->savedSettings[$name]['value'])) return $default;

        return $this->savedSettings[$name]['value'];
    }

    protected function getTranslationId($elementId, $translationTool, $defaultLanguage = false)
    {
        return $elementId;
    }

    public function filterSpecialMailsDailySend(&$specialMails, $time, $mailType)
    {
        // Only once a day
        $dailyHour = $this->config->get('daily_hour', '12');
        $dailyMinute = $this->config->get('daily_minute', '00');
        // The day it is currently based on the timezone specified in the CMS configuration
        $dayBasedOnCMSTimezone = acym_date('now', 'Y-m-d');
        // The UTC timestamp of the current day based on the CMS timezone, at the specified hour
        $dayBasedOnCMSTimezoneAtSpecifiedHour = acym_getTimeFromCMSDate($dayBasedOnCMSTimezone.' '.$dailyHour.':'.$dailyMinute);

        $campaignClass = new CampaignClass();

        $tmpMails = [];
        foreach ($specialMails as $i => $oneMail) {
            if ($oneMail->sending_type == $mailType) {
                if ($time >= $dayBasedOnCMSTimezoneAtSpecifiedHour && (empty($oneMail->next_trigger) || date('m-d', $oneMail->next_trigger) == date(
                            'm-d',
                            $dayBasedOnCMSTimezoneAtSpecifiedHour
                        ))) {
                    $oneMail->next_trigger = acym_getTime('tomorrow '.$dailyHour.':'.$dailyMinute);
                    $oneMail->last_generated = $time;
                    $campaignClass->save($oneMail);
                    $tmpMails[] = $oneMail;
                }
            } else {
                $tmpMails[] = $oneMail;
            }
        }
        $specialMails = $tmpMails;
    }

    protected function getIdsSelectAjax()
    {
        $ids = acym_getVar('string', 'id');
        if (strpos($ids, ',') !== false) {
            $ids = explode(',', $ids);
        } elseif (!empty($ids)) {
            $ids = [$ids];
        } elseif (!is_null($ids)) {
            echo json_encode([]);
            exit;
        } else {
            return false;
        }
        acym_arrayToInteger($ids);

        return $ids;
    }

    protected function cleanExtensionContent($text)
    {
        if (!acym_isExtensionActive('classic-editor/classic-editor.php') || strpos($text, '<!-- wp:') !== false) return $text;

        return nl2br($text);
    }

    protected function callApiSendingMethod($url, $data = [], $headers = [], $type = 'GET', $authentication = [], $dataDecoded = false)
    {
        $curl = curl_init();

        $optionsArray = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $type,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if (empty($dataDecoded) || $dataDecoded === false) {
            $optionsArray[CURLOPT_POSTFIELDS] = json_encode($data);
        } elseif ($dataDecoded === true) {
            $optionsArray[CURLOPT_POSTFIELDS] = $data;
        }

        if (!empty($authentication)) {
            $optionsArray[CURLOPT_USERPWD] = $authentication['name'].':'.$authentication['pwd'];
        }

        curl_setopt_array(
            $curl,
            $optionsArray
        );

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            return ['error_curl' => $error];
        } else {
            return json_decode($response, true);
        }
    }

    protected function getTestCredentialsSendingMethodButton($sendingMethodId)
    {
        return '<div class="cell grid-x margin-top-1 acym__sending__methods__credentials__test">
                    <button type="button" sending-method-id="'.$sendingMethodId.'" class="acym__configuration__sending__method-test cell shrink button button-secondary">
                    '.acym_translation('ACYM_TEST_CREDENTIALS').'
                    </button>
                    <span class="acym__configuration__sending__method-icon cell shrink margin-left-1 acym_vcenter"></span>
                    <span class="acym__configuration__sending__method-test__message cell shrink margin-left-1 acym_vcenter"></span>
                </div>';
    }

    public function getCopySettingsButton($data, $sendingMethodId, $fromPlugin)
    {
        if (empty($data[$fromPlugin.'_installed'])) return '';

        return '<div class="cell grid-x margin-top-1 acym__sending__methods__copy__data">
					<button 
					type="button"
					class="cell shrink button button-secondary acym__configuration__copy__mail__settings" 
					acym-data-plugin="'.$fromPlugin.'"
					acym-data-method="'.$sendingMethodId.'">
	                    '.acym_translationSprintf('ACYM_COPY_SETTINGS_FROM', $this->sendingPlugins[$fromPlugin]).'
                    </button>
                    <span class="acym__configuration__sending__method-icon cell shrink margin-left-1 acym_vcenter"></span>
				</div>';
    }

    protected function getLinks($account = '', $pricing = '')
    {
        if (empty($account) && empty($pricing)) return '';

        $html = '<div class="cell grid-x acym-grid-margin-x shrink"><p class="cell shrink">'.acym_translation('ACYM_DONT_HAVE_ACCOUNT').'</p>';
        if (!empty($account)) $html .= '<a target="_blank" class="cell shrink" href="'.$account.'">'.acym_translation('ACYM_CREATE_ONE').'</a>';
        if (!empty($account) && !empty($pricing)) $html .= '<p class="cell shrink">'.strtolower(acym_translation('ACYM_OR')).'</p>';
        if (!empty($pricing)) $html .= '<a target="_blank" class="cell shrink" href="'.$pricing.'">'.acym_translation('ACYM_CHECK_THEIR_PRICING').'</a>';
        $html .= '</div>';

        return $html;
    }

    public function onAcymGetSendingMethodsSelected(&$data)
    {
        if (ACYM_CMS == 'wordpress') $this->config->load();
        $mailerMethod = $this->config->get('mailer_method', 'phpmail');
        foreach ($data['sendingMethods'] as $key => $sendingMethod) {
            $data['sendingMethods'][$key]['selected'] = $key == $mailerMethod;
        }
    }

    public function errorCallback()
    {
        $reportPath = acym_getLogPath($this->logFilename, true);

        $lr = "\r\n";
        file_put_contents(
            $reportPath,
            $lr.$lr.'********************     '.acym_getDate(time()).'     ********************'.$lr.implode($lr, $this->errors),
            FILE_APPEND
        );

        $this->errors = [];
    }

    public function isLogFileEmpty()
    {
        $reportPath = acym_getLogPath($this->logFilename);

        return !file_exists($reportPath);
    }
}
