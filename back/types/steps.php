<?php

namespace AcyMailing\Types;

use AcyMailing\Libraries\acymObject;

class StepsType extends acymObject
{
    public function display(array $options): void
    {
        if (!isset($options['currentStep']) || !isset($options['totalSteps'])) {
            return;
        }

        $containerClasses = $options['containerClasses'] ?? 'cell large-6 xlarge-5 xxlarge-4 margin-bottom-3';

        $html = '<div class="'.$containerClasses.' acym__steps__container">';
        $html .= '<div class="acym__steps__circles">';
        for ($i = 1 ; $i <= $options['totalSteps'] ; $i++) {
            $stepClasses = 'acym__steps__circle';
            if ($i < $options['currentStep']) {
                $stepClasses .= ' acym__steps__done';
            }
            if ($i === $options['currentStep']) {
                $stepClasses .= ' acym__steps__current';
            }

            $html .= '<div class="'.$stepClasses.'"></div>';
        }
        $html .= '</div>';
        $html .= '</div>';

        echo $html;
    }
}
