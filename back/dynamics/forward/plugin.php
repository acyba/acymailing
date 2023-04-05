<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'ForwardMailboxAction.php';

class plgAcymForward extends acymPlugin
{
    use ForwardMailboxAction;
}
