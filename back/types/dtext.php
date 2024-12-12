<?php

namespace AcyMailing\Types;

use AcyMailing\Helpers\TabHelper;
use AcyMailing\Libraries\acymObject;

class DtextType extends acymObject
{
    public function display(array $options = []): void
    {
        $options['withButton'] = $options['withButton'] ?? true;
        $options['context'] = $options['context'] ?? '';

        if ($options['withButton']) {
            $this->displayButton($options);
        }

        $data = [
            'plugins' => $this->getIntegrations(),
            'options' => $options,
            'tabHelper' => new TabHelper(),
        ];

        include acym_getPartial('dtext', 'picker');
    }

    public function displayButton(array $options = []): void
    {
        $data = [
            'class' => $options['class'] ?? '',
            'editor' => $options['editor'] ?? '',
            'selection' => $options['selection'] ?? '',
            'icon' => $options['icon'] ?? 'acymicon-plus-circle',
            'text' => $options['text'] ?? '',
        ];

        if (!empty($data['icon'])) {
            $data['icon'] = '<i class="'.$data['icon'].'"></i>';
        }

        include acym_getPartial('dtext', 'button');
    }

    private function getIntegrations(): array
    {
        $integrations = acym_trigger('dynamicText', [null]);
        usort(
            $integrations,
            function ($a, $b) {
                return strtolower($a->name) > strtolower($b->name) ? 1 : -1;
            }
        );

        return empty($integrations) ? [] : $integrations;
    }
}
