<?php

use AcyMailing\Controllers\CampaignsController;

trait WooCommerceCampaignType
{
    private $mailType = 'woocommerce_cart';

    public function getNewEmailsTypeBlock(&$extraBlocks)
    {
        if (acym_isAdmin()) {
            $favoriteTemplate = $this->config->get('favorite_template', 0);
            if (empty($favoriteTemplate)) {
                $woocomerceMailLink = acym_completeLink('campaigns&task=edit&step=chooseTemplate&campaign_type='.$this->mailType);
            } else {
                $woocomerceMailLink = acym_completeLink('campaigns&task=edit&step=editEmail&from='.$favoriteTemplate.'&campaign_type='.$this->mailType);
            }
        } else {
            $woocomerceMailLink = acym_frontendLink('frontcampaigns&task=edit&step=chooseTemplate&campaign_type='.$this->mailType);
        }

        $extraBlocks[] = [
            'name' => $this->pluginDescription->name,
            'description' => acym_translation('ACYM_WOOCOMMERCE_EMAIL_DESC'),
            'icon' => 'acymicon-cart-arrow-down',
            'link' => $woocomerceMailLink,
            'level' => 1,
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

        $timeSelectOptions = [
            'hours' => acym_translation('ACYM_HOURS'),
            'days' => acym_translation('ACYM_DAYS'),
            'weeks' => acym_translation('ACYM_WEEKS'),
            'months' => acym_translation('ACYM_MONTHS'),
        ];

        $selectedType = 'days';
        if (!empty($sendingParams) && isset($sendingParams[$this->mailType.'_type'])) {
            $selectedType = $sendingParams[$this->mailType.'_type'];
        }
        $timeSelect = '<div class="cell medium-2 margin-left-1 margin-right-1">';
        $timeSelect .= acym_select(
            $timeSelectOptions,
            'acym_woocomerce_time_frame',
            $selectedType,
            ['class' => 'acym__select']
        );
        $timeSelect .= '</div>';

        $defaultNumber = 1;
        if (!empty($sendingParams) && isset($sendingParams[$this->mailType.'_number'])) {
            $defaultNumber = $sendingParams[$this->mailType.'_number'];
        }
        $inputTime = '<input type="number" min="0" stp="1" name="acym_woocomerce_time_number" class="intext_input" value="'.intval($defaultNumber).'">';

        $orderStatuses = $this->getOrderStatuses();
        $selectedStatus = 'wc-pending';
        if (!empty($sendingParams) && isset($sendingParams[$this->mailType.'_status'])) {
            $selectedStatus = $sendingParams[$this->mailType.'_status'];
        }
        $inputStatus = '<div class="cell medium-2 margin-left-1 margin-right-1">';
        $inputStatus .= acym_select(
            $orderStatuses,
            'acym_woocomerce_status',
            $selectedStatus,
            ['class' => 'acym__select']
        );
        $inputStatus .= '</div>';

        $whenSettings = '<div class="cell grid-x acym_vcenter">';
        $whenSettings .= acym_translationSprintf('ACYM_SEND_ORDER_PLACED_STATUS_CURRENTLY', $inputTime, $timeSelect, $inputStatus);
        $whenSettings .= '</div>';

        $specificSettings[] = [
            'whenSettings' => $whenSettings,
            'additionnalSettings' => '',
        ];
    }

    public function saveCampaignSpecificSendSettings($type, &$specialSendings)
    {
        if ($type != $this->mailType) return;

        $inputTime = acym_getVar('int', 'acym_woocomerce_time_number', 0);
        $typeTime = acym_getVar('string', 'acym_woocomerce_time_frame', 'day');
        $status = acym_getVar('string', 'acym_woocomerce_status', '0');

        $specialSendings[] = [
            $this->mailType.'_number' => $inputTime,
            $this->mailType.'_type' => $typeTime,
            $this->mailType.'_status' => $status,
        ];
    }

    public function onAcymSendCampaignSpecial($campaign, &$filters, &$pluginIsExisting)
    {
        if ($campaign->sending_type != $this->mailType) return;

        $sendingTime = (int)$campaign->sending_params[$this->mailType.'_number'];
        if ($campaign->sending_params[$this->mailType.'_type'] == 'weeks') {
            $sendingTime *= 7;
        } elseif ($campaign->sending_params[$this->mailType.'_type'] == 'months') {
            $sendingTime *= 30;
        } elseif ($campaign->sending_params[$this->mailType.'_type'] == 'hours') {
            $sendingTime /= 24;
        }
        $filter = [
            'wooreminder' => [
                'days' => $sendingTime,
                'status' => $campaign->sending_params[$this->mailType.'_status'],
                'payment' => 'any',
            ],
        ];

        if (!$this->installed) {
            $pluginIsExisting = false;

            return;
        }

        $filters[] = $filter;
    }

    public function onAcymDisplayCampaignListingSpecificTabs(&$tabs)
    {
        $tabs['specificListing&type='.$this->mailType] = 'ACYM_WOOCOMMERCE_ABANDONED_CART';
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
            $data['element_to_display'] = acym_translation('ACYM_WOOCOMMERCE_ABANDONED_CART_CAMPAIGN');
            $data['type'] = $this->mailType;
            $campaignController = new CampaignsController();
            $campaignController->prepareEmailsListing($data, $type);
        }
    }

    public function onAcymCampaignAddFiltersSpecificListing(&$filters, $type)
    {
        if ($type == $this->mailType) {
            $filters[] = 'campaign.sending_type = '.acym_escapeDB($this->mailType);
        }
    }

    public function filterSpecificMailsToSend(&$specialMails, $time)
    {
        $this->filterSpecialMailsDailySend($specialMails, $time, $this->mailType);
    }
}
