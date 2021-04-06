<?php

use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Classes\MailClass;
use AcyMailing\Types\DelayType;

class plgAcymStatistics extends acymPlugin
{
    public function searchMail()
    {
        $mailClass = new MailClass();
        $id = acym_getVar('int', 'id');
        if (!empty($id)) {
            $mail = acym_loadObject(
                'SELECT mail.`name`, campaign.`id` AS campaignId, mail.`id`
                FROM #__acym_mail AS mail 
                LEFT JOIN #__acym_campaign AS campaign ON mail.`id` = campaign.`mail_id` 
                WHERE mail.`id` = '.intval($id)
            );
            if (empty($mail)) {
                $name = '';
                $id = 0;
            } else {
                $mail = $mailClass->decode($mail);
                $name = $mail->name;
                if (!empty($mail->campaignId)) {
                    $name .= ' ['.acym_translation('ACYM_ID').' '.$mail->campaignId.']';
                }
                $id = $mail->id;
            }

            echo json_encode(['text' => $name, 'value' => $id]);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $search = utf8_encode($search);

        $mails = acym_loadObjectList(
            'SELECT mail.`id`, mail.`name`, mail.`subject`, mail.`type`, campaign.`id` AS campaignId 
            FROM #__acym_mail AS mail 
            LEFT JOIN #__acym_campaign AS campaign ON mail.`id` = campaign.`mail_id` 
            WHERE mail.`subject` LIKE '.acym_escapeDB('%'.$search.'%').' OR mail.`name` LIKE '.acym_escapeDB('%'.$search.'%').'
            ORDER BY mail.`name` ASC'
        );

        $mails = $mailClass->decode($mails);

        foreach ($mails as $oneMail) {
            $name = in_array($oneMail->type, $mailClass::TYPES_NO_NAME) ? $oneMail->subject : $oneMail->name;
            $campaignId = empty($oneMail->campaignId) ? '' : ' ['.acym_translation('ACYM_ID').' '.$oneMail->campaignId.']';
            $return[] = [$oneMail->id, $name.$campaignId];
        }

        echo json_encode($return);
        exit;
    }

    public function onAcymDeclareFilters(&$filters)
    {
        $status = [
            acym_selectOption('opened', 'ACYM_OPENED'),
            acym_selectOption('notopen', 'ACYM_NOTOPEN'),
            acym_selectOption('failed', 'ACYM_FAILED'),
            acym_selectOption('sent', 'ACYM_SENT'),
            acym_selectOption('notsent', 'ACYM_NOTSENT'),
            acym_selectOption('bounced', 'ACYM_BOUNCED'),
        ];

        $filters['statistics'] = new stdClass();
        $filters['statistics']->name = acym_translation('ACYM_STATISTICS');
        $filters['statistics']->option = '<div class="intext_select_automation cell">';
        $filters['statistics']->option .= acym_select(
            $status,
            'acym_action[filters][__numor__][__numand__][statistics][status]',
            'open',
            [
                'class' => 'intext_select_automation acym__select',
                'data-acym-toggle-filter' => json_encode(
                    [
                        'class' => 'acym__filter__stats_time',
                        'values' => ['opened', 'notopen'],
                    ]
                ),
            ]
        );
        $filters['statistics']->option .= '</div>';
        $filters['statistics']->option .= '<div class="intext_select_automation cell">';
        $ajaxParams = json_encode(['plugin' => __CLASS__, 'trigger' => 'searchMail',]);
        $filters['statistics']->option .= acym_select(
            [],
            'acym_action[filters][__numor__][__numand__][statistics][mail]',
            null,
            'class="acym__select acym_select2_ajax" data-placeholder="'.acym_translation('ACYM_SELECT_AN_EMAIL', true).'" data-params="'.acym_escape($ajaxParams).'"'
        );
        $filters['statistics']->option .= '</div>';

        $status = [
            acym_selectOption('opened', 'ACYM_OPENED'),
            acym_selectOption('notopen', 'ACYM_NOTOPEN'),
        ];

        $delayType = new DelayType();
        $delay = $delayType->display('[filters][__numor__][__numand__][statistics][time]', 1, 3, '__numor____numand__', 'margin-right-1');
        $filters['statistics']->option .= '<div style="display: none" class="cell grid-x acym__filter__stats_time acym_vcenter">
            <p class="cell margin-bottom-1">'.acym_translation('ACYM_ADVANCED_OPTIONS').':'.acym_info('ACYM_IF_SET_0_NO_CONDITION_ON_TIME').' </p>
            '.acym_translationSprintf('ACYM_X_AFTER_MAIL_SENT', $delay).'</div>';
    }

    public function onAcymProcessFilterCount_statistics(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_statistics($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_statistics(&$query, $options, $num)
    {
        if (empty($options['mail'])) {
            acym_enqueueMessage(acym_translation('ACYM_EMAIL_NOT_FOUND'), 'warning');

            return;
        }

        if (empty($options['status']) || !in_array($options['status'], ['opened', 'notopen', 'failed', 'bounced', 'notsent', 'sent'])) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_UNKNOWN_OPERATOR', $options['status']), 'warning');

            return;
        }

        $alias = '`stats'.$num.'`';
        $join = '#__acym_user_stat AS '.$alias.' ON '.$alias.'.`user_id` = `user`.`id` AND '.$alias.'.`mail_id` = '.intval($options['mail']);

        $query->leftjoin[$alias] = $join;

        if ($options['status'] == 'opened') {
            $where = $alias.'.`open` > 0';
        } elseif ($options['status'] == 'notopen' && !empty($options['time'])) {
            $where = $alias.'.`open` = 0 OR '.$alias.'.`user_id` IS NULL';
        } elseif ($options['status'] == 'failed') {
            $where = $alias.'.`fail` > 0';
        } elseif ($options['status'] == 'bounced') {
            $where = $alias.'.`bounce` > 0';
        } elseif ($options['status'] == 'notsent') {
            $where = $alias.'.`user_id` IS NULL';
        } elseif ($options['status'] == 'sent') {
            $where = $alias.'.`user_id` IS NOT NULL';
        }

        if (!empty($where)) $query->where[] = $where;

        if (!empty($options['time'])) {
            if ($options['status'] == 'notopen') {
                $query->where[] = $alias.'.open_date IS NULL OR '.$alias.'.open_date >= DATE_ADD('.$alias.'.send_date, INTERVAL '.intval($options['time']).' SECOND)';
            } else {
                $query->where[] = $alias.'.open_date <= DATE_ADD('.$alias.'.send_date, INTERVAL '.intval($options['time']).' SECOND)';
            }
            $query->where[] = $alias.'.`user_id` IS NOT NULL';
        }
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        foreach (['statistics'] as $filterName) {
            if (empty($automationFilter[$filterName])) continue;
            $status = acym_translation('ACYM_'.strtoupper($automationFilter[$filterName]['status']));

            $delayType = new DelayType();
            $time = empty($automationFilter[$filterName]['time']) ? '' : $delayType->get($automationFilter[$filterName]['time'], 3);

            $mailClass = new MailClass();
            $mail = $mailClass->getOneById(empty($automationFilter[$filterName]['mail']) ? 0 : $automationFilter[$filterName]['mail']);

            if (empty($mail)) {
                $automationFilter = acym_translationSprintf('ACYM_NOT_FOUND', acym_translation('ACYM_MAIL'));
            } elseif ($time) {
                $automationFilter = acym_translationSprintf(
                    'ACYM_FILTER_STATISTICS_OPEN_TIME_SUMMARY',
                    $status,
                    $mail->subject,
                    $mail->id,
                    $time->value.' '.strtolower($time->typeText)
                );
            } else {
                $automationFilter = acym_translationSprintf('ACYM_FILTER_STATISTICS_SUMMARY', $status, $mail->subject, $mail->id);
            }
        }
    }
}
