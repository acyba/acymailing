<?php

use AcyMailing\Types\OperatorinType;

trait LearndashAutomationConditions
{
    public function onAcymDeclareConditions(&$conditions): void
    {
        $this->groupFilter($conditions);
        $this->courseFilter($conditions);
    }

    public function onAcymProcessCondition_learndash_group(&$query, $options, $num, &$conditionNotValid): void
    {
        $this->processConditionFilter_learndash_group($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) {
            $conditionNotValid++;
        }
    }

    public function onAcymProcessCondition_learndash_course(&$query, $options, $num, &$conditionNotValid): void
    {
        $this->processConditionFilter_learndash_course($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) {
            $conditionNotValid++;
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition): void
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function groupFilter(&$conditions): void
    {
        $allGroups = acym_loadObjectList(
            'SELECT post.post_title AS `text`, post.`ID` AS `value` 
			 FROM #__posts AS post
             WHERE post.`post_type` = "groups" AND post.`post_status` = "publish" 
             ORDER BY post.post_title ASC'
        );
        if (empty($allGroups)) {
            return;
        }

        $conditions['user'][self::FILTER_GROUP] = new stdClass();
        $conditions['user'][self::FILTER_GROUP]->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'Learndash', acym_translation('ACYM_GROUP'));
        $conditions['user'][self::FILTER_GROUP]->option = '<div class="cell grid-x grid-margin-x">';

        $operatorIn = new OperatorinType();

        $conditions['user'][self::FILTER_GROUP]->option .= '<div class="intext_select_automation cell acym__small_select">';
        $conditions['user'][self::FILTER_GROUP]->option .= $operatorIn->display(
            'acym_condition[conditions][__numor__][__numand__]['.self::FILTER_GROUP.']['.self::FILTER_GROUP_FIELD_IN.']'
        );
        $conditions['user'][self::FILTER_GROUP]->option .= '</div>';

        $conditions['user'][self::FILTER_GROUP]->option .= '<div class="intext_select_automation cell">';
        $conditions['user'][self::FILTER_GROUP]->option .= acym_selectMultiple(
            $allGroups,
            'acym_condition[conditions][__numor__][__numand__]['.self::FILTER_GROUP.']['.self::FILTER_GROUP_FIELD_GROUP.']',
            [],
            [
                'class' => 'acym__select',
                'data-placeholder' => acym_translation('ACYM_ANY_GROUP'),
            ]
        );
        $conditions['user'][self::FILTER_GROUP]->option .= '</div>';

        $conditions['user'][self::FILTER_GROUP]->option .= '</div>';
    }

    private function courseFilter(&$conditions): void
    {
        $allCourses = acym_loadObjectList(
            'SELECT post.post_title AS `text`, post.`ID` AS `value` 
			 FROM #__posts AS post
             WHERE post.`post_type` = "sfwd-courses" AND post.`post_status` = "publish" 
             ORDER BY post.post_title ASC'
        );
        if (empty($allCourses)) {
            return;
        }

        $conditions['user'][self::FILTER_COURSE] = new stdClass();
        $conditions['user'][self::FILTER_COURSE]->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'Learndash', 'Course');
        $conditions['user'][self::FILTER_COURSE]->option = '<div class="cell grid-x grid-margin-x">';

        $operatorIn = new OperatorinType();

        $conditions['user'][self::FILTER_COURSE]->option .= '<div class="intext_select_automation cell acym__small_select">';
        $conditions['user'][self::FILTER_COURSE]->option .= $operatorIn->display(
            'acym_condition[conditions][__numor__][__numand__]['.self::FILTER_COURSE.']['.self::FILTER_COURSE_FIELD_IN.']'
        );
        $conditions['user'][self::FILTER_COURSE]->option .= '</div>';

        $conditions['user'][self::FILTER_COURSE]->option .= '<div class="intext_select_automation cell">';
        $conditions['user'][self::FILTER_COURSE]->option .= acym_selectMultiple(
            $allCourses,
            'acym_condition[conditions][__numor__][__numand__]['.self::FILTER_COURSE.']['.self::FILTER_COURSE_FIELD_COURSE.']',
            [],
            [
                'class' => 'acym__select',
                'data-placeholder' => acym_translation('ACYM_ANY'),
            ]
        );
        $conditions['user'][self::FILTER_COURSE]->option .= '</div>';

        $statuses = [
            'any' => acym_translation('ACYM_ANY_STATUS'),
            'started' => acym_translation('ACYM_STARTED'),
            'finished' => acym_translation('ACYM_FINISHED'),
        ];
        $conditions['user'][self::FILTER_COURSE]->option .= '<div class="intext_select_automation cell">';
        $conditions['user'][self::FILTER_COURSE]->option .= acym_select(
            $statuses,
            'acym_condition[conditions][__numor__][__numand__]['.self::FILTER_COURSE.']['.self::FILTER_COURSE_FIELD_STATUS.']',
            'any',
            ['class' => 'acym__select']
        );
        $conditions['user'][self::FILTER_COURSE]->option .= '</div>';

