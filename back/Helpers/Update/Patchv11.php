<?php

namespace AcyMailing\Helpers\Update;

trait Patchv11
{
    private function updateFor1102(): void
    {
        if ($this->isPreviousVersionAtLeast('11.0.2')) {
            return;
        }

        $this->updateQuery('UPDATE #__acym_user_has_list SET `status` = 0 WHERE `status` = -1');
    }
}
