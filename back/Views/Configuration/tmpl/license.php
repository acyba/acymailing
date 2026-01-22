<?php
if (!acym_level(ACYM_ESSENTIAL)) {
    include acym_getView('configuration', 'upgrade_license', true);
}
