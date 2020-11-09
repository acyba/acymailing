<?php

use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Classes\MailClass;

class plgAcymStatistics extends acymPlugin
{
    public function searchMail()
    {
        $id = acym_getVar('int', 'id');
        if (!empty($id)) {
            $mail = acym_loadObject(
                'SELECT mail.`subject`, campaign.`id` AS campaignId 
                FROM #__acym_mail AS mail 
                LEFT JOIN #__acym_campaign AS campaign ON mail.`id` = campaign.`mail_id` 
                WHERE mail.`id` = '.intval($id)
            );
            if (empty($mail)) {
                $subject = '';
            } else {
                $subject = $mail->subject;
                if (!empty($mail->campaignId)) {
                    $subject .= ' ['.acym_translation('ACYM_ID').' '.$mail->campaignId.']';
                }
            }
            echo json_encode(['value' => $subject]);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $search = utf8_encode($search);

        $mails = acym_loadObjectList(
            'SELECT mail.`id`, mail.`subject`, campaign.`id` AS campaignId 
            FROM #__acym_mail AS mail 
            LEFT JOIN #__acym_campaign AS campaign ON mail.`id` = campaign.`mail_id` 
            WHERE mail.`subject` LIKE '.acym_escapeDB('%'.$search.'%').' OR mail.`name` LIKE '.acym_escapeDB('%'.$search.'%').' 
            ORDER BY mail.`subject` ASC'
        );

        $mailClass = new MailClass();
        $mails = $mailClass->decode($mails);

        foreach ($mails as $oneMail) {
            $campaignId = empty($oneMail->campaignId) ? '' : ' ['.acym_translation('ACYM_ID').' '.$oneMail->campaignId.']';
            $return[] = [$oneMail->id, $oneMail->subject.$campaignId];
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
        $filters['statistics']->option .= acym_select($status, 'acym_action[filters][__numor__][__numand__][statistics][status]', 'open', 'class="intext_select_automation acym__select"');
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
    }

    public function onAcymProcessFilterCount_statistics(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_statistics($query, $options, $num);

        return acym_translation_sprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_statistics(&$query, $options, $num)
    {
        if (empty($options['mail'])) {
            acym_enqueueMessage(acym_translation('ACYM_EMAIL_NOT_FOUND'), 'warning');

            return;
        }

        if (empty($options['status']) || !in_array($options['status'], ['opened', 'notopen', 'failed', 'bounced', 'notsent', 'sent'])) {
            acym_enqueueMessage(acym_translation_sprintf('ACYM_UNKNOWN_OPERATOR', $options['status']), 'warning');

            return;
        }

        $alias = '`stats'.$num.'`';
        $join = '#__acym_user_stat AS '.$alias.' ON '.$alias.'.`user_id` = `user`.`id` AND '.$alias.'.`mail_id` = '.intval($options['mail']);

        $query->leftjoin[$alias] = $join;

        if ($options['status'] == 'opened') {
            $where = $alias.'.`open` > 0';
        } elseif ($options['status'] == 'notopen') {
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
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        if (!empty($automationFilter['statistics'])) {
            $status = acym_translation('ACYM_'.strtoupper($automationFilter['statistics']['status']));

            $mailClass = new MailClass();
            $mail = $mailClass->getOneById(empty($automationFilter['statistics']['mail']) ? 0 : $automationFilter['statistics']['mail']);

            if (empty($mail)) {
                $automationFilter = acym_translation_sprintf('ACYM_NOT_FOUND', acym_translation('ACYM_MAIL'));
            } else {
                $automationFilter = acym_translation_sprintf('ACYM_FILTER_STATISTICS_SUMMARY', $status, $mail->subject, $mail->id);
            }
        }
    }
}
