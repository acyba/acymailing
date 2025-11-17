<?php

namespace AcyMailing\Helpers;

use AcyMailing\Core\AcymObject;

class PaginationHelper extends AcymObject
{
    private int $totalNbOfElements;
    private int $currentPage;
    private int $nbPerPage;

    public function setStatus(int $total, int $page, int $nbPerPage)
    {
        $this->totalNbOfElements = $total;
        $this->currentPage = empty($page) ? 1 : $page;
        $this->nbPerPage = $nbPerPage;
    }

    public function display(string $page = '', string $suffix = '', bool $dynamics = false): string
    {
        $name = empty($page) ? 'pagination_page_ajax' : $page.'_pagination_page';

        if (empty($this->nbPerPage)) {
            return '';
        }

        $nbPages = ceil($this->totalNbOfElements / $this->nbPerPage);

        $class = $dynamics ? ' margin-bottom-1' : '';

        if (acym_isAdmin() || ACYM_CMS === 'joomla') {
            $classNavigationContainer = 'shrink grid-margin-x';
            $classNavigation = 'small-auto medium-shrink';
            $classDisplayNumber = 'shrink';
            $classPadding = 'pagination_padding ';
        } else {
            $classNavigationContainer = '';
            $classNavigation = '';
            $classDisplayNumber = 'align-center';
            $classPadding = '';
        }

        $pagination = '<div class="pagination text-center cell grid-x'.$class.'" role="navigation" aria-label="Pagination">
                        <div class="cell '.$classNavigationContainer.' margin-auto grid-x align-center">
                            <div class="'.$classNavigation.' pagination_container cell grid-x acym_vcenter align-center">';

        // Turbo first button
        if (!$dynamics) {
            $pagination .= '<div class="cell shrink pagination-turbo-left pagination_one_pagination '.$classPadding;
            $pagination .= $this->currentPage > 1 ? 'acym__pagination__page'.$suffix.'" page="1' : 'pagination_disabled';
            $pagination .= '"><i class="acymicon-play-arrow rotate180deg pagination__i"></i><i class="acymicon-play-arrow rotate180deg pagination__i"></i></div>';
            $pagination .= '<div class="cell shrink pagination_border_left"></div>';
        }

        // Previous button
        $pagination .= '<div class="cell shrink pagination-previous pagination_one_pagination '.$classPadding;
        $pagination .= $this->currentPage > 1 ? 'acym__pagination__page'.$suffix.'" page="'.($this->currentPage - 1) : 'pagination_disabled';
        $pagination .= '"><i class="acymicon-play-arrow rotate180deg pagination__i"></i></div>';

        $pagination .= '<div class="cell shrink pagination_border_left"></div>';
        $pagination .= '<input type="number" name="'.$name.'" min="1" max="'.(empty($nbPages) ? 1
                : $nbPages).'" value="'.$this->currentPage.'" class="cell shrink pagination_input" id="acym_pagination'.$suffix.'">';
        $pagination .= '<p class="cell shrink pagination_text">'.acym_translation('ACYM_OUT_OF').' '.$nbPages.'</p>';
        $pagination .= '<div class="cell shrink pagination_border_right"></div>';

        // Next button
        if ($this->currentPage < $nbPages) {
            $paramsNext = 'acym__pagination__page'.$suffix.'" page="'.($this->currentPage + 1);
            $paramsTurboNext = 'acym__pagination__page'.$suffix.'" page="'.$nbPages;
        } else {
            $paramsNext = 'pagination_disabled';
            $paramsTurboNext = 'pagination_disabled';
        }

        $pagination .= '<div class="cell shrink pagination-next pagination_one_pagination '.$classPadding.$paramsNext.'"><i class="acymicon-play-arrow pagination__i"></i></div>';

        // Turbo last button
        if (!$dynamics) {
            $pagination .= '<div class="cell shrink pagination_border_right"></div>';
            $pagination .= '<div class="cell shrink pagination-turbo-right pagination_one_pagination '.$classPadding.$paramsTurboNext.'">
                                    <i class="acymicon-play-arrow pagination__i"></i>
                                    <i class="acymicon-play-arrow pagination__i"></i>
                                </div>';
        }

        $pagination .= '</div>';

        if (!$dynamics) {
            $nbPagesOptions = [
                '5' => 5,
                '10' => 10,
                '15' => 15,
                '20' => 20,
                '30' => 30,
                '50' => 50,
                '100' => 100,
                '200' => 200,
            ];
            $pagination .= '<div class="cell '.$classDisplayNumber.' grid-x acym_vcenter acym__pagination__pagenb">';

            $selectValue = $page === 'archive' ? $this->getListLimit($this->nbPerPage) : $this->getListLimit();
            $paginationNumberEntries = '<div class="acym__select__pagination">'.acym_select(
                    $nbPagesOptions,
                    'acym_pagination_element_per_page',
                    $this->getClosest($selectValue, $nbPagesOptions),
                    ['class' => 'acym__select__pagination__dropdown']
                ).'</div>';

            $pagination .= '<p class="cell shrink">'.acym_translationSprintf('ACYM_DISPLAY_NUMBER_ENTRIES', $paginationNumberEntries).'</p>';
            $pagination .= '</div>';
        }

        $pagination .= '</div>';
        $pagination .= '</div>';

        return $pagination;
    }

    public function displayAjax(bool $dynamics = false): string
    {
        return $this->display('', '__ajax', $dynamics);
    }

    public function getListLimit(int $default = 20): int
    {
        $currentUserId = acym_currentUserId();
        if (empty($currentUserId)) $currentUserId = 0;
        $currentConfig = (int)$this->config->get('list_limit_'.$currentUserId, $default);
        $listLimitSelect = acym_getVar('int', 'acym_pagination_element_per_page', 0);

        if (!empty($listLimitSelect) && $listLimitSelect !== $currentConfig) {
            $this->config->saveConfig(['list_limit_'.$currentUserId => $listLimitSelect]);
            $currentConfig = $listLimitSelect;
        }

        return $currentConfig;
    }

    private function getClosest(int $search, array $arr): string
    {
        $closest = null;
        foreach ($arr as $item) {
            if ($closest === null || abs($search - $closest) > abs($item - $search)) {
                $closest = $item;
            }
        }

        return (string)$closest;
    }
}
