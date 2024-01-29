<?php

namespace AcyMailing\Controllers\Users;

use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\HistoryClass;
use AcyMailing\Classes\UrlClickClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\UserStatClass;
use AcyMailing\Helpers\EntitySelectHelper;
use AcyMailing\Helpers\TabHelper;

trait Edition
{
    public function edit()
    {
        acym_setVar('layout', 'edit');

        $data = [];
        $data['tab'] = new TabHelper();

        $userId = acym_getVar('int', 'userId', 0);

        if (!$this->prepareUserEdit($data, $userId)) return;
        $this->prepareEntitySelectEdit($data, $userId);
        $this->prepareUserFieldsEdit($data, $userId);
        $this->prepareSubscriptionsEdit($data, $userId);
        $this->prepareStatsEdit($data, $userId);
        $this->prepareHistoryEdit($data, $userId);
        $this->prepareMailHistory($data, $userId);
        $this->prepareFieldsEdit($data);

        parent::display($data);
    }

    private function prepareUserEdit(&$data, $userId)
    {
        if (empty($userId)) {
            $data['user-information'] = new \stdClass();
            $data['user-information']->name = '';
            $data['user-information']->email = '';
            $data['user-information']->active = '1';
            $data['user-information']->confirmed = '1';
            $data['user-information']->cms_id = null;
            $data['user-information']->tracking = 1;
            $data['user-information']->language = '';

            $this->breadcrumb[acym_escape(acym_translation('ACYM_NEW_SUBSCRIBER'))] = acym_completeLink('users&task=edit');
        } else {
            $userClass = new UserClass();
            $data['user-information'] = $userClass->getOneById($userId);

            if (empty($data['user-information']) || !$userClass->hasUserAccess($userId)) {
                acym_enqueueMessage(acym_translation('ACYM_USER_NOT_FOUND'), 'error');
                $this->listing();

                return false;
            }

            $this->breadcrumb[acym_escape($data['user-information']->email)] = acym_completeLink('users&task=edit&userId='.$userId);
        }

        if (empty($data['user-information']->language)) {
            $data['user-information']->language = acym_getLanguageTag();
        }

        return true;
    }

    private function prepareEntitySelectEdit(&$data, $userId)
    {
        if (empty($userId)) return;

        $entityHelper = new EntitySelectHelper();

        $columnsToDisplay = $entityHelper->getColumnsForList('userlist.list_id');

        $data['entityselect'] = acym_modal(
            acym_translation('ACYM_MANAGE_SUBSCRIPTION'),
            $entityHelper->entitySelect('list', ['join' => 'join_user-'.$userId], $columnsToDisplay, ['text' => acym_translation('ACYM_CONFIRM'), 'action' => 'apply']),
            null,
            '',
            'class="cell medium-6 large-shrink button button-secondary"'
        );
    }

    private function prepareUserFieldsEdit(&$data, $userId)
    {
        $data['fieldsValues'] = [];

        if (empty($userId)) return;

        $fieldClass = new FieldClass();
        $fieldsValues = $fieldClass->getFieldsValueByUserId($userId);
        foreach ($fieldsValues as $one) {
            $data['fieldsValues'][$one->field_id] = $one->value;
        }
    }

    private function prepareSubscriptionsEdit(&$data, $userId)
    {
        $data['subscriptionsIds'] = [];
        $data['subscriptions'] = [];
        $data['unsubscribe'] = [];

        if (empty($userId)) return;

        $data['allSubscriptions'] = $this->currentClass->getUserSubscriptionById($userId);

        foreach ($data['allSubscriptions'] as $sub) {
            if ($sub->status == 1) {
                $data['subscriptions'][] = $sub;
            } else {
                $data['unsubscribe'][] = $sub;
            }
        }

        $data['subscriptionsIds'] = [];

        if (!empty($data['subscriptions'])) {
            $data['subscriptionsIds'] = [];
            foreach ($data['subscriptions'] as $list) {
                $data['subscriptionsIds'][] = $list->id;
            }

            acym_arrayToInteger($data['subscriptionsIds']);
        }
    }

