<?php

trait MemberpressInsertion
{
    public function dynamicText(?int $mailId): ?object
    {
        return $this->pluginDescription;
    }

    public function textPopup(): void
    {
        ?>
		<script type="text/javascript">
            function changeMemberPressTag(tagname, element) {
                if (!tagname) return;
                setTag('{<?php echo esc_attr($this->name); ?>:' + tagname + '}', element);
            }
		</script>

        <?php
        $fields = $this->getMeprCustomFields();

        if (empty($fields)) {
            ?>
			<h2 class="cell text-center acym__title__primary__color margin-top-2">
                <?php echo esc_html(acym_translationSprintf('ACYM_YOU_DONT_HAVE_PLUGIN_CUSTOM_FIELD', 'MemberPress')); ?>
			</h2>
            <?php
            return;
        }

        $text = '<div class="acym__popup__listing text-center grid-x">';

        foreach ($fields as $field) {
            $text .= '<div style="cursor:pointer" class="grid-x medium-12 cell acym__row__no-listing acym__listing__row__popup text-left" onclick="changeMemberPressTag(\''.$field['field_key'].'\', jQuery(this));" >
                        <div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.$field['field_name'].'</div>
                        <div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.$field['field_type'].'</div>
                     </div>';
        }

        $text .= '</div>';

        echo wp_kses(
            $text,
            [
                'div' => [
                    'class' => [],
                    'style' => [],
                    'onclick' => [],
                ],
            ]
        );
    }

    public function replaceUserInformation(object &$email, ?object &$user, bool $send = true): void
    {
        $extractedTags = $this->pluginHelper->extractTags($email, $this->name);
        $fields = $this->getMeprCustomFields();
        if (empty($extractedTags)) return;

        $userCMS = empty($user->cms_id) ? [] : get_user_meta($user->cms_id);

        $tags = [];
        foreach ($extractedTags as $key => $tag) {
            if (!empty($tags[$key])) continue;

            if (empty($userCMS[$tag->id])) {
                $finalValue = $fields[$tag->id]['default_value'];
            } else {
                $finalValue = $userCMS[$tag->id];
            }

            if (is_array($finalValue)) $finalValue = $finalValue[0];

            $tags[$key] = $this->handleSerialize($finalValue, $fields[$tag->id]);
        }

        $this->pluginHelper->replaceTags($email, $tags);
    }

    private function handleSerialize($value, $field)
    {
        $valueUnserialize = unserialize($value);

        if ($valueUnserialize !== false && !empty($field['options'])) {
            $finalValue = [];
            foreach ($field['options'] as $option) {
                if (in_array($option['option_value'], $valueUnserialize)) $finalValue[] = $option['option_name'];
            }
            $finalValue = implode(', ', $finalValue);
        } elseif ($valueUnserialize !== false) {
            $finalValue = implode(', ', $valueUnserialize);
        } elseif ($valueUnserialize === false && !empty($field['options'])) {
            $finalValue = [];
            foreach ($field['options'] as $option) {
                if ($option['option_value'] == $value) $finalValue[] = $option['option_name'];
            }
            $finalValue = implode(', ', $finalValue);
        } else {
            $finalValue = $value;
        }

        return $finalValue;
    }

    private function getMeprCustomFields(): array
    {
        $meprOptions = acym_loadResult('SELECT option_value FROM `wp_options` WHERE option_name = "mepr_options"');
        if (empty($meprOptions)) {
            return [];
        }

        $meprOptions = unserialize($meprOptions);
        $meprOptions = json_decode(json_encode($meprOptions), true);
        if (empty($meprOptions['custom_fields'])) {
            return [];
        }

        $customFields = [];
        foreach ($meprOptions['custom_fields'] as $customField) {
            $customFields[$customField['field_key']] = $customField;
        }

        return $customFields;
    }
}
