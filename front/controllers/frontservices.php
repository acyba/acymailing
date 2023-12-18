<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Classes\HistoryClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\UserStatClass;
use AcyMailing\Libraries\acymController;

class FrontservicesController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        acym_setNoTemplate();

        $this->publicFrontTasks = [
            'sendinblue',
        ];
    }

    public function listing()
    {
        exit;
    }

    public function sendinblue()
    {
        // Check security key
        $securityKey = acym_getVar('string', 'seckey');
        if (empty($securityKey) || $securityKey !== $this->config->get('sendinblue_webhooks_seckey')) exit;

        // Check if sending method is sendinblue
        $mailerMethod = $this->config->get('mailer_method');
        if (!in_array($mailerMethod, ['brevo-smtp', 'sendinblue'])) exit;

        // Get the data passed by Sendinblue
        $entityBody = file_get_contents('php://input');
        if (empty($entityBody)) exit;

        $data = json_decode($entityBody, true);
        if ($data === null || empty($data['email'])) exit;

        // Get the user from the email
        $userClass = new UserClass();
        $user = $userClass->getOneByEmail($data['email']);
        if (empty($user)) exit;

        $action = empty($data['event']) ? 'brevo' : $data['event'];

        // Get the related email id if there is one
        $mailId = 0;
        if (!empty($data['campaign name']) && strpos($data['campaign name'], 'AcyMailing Mail ') === 0) {
            $mailId = preg_replace('#^AcyMailing Mail (\d+) \(.*$#Uis', '$1', $data['campaign name']);

            // Register the unsubscription on the email's stats
            if (in_array($action, ['unsubscribe', 'spam'])) {
                acym_query('UPDATE #__acym_user_stat SET unsubscribe = unsubscribe + 1 WHERE user_id = '.intval($user->id).' AND mail_id = '.intval($mailId));
                acym_query('UPDATE #__acym_mail_stat SET unsubscribe_total = unsubscribe_total + 1 WHERE mail_id = '.intval($mailId));
                acym_query('UPDATE #__acym_user_has_list SET status = 0 WHERE user_id = '.intval($user->id));
            }

            if ($action === 'hard_bounce') {
                $userStatClass = new UserStatClass();
                $currentUserStats = $userStatClass->getOneByMailAndUserId($mailId, $user->id);
                if ($currentUserStats->bounce < 1) {
                    acym_query('UPDATE #__acym_mail_stat SET bounce_unique = bounce_unique + 1 WHERE mail_id = '.intval($mailId));
                }
                acym_query('UPDATE #__acym_user_stat SET bounce = bounce + 1 WHERE user_id = '.intval($user->id).' AND mail_id = '.intval($mailId));
            }
        }

        // If found, disable the user and add the reason in their history
        $user->active = 0;
        $userClass->save($user);

        $historyClass = new HistoryClass();
        $historyClass->insert($user->id, $action, ['Brevo'], $mailId);

        exit;
    }
}