    private function prepareStatsEdit(&$data, $userId)
    {
        $data['percentageOpen'] = 0;
        $data['percentageClick'] = 0;

        if (empty($userId)) return;

        $userStatClass = new UserStatClass();
        $userStatFromDB = $userStatClass->getAllUserStatByUserId($userId);

        if (empty($userStatFromDB)) return;

        $userStat = new \stdClass();
        $userStat->totalSent = 0;
        $userStat->open = 0;
        $userStat->click = 0;

        foreach ($userStatFromDB as $oneStat) {
            if ($oneStat->sent > 0) $userStat->totalSent++;
            if ($oneStat->open > 0) $userStat->open++;
        }

        $urlClickClass = new UrlClickClass();
        $clickStats = $urlClickClass->getUserMailsClicked($userId);

        foreach ($clickStats as $oneStat) {
            if (!empty($oneStat->click)) {
                $userStat->click++;
            }
        }

        $userStat->percentageOpen = empty($userStat->open) || empty($userStat->totalSent) ? 0 : intval(($userStat->open * 100) / $userStat->totalSent);
        $userStat->percentageClick = empty($userStat->click) || empty($userStat->totalSent) ? 0 : intval(($userStat->click * 100) / $userStat->totalSent);

        $data['percentageOpen'] = number_format($userStat->percentageOpen, 2);
        $data['percentageClick'] = number_format($userStat->percentageClick, 2);
    }

    private function prepareMailHistory(&$data, $userId)
    {
        if (empty($userId)) return;
        $data['userMailHistory'] = $this->currentClass->getMailHistory($userId);
    }

    private function prepareHistoryEdit(&$data, $userId)
    {
        if (empty($userId)) return;

        $historyClass = new HistoryClass();
        $data['userHistory'] = $historyClass->getHistoryOfOneById($userId);
        foreach ($data['userHistory'] as &$oneHistory) {
            if (!empty($oneHistory->data)) {
                $historyData = explode("\n", $oneHistory->data);
                $details = '<div><h5>'.acym_translation('ACYM_DETAILS').'</h5><br />';
                if (!empty($oneHistory->mail_id)) {
                    $details .= '<b>'.acym_translation('NEWSLETTER').' : </b>';
                    $details .= acym_escape($oneHistory->subject).' ( '.acym_translation('ACYM_ID').' : '.$oneHistory->mail_id.' )<br />';
                }

                foreach ($historyData as $value) {
                    if (!strpos($value, '::')) {
                        $details .= $value.'<br />';
                        continue;
                    }
                    [$part1, $part2] = explode('::', $value);
                    if (preg_match('#^[A-Z_]*$#', $part2)) $part2 = acym_translation($part2);
                    $details .= '<b>'.acym_escape(acym_translation($part1)).' : </b>'.acym_escape($part2).'<br />';
                }
                if ($oneHistory->action === 'unsubscribed') {
                    $details .= acym_translation('ACYM_UNSUBSCRIBE_REASON');
                    if (empty(acym_escape($oneHistory->unsubscribe_reason))) {
                        $details .= ' '.acym_translation('ACYM_NO_REASON_SET_BY_USER');
                    } else {
                        $details .= ' '.acym_escape($oneHistory->unsubscribe_reason);
                    }
                }

                $details .= '</div>';

                $oneHistory->data = acym_modal(
                    acym_translation('ACYM_VIEW_DETAILS'),
                    $details,
                    null,
                    'style="word-break: break-word;"',
                    'class="history_details"',
                    true,
                    false
                );
            }

            if (!empty($oneHistory->source)) {
                $source = explode("\n", $oneHistory->source);
                $details = '<div><h5>'.acym_translation('ACYM_SOURCE').'</h5><br />';
                foreach ($source as $value) {
                    if (!strpos($value, '::')) continue;
                    [$part1, $part2] = explode('::', $value);
                    $details .= '<b>'.acym_escape($part1).' : </b>'.acym_escape($part2).'<br />';
                }
                $details .= '</div>';

                $oneHistory->source = acym_modal(
                    acym_translation('ACYM_VIEW_SOURCE'),
                    $details,
                    null,
                    'style="word-break: break-word;"',
                    'class="history_details"'
                );
            }
        }
    }

