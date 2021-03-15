<?php

use AcyMailing\Classes\UserClass;

class plgAcymcreateuser extends acymPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->cms = 'all';

        $usergroups = acym_getGroups();
        if ('joomla' === ACYM_CMS) {
            $joomlaUsersparams = JComponentHelper::getParams('com_users');
            $defaultUsergroup = $joomlaUsersparams->get('new_usertype');
        } else {
            $defaultUsergroup = get_option('default_role');
        }

        $onlyFrontOptions = [
            'front' => acym_translation('ACYM_FRONT'),
            'both' => acym_translation('ACYM_FRONT_BACK'),
        ];

        $this->settings = [
            'enableCreate' => [
                'type' => 'switch',
                'label' => 'ACYM_ALLOW_CMS_USER_CREATION',
                'value' => 0,
            ],
            'onlyFront' => [
                'type' => 'select',
                'label' => 'ACYM_CREATE_FROM_SUB_ON',
                'value' => 1,
                'data' => $onlyFrontOptions,
            ],
            'onModif' => [
                'type' => 'switch',
                'label' => 'ACYM_CREATE_ON_USER_MODIFICATION',
                'value' => 0,
            ],
            'group' => [
                'type' => 'select',
                'label' => 'ACYM_USER_GROUP',
                'value' => $defaultUsergroup,
                'data' => $usergroups,
            ],

        ];
    }

    public function onAcymAfterUserCreate(&$user)
    {
        $this->createCmsUser($user);
    }

    public function onAcymAfterUserModify(&$user)
    {
        if ($this->getParam('onModif', '0') === '0') return;

        $this->createCmsUser($user);
    }

    public function createCmsUser($user)
    {
        if (empty($user->email) || !empty($user->cms_id) || empty($this->getParam('enableCreate', '0'))) return;

        // Should we only create from front subscriber creation
        if ($this->getParam('onlyFront', 'front') === 'front' && acym_isAdmin()) return;

        if ('wordpress' === ACYM_CMS) {
            $this->createWordpressUser($user);
        } else {
            $this->createJoomlaUser($user);
        }
    }

    protected function createJoomlaUser($user)
    {
        $joomlaUsersparams = JComponentHelper::getParams('com_users');
        $joomlaConfig = JFactory::getConfig();

        $useractivation = $joomlaUsersparams->get('useractivation');
        $allowUserRegistration = $joomlaUsersparams->get('allowUserRegistration');
        if ($allowUserRegistration == 0) return;

        acym_loadLanguageFile('com_users', JPATH_SITE);

        // Generate the password
        $minimum_integers = $joomlaUsersparams->get('minimum_integers');
        $minimum_symbols = $joomlaUsersparams->get('minimum_symbols');
        $minimum_uppercase = $joomlaUsersparams->get('minimum_uppercase');
        $length = 8;
        if ($joomlaUsersparams->get('minimum_length') > 8) $length = $joomlaUsersparams->get('minimum_length');
        $tryCount = 0;
        do {
            $password = JUserHelper::genrandompassword($length);
            $tryCount++;
            //Could happen if the admin selected 90 as min integers and min length = 9, in this case we will never generate a satisfying password
            if ($tryCount > 50) break;
        } while ($this->getConditionPassword($password, $minimum_integers, $minimum_symbols, $minimum_uppercase));

        $defaultUsergroup = $joomlaUsersparams->get('new_usertype');
        $configUserGroup = $this->getParam('group', $defaultUsergroup);

        if (empty($user->name)) {
            $user->name = ucwords(trim(str_replace(['.', '_', ')', ',', '(', '-', 1, 2, 3, 4, 5, 6, 7, 8, 9, 0], ' ', substr($user->email, 0, strpos($user->email, '@')))));
        }
        $userData = [
            'name' => $user->name,
            'username' => $user->email,
            'password' => $password,
            'password2' => $password,
            'email' => $user->email,
            'block' => 0,
            'groups' => [$configUserGroup],
        ];

        $joomlaUser = new JUser();

        // Check if the user needs to activate his account.
        if ($useractivation == 1 || $useractivation == 2) {
            $userData['activation'] = JApplicationHelper::getHash(JUserHelper::genrandompassword());
            $userData['block'] = 1;
        }

        // Write to database
        if (!$joomlaUser->bind($userData)) return false;

        // Store the data.
        if (!$joomlaUser->save()) return false;

        $user->cms_id = $joomlaUser->id;
        $userClass = new UserClass();
        $userClass->save($user);

        // Compile the notification mail values.
        $data = $joomlaUser->getProperties();
        $data['fromname'] = $joomlaConfig->get('fromname');
        $data['mailfrom'] = $joomlaConfig->get('mailfrom');
        $data['sitename'] = $joomlaConfig->get('sitename');
        $data['siteurl'] = str_replace('/administrator', '', acym_baseURI());

        // Handle account activation/confirmation emails.
        $emailSubject = acym_translation_sprintf('COM_USERS_EMAIL_ACCOUNT_DETAILS', $data['name'], $data['sitename']);
        if ($useractivation == 2 || $useractivation == 1) {
            $base = acym_currentURL();
            $activationLink = 'index.php?option=com_users&task=registration.activate&token=';

            if ($useractivation == 2) {
                $emailBody = acym_translation_sprintf(
                    'COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY',
                    $data['name'],
                    $data['sitename'],
                    $data['siteurl'].$activationLink.$data['activation'],
                    $data['siteurl'],
                    $data['username'],
                    $data['password_clear']
                );
            } else {
                $activationMessage = 'COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY';
                $emailBody = acym_translation_sprintf(
                    $activationMessage,
                    $data['name'],
                    $data['sitename'],
                    $data['siteurl'].$activationLink.$data['activation'],
                    $data['siteurl'],
                    $data['username'],
                    $data['password_clear']
                );
            }

            $data['activate'] = $base.acym_route($activationLink.$data['activation'], false);
        } else {
            $emailBody = acym_translation_sprintf(
                'COM_USERS_EMAIL_REGISTERED_BODY',
                $data['name'],
                $data['sitename'],
                $data['siteurl'],
                $data['username'],
                $data['password_clear']
            );
        }

        // Fetch the mail object - A reference to the global mail object (JMail) is fetched through the JFactory object. This is the object creating our mail.
        $mailer = JFactory::getMailer();
        /* Set a sender - The sender of an email is set with setSender. The function takes an array with an email address and a name as an argument.
        We fetch the site's email address and name from the global configuration.
        This info is set in Global Configuration -> Server -> Mail Settings. */
        $sender = [$joomlaConfig->get('mailfrom'), $joomlaConfig->get('fromname')];
        $mailer->setSender($sender);
        /* Recipient - You set the recipient of an email with the function addRecipient.
        To set the email address to the currently logged in user, we fetch it from the user object. */
        $mailer->addRecipient($user->email);
        /* Set the subject and the body. The easy way to create an email body is as a string with plain text.
        You can also attach a file with addAttachment. It takes a single file name or an array of file names as argument.*/
        $mailer->setSubject($emailSubject);
        $mailer->setBody($emailBody);
        // Send the mail - It is sent with the function Send. It returns true on success or a JError object.
        $send = $mailer->Send();
        if ($send !== true) return false;
    }

    protected function createWordpressUser($user)
    {
        if (!get_option('users_can_register')) return;

        if (false !== username_exists($user->email)) return false;

        $defaultUsergroup = get_option('default_role');
        $configUserGroup = $this->getParam('group', $defaultUsergroup);

        $pwdTemp = wp_generate_password(12, true);
        $result = wp_create_user($user->email, $pwdTemp, $user->email);

        if (is_wp_error($result)) {
            return false;
        } else {
            $user->cms_id = $result;
            $userClass = new UserClass();
            $userClass->save($user);
            $wpUser = new WP_User($result);
            $wpUser->set_role($configUserGroup);
            wp_new_user_notification($result, null, 'user');
        }
    }

    protected function getConditionPassword($password, $minimum_integers, $minimum_symbols, $minimum_uppercase)
    {
        $notEnoughInt = !empty($minimum_integers) && preg_match_all("/[0-9]/", $password, $out) < $minimum_integers;
        $notEnoughSymbols = !empty($minimum_symbols) && preg_match_all("/[a-z]/", $password, $out) < $minimum_symbols;
        $notEnoughUpperCase = !empty($minimum_uppercase) && preg_match_all("/[A-Z]/", $password, $out) < $minimum_uppercase;

        return $notEnoughInt || $notEnoughSymbols || $notEnoughUpperCase;
    }
}