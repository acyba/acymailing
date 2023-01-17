<?php

use AcyMailing\Classes\UserClass;
use Uncanny_Automator\Recipe;

class ACYM_SAVE_USER
{
    use Recipe\Actions;

    const INTEGRATION_CODE = 'acymailing';
    const ACTION_CODE = 'ACYM_SAVE_USER';
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
        $this->set_sentence(sprintf(__('Save the AcyMailing subscriber {{Email address:%s}}', 'acymailing-integration-for-uncanny-automator'), $this->get_action_meta()));
        $this->set_readable_sentence(__('Save an AcyMailing {{subscriber}}', 'acymailing-integration-for-uncanny-automator'));
        $this->set_options_callback([$this, 'load_options']);

        $this->register_action();
    }

    public function load_options(): array
    {
        return Automator()->utilities->keep_order_of_options(
            [
                'options_group' => [
                    $this->get_action_meta() => [
                        Automator()->helpers->recipe->field->text(
                            [
                                'option_code' => 'ACYM_NAME',
                                'label' => __('Name', 'uncanny-automator'),
                                'input_type' => 'text',
                                'default' => '',
                                'required' => true,
                            ]
                        ),
                        Automator()->helpers->recipe->field->text(
                            [
                                'option_code' => 'ACYM_EMAIL',
                                'label' => __('Email address', 'uncanny-automator'),
                                'input_type' => 'email',
                                'default' => '',
                                'required' => true,
                            ]
                        ),
                    ],
                ],
            ]
        );
    }

    protected function process_action($user_id, $action_data, $recipe_id, $args, $parsed)
    {
        $user = new stdClass();
        $user->name = Automator()->parse->text($action_data['meta']['ACYM_NAME'], $recipe_id, $user_id, $args);
        $user->email = Automator()->parse->text($action_data['meta']['ACYM_EMAIL'], $recipe_id, $user_id, $args);

        if (!empty($user_id)) {
            $user->cms_id = $user_id;
        }

        $userClass = new UserClass();
        $existingUser = $userClass->getOneByEmail($user->email);

        if (!empty($existingUser)) {
            $user->id = $existingUser->id;
        }

        $userClass->save($user);

        // complete this action successfully
        Automator()->complete->action($user_id, $action_data, $recipe_id);
    }
}
