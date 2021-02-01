<?php

use AcyMailing\Classes\AutomationClass;
use AcyMailing\Classes\FormClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\RegacyHelper;

defined('_JEXEC') or die('Restricted access');

class plgSystemAcymtriggers extends JPlugin
{
    var $oldUser = null;
    var $formToDisplay = [];

    // Loads the Acy library
    public function initAcy()
    {
        if (function_exists('acym_get')) return true;
        $helperFile = rtrim(
                JPATH_ADMINISTRATOR,
                DIRECTORY_SEPARATOR
            ).DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_acym'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
        if (!file_exists($helperFile) || !include_once $helperFile) return false;

        return true;
    }

    // Used to know what changed when saving a user
    public function onUserBeforeSave($user, $isnew, $new)
    {
        if (is_object($user)) $user = get_object_vars($user);
        $this->oldUser = $user;

        return true;
    }

    // Create / update Acy user on site account creation / update
    public function onUserAfterSave($user, $isnew, $success, $msg)
    {
        // Some components don't give an array but an object
        if (is_object($user)) $user = get_object_vars($user);
        if ($success === false || empty($user['email']) || !$this->initAcy()) return true;

        $userClass = new UserClass();
        if (!method_exists($userClass, 'synchSaveCmsUser')) return true;
        $userClass->synchSaveCmsUser($user, $isnew, $this->oldUser);

        return true;
    }

    // Delete Acy user on site account deletion
    public function onUserAfterDelete($user, $success, $msg)
    {
        if (is_object($user)) $user = get_object_vars($user);
        if ($success === false || empty($user['email']) || !$this->initAcy()) return true;


        $userClass = new UserClass();
        if (!method_exists($userClass, 'synchDeleteCmsUser')) return true;
        $userClass->synchDeleteCmsUser($user['email']);

        return true;
    }

    public function plgVmOnUpdateOrderPayment($orderData)
    {
        $this->handleVirtuemartOrderCreateUpdate($orderData);
    }

    public function plgVmOnUserOrder($orderData)
    {
        $this->handleVirtuemartOrderCreateUpdate($orderData);
    }

    private function handleVirtuemartOrderCreateUpdate($orderData)
    {
        static $alreadyTriggerVirtuemart = false;
        if (empty($orderData->virtuemart_user_id) || !$this->initAcy() || $alreadyTriggerVirtuemart) return;
        $alreadyTriggerVirtuemart = true;

        $userID = acym_loadResult(
            'SELECT `user`.`id` 
            FROM `#__acym_user` AS `user` 
            JOIN `#__virtuemart_order_userinfos` AS `vmuser` ON `vmuser`.`email` = `user`.`email` 
            WHERE `vmuser`.`virtuemart_user_id` = '.intval($orderData->virtuemart_user_id)
        );
        if (empty($userID)) return;

        $automationClass = new AutomationClass();
        $automationClass->trigger('vmorder', ['userId' => $userID]);
    }

    // Hikashop trigger after an order is created
    public function onAfterOrderCreate(&$order, &$send_email)
    {
        return $this->onAfterOrderUpdate($order, $send_email);
    }

    // Hikashop trigger after an order is updated
    public function onAfterOrderUpdate(&$order, &$send_email)
    {
        if (!$this->initAcy()) return;

        acym_trigger('onAfterOrderUpdate', [&$order], 'plgAcymHikashop');
    }

    public function onBeforeCompileHead()
    {
        // Don't show forms in popup iframes
        if(!empty($_REQUEST['tmpl']) && in_array($_REQUEST['tmpl'], ['component', 'raw'])) return;
        if(!empty($_REQUEST['acym_preview'])) return;

        $app = JFactory::getApplication();
        if ($app->getName() != 'site') return;

        if (!$this->initAcy()) return;

        $menu = acym_getMenu();
        if (empty($menu)) return;

        $formClass = new FormClass();
        $forms = $formClass->getAllFormsToDisplay();
        if (empty($forms)) return;

        foreach ($forms as $form) {
            if (!empty($form->pages) && (in_array($menu->id, $form->pages) || in_array('all', $form->pages))) {
                $this->formToDisplay[] = $formClass->renderForm($form);
            }
        }

        if (!empty($this->formToDisplay)) acym_initModule();
    }

    private function displayForms()
    {
        if (empty($this->formToDisplay)) return;

        $buffer = JFactory::getApplication()->getBody();

        $buffer = preg_replace('/(<body.*>)/Ui', '$1'.implode('', $this->formToDisplay), $buffer);

        JFactory::getApplication()->setBody($buffer);
    }

