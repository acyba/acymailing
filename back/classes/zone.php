<?php

namespace AcyMailing\Classes;

use AcyMailing\Libraries\acymClass;

class ZoneClass extends acymClass
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'custom_zone';
        $this->pkey = 'id';
    }

    public function save($element)
    {
        $element = clone $element;
        $element->name = strip_tags($element->name);
        $element->content = base64_encode($element->content);

        return parent::save($element);
    }

    public function getOneById($id)
    {
        $element = parent::getOneById($id);

        return $this->decodeContent($element);
    }

    public function getOneByName($name)
    {
        $element = acym_loadObject('SELECT * FROM #__acym_custom_zone WHERE name = '.acym_escapeDB($name));

        return $this->decodeContent($element);
    }

    private function decodeContent($element)
    {
        if (empty($element->content)) return $element;

        $element->content = base64_decode($element->content);

        return $element;
    }

    public function getAll($key = null)
    {
        $elements = parent::getAll();
        if (empty($elements)) return $elements;

        foreach ($elements as $i => $element) {
            $elements[$i] = $this->decodeContent($element);
        }

        return $elements;
    }

    public function delete($elements)
    {
        if (empty($elements)) return 0;
        if (!is_array($elements)) {
            $elements = [$elements];
        }

        foreach ($elements as $oneElementId) {
            $zone = $this->getOneById($oneElementId);
            if (!empty($zone->image) && file_exists(ACYM_ROOT.$zone->image)) {
                unlink(ACYM_ROOT.$zone->image);
            }
        }

        return parent::delete($elements);
    }
}
