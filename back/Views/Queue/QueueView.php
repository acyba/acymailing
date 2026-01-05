<?php

namespace AcyMailing\Views\Queue;

use AcyMailing\Core\AcymView;

/**
 * Class QueueViewQueue
 */
class QueueView extends AcymView
{
    public function __construct()
    {
        parent::__construct();

        $this->steps = [
            'campaigns' => 'ACYM_MAILS',
        ];

        //__START__essential_
        if (acym_level(ACYM_ESSENTIAL)) {
            $this->steps['scheduled'] = 'ACYM_SCHEDULED';
        }
        //__END__essential_

        $this->steps['detailed'] = 'ACYM_QUEUE_DETAILED';
    }
}
