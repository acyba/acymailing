<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\UserClass;
use AcyMailing\Core\AcymController;
use AcyMailing\Controllers\Users\Listing;
use AcyMailing\Controllers\Users\Edition;
use AcyMailing\Controllers\Users\Import;
use AcyMailing\Controllers\Users\Export;
use AcyMailing\Controllers\Users\Subscription;

class UsersController extends AcymController
{
    use Listing;
    use Edition;
    use Import;
    use Export;
    use Subscription;

    public function __construct()
    {
        parent::__construct();

        $this->breadcrumb[acym_translation('ACYM_SUBSCRIBERS')] = acym_completeLink('users');
        $this->loadScripts = [
            'edit' => ['datepicker'],
            'all' => ['vue-applications' => ['entity_select']],
        ];
    }

    public function getAll(): array
    {
        $userClass = new UserClass();

        return $userClass->getAll();
    }

    public function clean(): void
    {
        if (acym_isAcyCheckerInstalled()) {
            if (ACYM_CMS === 'joomla') {
                acym_redirect(acym_route('index.php?option=com_acychecker', false));
            } else {
                acym_redirect(admin_url().'admin.php?page=acychecker_dashboard');
            }
        } else {
            acym_redirect(acym_completeLink('dashboard&task=acychecker', false, true));
        }
    }

    public function getUserInfoAjax(): void
    {
        $userId = acym_getVar('int', 'userId', 0);

        if (empty($userId)) {
            acym_sendAjaxResponse(acym_translation('ACYM_USER_NOT_FOUND'), [], false);
        }

        $userClass = new UserClass();
        $user = $userClass->getCustomFieldValueById($userId);

        if (empty($user)) {
            acym_sendAjaxResponse(acym_translation('ACYM_SUBSCRIBER_NOT_CUSTOM_FIELD'), [], false);
        }

        acym_sendAjaxResponse('', $user);
    }

    /**
     * Search user emails to suggest (autocomplete on send a test)
     */
    public function searchTestReceiversAjax(): void
    {
        $search = acym_getVar('string', 'search', '');
        $userClass = new UserClass();
        $users = $userClass->getUsersLikeEmail($search);

        $return = [];
        foreach ($users as $oneUser) {
            $return[] = [$oneUser->id, $oneUser->email];
        }
        echo json_encode($return);
        exit;
    }
}
