<?php

class acymtagClass extends acymClass
{
    var $table = 'tag';
    var $pkey = 'id';

    /**
     * Attaches the tags passed in parameter to the specified element
     *
     * @param string $type    Could be list, campaign, template, automation...
     * @param int    $elementId
     * @param array  $newTags Array of tag ids, or new tag names
     */
    public function setTags($type, $elementId, $newTags)
    {
        // Remove the old tags from the element
        acym_query('DELETE FROM #__acym_tag WHERE `type` = '.acym_escapeDB($type).' AND id_element = '.intval($elementId));

        $tagsToInsertQuery = [];

        foreach ($newTags as $oneTag) {

            $newTag = new stdClass();
            $newTag->type = $type;

            if (strpos($oneTag, "acy_new_tag_") !== false) {
                // New tag
                $tagName = substr($oneTag, strlen("acy_new_tag_"));
                if (empty($tagName)) {
                    continue;
                }
                $newTag->name = $tagName;
            } else {
                $newTag->name = $oneTag;
            }
            $tagsToInsertQuery[] = '('.acym_escapeDB($newTag->name).','.acym_escapeDB($newTag->type).', '.intval($elementId).')';
        }
        if (!empty($tagsToInsertQuery)) {
            acym_query('INSERT INTO #__acym_tag (`name`, `type`, `id_element`) VALUES '.implode(',', $tagsToInsertQuery));
        }
    }

    /**
     * @param $type
     *
     * @return array
     */
    public function getAllTagsByType($type)
    {
        $query = 'SELECT `name` as value, `name` FROM #__acym_tag WHERE `type` = '.acym_escapeDB($type).' GROUP BY `name`';

        return acym_loadObjectList($query);
    }

    /**
     * @param $type
     * @param $id
     *
     * @return array
     */
    public function getAllTagsByElementId($type, $id)
    {
        if (empty($id)) return [];

        $query = 'SELECT * FROM #__acym_tag WHERE type = '.acym_escapeDB($type).' AND id_element = '.intval($id);
        $tags = acym_loadResultArray($query);

        return empty($tags) ? [] : $tags;
    }

    /**
     * @param $type
     * @param $ids
     *
     * @return array
     */
    public function getAllTagsByTypeAndElementIds($type, $ids)
    {
        acym_arrayToInteger($ids);
        if (empty($ids)) {
            return [];
        }

        $query = 'SELECT * FROM #__acym_tag WHERE `type` = '.acym_escapeDB($type).' AND `id_element` IN ('.implode(',', $ids).')';

        return acym_loadObjectList($query);
    }
}