        $conditions['user'][self::FILTER_COURSE]->option .= '</div>';
    }

    private function processConditionFilter_learndash_group(&$query, $options, $num): void
    {
        $metaGroupTable = self::FILTER_GROUP.$num;

        $joinMetaGroup = '#__usermeta AS '.$metaGroupTable.' 
            ON '.$metaGroupTable.'.user_id = user.cms_id 
            AND user.`cms_id` > 0 
            AND '.$metaGroupTable.'.`meta_key` LIKE "learndash_group_users_%" ';

        if (!empty($options[self::FILTER_GROUP_FIELD_GROUP])) {
            acym_arrayToInteger($options[self::FILTER_GROUP_FIELD_GROUP]);
            $joinMetaGroup .= ' AND '.$metaGroupTable.'.`meta_value` IN ('.implode(', ', $options[self::FILTER_GROUP_FIELD_GROUP]).') ';
        }

        if (empty($options[self::FILTER_GROUP_FIELD_IN]) || $options[self::FILTER_GROUP_FIELD_IN] === 'in') {
            $query->join[$metaGroupTable] = $joinMetaGroup;
        } else {
            $query->leftjoin[$metaGroupTable] = $joinMetaGroup;
            $query->where[] = $metaGroupTable.'.user_id IS NULL';
        }
    }

    private function processConditionFilter_learndash_course(&$query, $options, $num): void
    {
        $activityCourseTable = '`'.self::FILTER_COURSE.$num.'`';
        $userTable = '`'.self::FILTER_COURSE.'_user'.$num.'`';

        $joinUser = '#__users AS '.$userTable.' ON '.$userTable.'.user_email = `user`.`email` COLLATE utf8mb4_unicode_520_ci AND `user`.`cms_id` > 0';
        $joinActivityCourse = '#__learndash_user_activity AS '.$activityCourseTable.' ON '.$activityCourseTable.'.`user_id` = '.$userTable.'.`ID` AND '.$activityCourseTable.'.`activity_type` = "course" ';

        if (!empty($options[self::FILTER_COURSE_FIELD_COURSE])) {
            acym_arrayToInteger($options[self::FILTER_COURSE_FIELD_COURSE]);
            $joinActivityCourse .= ' AND '.$activityCourseTable.'.`course_id` IN ('.implode(', ', $options[self::FILTER_COURSE_FIELD_COURSE]).') ';
        }

        if (!empty($options[self::FILTER_COURSE_FIELD_STATUS]) && $options[self::FILTER_COURSE_FIELD_STATUS] !== 'any') {
            if ($options[self::FILTER_COURSE_FIELD_STATUS] === 'started') {
                $joinActivityCourse .= ' AND '.$activityCourseTable.'.`activity_status` = 0 ';
            } else {
                $joinActivityCourse .= ' AND '.$activityCourseTable.'.`activity_status` = 1 ';
            }
        }

        if (empty($options[self::FILTER_COURSE_FIELD_IN]) || $options[self::FILTER_COURSE_FIELD_IN] === 'in') {
            $query->join[$userTable] = $joinUser;
            $query->join[$activityCourseTable] = $joinActivityCourse;
        } else {
            $query->leftjoin[$userTable] = $joinUser;
            $query->leftjoin[$activityCourseTable] = $joinActivityCourse;
            $query->where[] = $activityCourseTable.'.`user_id` IS NULL';
        }
    }

    private function summaryConditionFilters(&$automationCondition): void
    {
        $this->summaryGroup($automationCondition);
        $this->summaryCourse($automationCondition);
    }

    private function summaryGroup(&$automation): void
    {
        if (empty($automation[self::FILTER_GROUP])) {
            return;
        }

        if (empty($automation[self::FILTER_GROUP][self::FILTER_GROUP_FIELD_GROUP])) {
            $groups = acym_translation('ACYM_ANY_GROUP');
        } else {
            acym_arrayToInteger($automation[self::FILTER_GROUP][self::FILTER_GROUP_FIELD_GROUP]);
            $groups = acym_loadResultArray(
                'SELECT `post_title` 
                FROM #__posts 
                WHERE `ID` IN ('.implode(', ', $automation[self::FILTER_GROUP][self::FILTER_GROUP_FIELD_GROUP]).')'
            );

            $groups = empty($groups) ? acym_translation('ACYM_UNKNOWN_GROUP') : implode(', ', $groups);
        }

        $finalText = acym_translationSprintf(
            'ACYM_FILTER_ACY_GROUP_SUMMARY',
            acym_translation($automation[self::FILTER_GROUP][self::FILTER_GROUP_FIELD_IN] === 'in' ? 'ACYM_IN' : 'ACYM_NOT_IN'),
            $groups
        );

        $automation = $finalText;
    }

    private function summaryCourse(&$automation): void
    {
        if (empty($automation[self::FILTER_COURSE])) {
            return;
        }

        if (empty($automation[self::FILTER_COURSE][self::FILTER_COURSE_FIELD_COURSE])) {
            $courses = acym_translation('ACYM_ANY');
        } else {
            acym_arrayToInteger($automation[self::FILTER_COURSE][self::FILTER_COURSE_FIELD_COURSE]);
            $courses = acym_loadResultArray(
                'SELECT `post_title` 
                FROM #__posts 
                WHERE `ID` IN ('.implode(', ', $automation[self::FILTER_COURSE][self::FILTER_COURSE_FIELD_COURSE]).')'
            );

            $courses = empty($courses) ? acym_translation('ACYM_UNKNOWN') : implode(', ', $courses);
        }

        $finalText = acym_translationSprintf(
            'ACYM_FILTER_ACY_GROUP_SUMMARY',
            acym_translation($automation[self::FILTER_COURSE][self::FILTER_COURSE_FIELD_IN] === 'in' ? 'ACYM_IN' : 'ACYM_NOT_IN'),
            $courses
        );

        $automation = $finalText;
    }
}
