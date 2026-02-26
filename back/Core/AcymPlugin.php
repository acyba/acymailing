<?php

namespace AcyMailing\Core;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\PluginClass;
use AcyMailing\Helpers\PluginHelper;
use Joomla\CMS\HTML\HTMLHelper;

class AcymPlugin extends AcymObject
{
    public string $cms = 'all';
    public string $name = '';
    public bool $installed = true;

    protected array $addonDefinition;
    protected PluginHelper $pluginHelper;

    protected int $rootCategoryId = 1;
    protected array $categories;
    protected array $catvalues = [];
    private array $cats = [];
    private array $subCategories = [];

    protected array $tagvalues = [];

    protected array $tags = [];
    protected object $pageInfo;
    protected array $searchFields = [];
    protected string $querySelect = '';
    protected string $query = '';
    protected array $filters = [];
    protected string $elementIdTable = '';
    protected string $elementIdColumn = '';

    public object $pluginDescription;
    public object $generateCampaignResult;

    protected ?object $defaultValues;
    protected string $emailLanguage = '';
    private int $campaignId;
    private string $campaignType;

    protected array $displayOptions = [];
    protected array $replaceOptions = [];
    protected array $elementOptions = [];
    protected array $customOptions = [];

    private array $sendingPlugins = [
        'wp_mail_smtp' => 'WP Mail SMTP',
    ];

    public array $errors = [];
    public string $logFilename = '';
    protected int $responseCode;

    private $active;
    public $settings;
    private array $savedSettings = [];

    public function __construct()
    {
        parent::__construct();

        $this->elementOptions = ['wrappedText' => [acym_translation('ACYM_WRAPPED_TEXT')]];

        //TODO only load this helper once even if 50 plugins are loaded
        $this->pluginHelper = new PluginHelper();
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
        $currentAddon = $pluginClass->getOnePluginByFolderName($this->name);
        $this->active = empty($currentAddon) || $currentAddon->active;

        if (!empty($currentAddon->settings)) {
            if (is_string($currentAddon->settings)) {
                $this->savedSettings = json_decode($currentAddon->settings, true);
            } else {
                $this->savedSettings = $currentAddon->settings;
            }
        }

        $this->logFilename = acym_getErrorLogFilename(empty($this->name) ? get_class($this) : $this->name);
    }

    protected function displaySelectionZone(string $zoneContent): void
    {
        ?>
		<p class="acym__wysid__right__toolbar__p acym__wysid__right__toolbar__p__open acym__title">
            <?php echo acym_escape(acym_translation('ACYM_CONTENT_TO_INSERT')); ?><i class="acymicon-keyboard-arrow-up"></i>
		</p>
		<div class="acym__wysid__right__toolbar__design--show acym__wysid__right__toolbar__design acym__wysid__context__modal__container">
            <?php echo $zoneContent; ?>
		</div>
        <?php
    }

    /**
     * Called using ajax
     */
    public function displayListing(): void
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

