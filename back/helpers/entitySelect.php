<?php

namespace AcyMailing\Helpers;

use AcyMailing\Controllers\EntitySelectController;
use AcyMailing\Libraries\acymObject;

class EntitySelectHelper extends acymObject
{
    var $svg;
    var $columnsHeaderNotToDisplay;

    public function __construct()
    {
        parent::__construct();
        $this->svg = acym_loaderLogo(false);
        $this->columnsHeaderNotToDisplay = ['color'];
    }

    private function _getListing($type, $allSelector, $entity, $columnsToDisplay = [], $displayedName = '')
    {
        if (empty($displayedName)) $displayedName = $entity;
        $display = '<div class="cell medium-auto grid-x acym_area acym__entity_select__'.$type.'">
                        <h5 class="cell font-bold acym__title acym__title__secondary text-center">'.acym_translation('ACYM_'.strtoupper($type).'_'.strtoupper($displayedName)).'</h5>
                        <div class="cell grid-x">
                        <div class="cell grid-x acym__entity_select__header">
                            <div class="cell grid-x">
                                <div class="cell margin-bottom-1"><input type="text" v-model="'.$type.'Search" placeholder="'.acym_translation('ACYM_SEARCH').'"></div>
                                <div class="cell align-right grid-x acym__entity_select__select__all">
                                    <button type="button" v-show="!loading" v-if="displaySelectAll_'.$type.'" v-on:click="moveAll('.acym_escapeDB(
                $type
            ).')" class="cell shrink acym__entity_select__select__all__button acym__entity_select__select__all__button__'.$type.'">'.acym_translation(
                'ACYM_'.strtoupper($allSelector).'_ALL'
            ).'</button>
                                </div>
                            </div>
                        </div>
                        <div v-infinite-scroll="loadMoreEntity'.ucfirst(
                $type
            ).'" :infinite-scroll-disabled="busy" class="acym__listing cell acym__entity_select__'.$type.'__listing acym__content" infinite-scroll-distance="10">';
        $emptyMessage = acym_translation($type === 'available' ? 'ACYM_NOTHING_TO_SHOW_HERE_RIGHT_PANEL' : 'ACYM_PLEASE_CLICK_ON_THE_LEFT_PANEL');
        $display .= '<div class="cell text-center acym__entity_select__title margin-top-2" v-show="Object.keys(entitiesToDisplay_'.$type.').length == 0 && !loading">'.$emptyMessage.'</div>
                    <div class="cell acym_vcenter acym__listing__row grid-x acym__listing__row__header" v-if="Object.keys(entitiesToDisplay_'.$type.').length != 0">';

        //Start the listing

        //if it's the select listing we display the - at the start
        if ($type !== 'available') $display .= '<div class="cell small-1"></div>';

        //We lists all the columns
        foreach ($columnsToDisplay as $column => $class) {
            $display .= '<div class="cell grid-x '.$class.'">'.(in_array($column, $this->columnsHeaderNotToDisplay) ? '' : acym_translation('ACYM_'.strtoupper($column))).'</div>';
        }

        //if it's the available listing we display the + at the end
        if ($type === 'available') $display .= '<div class="cell small-1"></div>';
        //end of listing header
        $display .= '</div>';

        //trigger the good function depending if were in available or selected listing
        $functionClick = $type === 'available' ? 'v-on:click="selectEntity(entity.id)"' : 'v-on:click="unselectEntity(entity.id)"';
        //each row of the listing
        $display .= '<div '.$functionClick.' v-for="(entity, index) in entitiesToDisplay_'.$type.'" class="cell acym_vcenter acym__listing__row grid-x acym__entity_select__'.$type.'__listing__row" >';

        //if it's the select listing we display the - at the start
        if ($type !== 'available') {
            $display .= '<div class="cell small-1 vertical-align-middle text-center">
                            <div class="plus-container acym__entity_select__selected__listing__row__unselect">
                              <div class="top-plus plus-bar"></div>
                              <div class="plus plus-bar"></div>
                              <div class="bottom-plus plus-bar"></div>
                            </div>
                        </div>';
        }

        //Display all the value for the columns
        $display .= '<div v-for="(column, index) in columnsToDisplay" class="cell align-center acym__entity_select__columns" :class="getClass(column)" v-html="entity[column]"></div>';

        //if it's the available listing we display the + at the end
        if ($type === 'available') {
            $display .= '<div class="cell small-1 vertical-align-middle text-center">
                        <div class="plus-container acym__entity_select__available__listing__row__select">
                          <div class="top-plus plus-bar"></div>
                          <div class="plus plus-bar"></div>
                          <div class="bottom-plus plus-bar"></div>
                        </div>
        			</div>';
        }

        //End of listing with message loading and loading logo
        $display .= '</div>
                    <div class="cell grid-x align-center acym__entity_select__loading margin-top-1"  v-show="loading"><div class="cell text-center acym__entity_select__title">';
        $display .= acym_translation('ACYM_WE_ARE_LOADING_YOUR_DATA');
        $display .= '</div><div class="cell grid-x shrink margin-top-1">'.$this->svg.'</div></div>';
        $display .= '</div>';
        $display .= '</div>
                    </div>';

        return $display;
    }

    public function entitySelect(
        $entity, $entityParams = [], $columnsToDisplay = ['name'], $buttonSubmit = [
        'text' => '',
        'action' => '',
        'class' => '',
    ], $displaySelected = true, $additionalData = '', $displayedName = ''
    ) {
        $columnJoin = '';
        if (!empty($columnsToDisplay['join'])) {
            $columnJoin = explode('.', $columnsToDisplay['join']);
        }

        unset($columnsToDisplay['join']);

        if (empty($entityParams['elementsPerPage']) || $entityParams['elementsPerPage'] < 1) {
            $paginationHelper = new PaginationHelper();
            $entityParams['elementsPerPage'] = $paginationHelper->getListLimit();
        }

        if (!empty($columnJoin)) $columnJoin = 'data-column-join="'.$columnJoin[1].'" data-table-join="'.$columnJoin[0].'"';
        $display = '<div 
                        style="display: none;" 
                        id="acym__entity_select" 
                        class="acym__entity_select cell grid-x" 
                        data-display-selected="'.($displaySelected ? 'true' : 'false').'" 
                        data data-entity="'.acym_escape($entity).'" 
                        data-type="select" 
                        data-columns="'.implode(',', array_keys($columnsToDisplay)).'" 
                        data-columns-class="'.acym_escape(json_encode($columnsToDisplay)).'" 
                        data-join="'.$entityParams['join'].'" 
                        '.$columnJoin.'>';

        $display .= $this->_getListing('available', 'select', $entity, $columnsToDisplay, $displayedName);
        $display .= '<div class="cell medium-shrink text-center grid-x acym_vcenter"><i class="acymicon-arrows-h cell"></i></div>';
        $display .= $this->_getListing('selected', 'unselect', $entity, $columnsToDisplay, $displayedName);
        $display .= $additionalData;

        if (!empty($buttonSubmit['text'])) {
            $class = !empty($buttonSubmit['action']) ? 'acy_button_submit' : 'acym__entity_select__button__close';
            if (!empty($buttonSubmit['class'])) $class .= ' '.$buttonSubmit['class'];
            $buttonSubmit['action'] = !empty($buttonSubmit['action']) ? 'data-task="'.$buttonSubmit['action'].'"' : '';
            $display .= '<div class="cell grid-x align-center margin-top-1">';
            $display .= '<button 
                            type="button" 
                            id="acym__entity_select__button__submit" 
                            class="cell shrink grid-x '.$class.' button" '.$buttonSubmit['action'].'>'.$buttonSubmit['text'].'</button>';
            $display .= '</div>';
        }

        $display .= '<input type="hidden" class="acym__entity_select__selected" name="acym__entity_select__selected" value="">';
        $display .= '<input type="hidden" class="acym__entity_select__unselected" name="acym__entity_select__unselected" value="">';
        $display .= '</div>';

        return $display;
    }

    public function getColumnsForList($join = '', $small = false)
    {
        $columns = [
            'color' => $small ? 'small-2' : 'small-1',
            'name' => 'auto',
            'id' => 'small-2',
        ];
        if (!empty($join)) $columns['join'] = $join;

        return $columns;
    }

    public function getColumnsForUser($join = '')
    {
        $columns = [
            'email' => 'auto',
            'name' => 'auto',
            'id' => 'small-1',
        ];
        if (!empty($join)) $columns['join'] = $join;

        return $columns;
    }

}
