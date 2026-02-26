<?php

function acym_getEmailRegex(bool $secureJS = false, bool $forceRegex = false): string
{
    $config = acym_config();
    if ($forceRegex || $config->get('special_chars', 0) != 1) {
        $regex = '[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*\@([a-z0-9-]+\.)+[a-z0-9]{2,20}';
    } else {
        $regex = '.+\@(.+\.)+.{2,20}';
    }

    if ($secureJS) {
        $regex = str_replace(['"', "'"], ['\"', "\'"], $regex);
    }

    return $regex;
}

function acym_isValidEmail($email, bool $extended = false): bool
{
    if (empty($email) || !is_string($email)) {
        return false;
    }

    if (!preg_match('/^'.acym_getEmailRegex().'$/i', $email)) {
        return false;
    }

    if (!$extended) {
        return true;
    }

    //Now we do an extended verification...

    $config = acym_config();

    if ($config->get('email_checkdomain', false) && function_exists('getmxrr')) {
        $domain = substr($email, strrpos($email, '@') + 1);
        $mxhosts = [];
        //Check if the domain exists
        $checkDomain = getmxrr($domain, $mxhosts);
        //Sometimes the returned host is checkyouremailaddress-hostnamedoesnotexist262392208.com ... not sure why!
        //But we remove it if it's the case...
        if (!empty($mxhosts) && strpos($mxhosts[0], 'hostnamedoesnotexist')) {
            array_shift($mxhosts);
        }
        if (!$checkDomain || empty($mxhosts)) {
            //Lets check with another function in case of...
            $dns = @dns_get_record($domain, DNS_A);
            $domainChanged = true;
            foreach ($dns as $oneRes) {
                if (strtolower($oneRes['host']) == strtolower($domain)) {
                    $domainChanged = false;
                }
            }
            if (empty($dns) || $domainChanged) {
                return false;
            }
        }
    }

    $object = new stdClass();
    $object->IP = acym_getIP();
    $object->emailAddress = $email;

    // Check IP to limit subscription to max 3 per 2 hours
    if ($config->get('email_iptimecheck', 0)) {
        $lapseTime = time() - 7200;
        $nbUsers = acym_loadResult('SELECT COUNT(*) FROM #__acym_user WHERE creation_date > '.intval($lapseTime).' AND ip = '.acym_escapeDB($object->IP));
        if ($nbUsers >= 3) {
            return false;
        }
    }

    return true;
}

function acym_isPunycode(string $email): bool
{
    return strpos($email, '@xn--') !== false;
}

function acym_getDomain(string $email): string
{
    $aPos = strrpos($email, '@');
    if (empty($aPos)) {
        return '';
    }

    return substr($email, $aPos + 1);
}
