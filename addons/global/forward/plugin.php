<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'ForwardMailboxAction.php';

class plgAcymForward extends AcymPlugin
{
    use ForwardMailboxAction;
}
