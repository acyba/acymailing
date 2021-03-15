jQuery(document).ready(function ($) {
    function Init() {
        acym_helperSegment.reloadGlobalCounter($('.acym__segments__select__classic__filter').closest('.acym__segments__group__filter'));
        acym_helperSegment.refreshFilterProcess();
        acym_helperSegment.rebuildFilters();
        acym_helperFilter.setAutomationReload();
    }

    Init();
});
