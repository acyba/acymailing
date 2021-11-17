<?php


namespace AcyMailing\Init;

class acySecurity
{
    public function __construct()
    {
        add_filter('allowed_redirect_hosts', [$this, 'extendsAllowedHostsList']);
    }

    function extendsAllowedHostsList($hosts)
    {
        $config = acym_config();
        $allowedHosts = $config->get('allowed_hosts', []);
        if (!is_array($allowedHosts)) $allowedHosts = explode(',', $allowedHosts);

        if (empty($allowedHosts)) return $hosts;

        foreach ($allowedHosts as $host) {
            $hosts[] = $host;
        }

        return $hosts;
    }

}

$acySecurity = new acySecurity();