    public function getElements(): array
    {
        $conditions = '';
        if (!empty($this->filters)) {
            $conditions = ' WHERE ('.implode(') AND (', $this->filters).')';
        }

        $ordering = '';
        if (!empty($this->pageInfo->order)) {
            $ordering = ' ORDER BY '.acym_secureDBColumn($this->pageInfo->order).' '.acym_secureDBColumn($this->pageInfo->orderdir);
        }

        $rows = acym_loadObjectList($this->querySelect.$this->query.$conditions.$ordering, '', $this->pageInfo->start, $this->pageInfo->limit);
        $this->pageInfo->total = acym_loadResult('SELECT COUNT(*) '.$this->query.$conditions.$ordering);

        if (!empty($this->defaultValues->id) && $this->defaultValues->defaultPluginTab === $this->name) {
            $found = false;
            foreach ($rows as $oneRow) {
                if (intval($oneRow->{$this->elementIdColumn}) === intval($this->defaultValues->id)) {
                    $found = true;
                }
            }

            if (!$found) {
                $conditions = ' WHERE '.$this->elementIdTable.'.'.$this->elementIdColumn.' = '.intval($this->defaultValues->id);
                $row = acym_loadObject($this->querySelect.$this->query.$conditions);
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    protected function getFilteringZone(bool $categoryFilter = true): string
    {
        $result = '<div class="grid-x" id="plugin_listing_filters">
                    <div class="cell medium-6">
                        <input type="text" name="plugin_search" placeholder="'.acym_escape(acym_translation('ACYM_SEARCH')).'"/>
                    </div>
                    <div class="cell medium-6 grid-x">
                        <div class="cell hide-for-small-only medium-auto"></div>
                        <div class="cell medium-shrink">';

        if ($categoryFilter) {
            $result .= $this->getCategoryFilter();
        }

        $result .= '</div>
                    </div>
                </div>';

        return $result;
    }

    public function prepareWPCategories(string $type): void
    {
        $this->categories = acym_loadObjectList(
            'SELECT cat.term_taxonomy_id AS id, parent.term_taxonomy_id AS parent_id, catdetails.name AS title 
            FROM `#__term_taxonomy` AS cat 
            JOIN `#__terms` AS catdetails ON cat.term_id = catdetails.term_id 
            LEFT JOIN `#__term_taxonomy` AS parent ON cat.parent = parent.term_id 
            WHERE cat.taxonomy = '.acym_escapeDB($type)
        );
        foreach ($this->categories as $i => $oneCat) {
            if (empty($oneCat->parent_id)) {
                $this->categories[$i]->parent_id = $this->rootCategoryId;
            }
        }
    }

    protected function getCategoryFilter()
    {
        $filter_cat = acym_getVar('int', 'plugin_category', 0);
        $this->catvalues = [];
        $this->catvalues[] = acym_selectOption(0, 'ACYM_ALL');

        $this->cats = [];
        $this->subCategories = [];
        if (!empty($this->categories)) {
            foreach ($this->categories as $oneCat) {
                $this->cats[$oneCat->parent_id][] = $oneCat;
            }
            $this->handleChildrenCategories($this->rootCategoryId);
            foreach ($this->categories as $oneCat) {
                $this->subCategories[$oneCat->id] = $this->getSubCats($oneCat->id);
            }
        }

        return acym_select(
            $this->catvalues,
            'plugin_category',
            intval($filter_cat),
            ['class' => 'plugin_category_select']
        );
    }

    private function getSubCats(int $categoryId): array
    {
        $result = [$categoryId];
        if (empty($this->cats[$categoryId])) {
            return $result;
        }

        foreach ($this->cats[$categoryId] as $oneSubCategory) {
            $result = array_merge($result, $this->getSubCats($oneSubCategory->id));
        }

        return $result;
    }

    protected function handleChildrenCategories(int $parent_id, int $level = 0): void
    {
        if (empty($this->cats[$parent_id])) return;

        foreach ($this->cats[$parent_id] as $cat) {
            $this->catvalues[] = acym_selectOption($cat->id, str_repeat(' - - ', $level).$cat->title);
            $this->handleChildrenCategories($cat->id, $level + 1);
        }
    }

    protected function getSubCategories(int $categoryId): array
    {
        $this->getCategoryFilter();

        return $this->subCategories[$categoryId];
    }

    protected function autoContentOptions(array &$options, ?string $type = null): void
    {
        if ($type === 'event') {
            $options[] = [
                'title' => 'ACYM_FROM',
                'type' => 'date',
                'name' => 'from',
                'default' => time(),
                'relativeDate' => '+',
            ];

            $options[] = [
                'title' => 'ACYM_TO',
                'type' => 'date',
                'name' => 'to',
                'default' => '',
                'relativeDate' => '+',
            ];
        } elseif ($type !== 'simple') {
            $options[] = [
                'title' => 'ACYM_MIN_PUBLISH_DATE',
                'tooltip' => 'ACYM_MIN_PUBLISH_DATE_DESC',
                'type' => 'date',
                'name' => 'min_publish',
                'default' => '',
                'relativeDate' => '-',
            ];
        }

        $options[] = [
            'title' => 'ACYM_COLUMNS',
            'type' => 'number',
            'name' => 'cols',
            'default' => 1,
        ];

        $options[] = [
            'title' => 'ACYM_COLUMN_HORIZONTAL_PADDING',
            'type' => 'number',
            'name' => 'hpadding',
            'default' => 10,
        ];

        $options[] = [
            'title' => 'ACYM_COLUMN_VERTICAL_PADDING',
            'type' => 'number',
            'name' => 'vpadding',
            'default' => 10,
        ];

        $options[] = [
            'title' => 'ACYM_MAX_NB_ELEMENTS',
            'type' => 'number',
            'name' => 'max',
            'default' => 20,
        ];
    }

    protected function autoCampaignOptions(array &$options, bool $modified = false): void
    {
        if (empty($this->campaignId) && empty($this->campaignType)) {
            return;
        } elseif (!empty($this->campaignId) && (empty($this->campaignType) || $this->campaignType !== 'auto')) {
            $campaignClass = new CampaignClass();
            $campaign = $campaignClass->getOneById($this->campaignId);
            if ($campaign->sending_type !== 'auto') return;
        } elseif (empty($this->campaignId) && !empty($this->campaignType) && $this->campaignType !== 'auto') {
            return;
        }

        $options[] = [
            'title' => 'ACYM_DOCUMENTATION',
            'type' => 'custom',
            'name' => 'documentation',
            'output' => '<a target="_blank" href="'.ACYM_DOCUMENTATION.'main-pages/campaigns/automatic-campaigns#dont-send-twice-the-same-content"><i class="acymicon-book"></i></a>',
            'js' => '',
            'section' => 'ACYM_AUTO_CAMPAIGNS_OPTIONS',
        ];

        if ($modified) {
            $options[] = [
                'title' => 'ACYM_DATE',
                'type' => 'select',
                'name' => 'datefilter',
                'default' => 'onlynew',
                'tooltip' => 'ACYM_ONLY_NEWLY_CREATED_DESC',
                'options' => [
                    '' => 'ACYM_NO_FILTER',
                    'onlynew' => 'ACYM_ONLY_NEWLY_CREATED',
                    'onlymodified' => 'ACYM_ONLY_NEWLY_MODIFIED',
                ],
                'section' => 'ACYM_AUTO_CAMPAIGNS_OPTIONS',
            ];
        } else {
            $options[] = [
                'title' => 'ACYM_ONLY_NEWLY_CREATED',
                'type' => 'boolean',
                'name' => 'onlynew',
                'default' => true,
                'tooltip' => 'ACYM_ONLY_NEWLY_CREATED_DESC',
                'section' => 'ACYM_AUTO_CAMPAIGNS_OPTIONS',
            ];
        }

        $options[] = [
            'title' => 'ACYM_MIN_NB_ELEMENTS',
            'type' => 'number',
            'name' => 'min',
            'default' => 0,
            'tooltip' => 'ACYM_MIN_NB_ELEMENTS_DESC',
            'section' => 'ACYM_AUTO_CAMPAIGNS_OPTIONS',
        ];
    }

    protected function getElementsListing(array $options): string
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

    private function getInnerListing(array $options): string
    {
        if (empty($options['rows']) && $this->pageInfo->loadMore) {
            $listing = '<h3 class="cell acym__listing__empty__load-more text-center">'.acym_translation('ACYM_NO_MORE_RESULTS').'</h3>';
        } elseif (empty($options['rows'])) {
            $listing = '<h1 class="cell acym__listing__empty__search__modal text-center">'.acym_translation('ACYM_NO_RESULTS_FOUND').'</h1>';
        } else {
            $selected = explode(',', acym_getVar('string', 'selected', ''));
            if (!empty($this->defaultValues->id)) {
                $selected = [$this->defaultValues->id];
            }

            $listing = '';
            foreach ($options['rows'] as $row) {
                $class = 'cell grid-x acym__row__no-listing acym__listing__row__popup';
                if (in_array($row->{$options['id']}, $selected)) {
                    $class .= ' selected_row';
                }

                $listing .= '<div 
                    class="'.$class.'" 
                    data-id="'.acym_escape($row->{$options['id']}).'" 
                    onclick="applyContent'.acym_escape($this->name).'(\''.acym_escape($row->{$options['id']}).'\', this);">';

                foreach ($options['header'] as $column => $oneColumn) {
                    $value = $row->$column;

                    if (!empty($oneColumn['type'])) {
                        if ($oneColumn['type'] === 'date') {
                            if (empty($value)) {
                                $value = '-';
                            } else {
                                if (!is_numeric($value) && $value != '0000-00-00 00:00:00') {
                                    $value = strtotime($value);
                                }
                                $tooltip = acym_date($value, acym_translation('ACYM_DATE_FORMAT_LC2'));
                                $value = acym_tooltip(
                                    [
                                        'hoveredText' => acym_date($value, acym_translation('ACYM_DATE_FORMAT_LC5')),
                                        'textShownInTooltip' => $tooltip,
                                    ]
                                );
                            }
                        } elseif ($oneColumn['type'] === 'int') {
                            $value = intval($value);
                        }
                    }

                    $class = empty($oneColumn['class']) ? '' : ' '.$oneColumn['class'];
                    $listing .= '<div class="cell medium-'.$oneColumn['size'].$class.'">'.$value.'</div>';
                }

                $listing .= '</div>';
            }
        }

        return $listing;
    }

    protected function getCategoryListing(): string
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

            $classes = 'cell grid-x acym__row__no-listing acym__listing__row__popup';
            if (in_array($oneCat->value, $selected)) {
                $classes .= ' selected_row';
            }
            $listing .= '<div class="'.$classes.'" data-id="'.intval($oneCat->value).'" onclick="applyContentauto'.acym_escape($this->name).'('.intval($oneCat->value).', this);">
                    <div class="cell medium-5">'.acym_escape($oneCat->text).'</div>
                </div>';
        }
        $listing .= '</div>';

        return $listing;
    }

    protected function getTagListing(): string
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
        $termIdName = 'term_id';
        if ('wordpress' === ACYM_CMS) {
            $termIdName = 'term_taxonomy_id';
        }
        foreach ($this->tagvalues as $oneTag) {
            if (empty($oneTag->$termIdName)) continue;

            $class = 'cell grid-x acym__row__no-listing acym__listing__row__popup';
            if (in_array($oneTag->$termIdName, $selected)) $class .= ' selected_row';
            $listing .= '<div class="'.$class.'" data-id="'.intval($oneTag->$termIdName).'" onclick="applyContent'.acym_escape($this->name).'_tags('.intval($oneTag->$termIdName).', this);">
                        <div class="cell medium-5">'.acym_escape($oneTag->name).'</div>
                    </div>';
        }
        $listing .= '</div>';

        return $listing;
    }