    public function onAfterRender()
    {
        if (empty($this->formToDisplay)) return;
        if (!$this->initAcy()) return;

        $this->displayForms();

        $config = acym_config();
        if (!$config->get('regacy', 0)) return;

        // Get the current extension
        $option = acym_getVar('cmd', 'option');
        if (empty($option)) return;

        $components = [
            'com_users' => [
                'view' => ['registration', 'profile', 'user'],
                'edittasks' => ['profile', 'user'],
                'email' => ['jform[email2]', 'jform[email1]'],
                'password' => ['jform[password2]', 'jform[password1]'],
                'checkLayout' => ['profile' => 'edit'],
                'lengthafter' => 200,
                'containerClass' => 'control-group',
                'labelClass' => 'control-label',
                'valueClass' => 'controls',
                'baseOption' => 'regacy',
            ],
        ];

        acym_trigger('onRegacyAddComponent', [&$components]);
        if (!isset($components[$option])) return;


        // We're on a supported extension, good. But is this specific page supported?
        $viewVar = ['view'];
        if (!empty($components[$option]['viewvar'])) $viewVar = $components[$option]['viewvar'];

        $isvalid = false;
        foreach ($viewVar as $oneVar) {
            $view = acym_getVar('cmd', $oneVar, acym_getVar('cmd', 'task', acym_getVar('cmd', 'view')));
            if (in_array($view, $components[$option]['view'])) {
                $isvalid = true;
                break;
            }
        }
        if (!$isvalid) return;


        $regacyHelper = new RegacyHelper();
        if (!$regacyHelper->prepareLists($components[$option])) return;

        $this->includeRegacyLists($components[$option], $regacyHelper->label, $regacyHelper->lists);
    }

    private function includeRegacyLists($options, $label, $lists)
    {
        $config = acym_config();
        $body = JResponse::getBody();

        $listsPosition = $config->get('regacy_listsposition', 'password');
        if ('custom' === $listsPosition) {
            $listAfter = explode(';', str_replace(['\\[', '\\]'], ['[', ']'], $config->get('regacy_listspositioncustom')));
            $after = empty($listAfter) ? $options['password'] : $listAfter;
        } elseif (!empty($options[$listsPosition])) {
            $after = $options[$listsPosition];
        } else {
            $after = [$listsPosition == 'email' ? 'email' : 'password2'];
        }

        $i = 0;
        while ($i < count($after)) {

            $lengthAfterMin = empty($options['lengthaftermin']) ? 0 : $options['lengthaftermin'];
            $lengthAfter = $options['lengthafter'];

            // 1 - Let's first try to insert our code just after the specified field
            $regex = '#(name *= *"'.preg_quote($after[$i]).'".{'.$lengthAfterMin.','.$lengthAfter.'}</tr>)#Uis';
            if (preg_match($regex, $body)) {
                $lists = '<tr class="acym__regacy">
                        <td class="acym__regacy__label" style="padding-top:5px; vertical-align: top;">'.$label.'</td>
                        <td class="acym__regacy__values">'.$lists.'</td>
                    </tr>';
                $body = preg_replace($regex, '$1'.$lists, $body, 1);
                JResponse::setBody($body);

                return;
            }

            // 2 - If the field is in a special structure, try to handle it
            $containerClass = empty($options['containerClass']) ? '' : $options['containerClass'];
            $labelClass = empty($options['labelClass']) ? '' : $options['labelClass'];
            $valueClass = empty($options['valueClass']) ? '' : $options['valueClass'];

            $formats = ['li' => ['li', 'li'], 'div' => ['div', 'div'], 'p' => ['div', 'div'], 'dd' => ['dt', 'div']];

            for ($j = 0 ; $j < 2 ; $j++) {
                foreach ($formats as $oneFormat => $dispall) {
                    if (0 === $j) {
                        $regex = '#(name *= *"'.preg_quote($after[$i]).'".{'.$lengthAfterMin.','.$lengthAfter.'}</'.$oneFormat.'>)(?!\s*</'.$oneFormat.'>)#Uis';
                    } else {
                        $regex = '#(name *= *"'.preg_quote($after[$i]).'"((?!</'.$oneFormat.'>).)*</'.$oneFormat.'>)#Uis';
                    }
                    if (!preg_match($regex, $body)) continue;

                    if ($oneFormat == 'dd') {
                        $lists = '<dt class="'.$containerClass.'">
                                            <label class="acym__regacy__label '.$labelClass.'">'.$label.'</label>
                                        </dt>
                                        <dd class="acym__regacy__values '.$valueClass.'">'.$lists.'</dd>';
                    } else {
                        $lists = '<'.$dispall[0].' class="acym__regacy '.$containerClass.'">
                            <label class="acym__regacy__label '.$labelClass.'">'.$label.'</label>
                            <div class="acym__regacy__values '.$valueClass.'">'.$lists.'</div>
                        </'.$dispall[1].'>';
                    }
                    $body = preg_replace($regex, '$1'.$lists, $body, 1);
                    JResponse::setBody($body);

                    return;
                }
            }

            $i++;
        }
    }

    public function onAfterRoute()
    {
        // Fix a bug in Joomla: the session com_media.return_url is deleted the first time we submit the image upload form
        if (!empty($_REQUEST['author']) && 'acymailing' === $_REQUEST['author'] && !empty($_REQUEST['task']) && 'file.upload' === $_REQUEST['task'] && !empty($_REQUEST['option']) && 'com_media' === $_REQUEST['option']) {
            $session = JFactory::getSession();
            $session->set('com_media.return_url', 'index.php?option=com_media&view=images&tmpl=component');
        }
    }

    // Trigger for hikashop user creation
    function onAfterUserCreate(&$element)
    {
        if (!$this->initAcy()) return true;

        $formData = acym_getVar('array', 'data', []);
        $listData = acym_getVar('array', 'hikashop_visible_lists_checked', []);

        acym_trigger('onAfterHikashopUserCreate', [$formData, $listData, $element]);
    }

}
