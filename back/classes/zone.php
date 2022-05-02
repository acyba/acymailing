<?php

namespace AcyMailing\Classes;

use AcyMailing\Libraries\acymClass;

class ZoneClass extends acymClass
{
    var $table = 'custom_zone';
    var $pkey = 'id';

    public function save($element)
    {
        $element = clone $element;
        $element->name = strip_tags($element->name);
        $element->content = utf8_encode($element->content);

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

        $element->content = utf8_decode($element->content);

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
}
