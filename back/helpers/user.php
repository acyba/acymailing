<?php

class acymuserHelper extends acymObject
{
    public function exportdata($id)
    {
        if (empty($id)) {
            die('No user found');
        }

        $userClass = acym_get('class.user');
        $user = $userClass->getOneByIdWithCustomFields($id);
        if (empty($user)) {
            die('No user found');
        }

        //We display errors... it will be easier to fix if a client has an issue!
        acym_enqueueMessage(acym_translation('ACYM_ERROR'), 'error');

        $dateFields = ['creation_date', 'userstats_open_date', 'userstats_send_date', 'urlclick_date_click'];
        $excludedFields = ['id', 'cms_id', 'key', 'source'];

        // It will contain the generated xml with all the collected user data, and the files he uploaded
        $exportFiles = [];

        $xml = new SimpleXMLElement('<xml/>');
        $userNode = $xml->addChild('user');

        $fields = acym_loadObjectList('SELECT name, field.option as options, type, value FROM #__acym_field as field', 'name');
        $uploadFolder = trim(acym_cleanPath(html_entity_decode(acym_getFilesFolder(true))), DS.' ').DS;


        // User fields and global statistics
        foreach ($user as $column => $value) {
            if (in_array($column, $excludedFields) || strlen($value) == 0) {
                continue;
            }
            if (in_array($column, $dateFields)) {
                if (empty($value)) {
                    continue;
                }
                $value = acym_getDate($value, '%Y-%m-%d %H:%M:%S');
            }

            if (empty($fields[$column])) {
                $userNode->addChild($column, acym_escape($value));
                continue;
            }

            if ($fields[$column]->type == 'date') {
                $valueTmp = is_array(json_decode($value)) ? json_decode($value) : $value;
                $value = is_array($valueTmp) ? implode('/', $valueTmp) : $valueTmp;
            }

            if (in_array($fields[$column]->type, ["multiple_dropdown", "single_dropdown", "radio", "checkbox"])) {
                $selectedValues = [];
                if (!empty($fields[$column]->value)) {
                    $options = json_decode($fields[$column]->value);
                }
                $valueTmp = is_array(json_decode($value)) || is_object(json_decode($value)) ? json_decode($value) : $value;
                if (!is_array($valueTmp) && !is_string($valueTmp)) {
                    foreach ($valueTmp as $key => $one) {
                        $selectedValues[] = $key;
                    }
                } else {
                    foreach ($options as $key => $optionVal) {
                        if (is_string($valueTmp) && $optionVal->value == $valueTmp) {
                            $selectedValues[] = !empty($optionVal->title) ? $optionVal->title : $optionVal->value;
                        } elseif (!is_string($valueTmp) && in_array($optionVal->value, $valueTmp)) {
                            $selectedValues[] = !empty($optionVal->title) ? $optionVal->title : $optionVal->value;
                        }
                    }
                }

                $value = implode(',', $selectedValues);
            } elseif (in_array($fields[$column]->type, ['gravatar', 'file'])) {
                $data = acym_fileGetContent(ACYM_ROOT.$uploadFolder.'userfiles'.DS.$value);
                $value = str_replace('_', ' ', substr($value, strpos($value, '_')));
                $exportFiles[] = ['name' => $value, 'data' => $data];
                continue;
            }

            $userNode->addChild($column, acym_escape($value));
        }

        // Subscription statuses
        $subscription = acym_loadObjectList('SELECT list.name, list.id, user_list.subscription_date, user_list.unsubscribe_date, user_list.status FROM #__acym_user_has_list AS user_list JOIN #__acym_list AS list ON list.id = user_list.list_id WHERE user_list.user_id = '.intval($id));
        if (!empty($subscription)) {
            $dateFields = ['subscription_date', 'unsubscribe_date'];
            $subscriptionNode = $xml->addChild('subscription');

            foreach ($subscription as $oneSubscription) {
                $list = $subscriptionNode->addChild('list');

                $oneSubscription = get_object_vars($oneSubscription);
                foreach ($oneSubscription as $column => $value) {
                    if (strlen($value) == 0) {
                        continue;
                    }
                    if (in_array($column, $dateFields)) {
                        if (empty($value)) {
                            continue;
                        }
                        $value = acym_getDate($value, '%Y-%m-%d %H:%M:%S');
                    }
                    if ($column == 'status') {
                        $value = str_replace(['-1', '1', '2'], ['Unsubscribed', 'Subscribed', 'Waiting for confirmation'], $value);
                    }
                    $list->addChild($column, acym_escape($value));
                }
            }
        }

        // Geolocation data
        //$geolocation = acym_loadObjectList('SELECT * FROM #__acym_geolocation WHERE geolocation_subid = '.intval($id));
        //if (!empty($geolocation)) {
        //    $dateFields = array('geolocation_created');
        //    $excludedFields = array('geolocation_id', 'geolocation_subid');
        //    $geolocNode = $xml->addChild('geolocation');
        //
        //    foreach ($geolocation as $onePosition) {
        //        $position = $geolocNode->addChild('position');
        //
        //        $onePosition = get_object_vars($onePosition);
        //        foreach ($onePosition as $column => $value) {
        //            if (in_array($column, $excludedFields) || strlen($value) == 0) {
        //                continue;
        //            }
        //            if (in_array($column, $dateFields)) {
        //                if (empty($value)) {
        //                    continue;
        //                }
        //                $value = acym_getDate($value, '%Y-%m-%d %H:%M:%S');
        //            }
        //
        //            if ($column == 'geolocation_created') {
        //                $column = 'date';
        //            }
        //            if ($column == 'geolocation_type') {
        //                $column = 'event';
        //            }
        //            $position->addChild(str_replace('geolocation_', '', $column), acym_escape($value));
        //        }
        //    }
        //}

        // History
        // $mailClass = acym_get('class.mail');
        //$history = $mailClass->decode(acym_loadObjectList(
        //    'SELECT h.action, h.date, h.ip, h.data, h.source, m.subject AS newsletter
        //									FROM #__acym_history AS h
        //									LEFT JOIN #__acym_mail AS m ON h.mailid = m.mailid
        //									WHERE h.subid = '.intval($id)
        //));
        //if (!empty($history)) {
        //    $dateFields = array('date');
        //    $historyNode = $xml->addChild('history');
        //
        //    foreach ($history as $oneEvent) {
        //        $event = $historyNode->addChild('event');
        //
        //        $oneEvent = get_object_vars($oneEvent);
        //        foreach ($oneEvent as $column => $value) {
        //            if (empty($value)) {
        //                continue;
        //            }
        //            if (in_array($column, $dateFields)) {
        //                if (empty($value)) {
        //                    continue;
        //                }
        //                $value = acym_getDate($value, '%Y-%m-%d %H:%M:%S');
        //            }
        //
        //            $event->addChild($column, acym_escape($value));
        //        }
        //    }
        //}

        // Statistics
        $mailClass = acym_get('class.mail');
        $statistics = $mailClass->decode(acym_loadObjectList('SELECT mail.subject, user_stats.* FROM #__acym_user_stat AS user_stats JOIN #__acym_mail AS mail ON mail.id = user_stats.mail_id WHERE user_stats.user_id = '.intval($id)));
        if (!empty($statistics)) {
            $dateFields = ['send_date', 'open_date'];
            $excludedFields = ['user_id'];
            $statisticsNode = $xml->addChild('statistics');

            foreach ($statistics as $oneStat) {
                $detailedStat = $statisticsNode->addChild('email');

                $oneStat = get_object_vars($oneStat);
                foreach ($oneStat as $column => $value) {
                    if (in_array($column, $excludedFields) || strlen($value) == 0) {
                        continue;
                    }
                    if (in_array($column, $dateFields)) {
                        if (empty($value)) {
                            continue;
                        }
                        $value = acym_getDate($value, '%Y-%m-%d %H:%M:%S');
                    }

                    $detailedStat->addChild($column, acym_escape($value));
                }
            }
        }

        // Click statistics
        $clickStats = acym_loadObjectList('SELECT url.url, url_click.date_click FROM #__acym_url_click AS url_click JOIN #__acym_url AS url ON url.id = url_click.url_id WHERE url_click.user_id = '.intval($id)); //todo ip à ajouter
        if (!empty($clickStats)) {
            $dateFields = ['date_click'];
            $clickStatsNode = $xml->addChild('click_statistics');

            foreach ($clickStats as $oneClick) {
                $click = $clickStatsNode->addChild('click');

                $oneClick = get_object_vars($oneClick);
                foreach ($oneClick as $column => $value) {
                    if (strlen($value) == 0) {
                        continue;
                    }
                    if (in_array($column, $dateFields)) {
                        if (empty($value)) {
                            continue;
                        }
                        $value = acym_getDate($value, '%Y-%m-%d %H:%M:%S');
                    }

                    $click->addChild($column, acym_escape($value));
                }
            }
        }

        $exportFiles[] = ['name' => 'user_data.xml', 'data' => $xml->asXML()];

        // Create the zip
        $tempFolder = ACYM_MEDIA.'tmp'.DS;
        acym_createArchive($tempFolder.'export_data_user_'.$id, $exportFiles);

        // Export the zip
        $exportHelper = acym_get('helper.export');
        $exportHelper->setDownloadHeaders('export_data_user_'.$id, 'zip');
        readfile($tempFolder.'export_data_user_'.$id.'.zip');

        // Avoid issue when user cancels the download
        ignore_user_abort(true);
        // Delete the temp zip file
        unlink($tempFolder.'export_data_user_'.$id.'.zip');
        exit;
    }
}
