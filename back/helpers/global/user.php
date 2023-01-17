<?php

function acym_getCmsUserIdByEmail($email)
{
    global $acymCmsUserVars;

    return acym_loadResult('SELECT '.$acymCmsUserVars->id.' FROM '.$acymCmsUserVars->table.' WHERE '.$acymCmsUserVars->email.' = '.acym_escapeDB($email));
}
