<?php

namespace AcyMailing\Helpers;

use AcyMailing\Core\AcymObject;

class EntitySelectHelper extends AcymObject
{
    private array $columnsHeaderNotToDisplay;

    public function __construct()
    {
        parent::__construct();
        $this->columnsHeaderNotToDisplay = ['color'];
    }

    public function entitySelect(array $options): string
    {
        $options['columnsToDisplay'] = $options['columnsToDisplay'] ?? ['name'];
        $options['buttonSubmit'] = $options['buttonSubmit'] ?? [
            'text' => '',
            'action' => '',
            'class' => '',
        ];
        $options['displaySelected'] = $options['displaySelected'] ?? true;
        $options['displayedName'] = $options['displayedName'] ?? '';

        $columnJoin = '';
        if (!empty($options['columnsToDisplay']['join'])) {
            $columnJoin = explode('.', $options['columnsToDisplay']['join']);
        }

        unset($options['columnsToDisplay']['join']);

        if (empty($options['display'])) {
            ob_start();
        }

        echo '<div 
                    style="display: none;" 
                    id="acym__entity_select" 
                    class="acym__entity_select cell grid-x" 
                    data-display-selected="'.($options['displaySelected'] ? 'true' : 'false').'" 
                    data-entity="'.acym_escape($options['entity']).'" 
                    data-type="select" 
                    data-columns="'.acym_escape(implode(',', array_keys($options['columnsToDisplay']))).'" 
                    data-columns-class="'.acym_escape(json_encode($options['columnsToDisplay'])).'" 
                    data-join="'.acym_escape($options['entityParams']['join'] ?? '').'" ';

        if (!empty($columnJoin)) {
            echo ' data-column-join="'.acym_escape($columnJoin[1]).'" data-table-join="'.acym_escape($columnJoin[0]).'"';
        }
        echo '>';

        $this->displayListing('available', 'select', $options['entity'], $options['columnsToDisplay'], $options['displayedName']);
        echo '<div class="cell medium-shrink text-center grid-x acym_vcenter"><i class="acymicon-arrows-h cell"></i></div>';
        $this->displayListing('selected', 'unselect', $options['entity'], $options['columnsToDisplay'], $options['displayedName']);
        if (!empty($options['additionalData'])) {
            echo $options['additionalData'];
        }

        if (!empty($options['buttonSubmit']['text'])) {
            $class = !empty($options['buttonSubmit']['action']) ? 'acy_button_submit' : 'acym__entity_select__button__close';
            if (!empty($options['buttonSubmit']['class'])) {
                $class .= ' '.$options['buttonSubmit']['class'];
            }
            echo '<div class="cell grid-x align-center margin-top-1">';
            echo '<button 
                        type="button" 
                        id="acym__entity_select__button__submit" 
                        class="cell shrink grid-x '.acym_escape($class).' button" ';
            if (!empty($options['buttonSubmit']['action'])) {
                echo ' data-task="'.acym_escape($options['buttonSubmit']['action']).'"';
            }
            echo '>';
            echo acym_escapeHtml($options['buttonSubmit']['text']);
            echo '</button>';
            echo '</div>';
        }

        echo '<input type="hidden" class="acym__entity_select__selected" name="acym__entity_select__selected" value="">';
        echo '<input type="hidden" class="acym__entity_select__unselected" name="acym__entity_select__unselected" value="">';
        echo '</div>';

        if (empty($options['display'])) {
            return ob_get_clean();
        }

        return '';
    }

    public function getColumnsForList(string $join = '', bool $small = false): array
    {
        $columns = [
            'color' => $small ? 'small-2' : 'small-1',
            'name' => 'auto',
            'id' => 'small-2',
        ];
        if (!empty($join)) {
            $columns['join'] = $join;
        }

        return $columns;
    }

    public function getColumnsForUser(string $join = ''): array
    {
        $columns = [
            'email' => 'auto',
            'name' => 'auto',
            'id' => 'small-1',
        ];

        if (!empty($join)) {
            $columns['join'] = $join;
        }

        return $columns;
    }

