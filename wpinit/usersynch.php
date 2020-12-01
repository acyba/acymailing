<?php

namespace AcyMailing\Init;

use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\RegacyHelper;

class acyUsersynch extends acyHook
{
    public function __construct()
    {
        // Let the users opt in to the AcyMailing lists
        add_action('register_form', [$this, 'addRegistrationFields']);
        add_action('edit_user_profile', [$this, 'addProfileFields'], 10, 1);
        add_action('show_user_profile', [$this, 'addProfileFields'], 10, 1);

        // Hooks to create/update an Acy user when a WP user is created/updated
        add_action('user_register', [$this, 'synchSaveUsers'], 10, 1);
        add_action('profile_update', [$this, 'synchSaveUsers'], 10, 2);
        add_action('delete_user', [$this, 'synchDeleteUsers']);
    }

    public function addRegistrationFields($externalPluginConfig = '')
    {
        parent::addRegistrationFields();
    }

    public function addProfileFields()
    {

        $config = acym_config();
        if (!$config->get('regacy', 0)) return;

        $regacyHelper = new RegacyHelper();
        if (!$regacyHelper->prepareLists([])) return;

        ?>
		<h2><?php echo acym_translation('ACYM_SUBSCRIPTION'); ?></h2>
		<table class="form-table">
			<tbody>
                <?php
                foreach ($regacyHelper->lists as $listId => $oneList) {
                    $checked = $oneList['checked'] ? 'checked="checked"' : '';
                    ?>
					<tr>
						<th scope="row"><label class="acym__regacy__lists__label" for="acym__regacy__lists-<?php echo intval($listId); ?>"><?php echo acym_escape(
                                    $oneList['name']
                                ); ?></label></th>
						<td>
							<input name="regacy_visible_lists_checked[]"
								   type="checkbox"
								   id="acym__regacy__lists-<?php echo intval($listId); ?>"
								   value="<?php echo intval($listId); ?>" <?php echo $checked; ?>>
						</td>
					</tr>
                    <?php
                }
                ?>
			</tbody>
		</table>
		<input type="hidden" value="<?php echo implode(',', array_keys($regacyHelper->lists)); ?>" name="regacy_visible_lists" />
		<input type="hidden" value="WordPress user profile" name="acy_source" />
        <?php
    }

    public function synchSaveUsers($userId, $oldUser = null)
    {
        if (empty($userId)) return;

        $isnew = empty($oldUser);
        $cmsUser = get_user_by('id', $userId);
        if (empty($cmsUser->user_email)) return;

        $user = [
            'email' => $cmsUser->user_email,
            'id' => $cmsUser->ID,
            'block' => 0,
        ];

        if (!empty($cmsUser->display_name)) {
            $user['name'] = $cmsUser->display_name;
        } elseif (!empty($cmsUser->user_nicename)) {
            $user['name'] = $cmsUser->user_nicename;
        }

        $oldUser = empty($oldUser->user_email) ? null : ['email' => $oldUser->user_email];

        $userClass = new UserClass();
        $userClass->synchSaveCmsUser($user, $isnew, $oldUser);
    }

    public function synchDeleteUsers($userId)
    {
        $cmsUser = get_user_by('id', $userId);
        if (empty($cmsUser->user_email)) return;

        $userClass = new UserClass();
        $userClass->synchDeleteCmsUser($cmsUser->user_email);
    }
}

$acyUsersynch = new acyUsersynch();
