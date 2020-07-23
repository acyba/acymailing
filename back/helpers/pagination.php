<?php

class acympaginationHelper extends acymObject
{
    // The total number of elements in the database
    var $total;
    // Current page
    var $page;
    // Number of elements displayed per page
    var $nbPerPage;

    public function setStatus($total, $page, $nbPerPage)
    {
        $this->total = $total;
        $this->page = $page;
        $this->nbPerPage = $nbPerPage;
    }

    public function display($page, $suffix = '', $dynamics = false)
    {
        $name = empty($page) ? 'pagination_page_ajax' : $page.'_pagination_page';

        // There's only one page, don't display the pagination
        $nbPages = ceil($this->total / $this->nbPerPage);

        $class = $dynamics ? 'margin-bottom-1' : '';

        $pagination = '<div class="pagination text-center cell grid-x '.$class.'" role="navigation" aria-label="Pagination">
                            <div class="small-auto medium-shrink pagination_container cell margin-auto grid-x acym_vcenter">';

        // Turbo first button
        if (!$dynamics) {
            $pagination .= '<div class="cell shrink pagination-turbo-left pagination_one_pagination ';
            $pagination .= $this->page > 1 ? 'acym__pagination__page'.$suffix.'" page="1' : 'pagination_disabled';
            $pagination .= '"><i class="acymicon-play_arrow rotate180deg pagination__i"></i><i class="acymicon-play_arrow rotate180deg pagination__i"></i></div>';
            $pagination .= '<div class="cell shrink pagination_border_left"></div>';
        }

        // Previous button
        $pagination .= '<div class="cell shrink pagination-previous pagination_one_pagination ';
        $pagination .= $this->page > 1 ? 'acym__pagination__page'.$suffix.'" page="'.($this->page - 1) : 'pagination_disabled';
        $pagination .= '"><i class="acymicon-play_arrow rotate180deg pagination__i"></i></div>';

        $pagination .= '<div class="cell shrink pagination_border_left"></div>';
        $pagination .= '<input type="number" name="'.$name.'" min="1" max="'.(empty($nbPages) ? 1 : $nbPages).'" value="'.$this->page.'" class="cell shrink pagination_input" id="acym_pagination'.$suffix.'">';
        $pagination .= '<p class="cell shrink pagination_text">'.acym_translation('ACYM_OUT_OF').' '.$nbPages.'</p>';
        $pagination .= '<div class="cell shrink pagination_border_right"></div>';

        // Next button
        if ($this->page < $nbPages) {
            $paramsNext = 'acym__pagination__page'.$suffix.'" page="'.($this->page + 1);
            $paramsTurboNext = 'acym__pagination__page'.$suffix.'" page="'.$nbPages;
        } else {
            $paramsNext = 'pagination_disabled';
            $paramsTurboNext = 'pagination_disabled';
        }

        $pagination .= '<div class="cell shrink pagination-next pagination_one_pagination '.$paramsNext.'"><i class="acymicon-play_arrow pagination__i"></i></div>';

        // Turbo last button
        if (!$dynamics) {
            $pagination .= '<div class="cell shrink pagination_border_right"></div>';
            $pagination .= '<div class="cell shrink pagination-turbo-right pagination_one_pagination '.$paramsTurboNext.'">
                                    <i class="acymicon-play_arrow pagination__i"></i>
                                    <i class="acymicon-play_arrow pagination__i"></i>
                                </div>';
        }

        $pagination .= '</div></div>';

        if (!$dynamics) {
            $nbPagesOptions = [
                '5' => '5',
                '10' => '10',
                '15' => '15',
                '20' => '20',
                '30' => '30',
                '50' => '50',
                '100' => '100',
                '200' => '200',
            ];
            $pagination .= '<div class="cell grid-x align-center acym_vcenter margin-top-1">';

            $paginationNumberEntries = '<div class="acym__select__pagination">'.acym_select(
                    $nbPagesOptions,
                    'acym_pagination_element_per_page',
                    $this->getListLimit(),
                    ['class' => 'acym__select__pagination__dropdown']
                ).'</div>';

            $pagination .= '<p class="cell shrink">'.acym_translation_sprintf('ACYM_DISPLAY_NUMBER_ENTRIES', $paginationNumberEntries).'</p>';
            $pagination .= '</div>';
        }

        return $pagination;
    }

    public function displayAjax($dynamics = false)
    {
        return $this->display('', '__ajax', $dynamics);
    }

    public function displayFront()
    {
        $nbPages = ceil($this->total / $this->nbPerPage);

        $pagination = '';

        if ($nbPages < 2) {
            return $pagination;
        }

        $nextPage = $this->page + 1;
        $previousPage = $this->page - 1;


        $pagination .= '<div class="acym__front__pagination">';

        if ($this->page != 1) {
            $pagination .= '<span class="acym__front__pagination__element" onclick="acym_changePageFront(1)"><</span>';
            $pagination .= '<span class="acym__front__pagination__element" onclick="acym_changePageFront($previousPage)">'.$previousPage.'</span>';
        }
        $pagination .= '<b>'.$this->page.'</b>';
        if ($this->page != $nbPages) {
            $pagination .= '<span class="acym__front__pagination__element" onclick="acym_changePageFront('.$nextPage.')">'.$nextPage.'</span>';
            $pagination .= '<span class="acym__front__pagination__element" onclick="acym_changePageFront('.$nbPages.')">></span>';
        }

        $pagination .= '</div>';

        return $pagination;
    }

    public function getListLimit()
    {
        $currentUserId = acym_currentUserId();
        if (empty($currentUserId)) $currentUserId = 0;

        $currentConfig = $this->config->get('list_limit_'.$currentUserId, 20);
        $listLimitSelect = acym_getVar('int', 'acym_pagination_element_per_page', 0);

        if (!empty($listLimitSelect) && $listLimitSelect != $currentConfig) {
            $this->config->save(['list_limit_'.$currentUserId => $listLimitSelect]);
            $currentConfig = $listLimitSelect;
        }

        return $currentConfig;
    }
}
