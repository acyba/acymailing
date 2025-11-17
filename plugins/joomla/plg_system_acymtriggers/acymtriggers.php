<?php

use AcyMailing\Classes\AutomationClass;
use AcyMailing\Classes\FormClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\RegacyHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use AcyMailing\Helpers\ScenarioHelper;

defined('_JEXEC') or die('Restricted access');

class plgSystemAcymtriggers extends CMSPlugin
{
    private $oldUser = null;
    private $formToDisplay = [];

    public function __construct(&$subject, $config = [])
    {
        parent::__construct($subject, $config);

        if (version_compare(JVERSION, '4.0.0', '>=')) {
            $this->registerLegacyListener('plgVmOnUserOrder');
        }
    }

    // Used to know what changed when saving a user
    public function onUserBeforeSave($user, $isnew, $new)
    {
        if (is_object($user)) {
            $user = get_object_vars($user);
        }
        $this->oldUser = $user;

        return true;
    }

    // Create / update Acy user on site account creation / update
    public function onUserAfterSave($user, $isnew, $success, $msg)
    {
        // Some components don't give an array but an object
        if (is_object($user)) {
            $user = get_object_vars($user);
        }

        if ($success === false || empty($user['email']) || !$this->initAcy()) {
            return true;
        }

        $userClass = new UserClass();
        $userClass->synchSaveCmsUser($user, $isnew, $this->oldUser);

        return true;
    }

    public function onContentBeforeSave($context, $article, $isNew, $data = [])
    {
        if ($context !== 'com_contact.contact' || empty($data['email_to']) || !$this->initAcy()) {
            return;
        }

        $config = acym_config();
        if ($config->get('regacy_contact', 0) != 1) {
            return;
        }

        $source = 'Contact';
        acym_setVar('acy_source', $source);

        $userClass = new UserClass();
        $userClass->checkVisitor = false;
        $userClass->sendConf = false;

        if (!$isNew) {
            $previousEmailAddress = acym_loadResult('SELECT `email_to` FROM #__contact_details WHERE `id` = '.intval($data['id']));
            if (!empty($previousEmailAddress)) {
                $acyUser = $userClass->getOneByEmail($previousEmailAddress);
            }
        }

        if (empty($acyUser)) {
            $acyUser = $userClass->getOneByEmail($data['email_to']);
        }

        if (empty($acyUser)) {
            $acyUser = new stdClass();
            $acyUser->source = $source;
        }

        /* * * * * * * * * * * * * * * * * * *
        * Step 1: create / update the user  *
        * * * * * * * * * * * * * * * * * * */
        $acyUser->name = $data['name'] ?? '';
        $acyUser->email = $data['email_to'];
        $acyUser->active = $data['published'] == '0' ? 0 : 1;

        $regacyContactForceConf = $config->get('regacy_contact_forceconf', 0) == 1;
        if (!$regacyContactForceConf) {
            $acyUser->confirmed = 1;
        }

        $isNew = $isNew || empty($acyUser->id);

        $id = $userClass->save($acyUser);

        if (empty($id)) {
            return;
        }

        /* * * * * * * * * * * * * * * * * * * * * *
         * Step 2: Handle the user's subscription  *
         * * * * * * * * * * * * * * * * * * * * * */
        $currentSubscription = $userClass->getSubscriptionStatus($id);

        $autoLists = $isNew ? $config->get('regacy_contact_autolists') : '';
        $autoLists = explode(',', $autoLists);
        acym_arrayToInteger($autoLists);

        $listsClass = new ListClass();
        $allLists = $listsClass->getAll();

        $listsToSubscribe = [];
        foreach ($allLists as $oneList) {
            if (!$oneList->active || !empty($currentSubscription[$oneList->id])) {
                continue;
            }

            if (in_array($oneList->id, $autoLists)) {
                $listsToSubscribe[] = $oneList->id;
            }
        }

        if (!empty($listsToSubscribe)) {
            $userClass->subscribe([$id], $listsToSubscribe);
        }

        $confirmationRequired = $config->get('require_confirmation', 1);
        if (empty($acyUser->active) || !empty($acyUser->confirmed) || !$confirmationRequired) {
            return;
        }

        // New active user, or just activated the user, send the email
        if (empty($acyUser->id) && $regacyContactForceConf) {
            $userClass->forceConfAdmin = true;
            $userClass->sendConfirmation($id);
        }
    }

