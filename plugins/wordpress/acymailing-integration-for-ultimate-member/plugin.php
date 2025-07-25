<?php

use AcyMailing\Core\AcymPlugin;

class plgAcymUltimatemember extends AcymPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('ultimate-member/ultimate-member.php');

        $this->pluginDescription->name = 'Ultimate Member';
        $this->pluginDescription->category = 'Subscription system';
        $this->pluginDescription->description = '- Insert AcyMailing list on your Ultimate Member register form';
    }

    public function onRegacyUseExternalPlugins()
    {
        if (!is_plugin_active('ultimate-member/ultimate-member.php')) return;

        ?>
		<div class="cell grid-x grid-margin-x">
            <?php
            echo wp_kses(
                acym_switch(
                    'config[regacy_use_ultimate_member]',
                    $this->config->get('regacy_use_ultimate_member', 0),
                    acym_translation('ACYM_DISPLAY_FORM_ON_ULTIMATE_MEMBER'),
                    [],
                    'xlarge-3 medium-5 small-9'
                ),
                [
                    'div' => ['class' => [], 'data-toggle-showmore' => []],
                    'label' => ['for' => [], 'class' => [], 'data-acym-tooltip' => []],
                    'i' => ['class' => []],
                    'input' => [
                        'type' => [],
                        'name' => [],
                        'id' => [],
                        'value' => [],
                        'checked' => [],
                        'disabled' => [],
                        'class' => [],
                        'data-switch' => [],
                        'data-toggle-switch' => [],
                        'data-toggle-switch-open' => [],
                        'v-model' => [],
                    ],
                    'span' => ['class' => [], 'aria-hidden' => []],
                ]
            );
            ?>
		</div>
        <?php
    }

    public function onAcymGetPluginField(&$availablePlugins)
    {
        $availablePlugins[get_class($this)] = 'Ultimate Member';
    }

    public function getBirthdayField(&$availableFields)
    {
        $query = 'SELECT * FROM #__postmeta WHERE `meta_key` = "_um_custom_fields"';
        $customFields = acym_loadObjectList($query);
        foreach ($customFields as $customField) {
            foreach (unserialize($customField->meta_value) as $unserializedField) {
                if ($unserializedField['type'] === 'date') {
                    // pb si plusieurs champs de type birthday (car ultimate member force la meta_key "birth_date"
                    // pour les champs de type birthday donc ca s'écrase)
                    $availableFields[$unserializedField['metakey']] = $unserializedField['title'];
                }
            }
        }
    }

    public function getJsonBirthdayField()
    {
        $availableFields = [];
        $this->getBirthdayField($availableFields);
        echo json_encode(['fields' => $availableFields]);
        exit;
    }

    public function onAcymProcessFilter_birthday(&$query, $options, $num = null)
    {
        if ($options['plugin'] !== get_class($this)) return;

        $dateToCheck = $this->processDateToCheck($options);

        $query->join['um_fields'.$num] = '#__usermeta AS um'.$num.' ON um'.$num.'.user_id = user.cms_id';
        $query->where[] = 'um'.$num.'.meta_key = '.acym_escapeDB($options['field']);
        $query->where[] = 'user.cms_id != 0 ';
        $query->where[] = 'um'.$num.'.meta_value LIKE '.acym_escapeDB('%'.date_format($dateToCheck, '/m/d'));
    }

}
