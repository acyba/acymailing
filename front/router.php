<?php

include_once __DIR__.DIRECTORY_SEPARATOR.'Router'.DIRECTORY_SEPARATOR.'AcymRouter.php';

// We keep these old SEF functions as they may be used by SEF extensions
function AcymBuildRoute(&$query)
{
    $router = new AcyMailing\Router\AcymRouter();

    return $router->build($query);
}

function AcymParseRoute(&$segments)
{
    $router = new AcyMailing\Router\AcymRouter();

    return $router->parse($segments);
}