    public function onContentBeforeDelete($context, $data)
    {
        if ($context !== 'com_contact.contact' || !$this->initAcy()) {
            return;
        }

        $config = acym_config();
        if ($config->get('regacy_contact', '0') != 1 || $config->get('regacy_contact_delete', '0') != 1) {
            return;
        }

        $userClass = new UserClass();
        $contactEmail = $data->get('email_to');
        if (empty($contactEmail)) {
            return;
        }

        $acyUser = $userClass->getOneByEmail($contactEmail);
        if (!empty($acyUser)) {
            $userClass->delete([$acyUser->id]);
        }
    }

    // Delete Acy user on site account deletion
    public function onUserAfterDelete($user, $success, $msg)
    {
        if (is_object($user)) {
            $user = get_object_vars($user);
        }

        if ($success === false || empty($user['email']) || !$this->initAcy()) {
            return true;
        }


        $userClass = new UserClass();
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
        if (empty($orderData->virtuemart_user_id) || $alreadyTriggerVirtuemart || !$this->initAcy()) {
            return;
        }
        $alreadyTriggerVirtuemart = true;

        $userID = acym_loadResult(
            'SELECT `user`.`id` 
            FROM `#__acym_user` AS `user` 
            JOIN `#__virtuemart_order_userinfos` AS `vmuser` ON `vmuser`.`email` = `user`.`email` 
            WHERE `vmuser`.`virtuemart_user_id` = '.intval($orderData->virtuemart_user_id)
        );

        if (empty($userID)) {
            return;
        }

        $automationClass = new AutomationClass();
        $automationClass->trigger('vmorder', ['userId' => $userID]);

        $scenarioHelper = new ScenarioHelper();
        $scenarioHelper->trigger('vmorder', ['userId' => $userID]);
    }

    // Hikashop trigger after an order is created
    public function onAfterOrderCreate(&$order, &$send_email)
    {
        $this->onAfterOrderUpdate($order, $send_email);
    }

    // Hikashop trigger after an order is updated
    public function onAfterOrderUpdate(&$order, &$send_email)
    {
        if (!$this->initAcy()) {
            return;
        }

        acym_trigger('onAfterOrderUpdate', [&$order], 'plgAcymHikashop');
    }

    public function onAfterCartSave(&$element)
    {
        if (!$this->initAcy()) {
            return;
        }

        acym_trigger('onAfterCartSave', [&$element], 'plgAcymHikashop');
    }

    // Trigger for hikashop user creation
    public function onAfterUserCreate(&$element)
    {
        if (!$this->initAcy()) {
            return;
        }

        $formData = acym_getVar('array', 'data', []);
        $listData = acym_getVar('array', 'hikashop_visible_lists_checked', []);

        acym_trigger('onAfterHikashopUserCreate', [$formData, $listData, $element]);
    }

    public function onBeforeCompileHead()
    {
        // Don't show forms in popup iframes
        if (!empty($_REQUEST['tmpl']) && in_array($_REQUEST['tmpl'], ['component', 'raw'])) {
            return;
        }
        if (!empty($_REQUEST['acym_preview'])) {
            return;
        }

        $app = Factory::getApplication();
        if ($app->getName() != 'site') {
            return;
        }

        if (!$this->initAcy()) {
            return;
        }

        $menu = acym_getMenu();
        if (empty($menu)) {
            return;
        }

        $formClass = new FormClass();
        $forms = $formClass->getAllFormsToDisplay();
        if (empty($forms)) {
            return;
        }

        foreach ($forms as $form) {
            if (!empty($form->pages) && (in_array($menu->id, $form->pages) || in_array('all', $form->pages))) {
                $this->formToDisplay[] = $formClass->renderForm($form);
            }
        }

        if (!empty($this->formToDisplay)) {
            acym_initModule();
        }
    }

    public function onAfterRender()
    {
        $this->displayForms();
        $this->applyRegacy();
    }

