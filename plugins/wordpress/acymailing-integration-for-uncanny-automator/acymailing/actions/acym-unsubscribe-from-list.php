<?php

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;
use Uncanny_Automator\Recipe;

class ACYM_UNSUBSCRIBE_FROM_LIST
{
    use Recipe\Actions;

    const INTEGRATION_CODE = 'acymailing';
    const ACTION_CODE = 'ACYM_UNSUBSCRIBE_FROM_LIST';
    const ACTION_META = 'ACYM_LIST';

    public function __construct()
    {
        $this->setup_action();
    }

    protected function setup_action()
    {
        $this->set_integration(self::INTEGRATION_CODE);
        $this->set_action_code(self::ACTION_CODE);
        $this->set_action_meta(self::ACTION_META);
        // translators: %s: the trigger meta
        $this->set_sentence(sprintf(__('Unsubscribe a user from {{a list:%s}}', 'acymailing-integration-for-uncanny-automator'), $this->get_action_meta()));
        $this->set_readable_sentence(__('Unsubscribe a user from {{a list}}', 'acymailing-integration-for-uncanny-automator'));
        $this->set_options_callback([$this, 'load_options']);

        $this->register_action();
    }

    public function load_options(): array
    {
        $listClass = new ListClass();
        $lists = $listClass->getAllForSelect(false);

        return Automator()->utilities->keep_order_of_options(
            [
                'options' => [
                    Automator()->helpers->recipe->field->select(
                        [
                            'option_code' => $this->get_action_meta(),
                            // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                            'label' => __('List', 'acymailing'),
                            'options' => $lists,
                        ]
                    ),
                ],
            ]
        );
    }

    protected function process_action($user_id, $action_data, $recipe_id, $args, $parsed)
    {
        $listId = intval($action_data['meta'][$this->get_action_meta()]);
        if (empty($listId)) {
            return;
        }

        $userClass = new UserClass();
        $user = $userClass->getOneByCMSId($user_id);

        if (empty($user)) {
            return;
        }

        $userClass->unsubscribe([$user->id], [$listId]);

        // complete this action successfully
        Automator()->complete->action($user_id, $action_data, $recipe_id);
    }
}
