<?php

namespace AcyMailing\Classes;

use AcyMailing\Core\AcymClass;

class MailpoetClass extends AcymClass
{
    public function getAllLists()
    {
        $query = 'SELECT id, name FROM #__mailpoet_segments WHERE deleted_at IS NULL';

        return acym_loadObjectList($query);
    }
}
