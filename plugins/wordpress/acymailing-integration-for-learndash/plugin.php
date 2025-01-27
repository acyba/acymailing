<?php

use AcyMailing\Core\AcymPlugin;

if (!defined('ABSPATH')) exit;

require_once __DIR__.DIRECTORY_SEPARATOR.'LearndashAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'LearndashAutomationFilters.php';

class plgAcymLearndash extends AcymPlugin
{
    use LearndashAutomationConditions;
    use LearndashAutomationFilters;

    const FILTER_GROUP = 'learndash_group';
    const FILTER_GROUP_FIELD_IN = 'type';
    const FILTER_GROUP_FIELD_GROUP = 'group';

    const FILTER_COURSE = 'learndash_course';
    const FILTER_COURSE_FIELD_IN = 'type';
    const FILTER_COURSE_FIELD_COURSE = 'course';
    const FILTER_COURSE_FIELD_STATUS = 'status';

    public function __construct()
    {
        parent::__construct();

        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('sfwd-lms/sfwd_lms.php');
        $this->pluginDescription->name = 'Learndash';
        $this->pluginDescription->category = 'User management';
        $this->pluginDescription->description = '- Filter AcyMailing users on their Learndash groups<br />- Filter users based on attended courses';
    }
}
