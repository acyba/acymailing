<?php

declare(strict_types=1);

namespace AcyMailing\Libraries\Sabberworm\CSS;

interface Renderable
{
    public function render(OutputFormat $outputFormat): string;
}
