<?php

global $acymCmsUserVars;
$acymCmsUserVars = new stdClass();
$acymCmsUserVars->table = '#__users';
$acymCmsUserVars->name = 'name';
$acymCmsUserVars->username = 'username';
$acymCmsUserVars->id = 'id';
$acymCmsUserVars->email = 'email';
$acymCmsUserVars->registered = 'registerDate';
$acymCmsUserVars->blocked = 'block';

/**
 * @param null $userid
 * @param null $recursive
 * @param bool $names Return an array of ids or names
 *
 * @return array
 */
function acym_getGroupsByUser($userid = null, $recursive = null, $names = false)
{
    if ($userid === null) {
        $userid = acym_currentUserId();
        $recursive = true;
    }

    jimport('joomla.access.access');

    $groups = JAccess::getGroupsByUser($userid, $recursive);

    if ($names) {
        acym_arrayToInteger($groups);
        $groups = acym_loadResultArray(
            'SELECT ugroup.title 
            FROM #__usergroups AS ugroup 
            JOIN #__user_usergroup_map AS map ON ugroup.id = map.group_id 
            WHERE map.user_id = '.intval($userid).' AND ugroup.id IN ('.implode(',', $groups).')'
        );
    }

    return $groups;
}

function acym_getGroups()
{
    return acym_loadObjectList(
        'SELECT `groups`.*, `groups`.title AS text, `groups`.id AS `value` 
        FROM #__usergroups AS `groups`',
        'id'
    );
}

function acym_punycode($email, $method = 'emailToPunycode')
{
    if (empty($email) || acym_isPunycode($email) || version_compare(ACYM_CMSV, '3.1.2', '<')) {
        return $email;
    }
    $email = JStringPunycode::$method($email);

    return $email;
}

function acym_currentUserId()
{
    $acymy = JFactory::getUser();

    return $acymy->id;
}

function acym_currentUserName($userid = null)
{
    if (!empty($userid)) {
        $special = JFactory::getUser($userid);

        return $special->name;
    }

    $acymy = JFactory::getUser();

    return $acymy->name;
}

function acym_currentUserEmail($userid = null)
{
    if (!empty($userid)) {
        $special = JFactory::getUser($userid);

        return $special->email;
    }

    $acymy = JFactory::getUser();

    return $acymy->email;
}

function acym_replaceGroupTags($uploadFolder)
{
    if (strpos($uploadFolder, '{groupname}') === false) return $uploadFolder;

    // Get user groups
    $groups = acym_getGroupsByUser(acym_currentUserId(), false);
    acym_arrayToInteger($groups);

    // Get group name
    $group = acym_loadResult('SELECT title FROM #__usergroups WHERE id = '.intval(max($groups)));

    $uploadFolder = str_replace(
        '{groupname}',
        strtolower(
            str_replace(
                '-',
                '_',
                acym_getAlias($group)
            )
        ),
        $uploadFolder
    );

    return $uploadFolder;
}

function acym_getCmsUserEdit($userId)
{
    return 'index.php?option=com_users&task=user.edit&id='.intval($userId);
}
