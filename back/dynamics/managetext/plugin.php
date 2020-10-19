<?php

use AcyMailing\Classes\MailClass;
use AcyMailing\Helpers\CronHelper;
use AcyMailing\Libraries\acymPlugin;

class plgAcymManagetext extends acymPlugin
{
    public function replaceContent(&$email, $send = true)
    {
        $this->_replaceConstant($email);
        $this->_replaceRandom($email);
        $this->_handleAnchors($email);
    }

    public function replaceUserInformation(&$email, &$user, $send = true)
    {
        $this->pluginHelper->cleanHtml($email->body);
        $this->pluginHelper->replaceVideos($email->body);

        $this->_removetext($email);
        $this->_ifstatement($email, $user);
    }

    //Replace tags such as {const:CONTANT_NAME} or {trans:MY_TRANSLATION}
    private function _replaceConstant(&$email)
    {
        //load the tags
        $tags = $this->pluginHelper->extractTags($email, '(?:const|trans|config)');
        if (empty($tags)) {
            return;
        }

        $tagsReplaced = [];
        foreach ($tags as $i => $oneTag) {
            $val = '';
            // Complex tag (contains "|param1|param2|...") for dynamic text with values to replace
            $arrayVal = [];
            foreach ($oneTag as $valname => $oneValue) {
                if ($valname == 'id') {
                    $val = trim(strip_tags($oneValue));
                } elseif ($valname != 'default') {
                    // Recreate tags (not before to prevent premature end of tag when parsing)
                    $arrayVal[] = '{'.$valname.'}';
                }
            }

            if (empty($val)) {
                continue;
            }
            $tagValues = explode(':', $i);
            $type = ltrim($tagValues[0], '{');
            if ($type == 'const') {
                $tagsReplaced[$i] = defined($val) ? constant($val) : 'Constant not defined : '.$val;
            } elseif ($type == 'config') {
                //We do not allow all config values... for now only sitename.
                if ($val == 'sitename') {
                    $tagsReplaced[$i] = acym_getCMSConfig($val);
                }
            } else {
                static $done = false;
                if (!$done && strpos($val, 'COM_USERS') !== false) {
                    $done = true;
                    //Com_users? Let's load the com_users language file then!

                    acym_loadLanguageFile('com_users');
                }
                if (!empty($arrayVal)) {
                    $tagsReplaced[$i] = nl2br(vsprintf(acym_translation($val), $arrayVal));
                } else {
                    $tagsReplaced[$i] = acym_translation($val);
                }
            }
        }

        //We replace standard tags
        $this->pluginHelper->replaceTags($email, $tagsReplaced, true);
    }

    private function _replaceRandom(&$email)
    {
        $randTag = $this->pluginHelper->extractTags($email, "rand");
        if (empty($randTag)) {
            return;
        }
        foreach ($randTag as $oneRandTag) {
            $results[$oneRandTag->id] = explode(';', $oneRandTag->id);
            $randNumber = rand(0, count($results[$oneRandTag->id]) - 1);
            $results[$oneRandTag->id][count($results[$oneRandTag->id])] = $results[$oneRandTag->id][$randNumber];
        }

        $tags = [];
        foreach (array_keys($results) as $oneResult) {
            $tags['{rand:'.$oneResult.'}'] = end($results[$oneResult]);
        }

        if (empty($tags)) {
            return;
        }
        $this->pluginHelper->replaceTags($email, $tags, true);
    }


