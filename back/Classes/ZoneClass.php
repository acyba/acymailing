<?php

namespace AcyMailing\Classes;

use AcyMailing\Core\AcymClass;

class ZoneClass extends AcymClass
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'custom_zone';
        $this->pkey = 'id';
    }

    public function save(object $element): ?int
    {
        $zone = clone $element;
        $zone->name = strip_tags($zone->name);
        $zone->content = base64_encode($zone->content);

        return parent::save($zone);
    }

    public function getOneById(int $id): ?object
    {
        $element = parent::getOneById($id);

        return empty($element) ? null : $this->decodeContent($element);
    }

    public function getOneByName(string $name): ?object
    {
        $element = acym_loadObject('SELECT * FROM #__acym_custom_zone WHERE name = '.acym_escapeDB($name));

        return empty($element) ? null : $this->decodeContent($element);
    }

    public function getAll(?string $key = null): array
    {
        $elements = parent::getAll();
        if (empty($elements)) {
            return $elements;
        }

        foreach ($elements as $i => $element) {
            $elements[$i] = $this->decodeContent($element);
        }

        return $elements;
    }

    public function delete(array $elements): int
    {
        if (empty($elements)) return 0;

        foreach ($elements as $oneElementId) {
            $zone = $this->getOneById($oneElementId);
            if (!empty($zone->image) && file_exists(ACYM_ROOT.$zone->image)) {
                unlink(ACYM_ROOT.$zone->image);
            }
        }

        return parent::delete($elements);
    }

    private function decodeContent(object $element): object
    {
        if (empty($element->content)) {
            return $element;
        }

        $element->content = base64_decode($element->content);

        return $element;
    }
}
