<?php

trait ModuleInsertion
{
    public function insertionOptions($defaultValues = null)
    {
        $languages = [
            '' => 'ACYM_DEFAULT_LANGUAGE',
        ];

        $siteLanguages = acym_getLanguages(false, true);
        foreach ($siteLanguages as $oneLanguage) {
            $languages[$oneLanguage->sef] = $oneLanguage->name;
        }

        $displayOptions = [
            [
                'title' => 'ACYM_LANGUAGE',
                'tooltip' => 'ACYM_LANGUAGE_DESC',
                'type' => 'select',
                'name' => 'language',
                'options' => $languages,
                'default' => 'default',
                'section' => 'ACYM_MAIN_OPTIONS',
            ],
        ];

        echo '<div><i class="acymicon-exclamation-triangle"></i>'.acym_translation('ACYM_MODULE_INSERTION_WARNING').'</div>';
        $zoneContent = $this->getFilteringZone(false).$this->prepareListing();
        echo $this->displaySelectionZone($zoneContent);
        echo $this->pluginHelper->displayOptions($displayOptions, $this->name, 'individual', $defaultValues);
    }

    public function prepareListing()
    {
        $this->querySelect = 'SELECT item.id, item.title, item.position, item.module ';
        $this->query = 'FROM #__modules AS item ';
        $this->filters = [];
        $this->filters[] = 'item.published != -1';
        $this->filters[] = 'item.client_id = 0';
        $this->filters[] = 'item.module NOT IN ("mod_poll", "mod_login", "mod_breadcrumbs", "mod_acym", "mod_wrapper")';
        $this->searchFields = ['item.title', 'item.position', 'item.module'];
        $this->pageInfo->order = 'item.position';
        $this->elementIdTable = 'item';
        $this->elementIdColumn = 'id';

        parent::prepareListing();

        $listingOptions = [
            'header' => [
                'title' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '4',
                ],
                'position' => [
                    'label' => 'ACYM_POSITION',
                    'size' => '3',
                ],
                'module' => [
                    'label' => 'ACYM_MODULE',
                    'size' => '4',
                ],
                'id' => [
                    'label' => 'ACYM_ID',
                    'size' => '1',
                    'class' => 'text-center',
                ],
            ],
            'id' => 'id',
            'rows' => $this->getElements(),
        ];

        return $this->getElementsListing($listingOptions);
    }

    public function replaceContent(&$email)
    {
        $this->replaceOne($email);
    }

    public function replaceIndividualContent($tag)
    {
        $module = acym_loadObject('SELECT * FROM #__modules WHERE id = '.intval($tag->id));
        if (empty($module)) {
            return '';
        }

        $url = ACYM_LIVE.'index.php?option=com_acym&tmpl=component&ctrl=moduleloader&moduleId='.$tag->id;
        $url .= '&seckey='.urlencode($this->config->get('security_key'));
        $url .= '&time='.time();

        $language = empty($tag->language) && !empty($email->language) ? $email->language : $tag->language;
        if (!empty($language)) {
            $url .= '&lang='.$language;
        } else {
            // If the language switcher is active, we must specify a language or the page breaks
            $languageSwitcher = acym_loadObject('SELECT * FROM #__modules WHERE module = "mod_languages" AND published = 1');
            if (!empty($languageSwitcher)) {
                $url .= '&lang='.acym_getLanguageTag(true);
            }
        }

        $data = acym_fileGetContent($url);

        $decodedData = @json_decode($data, true);
        if (empty($decodedData['output'])) {
            return '';
        }

        $moduleOutput = $decodedData['output'];
        // Replace any occurrence of the loading URL in the inserted module by the homepage URL
        $temporaryUrl = str_replace(ACYM_LIVE, '', $url);
        $moduleOutput = str_replace([$temporaryUrl, str_replace('&', '&amp;', $temporaryUrl)], 'index.php', $moduleOutput);
        // Clean the module output from any javascript code
        $moduleOutput = preg_replace("#(onclick|onfocus|onload|onblur) *= *\"(?:(?!\").)*\"#Ui", '', $moduleOutput);
        $moduleOutput = preg_replace("#< *script(?:(?!< */ *script *>).)*< */ *script *>#Uis", '', $moduleOutput);

        return $moduleOutput;
    }
}
