<?php

use AcyMailing\Classes\FollowupClass;
use AcyMailing\Classes\SegmentClass;
use AcyMailing\Helpers\AutomationHelper;
use AcyMailing\Helpers\ExportHelper;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\AutomationClass;

trait SubscriberFollowup
{
    private $followTriggerName = 'user_creation';

    private $triggerMail = ['user_click', 'user_open'];

    public function onAcymAfterUserCreate(&$user)
    {
        $automationClass = new AutomationClass();
        $automationClass->trigger('user_creation', ['userId' => $user->id]);

        $followupClass = new FollowupClass();
        $followupClass->addFollowupEmailsQueue($this->followTriggerName, $user->id);
    }

    public function matchFollowupsConditions(&$followups, $userId, $params)
    {
        $segmentClass = new SegmentClass();
        foreach ($followups as $key => $followup) {
            if (empty($followup->condition['segments_status']) || empty($followup->condition['segments'])) continue;

            $segments = $segmentClass->getByIds($followup->condition['segments']);
            if (empty($segments)) continue;

            $mustMatch = $followup->condition['segments_status'] === 'is';

            if ($mustMatch) {
                foreach ($segments as $segment) {
                    $segmentMatched = false;
                    foreach ($segment->filters as $orBlock) {
                        if ($this->isUserMatchingOr($userId, $orBlock)) {
                            $segmentMatched = true;
                            // No need to test the other OR blocks since one matched
                            break;
                        }
                    }

                    // Must match all segments and the user didn't match a segment
                    if (!$segmentMatched) {
                        unset($followups[$key]);

                        return;
                    }
                }
            } else {
                foreach ($segments as $segment) {
                    foreach ($segment->filters as $orBlock) {
                        // Must not match the segments and the user matched at least one OR of a segment
                        if ($this->isUserMatchingOr($userId, $orBlock)) {
                            unset($followups[$key]);

                            return;
                        }
                    }
                }
            }
        }
    }

    private function isUserMatchingOr($userId, $orBlock): bool
    {
        $automationHelper = new AutomationHelper();
        $automationHelper->where[] = 'user.id = '.intval($userId);
        foreach ($orBlock as $and => $andValues) {
            $and = intval($and);
            foreach ($andValues as $filterName => $options) {
                acym_trigger('onAcymProcessFilter_'.$filterName, [&$automationHelper, &$options, &$and]);
            }
        }
        $userMatchingOr = acym_loadResult($automationHelper->getQuery(['user.id']));

        return !empty($userMatchingOr);
    }

    public function onAcymAfterUserModify(&$user, &$oldUser)
    {
        if (empty($user)) return;

        $automationClass = new AutomationClass();
        $automationClass->trigger('user_modification', ['userId' => $user->id]);

        if (empty($oldUser)) return;

        $exportChanges = $this->config->get('export_data_changes', 0);
        if (!$exportChanges) return;

        $fieldsToExport = $this->config->get('export_data_changes_fields', []);
        if (empty($fieldsToExport)) return;

        $userClass = new UserClass();
        $newUser = $userClass->getOneByIdWithCustomFields($user->id);
        if (empty($newUser)) return;

        if (empty($fieldsToExport)) return;

        $fieldsToExport = explode(',', $fieldsToExport);
        $fieldClass = new FieldClass();
        $fields = $fieldClass->getByIds($fieldsToExport);

        $fieldsName = [];
        foreach ($fields as $field) {
            if ($field->name == 'ACYM_NAME') {
                $name = 'name';
            } elseif ($field->name == 'ACYM_EMAIL') {
                $name = 'email';
            } elseif ($field->name == 'ACYM_LANGUAGE') {
                $name = 'language';
            } else {
                $name = $field->name;
            }
            $fieldsName[] = $name;
        }

        if (empty($fieldsName)) return;

        $exportHelper = new ExportHelper();

        foreach ($newUser as $column => $value) {
            if (!isset($oldUser[$column])) $oldUser[$column] = '';
            if (!isset($newUser[$column])) $newUser[$column] = '';

            if ($oldUser[$column] == $newUser[$column]) continue;

            $exportHelper->exportChanges($newUser, $fieldsName, $column, $newUser[$column], $oldUser[$column]);
        }
    }

    public function getFollowupTriggers(&$triggers)
    {
        $triggers[$this->followTriggerName] = acym_translation('ACYM_SUBSCRIBER_CREATION');
    }

    public function getFollowupTriggerBlock(&$blocks)
    {
        $blocks[] = [
            'name' => acym_translation('ACYM_SUBSCRIBER_CREATION'),
            'description' => acym_translation('ACYM_SUBSCRIBER_CREATION_DESC'),
            'icon' => 'acymicon-user-plus',
            'link' => acym_completeLink('campaigns&task=edit&step=followupCondition&trigger='.$this->followTriggerName),
            'level' => 2,
            'alias' => $this->followTriggerName,
        ];
    }
}
