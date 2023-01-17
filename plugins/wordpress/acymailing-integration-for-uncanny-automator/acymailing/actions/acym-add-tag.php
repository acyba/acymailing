<?php

use AcyMailing\Classes\TagClass;
use Uncanny_Automator\Recipe;

class ACYM_ADD_TAG
{
    use Recipe\Actions;

    const INTEGRATION_CODE = 'acymailing';
    const ACTION_CODE = 'ACYM_ADD_TAG';
    const ACTION_META = 'ACYM_TAG_NAME';

    public function __construct()
    {
        $this->setup_action();
    }

    protected function setup_action()
    {
        $this->set_integration(self::INTEGRATION_CODE);
        $this->set_action_code(self::ACTION_CODE);
        $this->set_action_meta(self::ACTION_META);
        $this->set_sentence(sprintf(__('Add a new tag for lists/campaigns {{Tag name:%s}}', 'acymailing-integration-for-uncanny-automator'), $this->get_action_meta()));
        $this->set_readable_sentence(__('Add a new tag for lists/campaigns', 'acymailing-integration-for-uncanny-automator'));
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
                            'label' => __('Name', 'uncanny-automator'),
                        ]
                    ),
                ],
            ]
        );
    }

    protected function process_action($user_id, $action_data, $recipe_id, $args, $parsed)
    {
        $tagName = Automator()->parse->text($action_data['meta'][$this->get_action_meta()], $recipe_id, $user_id, $args);
        if (empty($tagName)) {
            return;
        }

        $tagClass = new TagClass();
        $tag = new stdClass();
        $tag->name = $tagName;
        $tag->id_element = 0;
        $tag->type = 'list';
        $tagClass->save($tag);
        $tag->type = 'mail';
        $tagClass->save($tag);

        // complete this action successfully
        Automator()->complete->action($user_id, $action_data, $recipe_id);
    }
}