    public function onAfterRoute()
    {
        // Fix a bug in Joomla: the session com_media.return_url is deleted the first time we submit the image upload form
        if (!empty($_REQUEST['author']) && 'acymailing' === $_REQUEST['author'] && !empty($_REQUEST['task']) && 'file.upload' === $_REQUEST['task'] && !empty($_REQUEST['option']) && 'com_media' === $_REQUEST['option']) {
            $session = Factory::getSession();
            $session->set('com_media.return_url', 'index.php?option=com_media&view=images&tmpl=component');
        }

        $source = $this->getVar('string', 'acy_source');
        if ($source === 'virtuemart registration form') {
            if (!$this->initAcy()) {
                return;
            }
            acym_trigger('onRegacyAfterRoute', []);
        }

        // Oauth redirection after consent is granted
        if (empty($_GET['code']) || empty($_GET['state']) || !$this->initAcy()) {
            return;
        }

        if ($_GET['state'] === 'acymailingsmtp') {
            acym_redirect(acym_completeLink('configuration&auth_type=smtp&code='.$_GET['code'], false, true));
        }

        if ($_GET['state'] === 'acymailingbounce') {
            acym_redirect(acym_completeLink('configuration&auth_type=bounce&code='.$_GET['code'], false, true));
        }
    }

    public function onAfterInitialise()
    {
        $this->handleCron();
        $this->handleAutologin();
    }


    private function initAcy(): bool
    {
        $isInstalling = !empty($_REQUEST['option']) && in_array(
                $_REQUEST['option'],
                [
                    'com_installer',
                    'com_joomlaupdate',
                    'com_postinstall',
                ]
            );

        if ($isInstalling) {
            return false;
        }

        if (function_exists('acym_getVar')) {
            return true;
        }

        $ds = DIRECTORY_SEPARATOR;
        $helperFile = rtrim(JPATH_ADMINISTRATOR, $ds).$ds.'components'.$ds.'com_acym'.$ds.'Core'.$ds.'init.php';
        if (!file_exists($helperFile) || !include_once $helperFile) {
            return false;
        }

        return true;
    }

    private function displayForms()
    {
        if (empty($this->formToDisplay)) {
            return;
        }

        $buffer = Factory::getApplication()->getBody();

        $buffer = preg_replace('/(<body.*>)/Ui', '$1'.implode('', $this->formToDisplay), $buffer);

        Factory::getApplication()->setBody($buffer);
    }

    private function applyRegacy()
    {
        // Get the current extension
        $option = $this->getVar('cmd', 'option');
        if (empty($option)) {
            return;
        }

        $db = Factory::getDbo();
        $db->setQuery('SELECT `value` FROM #__acym_configuration WHERE `name` LIKE "%regacy" OR `name` LIKE "%\_sub"');
        $regacyOptions = $db->loadColumn();

        $regacyNeeded = false;
        foreach ($regacyOptions as $oneOption) {
            if (!empty($oneOption)) {
                $regacyNeeded = true;
                break;
            }
        }
        if (!$regacyNeeded) {
            return;
        }

        if (!$this->initAcy()) {
            return;
        }

        $config = acym_config();
        if ($config->get('regacy', 0)) {
            $components = [
                'com_users' => [
                    'view' => ['registration', 'profile', 'user'],
                    'edittasks' => ['profile', 'user'],
                    'email' => ['jform[email2]', 'jform[email1]'],
                    'password' => ['jform[password2]', 'jform[password1]'],
                    'checkLayout' => ['profile' => 'edit'],
                    'lengthafter' => ACYM_J40 ? 500 : 200,
                    'containerClass' => 'control-group',
                    'labelClass' => 'control-label',
                    'valueClass' => 'controls',
                    'baseOption' => 'regacy',
                ],
            ];
        } else {
            $components = [];
        }

        acym_trigger('onRegacyAddComponent', [&$components]);
        if (!isset($components[$option])) {
            return;
        }


        // We're on a supported extension, good. But is this specific page supported?
        $viewVar = ['view'];
        if (!empty($components[$option]['viewvar'])) {
            $viewVar = $components[$option]['viewvar'];
        }

        $isvalid = false;
        foreach ($viewVar as $oneVar) {
            $view = acym_getVar('cmd', $oneVar, acym_getVar('cmd', 'task', acym_getVar('cmd', 'view')));
            if (in_array($view, $components[$option]['view'])) {
                $isvalid = true;
                break;
            }
        }
        if (!$isvalid) {
            return;
        }

        $regacyHelper = new RegacyHelper();
        if (!$regacyHelper->prepareLists($components[$option])) {
            return;
        }

        $this->includeRegacyLists($components[$option], $regacyHelper->label, $regacyHelper->listsHtml);
    }