    private function displayListing(
        string $type,
        string $allSelector,
        string $entity,
        array  $columnsToDisplay = [],
        string $displayedName = ''
    ): void {
        if (empty($displayedName)) {
            $displayedName = $entity;
        }

        echo '<div class="cell medium-auto grid-x acym_area acym__entity_select__'.acym_escape($type).'">
                <h5 class="cell font-bold acym__title acym__title__secondary text-center">'.acym_escapeHtml(
                acym_translation('ACYM_'.strtoupper($type).'_'.strtoupper($displayedName))
            ).'</h5>
                <div class="cell grid-x">
                <div class="cell grid-x acym__entity_select__header">
                    <div class="cell grid-x">
                        <div class="cell margin-bottom-1"><input type="text" v-model="'.acym_escape($type).'Search" placeholder="'.acym_escape(acym_translation('ACYM_SEARCH')).'"></div>
                        <div class="cell align-right grid-x acym__entity_select__select__all">
                            <button type="button" 
                                v-show="!loading" 
                                v-if="displaySelectAll_'.acym_escape($type).'" 
                                v-on:click="moveAll('.acym_escape(json_encode($type)).')" 
                                class="cell shrink acym__entity_select__select__all__button acym__entity_select__select__all__button__'.acym_escape($type).'">'.acym_escapeHtml(
                acym_translation('ACYM_'.strtoupper($allSelector).'_ALL')
            ).'</button>
                                </div>
                            </div>
                        </div>
                        <div v-infinite-scroll="loadMoreEntity'.acym_escape(
                ucfirst($type)
            ).'" :infinite-scroll-disabled="busy" class="acym__listing cell acym__entity_select__'.acym_escape($type).'__listing acym__content" infinite-scroll-distance="10">';
        $emptyMessage = acym_translation($type === 'available' ? 'ACYM_NOTHING_TO_SHOW_HERE_RIGHT_PANEL' : 'ACYM_PLEASE_CLICK_ON_THE_LEFT_PANEL');
        echo '<div class="cell text-center acym__entity_select__title margin-top-2" v-show="Object.keys(entitiesToDisplay_'.acym_escape(
                $type
            ).').length == 0 && !loading">'.acym_escapeHtml($emptyMessage).'</div>
                    <div class="cell acym_vcenter acym__listing__row grid-x acym__listing__row__header" v-if="Object.keys(entitiesToDisplay_'.acym_escape($type).').length != 0">';

        //Start the listing

        //if it's the select listing we display the - at the start
        if ($type !== 'available') {
            echo '<div class="cell small-1"></div>';
        }

        //We lists all the columns
        foreach ($columnsToDisplay as $column => $class) {
            echo '<div class="cell grid-x '.acym_escape($class).'">'.acym_escapeHtml(
                    in_array($column, $this->columnsHeaderNotToDisplay) ? '' : acym_translation('ACYM_'.strtoupper($column))
                ).'</div>';
        }

        //if it's the available listing we display the + at the end
        if ($type === 'available') {
            echo '<div class="cell small-1"></div>';
        }
        //end of listing header
        echo '</div>';

        //trigger the good function depending if were in available or selected listing
        $functionClick = $type === 'available' ? 'selectEntity(entity.id)' : 'unselectEntity(entity.id)';
        //each row of the listing
        echo '<div v-on:click="'.acym_escape($functionClick).'" v-for="(entity, index) in entitiesToDisplay_'.acym_escape(
                $type
            ).'" class="cell acym_vcenter acym__listing__row grid-x acym__entity_select__'.acym_escape($type).'__listing__row" >';

        //if it's the select listing we display the - at the start
        if ($type !== 'available') {
            echo '<div class="cell small-1 vertical-align-middle text-center">
                        <div class="plus-container acym__entity_select__selected__listing__row__unselect">
                          <div class="top-plus plus-bar"></div>
                          <div class="plus plus-bar"></div>
                          <div class="bottom-plus plus-bar"></div>
                        </div>
                    </div>';
        }

        //Display all the value for the columns
        echo '<div v-for="(column, index) in columnsToDisplay" class="cell align-center acym__entity_select__columns" :class="getClass(column)" v-html="entity[column]"></div>';

        //if it's the available listing we display the + at the end
        if ($type === 'available') {
            echo '<div class="cell small-1 vertical-align-middle text-center">
                        <div class="plus-container acym__entity_select__available__listing__row__select">
                          <div class="top-plus plus-bar"></div>
                          <div class="plus plus-bar"></div>
                          <div class="bottom-plus plus-bar"></div>
                        </div>
        			</div>';
        }

        //End of listing with message loading and loading logo
        echo '</div>
                    <div class="cell grid-x align-center acym__entity_select__loading margin-top-1"  v-show="loading"><div class="cell text-center acym__entity_select__title">';
        echo acym_escapeHtml(acym_translation('ACYM_WE_ARE_LOADING_YOUR_DATA'));
        echo '</div><div class="cell grid-x shrink margin-top-1">';
        acym_loaderLogo(false);
        echo '</div></div>';
        echo '</div>';
        echo '</div>
            </div>';
    }
}
