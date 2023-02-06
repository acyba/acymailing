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
            WHERE (mail.`subject` LIKE '.acym_escapeDB('%'.$search.'%').' OR mail.`name` LIKE '.acym_escapeDB('%'.$search.'%').') 
                AND mail.`type` != '.acym_escapeDB($mailClass::TYPE_TEMPLATE).'
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

    public function searchUrl()
    {
        $idMail = acym_getVar('int', 'mailId');

        if (!empty($idMail)) {
            $urls = acym_loadObjectList(
                'SELECT DISTINCT(`urlClick`.`url_id`),`url`.`name` FROM #__acym_url AS url
                JOIN #__acym_url_click AS `urlClick` ON `url`.`id` = `urlClick`.`url_id`
                WHERE `urlClick`.`mail_id` = '.intval($idMail)
            );

            $oneUrl = new stdClass();
            $oneUrl->name = acym_translation('ACYM_ANY_URL');
            $oneUrl->url_id = -1;

            //to set ACYM_ANY_URL first
            $urls = array_merge([$oneUrl], $urls);

            echo json_encode($urls);
            exit;
        }
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
            acym_selectOption('click_on_url', 'ACYM_CLICKED_ON_LINK'),
            acym_selectOption('neveropen', 'ACYM_NEVER_OPEN'),
            acym_selectOption('neverclicked', 'ACYM_NEVER_CLICKED'),
            acym_selectOption('neversent', 'ACYM_NEVER_SENT'),
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
                        [
                            'class' => 'acym__filter__stats_time',
                            'values' => ['opened', 'notopen'],
                        ],
                        [
                            'class' => 'acym__filter__stats_url',
                            'values' => ['click_on_url'],
                        ],
                        [
                            'class' => 'acym__filter__stats_mail',
                            'values' => ['opened', 'notopen', 'failed', 'sent', 'notsent', 'bounced', 'click_on_url'],
                        ],
                    ]
                ),
            ]
        );
        $filters['statistics']->option .= '</div>';

        $onchange = "
        if (this.options[this.selectedIndex] !== undefined) {
            let mailId = this.options[this.selectedIndex].value;
            let ajaxUrl = ACYM_AJAX_URL + '&ctrl=dynamics&task=trigger&plugin=plgAcymStatistics&trigger=searchUrl&mailId=' + mailId;
            let urlSelect = jQuery(this).parent().parent().find('.acym__select_url');
    
            let filters;
            let or;
            let and;
            let urlId = null;
            if (jQuery('#acym__segments__filters').val() !== undefined && jQuery('#acym__segments__filters').val() !== '') {
                filters = JSON.parse(jQuery('#acym__segments__filters').val());
                and = jQuery(this).closest('.acym__segments__inserted__filter').attr('data-and');
                or = jQuery(this).closest('[data-filter-number]').attr('data-filter-number');
            } else if (jQuery('#filters').val() !== undefined) {
                filters = JSON.parse(jQuery('#filters').val());
                or = urlSelect.closest('.acym__automation__group__filter').attr('data-filter-number');
                and = urlSelect.closest('.acym__automation__inserted__filter').attr('data-and');
            }
            if (filters !== undefined && filters[or] !== undefined && filters[or][and] !== undefined) urlId = filters[or][and].statistics.urlId;
    
            urlSelect.empty();
            jQuery.get(ajaxUrl, function (response) {
                response = acym_helper.parseJson(response);
                if (response.length === undefined) return;
                response.forEach(url => {
                    let option = document.createElement('option');
                    option.value = url.url_id;
                    option.text = url.name;
                    option.selected = url.url_id == urlId;
                    urlSelect.append(option);
                });
                urlSelect.trigger('change');
            });
        }";

        $filters['statistics']->option .= '<div class="intext_select_automation cell acym__filter__stats_mail">';
        $ajaxParams = json_encode(['plugin' => __CLASS__, 'trigger' => 'searchMail']);
        $filters['statistics']->option .= acym_select(
            [],
            'acym_action[filters][__numor__][__numand__][statistics][mail]',
            null,
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('ACYM_SELECT_AN_EMAIL'),
                'data-params' => $ajaxParams,
                'onchange' => $onchange,
            ]
        );
        $filters['statistics']->option .= '</div>';

        $filters['statistics']->option .= '<div class="intext_select_automation cell acym__filter__stats_url">';
        $filters['statistics']->option .= acym_select(
            [],
            'acym_action[filters][__numor__][__numand__][statistics][urlId]',
            null,
            [
                'class' => 'intext_select_automation acym__select acym__select_url',
                'data-placeholder' => acym_translation('ACYM_ANY_URL'),
            ]
        );
        $filters['statistics']->option .= '</div>';

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

        $alias = '`stats'.$num.'`';
        $urlClickAlias = '`urlClick'.$num.'`';

        if (empty($options['mail'])) {
            if (!empty($options['status'])) {
                switch ($options['status']) {
                    case 'neveropen':
                        $query->join[] = '(SELECT user_id , SUM(open) AS sumOpen FROM #__acym_user_stat GROUP BY user_id) AS '.$alias.' ON user.id = '.$alias.'.user_id AND '.$alias.'.sumOpen = 0';
                        break;
                    case 'neverclicked':
                        $query->leftjoin[] = '#__acym_url_click AS '.$urlClickAlias.' ON user.id = '.$urlClickAlias.'.user_id';
                        $query->where[] = $urlClickAlias.'.user_id IS NULL';
                        break;
                    case 'neversent':
                        $query->leftjoin[] = '#__acym_user_stat AS '.$alias.' ON user.id = '.$alias.'.user_id';
                        $query->where[] = $alias.'.user_id IS NULL';
                        break;
                }
            } else {
                acym_enqueueMessage(acym_translation('ACYM_EMAIL_NOT_FOUND'), 'warning');
            }

            return;
        }

        if (empty($options['status']) || !in_array($options['status'], ['opened', 'notopen', 'failed', 'bounced', 'notsent', 'sent', 'click_on_url'])) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_UNKNOWN_OPERATOR', $options['status']), 'warning');

            return;
        }

        if ($options['status'] == 'click_on_url') {
            $query->join[] = '#__acym_url_click AS '.$urlClickAlias.' ON `user`.`id` = '.$urlClickAlias.'.`user_id`';
        } else {
            $query->leftjoin[] = '#__acym_user_stat AS '.$alias.' ON '.$alias.'.`user_id` = `user`.`id` AND '.$alias.'.`mail_id` = '.intval($options['mail']);
        }

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
        } elseif ($options['status'] == 'click_on_url' && !empty($options['urlId']) && $options['urlId'] != -1) {
            $where = $urlClickAlias.'.`url_id` = '.intval($options['urlId']);
        }

        if (!empty($where)) $query->where[] = $where;

        if (!empty($options['time']) && in_array($options['status'], ['opened', 'notopen'])) {
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

            if ($status == 'ACYM_NEVEROPEN') {
                $automationFilter = acym_translation('ACYM_NEVER_OPEN_SUMMARY');
            } elseif ($status == 'ACYM_NEVERCLICKED') {
                $automationFilter = acym_translation('ACYM_NEVER_CLICKED_SUMMARY');
            } elseif ($status == 'ACYM_NEVERSENT') {
                $automationFilter = acym_translation('ACYM_NEVER_SENT_SUMMARY');
            } else {
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
                    $action = acym_translation('ACYM_STATUS');
                    if (!empty($automationFilter[$filterName]['urlId'])) {
                        $action = strtolower(acym_translation('ACYM_CLICKED'));
                        if ($automationFilter[$filterName]['urlId'] != -1) {
                            $url = acym_loadObject(
                                'SELECT `url`.`name` 
                            FROM #__acym_url AS `url`
                            WHERE `url`.`id` = '.intval($automationFilter[$filterName]['urlId'])
                            );
                            $status = $url->name;
                        } else {
                            $status = strtolower(acym_translation('ACYM_ANY_URL'));
                        }
                    }
                    $automationFilter = acym_translationSprintf('ACYM_FILTER_STATISTICS_SUMMARY', $action, $status, $mail->subject, $mail->id);
                }
            }
        }
    }
}