    protected function replaceMultiple(object &$email)
    {
        $this->generateByCategory($email);
        if (empty($this->tags)) return;

        $this->pluginHelper->replaceTags($email, $this->tags, true);
    }

    protected function handleOrderBy(string &$query, object $parameter, ?string $table = null)
    {
        if (empty($parameter->order)) return;

        $ordering = explode(',', $parameter->order);
        if ($ordering[0] === 'rand') {
            $query .= ' ORDER BY rand()';
        } else {
            $table = null === $table ? '' : $table.'.';
            $column = $ordering[0];

            if (strpos($column, '.') !== false) {
                $parts = explode('.', $column, 2);
                $table = acym_secureDBColumn($parts[0]).'.';
                $column = $parts[1];
            }

            $query .= ' ORDER BY '.$table.'`'.acym_secureDBColumn(trim($column)).'` '.acym_secureDBColumn(trim($ordering[1]));
        }
    }

    protected function handleMax(string &$query, object $parameter): void
    {
        if (empty($parameter->max)) {
            $parameter->max = 20;
        }
        $query .= ' LIMIT '.intval($parameter->max);
    }

    protected function handleMin(array $elements, object $parameter): void
    {
        if (!empty($parameter->min) && count($elements) < $parameter->min) {
            $this->generateCampaignResult->status = false;
            $this->generateCampaignResult->message = acym_translationSprintf(
                'ACYM_GENERATE_CAMPAIGN_NOT_ENOUGH_CONTENT',
                $this->pluginDescription->name,
                count($elements),
                $parameter->min
            );
        }
    }

    protected function getLastGenerated(int $mailId): int
    {
        $campaignClass = new CampaignClass();

        return $campaignClass->getLastGenerated($mailId);
    }

    /**
     * Returns the individual elements tags based on a query result
     */
    protected function finalizeCategoryFormat(string $query, object $parameter, ?string $table = null): string
    {
        $this->handleOrderBy($query, $parameter, $table);
        $this->handleMax($query, $parameter);

        $elements = acym_loadResultArray($query);

        return $this->formatIndividualTags($elements, $parameter);
    }

