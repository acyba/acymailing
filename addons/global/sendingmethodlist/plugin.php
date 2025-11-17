<?php

use AcyMailing\Core\AcymPlugin;

class plgAcymSendingmethodlist extends AcymPlugin
{
    /**
     * Add a new tab on the configuration for sending method list
     *
     * @param $tabs
     *
     * @return void
     */
    public function onConfigurationAddTabs(&$tabs): void
    {
        $tabs['sending_method_list'] = 'ACYM_SENDING_METHOD_LIST';
    }

    /**
     * Display the tab content for the sending method list
     *
     * @param $data
     *
     * @return void
     */
    public function onConfigurationTab_sending_method_list($data): void
    {
        $sendingMethods = $this->getSendingMethodsConfig();
        $data['isSml'] = true;

        include 'configurationTab.php';
    }

    /**
     * Save the new sending method list
     *
     * @return void
     */
    public function onConfigurationAddSml(): void
    {
        acym_checkToken();

        $formData = acym_getVar('array', 'sml', []);

        if (empty($formData['name'])) {
            return;
        }

        $sendingMethods = $this->getSendingMethodsConfig();

        $id = uniqid();
        if (!empty($formData['id'])) {
            $id = $formData['id'];
        }

        $sendingMethods[$id] = $formData;

        $this->saveSendingMethodsConfig($sendingMethods);
    }

    public function onConfigurationDeleteSml(): void
    {
        acym_checkToken();

        $formData = acym_getVar('array', 'sml', []);

        if (empty($formData['id'])) {
            return;
        }

        $sendingMethods = $this->getSendingMethodsConfig();

        if (empty($sendingMethods[$formData['id']])) {
            return;
        }

        unset($sendingMethods[$formData['id']]);

        $this->saveSendingMethodsConfig($sendingMethods);
    }

    /**
     * @return array
     */
    private function getSendingMethodsConfig(): array
    {
        $sendingMethods = $this->config->get('sending_method_list', '{}');

        return json_decode($sendingMethods, true);
    }

    /**
     * @return array
     */
    private function getSendingMethodByListConfig(): array
    {
        $sendingMethodsByList = $this->config->get('sending_method_list_by_list', '{}');

        return json_decode($sendingMethodsByList, true);
    }

    /**
     * @param $sendingMethods
     *
     * @return void
     */
    private function saveSendingMethodsConfig($sendingMethods): void
    {
        $this->config->saveConfig(['sending_method_list' => json_encode($sendingMethods)]);
    }

    /**
     * @param $sendingMethods
     *
     * @return void
     */
    private function saveSendingMethodByListConfig($sendingMethodByList): void
    {
        $this->config->saveConfig(['sending_method_list_by_list' => json_encode($sendingMethodByList)]);
    }

    /**
     * @param $data
     *
     * @return void
     */
    public function onListSetting($data): void
    {
        $sendingMethods = $this->getSendingMethodsConfig();
        $sendingMethodByList = $this->getSendingMethodByListConfig();

        $sendingMethodsFormatted = [
            '' => acym_translation('ACYM_DEFAULT_SENDING_METHOD'),
        ];
        foreach ($sendingMethods as $id => $sendingMethod) {
            $sendingMethodsFormatted[$id] = $sendingMethod['name'];
        }

        $listId = $data['listInformation']->id;
        $sendingMethodSelected = empty($sendingMethodByList[$listId]) ? '' : $sendingMethodByList[$listId];

        include 'listSetting.php';
    }

    public function onAcymAfterListCreate(&$list): void
    {
        $this->onListSave($list->id);
    }

    public function onAcymAfterListModify(&$list, &$oldList): void
    {
        $this->onListSave($list->id);
    }

    public function onListSave($listId): void
    {
        $sendingMethodId = acym_getVar('string', 'sml_sending_method', '');

        $sendingMethodByList = $this->getSendingMethodByListConfig();

        if (empty($sendingMethodId)) {
            if (!empty($sendingMethodByList[$listId])) {
                unset($sendingMethodByList[$listId]);
            }
        } else {
            $sendingMethodByList[$listId] = $sendingMethodId;
        }

        $this->saveSendingMethodByListConfig($sendingMethodByList);
    }

    public function sendingMethodByListActive(&$isSendingMethodByListActive)
    {
        $isSendingMethodByListActive = true;
    }
}
