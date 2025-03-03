<?php

namespace AcyMailing\Controllers\Configuration;

trait Language
{
    public function multilingual(): void
    {
        $remindme = json_decode($this->config->get('remindme', '[]'), true);
        $remindme[] = 'multilingual';
        $this->config->save(['remindme' => json_encode($remindme)]);

        $this->listing();
    }
}
