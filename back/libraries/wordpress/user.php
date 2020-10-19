<?php

use AcyMailing\Libraries\acympunycode;

global $acymCmsUserVars;
$acymCmsUserVars = new stdClass();
$acymCmsUserVars->table = '#__users';
$acymCmsUserVars->name = 'display_name';
$acymCmsUserVars->username = 'user_login';
$acymCmsUserVars->id = 'id';
$acymCmsUserVars->email = 'user_email';
$acymCmsUserVars->registered = 'user_registered';
$acymCmsUserVars->blocked = 'user_status';

function acym_getGroupsByUser($userid = null, $recursive = null, $names = false)
{
    if ($userid === null) {
        $user = wp_get_current_user();
    } else {
        $user = new WP_User($userid);
    }

    return $user->roles;
}

function acym_getGroups()
{
    $groups = acym_loadResult('SELECT option_value FROM #__options WHERE option_name = "#__user_roles"');
    $groups = unserialize($groups);

    $usersPerGroup = acym_loadObjectList('SELECT meta_value, COUNT(meta_value) AS nbusers FROM #__usermeta WHERE meta_key = "#__capabilities" GROUP BY meta_value');

    $nbUsers = [];
    foreach ($usersPerGroup as $oneGroup) {
        $oneGroup->meta_value = unserialize($oneGroup->meta_value);
        $nbUsers[key($oneGroup->meta_value)] = $oneGroup->nbusers;
    }

    foreach ($groups as $key => $group) {
        $newGroup = new stdClass();
        $newGroup->id = $key;
        $newGroup->value = $key;
        $newGroup->parent_id = 0;
        $newGroup->text = $group['name'];
        $newGroup->nbusers = empty($nbUsers[$key]) ? 0 : $nbUsers[$key];
        $groups[$key] = $newGroup;
    }

    return $groups;
}

function acym_punycode($email, $method = 'emailToPunycode')
{
    if (empty($email)) {
        return $email;
    }

    $explodedAddress = explode('@', $email);
    $newEmail = $explodedAddress[0];

    if (!empty($explodedAddress[1])) {
        $domainExploded = explode('.', $explodedAddress[1]);
        $newdomain = '';
        $puc = new acympunycode();

        foreach ($domainExploded as $domainex) {
            $domainex = $puc->$method($domainex);
            $newdomain .= $domainex.'.';
        }

        $newdomain = substr($newdomain, 0, -1);
        $newEmail = $newEmail.'@'.$newdomain;
    }

    return $newEmail;
}

function acym_currentUserId()
{
    return get_current_user_id();
}

function acym_currentUserName($userid = null)
{
    if (!empty($userid)) {
        $special = get_user_by('id', $userid);

        return $special->display_name;
    }

    $current_user = wp_get_current_user();

    return $current_user->display_name;
}

function acym_currentUserEmail($userid = null)
{
    if (!empty($userid)) {
        $special = get_user_by('id', $userid);

        return $special->user_email;
    }

    $current_user = wp_get_current_user();

    return $current_user->user_email;
}

function acym_replaceGroupTags($uploadFolder)
{
    if (strpos($uploadFolder, '{groupname}') === false) return $uploadFolder;

    // Get user groups
    $groups = acym_getGroupsByUser(acym_currentUserId());
    $group = array_shift($groups);

    $uploadFolder = str_replace(
        '{groupname}',
        strtolower(str_replace(' ', '_', $group)),
        $uploadFolder
    );

    return $uploadFolder;
}

function acym_getCmsUserEdit($userId)
{
    return 'user-edit.php?user_id='.intval($userId);
}