    protected function prepareFieldsEdit(&$data, $fieldVisibility = 'backend_edition')
    {
        $data['allFields'] = [];

        $fieldClass = new FieldClass();
        $fieldsElements = $fieldClass->getMatchingElements();
        $allFields = $fieldsElements['elements'];
        $languageFieldId = $fieldClass->getLanguageFieldId();

        foreach ($allFields as $one) {
            $one->option = empty($one->option) ? new \stdClass() : json_decode($one->option);
            $one->value = empty($one->value) ? '' : json_decode($one->value);
            $fieldDB = empty($one->option->fieldDB) ? '' : json_decode($one->option->fieldDB);

            // Keep this code, search for data-display-optional for more info
            //$displayIf = empty($one->option->display) ? '' : 'data-display-optional="'.acym_escape($one->option->display).'"';

            $valuesArray = [];
            if (!empty($one->value)) {
                foreach ($one->value as $value) {
                    $valueTmp = new \stdClass();
                    $valueTmp->text = $value->title;
                    $valueTmp->value = $value->value;
                    if ($value->disabled == 'y') $valueTmp->disable = true;
                    $valuesArray[$value->value] = $valueTmp;
                }
            }
            if (!empty($fieldDB) && !empty($fieldDB->value)) {
                $fromDB = $fieldClass->getValueFromDB($fieldDB);
                foreach ($fromDB as $value) {
                    $valuesArray[$value->value] = $value->title;
                }
            }

            $one->display = empty($one->option->display) ? '' : json_decode($one->option->display);
            $data['allFields'][$one->id] = $one;
            if ($one->id == 1) {
                $defaultValue = empty($data['user-information']->id) ? '' : $data['user-information']->name;
            } elseif ($one->id == 2) {
                $defaultValue = empty($data['user-information']->id) ? '' : $data['user-information']->email;
            } elseif ($one->id == $languageFieldId) {
                $defaultValue = empty($data['user-information']->id) ? acym_getLanguageTag() : $data['user-information']->language;
            } elseif (isset($data['fieldsValues'][$one->id]) && (((is_array($data['fieldsValues'][$one->id]) || $data['fieldsValues'][$one->id] instanceof Countable) && count(
                            $data['fieldsValues'][$one->id]
                        ) > 0) || (is_string($data['fieldsValues'][$one->id]) && strlen($data['fieldsValues'][$one->id]) > 0))) {
                $decoded = json_decode($data['fieldsValues'][$one->id]);
                $defaultValue = is_null($decoded) ? $data['fieldsValues'][$one->id] : $decoded;
            } else {
                $defaultValue = $one->default_value;
            }
            $size = empty($one->option->size) ? '' : 'width:'.$one->option->size.'px';

            $data['allFields'][$one->id]->html = $fieldClass->displayField($one, $defaultValue, $size, $valuesArray, true, !acym_isAdmin(), null, $one->$fieldVisibility);
        }
    }

    public function save()
    {
        $this->apply(true);
    }

    public function apply($listing = false)
    {
        $userInformation = acym_getVar('array', 'user');
        $userId = acym_getVar('int', 'userId');
        $listsToAdd = json_decode(acym_getVar('string', 'acym__entity_select__selected', '{}'));
        $listsToUnsub = json_decode(acym_getVar('string', 'acym__entity_select__unselected', '{}'));

        $user = new \stdClass();
        $user->name = $userInformation['name'];
        $user->email = $userInformation['email'];
        if (!empty($userInformation['language'])) {
            $user->language = $userInformation['language'];
        }
        $user->active = $userInformation['active'];
        $user->confirmed = $userInformation['confirmed'];
        $user->tracking = $userInformation['tracking'];
        $customFields = acym_getVar('array', 'customField');

        preg_match('/'.acym_getEmailRegex().'/i', $user->email, $matches);

        if (empty($matches)) {
            $this->edit();
            acym_enqueueMessage(acym_translationSprintf('ACYM_VALID_EMAIL', $user->email), 'error');

            return;
        }

        $userClass = new UserClass();
        $frontCreation = false;
        $existingUser = $userClass->getOneByEmail($user->email);
        if (empty($userId)) {
            if (!empty($existingUser) && acym_isAdmin()) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_X_ALREADY_EXIST', $user->email), 'error');

                $this->edit();

                return;
            } elseif (!empty($existingUser)) {
                $userId = $existingUser->id;
            } else {
                $user->creation_date = acym_date('now', 'Y-m-d H:i:s', false);
                $userId = $userClass->save($user, $customFields);
                $frontCreation = true;
            }
            acym_setVar('userId', $userId);
        } else {
            if (!empty($existingUser) && $existingUser->id != $userId) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_X_ALREADY_EXIST', $user->email), 'error');
                $this->edit();

                return;
            }

            if (!$userClass->hasUserAccess($userId)) {
                die('Access denied for the modification of this user');
            }

            $user->id = $userId;
            $userClass->save($user, $customFields);
        }

        if (!empty($listsToAdd)) {
            $this->subscribeUser(false, $frontCreation);
        }
        if (!empty($listsToUnsub)) {
            $userClass->unsubscribeOnSubscriptions($userId, $listsToUnsub);
        }

        if ($listing) {
            $this->listing();
        } else {
            $this->edit();
        }
    }
}
