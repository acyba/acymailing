<?php

namespace AcyMailing\Classes;

use AcyMailing\Libraries\acymClass;

class MailpoetClass extends acymClass
{
    public function getAllLists()
    {
        $query = 'SELECT id, name FROM #__mailpoet_segments WHERE deleted_at IS NULL';

        return acym_loadObjectList($query);
    }
}
