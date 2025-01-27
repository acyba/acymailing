<?php

use AcyMailing\Controllers\CampaignsController;
use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\FieldClass;

trait BirthdayCampaignType
{
    private $mailType = 'birthday';

    public function getNewEmailsTypeBlock(&$extraBlocks)
    {
        if (acym_isAdmin()) {
            $favoriteTemplate = $this->config->get('favorite_template', 0);
            if (empty($favoriteTemplate)) {
                $birthdayMailLink = acym_completeLink('campaigns&task=edit&step=chooseTemplate&campaign_type='.$this->mailType);
            } else {
                $birthdayMailLink = acym_completeLink('campaigns&task=edit&step=editEmail&from='.$favoriteTemplate.'&campaign_type='.$this->mailType);
            }
        } else {
            $birthdayMailLink = acym_frontendLink('frontcampaigns&task=edit&step=chooseTemplate&campaign_type='.$this->mailType);
        }

        $extraBlocks[] = [
            'name' => acym_translation('ACYM_BIRTHDAY'),
            'description' => acym_translation('ACYM_BIRTHDAY_MAIL_DESC'),
            'icon' => 'acymicon-calendar',
            'link' => $birthdayMailLink,
            'level' => ACYM_ENTERPRISE,
            'email_type' => $this->mailType,
        ];
    }

    public function getCampaignTypes(&$types)
    {
        $types[$this->mailType] = $this->mailType;
    }

    public function getCampaignSpecificSendSettings($type, $sendingParams, &$specificSettings)
    {
        if ($type != $this->mailType) return;

        $defaultNumber = 1;
        if (!empty($sendingParams) && isset($sendingParams[$this->mailType.'_number'])) {
            $defaultNumber = $sendingParams[$this->mailType.'_number'];
        }
        $inputTime = '<input type="number" min="0" stp="1" name="acym_birthday_time_number" class="intext_input" value="'.$defaultNumber.'">';

        $timeSelectOptions = [
            'days' => acym_translation('ACYM_DAYS'),
            'weeks' => acym_translation('ACYM_WEEKS'),
            'months' => acym_translation('ACYM_MONTHS'),
        ];

        $selectedtType = 'days';
        if (!empty($sendingParams) && isset($sendingParams[$this->mailType.'_type'])) {
            $selectedtType = $sendingParams[$this->mailType.'_type'];
        }
        $timeSelect = '<div class="cell medium-2 margin-left-1 margin-right-1">';
        $timeSelect .= acym_select(
            $timeSelectOptions,
            'acym_birthday_time_frame',
            $selectedtType,
            ['class' => 'acym__select']
        );
        $timeSelect .= '</div>';

        $timeRelativeOptions = [
            'before' => acym_translation('ACYM_BEFORE'),
            'after' => acym_translation('ACYM_AFTER'),
        ];

        $selectedRelative = 'before';
        if (!empty($sendingParams) && isset($sendingParams[$this->mailType.'_relative'])) {
            $selectedRelative = $sendingParams[$this->mailType.'_relative'];
        }
        $inputRelative = '<div class="cell medium-2 margin-left-1 margin-right-1">';
        $inputRelative .= acym_select(
            $timeRelativeOptions,
            'acym_birthday_relative',
            $selectedRelative,
            ['class' => 'acym__select']
        );
        $inputRelative .= '</div>';

        $whenSettings = '<div class="cell grid-x acym_vcenter">';
        $whenSettings .= acym_translationSprintf('ACYM_SEND_IT_BEFORE_USER_BIRTHDAY', $inputTime, $timeSelect, $inputRelative);
        $whenSettings .= '</div>';

        // Birthday field choice
        $fieldClass = new FieldClass();
        $dateFields = $fieldClass->getFieldsByType(['date']);

        $pluginFields = [];
        foreach ($dateFields as $oneField) {
            $pluginFields[$oneField->id] = acym_translation($oneField->name);
        }

        $availablePlugins = [
            get_class($this) => 'AcyMailing',
        ];
        acym_trigger('onAcymGetPluginField', [&$availablePlugins]);

        $onlyAcymailing = count($availablePlugins) === 1;
        $selectedPlugin = '';
        $availableFields = [
            '' => acym_translation('ACYM_SELECT_FIELD'),
        ];
        if (!empty($sendingParams) && !empty($sendingParams[$this->mailType.'_plugin'])) {
            $selectedPlugin = $sendingParams[$this->mailType.'_plugin'];
        }

        if (!empty($selectedPlugin)) {
            $installed = false;
            acym_trigger('onAcymCheckInstalled', [&$installed], $selectedPlugin);

            if (!$installed) {
                acym_trigger('getBirthdayField', [&$availableFields], get_class($this));
                acym_enqueueMessage(
                    acym_translation('ACYM_WARNING_CAMPAIGN_BASED_ON_ANOTHER_PLUGIN'),
                    'error'
                );
            } else {
                acym_trigger('getBirthdayField', [&$availableFields], $selectedPlugin);
            }
        } elseif ($onlyAcymailing) {
            acym_trigger('getBirthdayField', [&$availableFields], get_class($this));
        }

        if (count($availableFields) == 1) {
            $availableFields = ['' => acym_translation('ACYM_NO_FIELD_AVAILABLE')];
        }

        $inputField = '<div class="cell medium-2 margin-left-1 margin-right-1" >';
        $inputField .= acym_select(
            $availablePlugins,
            'acym_plugin_field',
            !empty($selectedPlugin) ? $selectedPlugin : '',
            ['class' => 'acym__select']
        );
        $inputField .= '</div><div class="cell medium-8"></div>';

        $additionalSettings = '<div class="cell grid-x acym_vcenter margin-left-3 margin-bottom-1" '.($onlyAcymailing ? 'style="display:none"' : '').'>';
        $additionalSettings .= acym_translationSprintf('ACYM_CHOOSE_PLUGIN_TO_SELECT_BIRTHDAY_FIELD', $inputField);
        $additionalSettings .= '</div>';


        $selectedField = '';
        if (!empty($sendingParams) && isset($sendingParams[$this->mailType.'_field'])) {
            $selectedField = $sendingParams[$this->mailType.'_field'];
        }

        $inputField = '<div class="cell medium-2 margin-left-1 margin-right-1">';
        $inputField .= acym_select(
            $availableFields,
            'acym_birthday_field',
            $selectedField,
            ['class' => 'acym__select']
        );
        $inputField .= '</div><div class="cell medium-8"></div>';

        $additionalSettings .= '<div class="cell grid-x acym_vcenter margin-left-3 margin-bottom-1" id="acym_div_date_field"'.(empty($selectedPlugin) && !$onlyAcymailing
                ? 'style="display:none"' : '').'>';
        $additionalSettings .= acym_translationSprintf('ACYM_BIRTHDAY_FIELD_PLUGIN', $inputField);
        $additionalSettings .= '</div>';

        $specificSettings[] = [
            'whenSettings' => $whenSettings,
            'additionnalSettings' => $additionalSettings,
        ];
    }

