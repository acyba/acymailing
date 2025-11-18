<?php

namespace AcyMailing\Helpers\Update;

use AcyMailing\Controllers\ConfigurationController;

trait SQLPatch
{
    public function updateSQL(): void
    {
        if (!$this->isUpdating) {
            return;
        }

        $config = acym_config();

        $this->updateFor603();
        $this->updateFor610();
        $this->updateFor612();
        $this->updateFor613();
        $this->updateFor614();
        $this->updateFor615($config);
        $this->updateFor616();
        $this->updateFor617();
        $this->updateFor622($config);
        $this->updateFor640();
        $this->updateFor650();
        $this->updateFor660();
        $this->updateFor670();
        $this->updateFor692();
        $this->updateFor6100();
        $this->updateFor6102();
        $this->updateFor6104();
        $this->updateFor6110();
        $this->updateFor6120();
        $this->updateFor6130();
        $this->updateFor6140();
        $this->updateFor6150();
        $this->updateFor6160();
        $this->updateFor6170();
        $this->updateFor6180();
        $this->updateFor6181();
        $this->updateFor6190();

        $this->updateFor700($config);
        $this->updateFor710();
        $this->updateFor720($config);
        $this->updateFor721();
        $this->updateFor740($config);
        $this->updateFor750($config);
        $this->updateFor755();
        $this->updateFor759();
        $this->updateFor7510();
        $this->updateFor760();
        $this->updateFor761();
        $this->updateFor762();
        $this->updateFor776();
        $this->updateFor781();
        $this->updateFor792();
        $this->updateFor793();
        $this->updateFor794();
        $this->updateFor796();

        $this->updateFor800();
        $this->updateFor810();
        $this->updateFor811();
        $this->updateFor850();
        $this->updateFor860();
        $this->updateFor862();
        $this->updateFor870();
        $this->updateFor872($config);
        $this->updateFor873($config);
        $this->updateFor874($config);
        $this->updateFor881($config);

        $this->updateFor920($config);
        $this->updateFor930();
        $this->updateFor931();
        $this->updateFor940($config);
        $this->updateFor961();
        $this->updateFor970();
        $this->updateFor980();
        $this->updateFor990();
        $this->updateFor9101();
        $this->updateFor9102();
        $this->updateFor9110();

        $this->updateFor1000();
        $this->updateFor1020($config);
        $this->updateFor1050();
        $this->updateFor1060();
        $this->updateFor1062();
    }

    public function checkDB(): void
    {
        $configController = new ConfigurationController();
        $messages = $configController->checkDB(false);

        if (empty($messages)) {
            return;
        }

        $isError = false;
        $textMsgs = [];
        foreach ($messages as $oneMsg) {
            if ($oneMsg['error']) {
                $isError = true;
            }
            $textMsgs[] = $oneMsg['msg'];
        }

        if ($isError && !empty($textMsgs)) {
            acym_enqueueMessage(implode('<br />', $textMsgs), 'warning');
        }
    }

    private function isPreviousVersionAtLeast(string $versionNumber): bool
    {
        return version_compare($this->previousVersion, $versionNumber, '>=');
    }
}
