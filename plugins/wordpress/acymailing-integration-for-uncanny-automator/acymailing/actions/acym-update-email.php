<?php

use AcyMailing\Classes\UserClass;
use Uncanny_Automator\Recipe;

class ACYM_UPDATE_EMAIL
{
    use Recipe\Actions;

    const INTEGRATION_CODE = 'acymailing';
    const ACTION_CODE = 'ACYM_UPDATE_EMAIL';
    const ACTION_META = 'ACYM_USER_EMAIL';

    public function __construct()
    {
        $this->setup_action();
    }

    protected function setup_action()
    {
        $this->set_integration(self::INTEGRATION_CODE);
        $this->set_action_code(self::ACTION_CODE);
        $this->set_action_meta(self::ACTION_META);
        $this->set_sentence(sprintf(__('Change the email address for a subscriber {{Email address:%s}}', 'acymailing-integration-for-uncanny-automator'), $this->get_action_meta()));
        $this->set_readable_sentence(__('Change the {{email address}} for a subscriber', 'acymailing-integration-for-uncanny-automator'));
        $this->set_options_callback([$this, 'load_options']);

        $this->register_action();
    }

    public function load_options(): array
    {
        return Automator()->utilities->keep_order_of_options(
            [
                'options' => [
                    Automator()->helpers->recipe->field->text(
                        [
                            'option_code' => $this->get_action_meta(),
                            'label' => __('Email address', 'uncanny-automator'),
                            'input_type' => 'email',
                        ]
                    ),
                ],
            ]
        );
    }

    protected function process_action($user_id, $action_data, $recipe_id, $args, $parsed)
    {
        $newEmail = Automator()->parse->text($action_data['meta'][$this->get_action_meta()], $recipe_id, $user_id, $args);

        if (empty($user_id) || empty($newEmail)) {
            return;
        }

        $userClass = new UserClass();
        $existingUser = $userClass->getOneByEmail($newEmail);

        if (!empty($existingUser)) {
            return;
        }

        $currentUser = $userClass->getOneByCMSId($user_id);
        $currentUser->email = $newEmail;

        $userClass->save($currentUser);

        // complete this action successfully
        Automator()->complete->action($user_id, $action_data, $recipe_id);
    }
}