    protected function formatIndividualTags(array $elements, object $parameter): string
    {
        $this->handleMin($elements, $parameter);

        if (empty($elements)) return '';

        $elements = $this->groupByCategory($elements);

        $customLayout = ACYM_CUSTOM_PLUGIN_LAYOUT.$this->name.'_auto.php';
        if (file_exists($customLayout)) {
            ob_start();
            require $customLayout;

            return ob_get_clean();
        }

        $arrayElements = $this->buildIndividualTags($elements, $parameter);

        return $this->pluginHelper->getFormattedResult($arrayElements, $parameter);
    }

    protected function buildIndividualTags(array $elements, object $parameter): array
    {
        $arrayElements = [];
        unset($parameter->id);

        $i = 0;
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

            if ($i % 2 === 1 && !empty($parameter->alternate)) {
                $args[] = 'invert';
            }

            $arrayElements[] = '{'.implode('| ', $args).'}';

            $i++;
        }

        return $arrayElements;
    }

    /**
     * This method can be overridden in the add-on when a group by category option is added
     */
    protected function groupByCategory(array $elements)
    {
        return $elements;
    }

    protected function getSelectedArea(object $parameter): array
    {
        $allcats = explode('-', $parameter->id);
        $selectedArea = [];
        foreach ($allcats as $oneCat) {
            if (empty($oneCat)) continue;
            $selectedArea[] = intval($oneCat);
        }

        return $selectedArea;
    }

    protected function replaceOne(object &$email): void
    {
        $tags = $this->pluginHelper->extractTags($email, $this->name);
        if (empty($tags)) return;

        $newConfiguration = [
            'dcontent_default_'.get_class($this) => json_encode(end($tags)),
        ];
        $this->config->saveConfig($newConfiguration);

        if (method_exists($this, 'loadLibraries') && !$this->loadLibraries($email)) {
            return;
        }

        $this->emailLanguage = $email->links_language;
        $translationTool = $this->config->get('translate_content', 'no');

        $tagsReplaced = [];
        foreach ($tags as $i => $oneTag) {
            if (isset($tagsReplaced[$i])) continue;

            if (!empty($this->emailLanguage) && $translationTool !== 'no' && acym_isMultilingual()) {
                $oneTag->id = $this->getTranslationId($oneTag->id, $translationTool);
            }
            $tagsReplaced[$i] = $this->replaceIndividualContent($oneTag);
        }

        $email->custom_view = file_exists(ACYM_CUSTOM_PLUGIN_LAYOUT.$this->name.'.html');

        $this->pluginHelper->replaceTags($email, $tagsReplaced, true);
    }

    protected function initIndividualContent(object &$tag, string $query)
    {
        $element = acym_loadObject($query);

        if (empty($element)) {
            if (acym_isAdmin()) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_CONTENT_NOT_FOUND', $tag->id), 'notice');
            }

            return null;
        }

        if (empty($tag->display)) {
            $tag->display = [];
        } else {
            $tag->display = explode(',', $tag->display);
        }

        return $element;
    }

    protected function getCustomLayoutVars(object $element): array
    {
        $varFields = [];
        $varFields['{picthtml}'] = '';
        foreach ($element as $fieldName => $oneField) {
            if (is_object($oneField) || is_array($oneField)) {
                $varFields['{'.$fieldName.'}'] = json_encode($oneField);
            } else {
                $varFields['{'.$fieldName.'}'] = $oneField;
            }
        }

        return $varFields;
    }

    /**
     * Handles the custom layouts and the pictures management
     *
     * @param string $htmlResult What will be inserted in the email
     * @param object $insertionOptions Selected options when inserting dcontent
     * @param array  $dataShortcodes Data used as shortcodes in custom layouts
     */
    protected function finalizeElementFormat(string $htmlResult, object $insertionOptions, array $dataShortcodes): string
    {
        // Hidden feature, users can do a PHP custom view
        $fullCustomLayoutPath = ACYM_CUSTOM_PLUGIN_LAYOUT.$this->name.'.php';
        if ($this->config->get('php_overrides', 0) == 1 && file_exists($fullCustomLayoutPath)) {
            ob_start();
            require $fullCustomLayoutPath;
            $viewContent = ob_get_clean();
            $htmlResult = str_replace(array_keys($dataShortcodes), $dataShortcodes, $viewContent);
        } else {
            // Default custom view feature with only HTML for security reasons
            $customLayoutPath = ACYM_CUSTOM_PLUGIN_LAYOUT.$this->name.'.html';
            if (file_exists($customLayoutPath)) {
                $dataShortcodes['{wrappedText}'] = $this->pluginHelper->wrappedText;
                $viewContent = acym_fileGetContent($customLayoutPath);
                $viewContentReplace = str_replace(array_keys($dataShortcodes), $dataShortcodes, $viewContent);
                if ($viewContent !== $viewContentReplace) {
                    $htmlResult = $viewContentReplace;
                }
            }
        }

        $allShortcodesList = '<table><tr><th>Shortcode</th><th>Value</th></tr>';
        foreach ($dataShortcodes as $name => $value) {
            if (!is_string($value)) {
                continue;
            }

            $allShortcodesList .= '<tr><td>'.trim($name, '{}').'</td><td>'.$value.'</td></tr>';
        }
        $allShortcodesList .= '</table>';
        $htmlResult = str_replace('{allshortcodes}', $allShortcodesList, $htmlResult);

        // Resize/remove pictures if needed
        return $this->pluginHelper->managePicts($insertionOptions, $htmlResult);
    }

    protected function filtersFromConditions(array &$filters): void
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

    protected function getElementTags(string $type, int $id): array
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

    /**
     * @return mixed
     */
    protected function getFormattedValue(array $fieldValues)
    {
        $field = $fieldValues[0];

        if (!empty($field->fieldparams) && !is_array($field->fieldparams)) {
            $field->fieldparams = json_decode($field->fieldparams, true);
        }

        switch ($field->type) {
            case 'calendar':
                $format = acym_translation($field->fieldparams['showtime'] == '1' ? 'ACYM_DATE_FORMAT_LC2' : 'ACYM_DATE_FORMAT_LC1');
                $field->value = HTMLHelper::_('date', $field->value, $format);
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

                $field->value = implode(', ', $values);
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
                $value = '';
                if (!empty($field->value)) {
                    if (substr($field->value, 0, 1) === '{') {
                        $value = json_decode($field->value, true);
                        if (!empty($value['imagefile'])) {
                            $alt = empty($value['alt_text']) ? '' : ' alt="'.acym_escape($value['alt_text']).'"';
                            $value = '<img src="'.$value['imagefile'].'"'.$alt.' />';
                        }
                    } else {
                        $value = '<img src="'.$field->value.'" alt="" />';
                    }
                }

                $field->value = $value;
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
            case 'subform':
                if (!empty($field->value)) {
                    $rows = json_decode($field->value, true);

                    $formattedValues = [];
                    foreach ($rows as $values) {
                        $fieldValues = [];
                        foreach ($values as $fieldIdentifier => $fieldValue) {
                            $fieldId = str_replace('field', '', $fieldIdentifier);
                            $oneFieldDefinition = acym_loadObject(
                                'SELECT `field`.*
                                FROM #__fields AS `field`
                                WHERE `field`.`id` = '.intval($fieldId)
                            );

                            if (empty($oneFieldDefinition)) {
                                break 3;
                            }

                            $multiCompatibleValues = [];
                            if (is_array($fieldValue)) {
                                foreach ($fieldValue as $oneFieldValue) {
                                    $oneValue = clone $oneFieldDefinition;
                                    $oneValue->value = $oneFieldValue;
                                    $multiCompatibleValues[] = $oneValue;
                                }
                            } else {
                                $oneFieldDefinition->value = $fieldValue;
                                $multiCompatibleValues[] = $oneFieldDefinition;
                            }

                            $fieldValues[] = $oneFieldDefinition->label.': '.$this->getFormattedValue($multiCompatibleValues);
                        }
                        $formattedValues[] = implode(', ', $fieldValues);
                    }

                    $field->value = empty($formattedValues) ? '' : '<ul><li>'.implode('</li><li>', $formattedValues).'</li></ul>';
                }
        }

        return $field->value;
    }

    protected function getLanguage(?string $elementLanguage = null, bool $onlyValue = false): string
    {
        $value = $this->emailLanguage;
        if (!empty($elementLanguage) && $elementLanguage !== '*') {
            $value = $elementLanguage;
        }

        if (empty($value)) {
            return '';
        }

        if (ACYM_CMS === 'joomla' && acym_isPluginActive('languagefilter')) {
            $languages = acym_loadObjectList('SELECT * FROM #__languages', 'lang_code');
            if (isset($languages[$value])) {
                $value = $languages[$value]->sef;
            } else {
                $value = substr($value, 0, strpos($value, '-'));
            }

            if ($onlyValue) {
                return $value;
            }

            return '&lang='.$value;
        } else {
            if ($onlyValue) {
                return $value;
            }

            return '&language='.$value;
        }
    }

    // Don't add types for $tag and $sef, for retro-compatibility
    protected function finalizeLink(string $link, $tag = null, $sef = true): string
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

        if (!empty($tag->autologin)) {
            $link .= (strpos($link, '?') ? '&' : '?').'autoSubId={subscriber:id}&subKey={subscriber:key|urlencode}';
        }

        // Retro-compatibility for add-on versions before the v8.6.0
        if (is_bool($tag)) {
            $sef = $tag;
        }

        return acym_frontendLink($link, false, $sef);
    }

    protected function handleCustomFields(object $tag, array &$customFields): void
    {
        if (empty($tag->custom)) {
            return;
        }

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

        usort($fields, function ($a, $b) {
            return $a[0]->ordering <=> $b[0]->ordering;
        });

        foreach ($fields as $fieldValues) {
            $value = $this->getFormattedValue($fieldValues);

            if (empty($value)) continue;

            $customFields[] = [
                $value,
                $fieldValues[0]->label,
            ];
        }
    }

    protected function getIntro(string $text): string
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

        if (empty($pageBreak)) {
            return $text;
        }

        $split = explode($pageBreak, $text, 2);

        return array_shift($split);
    }

    public function displayCustomViewEditor(string &$output): void
    {
        $plugin = new \stdClass();
        $plugin->folder_name = $this->name;
        $plugin->settings = $this->settings;
        $this->generateSettings($plugin);

        if (empty($plugin->settings['custom_view'])) {
            return;
        }

        $output .= '<p class="acym__wysid__right__toolbar__p acym__wysid__right__toolbar__p__open acym__title">';
        $output .= acym_translation('ACYM_ADDON_SETTINGS');
        $output .= '<i class="acymicon-keyboard-arrow-up"></i></p>';
        $output .= '<div class="acym__wysid__right__toolbar__design--show acym__wysid__right__toolbar__design acym__wysid__context__modal__container">';
        $output .= $plugin->settings['custom_view'];
        $output .= '</div>';
    }

    public function generateSettings(object $plugin): bool
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
            if (!empty($field['label'])) {
                $field['label'] = acym_translation($field['label']);
            }

            if (!empty($field['info'])) {
                $field['label'] .= acym_info(
                    [
                        'textShownInTooltip' => acym_translation($field['info']),
                        'classText' => 'wysid_tooltip',
                        'isWarning' => !empty($field['info_warning']),
                    ]
                );
            }

            if ($field['type'] === 'checkbox') {
                $classLabel = 'shrink';
                $text .= '<label for="'.acym_escape($id).'" class="cell '.acym_escape($classLabel).'">'.acym_escape($field['label']).'</label>';
                $text .= '<input id="'.acym_escape($id).'" class="cell shrink" type="checkbox" name="'.acym_escape($name).'" '.(empty($field['value']) ? '' : 'checked').'>';
            } elseif ($field['type'] === 'switch') {
                $text .= acym_switch(
                    $name,
                    $field['value'],
                    $field['label'],
                    [],
                    'large-7'
                );
            } elseif ($field['type'] === 'select') {
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
            } elseif ($field['type'] === 'multiple_select') {
                $text .= '<label class="cell shrink">'.$field['label'].'</label>';
                $text .= acym_selectMultiple(
                    $field['data'],
                    $name,
                    empty($field['value']) ? [] : $field['value'],
                    ['class' => 'acym__select']
                );
            } elseif ($field['type'] === 'text') {
                $text .= '<label class="cell shrink">'.$field['label'].'</label>';
                $text .= '<input type="text" name="'.$name.'" value="'.acym_escape($field['value']).'" class="cell shrink">';
            } elseif ($field['type'] === 'number') {
                $text .= '<label class="cell shrink">'.$field['label'].'</label>';
                $text .= '<input type="number" name="'.$name.'" value="'.acym_escape($field['value']).'" class="cell large-2 medium-5">';
                if (!empty($field['post_text'])) $text .= '<span class="cell shrink">'.strtolower($field['post_text']).'</span>';
            } elseif ($field['type'] === 'radio') {
                $text .= '<p class="cell">'.$field['label'].'</p>';
                $text .= acym_radio(
                    $field['data'],
                    $name,
                    $field['value']
                );
            } elseif ($field['type'] === 'date') {
                $text .= '<label class="cell shrink">'.$field['label'].'</label>';
                $text .= acym_dateField(
                    $name,
                    $field['value'],
                    'cell shrink'
                );
            } elseif ($field['type'] === 'custom_view' && acym_isAdmin()) {
                $idCustomView = 'acym__plugins__installed__custom-view__'.$this->name;
                $ctrl = acym_getVar('string', 'ctrl', '');
                $classTooltip = $ctrl === 'dynamics' ? '' : 'wysid_tooltip';
                $text .= '<label class="cell">';
                $text .= acym_translation('ACYM_CUSTOM_VIEW');
                $text .= acym_info(
                    [
                        'textShownInTooltip' => 'ACYM_CUSTOM_VIEW_DESC',
                        'classText' => $classTooltip,
                    ]
                );
                $text .= '</label>';

                if (empty($field['tags'])) {
                    $field['tags'] = [];
                }

                $modalContent = '<div id="'.acym_escape($idCustomView).'" class="cell grid-x acym__plugins__installed__custom-view" acym-data-tags="'.acym_escape(
                        json_encode($field['tags'])
                    ).'">
                                    <h2 class="cell text-center acym__title__primary__color">'.acym_translationSprintf('ACYM_CUSTOM_VIEW_FOR_X', $this->pluginDescription->name).'</h2>
                                    <div class="cell grid-x acym__plugins__installed__custom-view__edit-container">
                                        <div class="acym__plugins__installed__custom-view__editor-loader grid-x cell align-center acym_vcenter" v-if="loading">'.acym_loaderLogo().'</div>
                                        <vue-prism-editor :emitEvents="true" class="cell acym__plugins__installed__custom-view__code cell auto" v-model="code" :language="language" lineNumbers="true"></vue-prism-editor>
                                        <div class="cell grid-x medium-3 margin-left-1 acym__plugins__installed__custom-view__tags">
                                            <h3 class="acym__title acym__title__secondary cell text-center">'.acym_translation('ACYM_DYNAMIC_CONTENT').acym_info(
                        [
                            'textShownInTooltip' => acym_translation('ACYM_DYNAMIC_CONTENT_DESC'),
                        ]
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
                                            <button @click="save()" class="cell shrink button" type="button">'.acym_translation('ACYM_SAVE_NEW_CUSTOM_VIEW_VERSION').'</button>
                                        </div>
                                    </div>
                                </div>';
                $text .= acym_modal(
                    acym_translation('ACYM_EDIT_CUSTOM_VIEW'),
                    $modalContent,
                    null,
                    [
                        'acym-data-plugins-id' => $idCustomView,
                        'acym-data-plugin-class' => get_class($this),
                        'acym-data-plugin-folder' => $this->name,
                    ],
                    ['class' => 'cell button']
                );
            } elseif ($field['type'] == 'multikeyvalue') {
                $text .= '<label class="cell shrink">'.$field['label'].'</label>';

                $text .= '<div class="multikeyvalue_container grid-x">';

                if (!empty($field['value'])) {
                    $headers = json_decode($field['value'], true);
                    foreach ($headers as $headerKey => $headerValue) {
                        $text .= '<input type="text" class="cell" placeholder="'.acym_translation('ACYM_DKIM_KEY', true).'" value="'.acym_escape($headerKey).'"/>';
                        $text .= '<input type="text" class="cell" placeholder="'.acym_translation('ACYM_VALUE', true).'" value="'.acym_escape($headerValue).'" />';
                        $text .= '<div class="multikeyvalue_container_separator cell small-6"></div>';
                    }
                }

                $text .= '<input type="text" class="cell" placeholder="'.acym_translation('ACYM_DKIM_KEY', true).'" value=""/>';
                $text .= '<input type="text" class="cell" placeholder="'.acym_translation('ACYM_VALUE', true).'" value="" />';

                $text .= '<button class="button multikeyvalue_container_new">'.acym_translation('ACYM_ADD_NEW').'</button>';

                $text .= '<input type="hidden" name="'.$name.'" value="" />';
                $text .= '</div>';
            } elseif ($field['type'] == 'custom') {
                $text .= $field['content'];
            }
            $plugin->settings[$key] = $text;
        }

        return true;
    }

    /**
     * Called using ajax
     *
     * @param string $css This attribute is the name of the file in the folder css of the plugin OR it can be raw CSS
     */
    public function loadCSS(string $css, bool $raw = false, ?string $path = null): void
    {
        if (!$raw) {
            if (empty($path)) $path = ACYM_DYNAMICS_URL.$this->name;
            $css = $path.'/css/'.$css.'.css';
        }
        acym_addStyle($raw, $css);
    }

    /**
     * @param string $js This attribute is the name of the file in the folder js of the plugin OR it can be raw Javascript
     */
    public function loadJavascript(string $js, bool $raw = false, ?string $path = null): void
    {
        if (!$raw) {
            if (empty($path)) $path = ACYM_DYNAMICS_URL.$this->name;
            $js = $path.'/js/'.$js.'.js';
        }
        acym_addScript($raw, $js);
    }

    public function includeView(string $view, array $data = [], ?string $path = null): string
    {
        if (empty($path)) {
            $path = ACYM_ADDONS_FOLDER_PATH.$this->name.DS.'views'.DS.$view.'.php';
        } else {
            $path = $path.DS.'views'.DS.$view.'.php';
        }

        if (!file_exists($path)) {
            throw new \Exception(acym_translation('ACYM_NON_EXISTING_PAGE'));
        }

        ob_start();
        include $path;

        return ob_get_clean();
    }

    /**
     * This function will parse and show all settings
     */
    public function onAcymAddSettings(array &$plugins): void
    {
        foreach ($plugins as $key => $plugin) {
            if ($plugin->folder_name === $this->name) {
                if (!empty($plugin->settings)) {
                    foreach ($plugin->settings as $keySettings => $value) {
                        if (isset($this->settings[$keySettings]['value'])) {
                            $this->settings[$keySettings]['value'] = $plugin->settings[$keySettings]['value'];
                        }
                    }
                }
                $plugins[$key]->settings = $this->settings;

                $this->generateSettings($plugins[$key]);
                break;
            }
        }
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getParam(string $name, $default = '')
    {
        return $this->savedSettings[$name]['value'] ?? $default;
    }

    protected function getTranslationId(int $elementId, string $translationTool, bool $defaultLanguage = false)
    {
        return $elementId;
    }

    public function filterSpecialMailsDailySend(array &$specialMails, int $time, string $mailType): void
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
        foreach ($specialMails as $oneMail) {
            if ($oneMail->sending_type === $mailType) {
                $noNextTrigger = empty($oneMail->next_trigger);
                $nextTriggerIsNow = date('m-d', $oneMail->next_trigger) == date('m-d', $dayBasedOnCMSTimezoneAtSpecifiedHour);
                $nextTriggerIsInPast = $oneMail->next_trigger < $time;
                if ($time >= $dayBasedOnCMSTimezoneAtSpecifiedHour && ($noNextTrigger || $nextTriggerIsNow || $nextTriggerIsInPast)) {
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

    protected function getIdsSelectAjax(): array
    {
        $ids = acym_getVar('string', 'id');
        if (empty($ids)) {
            return [];
        }

        $ids = explode(',', $ids);
        acym_arrayToInteger($ids);

        return $ids;
    }

    protected function cleanExtensionContent(string $text)
    {
        if (ACYM_CMS === 'wordpress') {
            if (!acym_isExtensionActive('classic-editor/classic-editor.php') || strpos($text, '<!-- wp:') !== false) {
                return $text;
            }

            return nl2br($text);
        } else {
            return preg_replace('#\{igallery[^}]+\}#Ui', '', $text);
        }
    }

    protected function callApiSendingMethod(string $url, array $data = [], array $headers = [], string $type = 'GET', array $authentication = [], bool $dataDecoded = false): array
    {
        if (!empty($headers) && empty($headers[0])) {
            $newHeaders = [];
            foreach ($headers as $key => $value) {
                $newHeaders[] = $key.': '.$value;
            }
            $headers = $newHeaders;
        }

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
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ];

        if (!empty($data)) {
            if (empty($dataDecoded)) {
                $optionsArray[CURLOPT_POSTFIELDS] = json_encode($data);
            } elseif ($dataDecoded === true) {
                $optionsArray[CURLOPT_POSTFIELDS] = $data;
            }
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
        $this->responseCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($error) {
            return ['error_curl' => $error];
        } else {
            $response = json_decode($response, true);

            return $response === null ? ['error_curl' => 'Malformed response'] : $response;
        }
    }

    protected function getTestCredentialsSendingMethodButton(string $sendingMethodId): string
    {
        return '<div class="cell grid-x margin-top-1 acym__sending__methods__credentials__test">
                    <button type="button" sending-method-id="'.acym_escape($sendingMethodId).'" class="acym__configuration__sending__method-test cell shrink button button-secondary">
                    '.acym_translation('ACYM_TEST_CREDENTIALS').'
                    </button>
                    <span class="acym__configuration__sending__method-icon cell shrink margin-left-1 acym_vcenter"></span>
                    <span class="acym__configuration__sending__method-test__message cell shrink margin-left-1 acym_vcenter"></span>
                </div>';
    }

    public function getCopySettingsButton(array $data, string $sendingMethodId, string $fromPlugin, bool $withContainer = true): string
    {
        if (empty($data[$fromPlugin.'_installed'])) {
            return '';
        }

        $button = '<button
                        type="button"
                        class="cell shrink button button-secondary acym__configuration__copy__mail__settings"
                        acym-data-plugin="'.acym_escape($fromPlugin).'"
                        acym-data-method="'.acym_escape($sendingMethodId).'">
	                    '.acym_translationSprintf('ACYM_COPY_SETTINGS_FROM', $this->sendingPlugins[$fromPlugin]).'
                    </button>
                    <span class="acym__configuration__sending__method-icon cell shrink margin-left-1 acym_vcenter"></span>';

        if ($withContainer) {
            $button = '<div class="cell grid-x margin-top-1">'.$button.'</div>';
        }

        return $button;
    }

    protected function getLinks(string $account = '', string $pricing = ''): string
    {
        if (empty($account) && empty($pricing)) return '';

        $html = '<div class="cell grid-x acym-grid-margin-x shrink acym_vcenter"><p class="cell shrink">'.acym_translation('ACYM_DONT_HAVE_ACCOUNT').'</p>';
        if (!empty($account)) $html .= '<a target="_blank" class="cell shrink" href="'.$account.'">'.acym_translation('ACYM_CREATE_ONE').'</a>';
        if (!empty($account) && !empty($pricing)) $html .= '<p class="cell shrink">'.strtolower(acym_translation('ACYM_OR')).'</p>';
        if (!empty($pricing)) $html .= '<a target="_blank" class="cell shrink" href="'.$pricing.'">'.acym_translation('ACYM_CHECK_THEIR_PRICING').'</a>';
        $html .= '</div>';

        return $html;
    }

    public function onAcymGetSendingMethodsSelected(array &$data): void
    {
        if (ACYM_CMS === 'wordpress') {
            $this->config->load();
        }

        $mailerMethod = $this->config->get('mailer_method', 'phpmail');
        foreach ($data['sendingMethods'] as $key => $sendingMethod) {
            $data['sendingMethods'][$key]['selected'] = $key == $mailerMethod;
        }
    }

    public function errorCallback(): void
    {
        foreach ($this->errors as $error) {
            acym_logError($error, empty($this->name) ? get_class($this) : $this->name);
        }

        $this->errors = [];
    }

    public function isLogFileEmpty(): bool
    {
        return !acym_isLogFileErrorExist(empty($this->name) ? get_class($this) : $this->name);
    }

    public function initCustomView(bool $customFields = false): void
    {
        $page = acym_getVar('cmd', 'page');
        if (is_array($page)) return;
        if (!empty($page) && is_string($page)) {
            $page = str_replace(ACYM_COMPONENT.'_', '', $page);
        }
        $ctrl = acym_getVar('cmd', 'ctrl', $page);
        if (!in_array($ctrl, ['plugins', 'dynamics'])) return;

        $task = acym_getVar('cmd', 'task', 'installed');

        // Installed add-ons page or installed + editor
        if (($ctrl === 'plugins' && $task === 'installed') || ($this->active && $ctrl === 'dynamics' && $task === 'trigger')) {
            $this->initElementOptionsCustomView();
            $this->initReplaceOptionsCustomView();
            if ($customFields) $this->initCustomOptionsCustomView();
        }
    }

    public function processDateToCheck(array $options): \DateTime
    {
        $dateNowWithTimeZone = acym_date('now', 'Y-m-d h:i:s');
        $dateToCheck = new \DateTime($dateNowWithTimeZone);
        $interval = new \DateInterval('P'.intval($options['days']).'D');
        if ($options['relative'] === 'before') {
            $dateToCheck->add($interval);
        } else {
            $dateToCheck->sub($interval);
        }

        return $dateToCheck;
    }

    public function onAcymCheckInstalled(bool &$installed): void
    {
        $installed = $this->installed;
    }

    protected function getLinkTranslated(string $link): string
    {
        if (empty($this->emailLanguage)) return $link;
        if (!acym_isMultilingual()) return $link;

        $translationTool = $this->config->get('translate_content', 'no');
        if ($translationTool !== 'wpml') return $link;

        if (!acym_isExtensionActive('sitepress-multilingual-cms/sitepress.php')) return $link;

        $languageCode = substr($this->emailLanguage, 0, 2);

        return apply_filters('wpml_permalink', $link, $languageCode);
    }

    protected function replaceShortcode(string $content): string
    {
        $content = do_shortcode($content);

        return preg_replace('#<!-- wp:shortcode -->(.*)<!-- /wp:shortcode -->#Uis', '$1', $content);
    }

    protected function fixDivStructure(string $text): string
    {
        $nbOpeningDivs = substr_count($text, '<div');
        $nbClosingDivs = substr_count($text, '</div>');

        if ($nbOpeningDivs > $nbClosingDivs) {
            $text .= str_repeat('</div>', $nbOpeningDivs - $nbClosingDivs);
        } elseif ($nbOpeningDivs < $nbClosingDivs) {
            $text = str_repeat('<div>', $nbClosingDivs - $nbOpeningDivs).$text;
        }

        return $text;
    }

    protected function getBounceAddress(object $mailerHelper): string
    {
        if (method_exists($mailerHelper, 'getSendSettings')) {
            return $mailerHelper->getSendSettings('bounce_email');
        } else {
            return $this->config->get('bounce_email');
        }
    }
}
