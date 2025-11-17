<?php

function acym_getCmsUserIdByEmail(string $email): int
{
    global $acymCmsUserVars;

    $userId = acym_loadResult(
        'SELECT '.$acymCmsUserVars->id.' 
        FROM '.$acymCmsUserVars->table.' 
        WHERE '.$acymCmsUserVars->email.' = '.acym_escapeDB($email)
    );

    return empty($userId) ? 0 : $userId;
}