    private function getVar($type, $name)
    {
        $jversion = preg_replace('#[^0-9\.]#i', '', JVERSION);
        if (version_compare($jversion, '4.0.0', '>=')) {
            $acyapp = Factory::getApplication();
            $input = $acyapp->input;
            $sourceInput = $input->__get('REQUEST');

            if ($acyapp->isClient('administrator')) {
                $result = $sourceInput->get($name, null, $type);
            } else {
                // When the SEF is active, $_REQUEST is empty as Joomla doesn't populate it anymore
                $result = $sourceInput->get($name, $input->get($name, null, $type), $type);
            }
        } else {
            $result = JRequest::getVar($name, null, 'REQUEST', $type);
        }

        if (is_string($result)) {
            return ComponentHelper::filterText($result);
        }

        return $result;
    }

    private function includeRegacyLists($options, string $label, string $lists): void
    {
        $config = acym_config();
        if (ACYM_J40) {
            $body = Factory::getApplication()->getBody(false);
        } else {
            $body = JResponse::getBody();
        }

        $listsPosition = $config->get('regacy_listsposition', 'password');

        if ($options['baseOption'] != 'regacy') {
            $listsPosition = $config->get($options['baseOption'].'_regacy_listsposition', 'password');
        }

        if ('custom' === $listsPosition) {
            $customOptionName = $options['baseOption'] == 'regacy' ? 'regacy_listspositioncustom' : $options['baseOption'].'_regacy_listspositioncustom';
            $listAfter = explode(';', str_replace(['\\[', '\\]'], ['[', ']'], $config->get($customOptionName)));
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
                if (ACYM_J40) {
                    Factory::getApplication()->setBody($body);
                } else {
                    JResponse::setBody($body);
                }

                return;
            }

            // 2 - If the field is in a special structure, try to handle it
            $containerClass = empty($options['containerClass']) ? '' : $options['containerClass'];
            $labelClass = empty($options['labelClass']) ? '' : $options['labelClass'];
            $valueClass = empty($options['valueClass']) ? '' : $options['valueClass'];

            $customHtmlElement = $config->get('regacy_customhtmlelement', '');

            switch ($customHtmlElement) {
                case 'li':
                    $formats = ['li' => ['li', 'li']];
                    break;
                case 'div':
                    $formats = ['div' => ['div', 'div']];
                    break;
                case 'p':
                    $formats = ['p' => ['div', 'div']];
                    break;
                case 'dd':
                    $formats = ['dd' => ['dt', 'div']];
                    break;
                default:
                    $formats = ['li' => ['li', 'li'], 'div' => ['div', 'div'], 'p' => ['div', 'div'], 'dd' => ['dt', 'div']];
            }

            for ($j = 0; $j < 2; $j++) {
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
                    if (ACYM_J40) {
                        Factory::getApplication()->setBody($body);
                    } else {
                        JResponse::setBody($body);
                    }

                    return;
                }
            }

            $i++;
        }
    }

    private function getAcyConf($conf)
    {
        static $db;
        if (empty($db)) {
            $db = Factory::getDbo();
        }

        $db->setQuery('SELECT `value` FROM #__acym_configuration WHERE `name` = '.$db->quote($conf));

        return $db->loadResult();
    }

    private function handleCron()
    {
    }

    private function handleAutologin()
    {
        $subId = $this->getVar('int', 'autoSubId');
        $subKey = $this->getVar('string', 'subKey');

        if (empty($subId) || empty($subKey)) {
            return;
        }

        if (!$this->initAcy()) {
            return;
        }

        $currentUrl = acym_currentURL();
        $cleanedUrl = acym_cleanUrl($currentUrl, ['autoSubId', 'subKey']);

        $config = acym_config();
        if ($config->get('autologin_urls', 0) == 0) {
            acym_redirect($cleanedUrl);
        }

        $cmsId = acym_loadResult('SELECT `cms_id` FROM #__acym_user WHERE `id` = '.intval($subId).' AND `key` = '.acym_escapeDB($subKey));
        if (empty($cmsId) || $cmsId === acym_currentUserId()) {
            acym_redirect($cleanedUrl);

            return;
        }

        $username = acym_loadResult('SELECT `username` FROM #__users WHERE `id` = '.intval($cmsId));
        if (empty($username)) {
            acym_redirect($cleanedUrl);

            return;
        }

        acym_loadJoomlaPlugin('user');

        $options = ['action' => 'core.login.site'];
        $response = ['username' => $username];
        acym_triggerCmsHook('onUserLogin', [$response, $options]);

        acym_redirect($cleanedUrl);
    }
}