    public function saveCampaignSpecificSendSettings($type, &$specialSendings)
    {
        if ($type != $this->mailType) return;

        $inputTime = acym_getVar('int', 'acym_birthday_time_number', 0);
        $typeTime = acym_getVar('string', 'acym_birthday_time_frame', 'day');
        $relative = acym_getVar('string', 'acym_birthday_relative', 'before');
        $plugin = acym_getVar('string', 'acym_plugin_field', 0);
        $field = acym_getVar('string', 'acym_birthday_field', 0);

        $specialSendings[] = [
            $this->mailType.'_number' => $inputTime,
            $this->mailType.'_type' => $typeTime,
            $this->mailType.'_relative' => $relative,
            $this->mailType.'_plugin' => $plugin,
            $this->mailType.'_field' => $field,
        ];
    }

    public function onAcymSendCampaignSpecial($campaign, &$filters, &$pluginIsExisting)
    {
        if ($campaign->sending_type !== $this->mailType) {
            return;
        }

        $sendingTime = $campaign->sending_params[$this->mailType.'_number'] ?? 1;
        $sendingTime = intval($sendingTime);

        if (empty($campaign->sending_params[$this->mailType.'_type'])) {
            $campaign->sending_params[$this->mailType.'_type'] = 'days';
        }

        if ($campaign->sending_params[$this->mailType.'_type'] === 'weeks') {
            $sendingTime *= 7;
        } elseif ($campaign->sending_params[$this->mailType.'_type'] === 'months') {
            $sendingTime *= 30;
        }
        $filter = [
            'birthday' => [
                'days' => $sendingTime,
                'field' => $campaign->sending_params[$this->mailType.'_field'] ?? '',
                'relative' => $campaign->sending_params[$this->mailType.'_relative'] ?? 'before',
                'plugin' => $campaign->sending_params[$this->mailType.'_plugin'] ?? get_class($this),
            ],
        ];

        $installed = false;
        acym_trigger('onAcymCheckInstalled', [&$installed], $filter['birthday']['plugin']);

        if (!$installed) {
            $pluginIsExisting = false;

            return;
        }
        $filters[] = $filter;
    }

    public function specialActionOnDelete($typeElement, $elements)
    {
        if ($typeElement != 'field') return;
        $campaignClass = new CampaignClass();
        $fieldClass = new FieldClass();
        $birthdayMails = $campaignClass->getCampaignsByTypes([$this->mailType]);
        foreach ($elements as $oneElement) {
            if ($fieldClass->getFieldTypeById($oneElement) !== 'date') continue;
            foreach ($birthdayMails as $oneBirthdayMail) {
                if ($oneBirthdayMail->sending_params['birthday_field'] != $oneElement) continue;
                $oneBirthdayMail->sending_params['birthday_field'] = '';
                $oneBirthdayMail->draft = 1;
                $campaignClass->save($oneBirthdayMail);
            }
        }
    }

    public function onAcymDisplayCampaignListingSpecificTabs(&$tabs)
    {
        if (acym_level(ACYM_ENTERPRISE)) {
            $tabs['specificListing&type='.$this->mailType] = 'ACYM_BIRTHDAY_EMAIL';
        }
    }

    public function onAcymSpecificListingActive(&$exists, $task)
    {
        if ($task == $this->mailType) {
            $exists = true;
        }
    }

    public function onAcymCampaignDataSpecificListing(&$data, $type)
    {
        if ($type == $this->mailType) {
            $data['typeWorkflowTab'] = 'specificListing&type='.$this->mailType;
            $data['element_to_display'] = acym_translation('ACYM_BIRTHDAY_EMAIL');
            $campaignController = new CampaignsController();
            $campaignController->prepareEmailsListing($data, $type);
        }
    }

    public function onAcymCampaignAddFiltersSpecificListing(&$filters, $type)
    {
        if ($type === $this->mailType) {
            $filters[] = 'campaign.sending_type = '.acym_escapeDB($this->mailType);
        }
    }

    public function filterSpecificMailsToSend(&$specialMails, $time)
    {
        $this->filterSpecialMailsDailySend($specialMails, $time, $this->mailType);
    }
}
