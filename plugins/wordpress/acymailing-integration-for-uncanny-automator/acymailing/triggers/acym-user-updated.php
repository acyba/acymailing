<?php

use AcyMailing\Classes\UserClass;
use Uncanny_Automator\Recipe;

class ACYM_USER_UPDATED
{
    use Recipe\Triggers;

    const INTEGRATION_CODE = 'acymailing';
    const TRIGGER_CODE = 'ACYM_USER_UPDATED';
    const TRIGGER_META = 'ACYM_STATUS';

    public function __construct()
    {
        $this->setup_trigger();
    }

    public function setup_trigger()
    {
        $this->set_integration(self::INTEGRATION_CODE);
        $this->set_trigger_code(self::TRIGGER_CODE);
        $this->set_trigger_meta(self::TRIGGER_META);
        // translators: %s: the trigger meta
        $this->set_sentence(sprintf(__('An AcyMailing subscriber is {{created or modified:%s}}', 'acymailing-integration-for-uncanny-automator'), $this->get_trigger_meta()));
        $this->set_readable_sentence(__('An AcyMailing subscriber is {{created or modified}}', 'acymailing-integration-for-uncanny-automator'));
        $this->set_options_callback([$this, 'load_options']);
        $this->add_action('onAcymAfterUserSave');
        $this->set_action_priority(20);
        $this->set_action_args_count(2);

        $this->register_trigger();
    }

    /**
     * Fill the "{{created of updated}}" dropdown with a select field. There are text, int and other field types too.
     * @return array
     */
    public function load_options(): array
    {
        return Automator()->utilities->keep_order_of_options(
            [
                'options' => [
                    Automator()->helpers->recipe->field->select(
                        [
                            'option_code' => $this->get_trigger_meta(),
                            // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                            'label' => __('Status', 'acymailing'),
                            'options' => [
                                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                                'created' => __('Created', 'acymailing'),
                                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                                'updated' => __('Modified', 'acymailing'),
                            ],
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * Validation trigger, whether it passes a basic criteria to run or not.
     *
     * @param ...$args
     *
     * @return bool
     */
    protected function validate_trigger(...$args): bool
    {
        if (empty($args[0][0]->id)) {
            return false;
        }

        $subscriber = $args[0][0];

        $userCLass = new UserClass();
        $subscriber = $userCLass->getOneById($subscriber->id);

        $this->set_user_id(empty($subscriber->cms_id) ? 0 : $subscriber->cms_id);

        return true;
    }

    /**
     * Whether to conditionally check something or not. Set to false and the trigger will fire without any condition check.
     *
     * @param ...$args
     *
     * @return void
     */
    protected function prepare_to_run(...$args)
    {
        $this->set_conditional_trigger(true);
    }

    /**
     * If set_conditional_trigger() is set to true, then this function kicks in
     *
     * @param ...$args
     *
     * @return mixed
     */
    public function validate_conditions(...$args)
    {
        $mustMatch = empty($args[0][1]) ? 'updated' : 'created';

        return $this->find_all($this->trigger_recipes()) // get all the recipes with this specific trigger selected
                    ->where([$this->get_trigger_meta()])
                    ->compare(['string_contains'])
                    ->match([$mustMatch]) // Match the selected status
                    ->format(['trim']) // format of the data (intval, or int, trim etc)
                    ->get(); // return matching recipes only
    }
}
