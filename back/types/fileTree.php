<?php

namespace AcyMailing\Types;

use AcyMailing\Libraries\acymObject;

class FileTreeType extends acymObject
{
    public function display($folders, $currentFolder, $nameInput)
    {
        $tree = [];
        foreach ($folders as $root => $children) {
            $tree = array_merge($tree, $this->searchChildren($children, $root));
        }

        $treeView = '<div id="displaytree" class="cell medium-11"><input type="text" readonly name="currentPath" id="currentPath" value="'.acym_escape($currentFolder).'"></div>';
        $treeView .= '<div class="cell" id="treefile" style="display: none;">'.$this->displayTree($tree, $currentFolder).'</div>';
        $treeView .= '<input type="hidden" name="'.acym_escape($nameInput).'" id="'.acym_escape($nameInput).'" value="'.acym_escape($currentFolder).'">';

        return $treeView;
    }

    private function searchChildren($folders, $root)
    {
        $tree = [];
        $tree[$root] = [];

        foreach ($folders as $folder) {
            $folder = trim(str_replace($root, '', $folder), '/\\');
            if (empty($folder)) {
                continue;
            }

            $pathParts = explode('/', $folder);
            $variable = &$tree[$root];
            foreach ($pathParts as $pathPart) {
                if (empty($variable[$pathPart])) {
                    $variable[$pathPart] = [];
                }
                $variable = &$variable[$pathPart];
            }
        }

        return $tree;
    }

    private function displayTree($tree, $pathValue, $path = '')
    {
        if (empty($tree)) return '';
        $results = '<ul>';
        foreach ($tree as $key => $treeItem) {
            if (empty($path)) {
                $currentPath = $key;
                $title = '/';
            } else {
                $currentPath = rtrim($path, '/').'/'.trim($key, '/').'/';
                $title = $key;
            }

            $extraClass = 'tree-closed';
            $icon = 'acymicon-folder';

            if (strpos($pathValue, $currentPath) !== false) {
                $extraClass = $pathValue == $currentPath ? 'tree-current' : '';
                $icon .= '-open';
            }

            if (empty($treeItem)) $extraClass .= ' tree-empty';

            $subTree = $this->displayTree($treeItem, $pathValue, $currentPath);
            $results .= '<li class="tree-child-item '.acym_escape($extraClass).'" data-path="'.acym_escape($currentPath).'">
                            <span class="tree-child-title">
                                <i class="'.acym_escape($icon).'"></i> '.$title.'
                            </span>'.$subTree.'
                        </li>';
        }
        $results .= '</ul>';

        return $results;
    }
}
