<?php

use AcyMailing\Core\AcymPunycode;

global $acymCmsUserVars;
$acymCmsUserVars = new stdClass();
$acymCmsUserVars->table = '#__users';
$acymCmsUserVars->name = 'display_name';
$acymCmsUserVars->username = 'user_login';
$acymCmsUserVars->id = 'ID';
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

    if (is_multisite() && is_super_admin()) {
        return ['administrator'];
    }

    return $user->roles;
}

function acym_getGroups()
{
    $roles = wp_roles();
    if (empty($roles->roles)) {
        $groups = acym_loadResult('SELECT option_value FROM #__options WHERE option_name = "#__user_roles"');
        if (!empty($groups)) {
            $groups = unserialize($groups);
        } else {
            $groups = [];
        }
    } else {
        $groups = $roles->roles;
    }

    foreach ($groups as $key => $group) {
        $newGroup = new stdClass();
        $newGroup->id = $key;
        $newGroup->value = $key;
        $newGroup->parent_id = 0;
        $newGroup->text = translate_user_role($group['name']);
        $groups[$key] = $newGroup;
    }

    return $groups;
}

function acym_punycode($email, $method = 'emailToPunycode')
{
    if (empty($email) || acym_isPunycode($email)) {
        return $email;
    }

    $explodedAddress = explode('@', $email);
    $newEmail = $explodedAddress[0];

    if (!empty($explodedAddress[1])) {
        $domainExploded = explode('.', $explodedAddress[1]);
        $newdomain = '';
        $puc = new AcymPunycode();

        foreach ($domainExploded as $domainex) {
            $domainex = $puc->$method($domainex);
            $newdomain .= $domainex.'.';
        }

        $newdomain = substr($newdomain, 0, -1);
        $newEmail = $newEmail.'@'.$newdomain;
    }

    return $newEmail;
}

function acym_currentUserId(): int
{
    return intval(get_current_user_id());
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
