<?php

namespace AcyMailing\Views;

use AcyMailing\Libraries\acymView;

/**
 * Class QueueViewQueue
 */
class QueueViewQueue extends acymView
{
    public function __construct()
    {
        parent::__construct();

        $this->steps = [
            'campaigns' => 'ACYM_MAILS',
            //TODO: 'automated' => 'ACYM_QUEUE_AUTOMATED',
            'detailed' => 'ACYM_QUEUE_DETAILED',
        ];
    }
}
