<?php

class acympluginClass extends acymClass
{
    var $table = 'plugin';
    var $pkey = 'id';

    public function getNotUptoDatePlugins()
    {
        $testPluginTable = 'SHOW TABLES LIKE "%_acym_plugin"';
        $result = acym_loadResult($testPluginTable);
        if (empty($result)) return 0;

        $query = 'SELECT count(id) FROM #__acym_plugin WHERE uptodate = 0';

        return acym_loadResult($query);
    }

    public function getOneByFolderName($folderName)
    {
        return acym_loadObject('SELECT * FROM #__acym_plugin WHERE folder_name = '.acym_escapeDB($folderName));
    }

    public function getSettings($addon)
    {
        $settings = acym_loadResult('SELECT settings FROM #__acym_plugin WHERE folder_name = '.acym_escapeDB($addon));

        return empty($settings) ? [] : json_decode($settings, true);
    }
}
