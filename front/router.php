<?php

include_once __DIR__.DIRECTORY_SEPARATOR.'router'.DIRECTORY_SEPARATOR.'router.php';

// We keep these old SEF functions as they may be used by SEF extensions
function AcymBuildRoute(&$query)
{
    $router = new AcymRouter();

    return $router->build($query);
}

function AcymParseRoute($segments)
{
    $router = new AcymRouter();

    return $router->parse($segments);
}