    private function _ifstatement(&$email, $user, $loop = 1)
    {
        if (isset($this->noIfStatementTags[$email->id])) {
            return;
        }

        $isAdmin = acym_isAdmin();

        if ($loop > 3) {
            if ($isAdmin) {
                acym_display('You cannot have more than 3 nested {if} tags.', 'warning');
            }

            return;
        }

        //Handle if statements...
        $match = '#{if:(((?!{if).)*)}(((?!{if).)*){/if}#Uis';
        $variables = ['subject', 'body', 'altbody', 'From', 'FromName', 'ReplyTo'];
        $found = false;
        foreach ($variables as $var) {
            if (empty($email->$var)) continue;

            if (is_array($email->$var)) {
                foreach ($email->$var as $i => &$arrayField) {
                    if (empty($arrayField) || !is_array($arrayField)) continue;

                    foreach ($arrayField as $key => &$oneval) {
                        $found = preg_match_all($match, $oneval, $results[$var.$i.'-'.$key]) || $found;
                        if (empty($results[$var.$i.'-'.$key][0])) unset($results[$var.$i.'-'.$key]);
                    }
                }
            } else {
                $found = preg_match_all($match, $email->$var, $results[$var]) || $found;
                //we unset the results so that we won't handle it later... it will save some memory and processing
                if (empty($results[$var][0])) unset($results[$var]);
            }
        }

        //If we didn't find anything...
        if (!$found) {
            if ($loop == 1) {
                $this->noIfStatementTags[$email->id] = true;
            }

            return;
        }

        //Handles error messages
        static $a = false;

        $tags = [];
        foreach ($results as $var => $allresults) {
            foreach ($allresults[0] as $i => $oneTag) {
                //Don't need to process twice a tag we already have!
                if (isset($tags[$oneTag])) {
                    continue;
                }
                //We explode each argument of the tag
                $allresults[1][$i] = html_entity_decode($allresults[1][$i]);
                if (!preg_match('#^(.+)(!=|<|>|&gt;|&lt;|!~)([^=!<>~]+)$#is', $allresults[1][$i], $operators) && !preg_match('#^(.+)(=|~)([^=!<>~]+)$#is', $allresults[1][$i], $operators)) {
                    if ($isAdmin) {
                        acym_enqueueMessage(acym_translation_sprintf('ACYM_OPERATION_NOT_FOUND', $allresults[1][$i]), 'error');
                    }
                    $tags[$oneTag] = $allresults[3][$i];
                    continue;
                };
                $field = trim($operators[1]);
                $prop = '';

                //Check operators.... if may be a:
                //acym.field
                //joomla.field
                //...
                $operatorsParts = explode('.', $operators[1]);
                $operatorComp = 'acym';
                if (count($operatorsParts) > 1 && in_array($operatorsParts[0], ['acym', 'joomla', 'var'])) {
                    $operatorComp = $operatorsParts[0];
                    unset($operatorsParts[0]);
                    $field = implode('.', $operatorsParts);
                }

                //Handle Joomla fields first...
                if ($operatorComp == 'joomla') {
                    if (!empty($user->userid)) {
                        if ($field == 'gid') {
                            //$prop should be a list of group Ids separated by semi-colon so we will do an in_array if it's compared with =
                            //We have a userid... lets load its groups then!
                            $prop = implode(';', acym_loadResultArray('SELECT group_id FROM #__user_usergroup_map WHERE user_id = '.intval($user->userid)));
                        } else {
                            //We need to load the value for that field...
                            $juser = acym_loadObject('SELECT * FROM #__users WHERE id = '.intval($user->userid));
                            if (isset($juser->{$field})) {
                                $prop = strtolower($juser->{$field});
                            } else {
                                if ($isAdmin && !$a) {
                                    acym_display('User variable not set : '.$field.' in '.$allresults[1][$i], 'error');
                                }
                                $a = true;
                            }
                        }
                    }
                } elseif ($operatorComp == 'var') {
                    //Static var to have a tag inside an if condition.
                    //For example {if:var.{cbtag:name}=adrien}...{/if}
                    $prop = strtolower($field);
                } else {
                    //AcyMailing fields...
                    if (!isset($user->{$field})) {
                        //We make sure to display it only once...
                        if ($isAdmin && !$a) {
                            acym_display('User variable not set : '.$field.' in '.$allresults[1][$i], 'error');
                        }
                        $a = true;
                    } else {
                        $prop = strtolower($user->{$field});
                    }
                }

                $tags[$oneTag] = '';
                $val = trim(strtolower($operators[3]));
                //We can hanlde several propositions in one if it contains ; such as {if:category=34;214;53}...
                if ($operators[2] == '=' && ($prop == $val || in_array($prop, explode(';', $val)) || in_array($val, explode(';', $prop)))) {
                    $tags[$oneTag] = $allresults[3][$i];
                } elseif ($operators[2] == '!=' && $prop != $val) {
                    $tags[$oneTag] = $allresults[3][$i];
                } elseif (($operators[2] == '>' || $operators[2] == '&gt;') && $prop > $val) {
                    $tags[$oneTag] = $allresults[3][$i];
                } elseif (($operators[2] == '<' || $operators[2] == '&lt;') && $prop < $val) {
                    $tags[$oneTag] = $allresults[3][$i];
                } elseif ($operators[2] == '~' && strpos($prop, $val) !== false) {
                    $tags[$oneTag] = $allresults[3][$i];
                } elseif ($operators[2] == '!~' && strpos($prop, $val) === false) {
                    $tags[$oneTag] = $allresults[3][$i];
                }
            }
        }

        $this->pluginHelper->replaceTags($email, $tags, true);

        $this->_ifstatement($email, $user, $loop + 1);
    }

    private function _removetext(&$email)
    {
        //Remove text
        $removetext = '{reg},{/reg},{pub},{/pub}';
        if (!empty($removetext)) {
            $removeArray = explode(',', trim($removetext, ' ,'));
            if (!empty($email->body)) {
                $email->body = str_replace($removeArray, '', $email->body);
            }
        }


        $removetags = 'youtube';
        if (!empty($removetags)) {
            $regex = [];
            $removeArray = explode(',', trim($removetags, ' ,'));
            foreach ($removeArray as $oneTag) {
                if (empty($oneTag)) {
                    continue;
                }
                $regex[] = '#(?:{|%7B)'.preg_quote($oneTag, '#').'(?:}|%7D).*(?:{|%7B)/'.preg_quote($oneTag, '#').'(?:}|%7D)#Uis';
                $regex[] = '#(?:{|%7B)'.preg_quote($oneTag, '#').'[^}]*(?:}|%7D)#Uis';
            }

            if (!empty($email->body)) {
                $email->body = preg_replace($regex, '', $email->body);
            }
        }
    }

    private function _handleAnchors(&$email)
    {
        if (empty($email->body)) return;

        $newBody = preg_replace('/(<a +href="#[^"]*"[^>]*) target="_blank"([^>]*>)/Uis', '$1 $2', $email->body);

        if (!empty($newBody)) $email->body = $newBody;
    }
}
