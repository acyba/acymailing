<?php

use AcyMailing\Helpers\ImportHelper;
use AcyMailing\Core\AcymPlugin;
use AcyMailing\Types\OperatorInType;
use AcyMailing\Types\OperatorType;

class plgAcymUniversalfilter extends AcymPlugin
{
    public function __construct()
    {
        parent::__construct();

        $this->pluginDescription->name = 'Universal filters in automations';
        $this->pluginDescription->category = 'User management';
        $this->pluginDescription->description = '- Filter AcyMailing subscribers based on any data from your database<br />- Filter users based on email addresses in a specified text';
    }

    public function getColumnsForSelectedTable()
    {
        $defaultSelect = acym_select(
            [],
            acym_getVar('string', 'name', ''),
            acym_getVar('string', 'value', 'user_id'),
            [
                'class' => 'acym__select',
            ]
        );

        $table = acym_getVar('string', 'table', '');
        if (empty($table)) {
            echo $defaultSelect;
            exit;
        }

        $elements = acym_getColumns($table, false, false);
        if (empty($elements)) {
            echo $defaultSelect;
            exit;
        }

        $columns = [];
        $columns[0] = acym_getVar('string', 'defaultValue', '');
        foreach ($elements as $key => $value) {
            $columns[$value] = $value;
        }

        echo acym_select(
            $columns,
            acym_getVar('string', 'name', ''),
            acym_getVar('string', 'value', 'user_id'),
            [
                'class' => 'acym__select',
            ]
        );
        exit;
    }

    public function onAcymDeclareConditions(&$conditions)
    {
        $operator = new OperatorType();

        $conditions['classic']['universalfilter'] = new stdClass();
        $conditions['classic']['universalfilter']->name = acym_translation('ACYM_SIMPLE_QUERY');
        $conditions['classic']['universalfilter']->option = '<div class="cell grid-x grid-margin-x margin-y">';

        $firstTable = new stdClass();
        $firstTable->text = acym_translation('ACYM_SELECT_TABLE');
        $firstTable->value = 0;

        $tableChoices = [];
        $tableChoices[] = $firstTable;

        $tables = acym_getTableList();
        foreach ($tables as $oneTable) {
            $oneChoice = new stdClass();
            $oneChoice->text = $oneTable;
            $oneChoice->value = $oneTable;
            $tableChoices[] = $oneChoice;
        }

        $conditions['classic']['universalfilter']->option .= '<div class="cell grid-x">';
        $conditions['classic']['universalfilter']->option .= '<div class="cell medium-3 large-2 acym_vcenter margin-right-1">';
        $conditions['classic']['universalfilter']->option .= acym_translation('ACYM_TABLE_NAME');
        $conditions['classic']['universalfilter']->option .= '</div>';
        $conditions['classic']['universalfilter']->option .= '<div class="intext_select_automation cell">';
        $conditions['classic']['universalfilter']->option .= acym_select(
            $tableChoices,
            'acym_condition[conditions][__numor__][__numand__][universalfilter][conn_table]',
            null,
            [
                'class' => 'acym__select',
                'acym-automation-reload' => [
                    [
                        'plugin' => __CLASS__,
                        'trigger' => 'getColumnsForSelectedTable',
                        'change' => '#universalfilter_column_tochange___numor_____numand__',
                        'name' => 'acym_condition[conditions][__numor__][__numand__][universalfilter][column]',
                        'paramFields' => [
                            'table' => 'acym_condition[conditions][__numor__][__numand__][universalfilter][conn_table]',
                            'defaultValue' => 'acym_condition[conditions][__numor__][__numand__][universalfilter][column]',
                        ],
                    ],
                    [
                        'plugin' => __CLASS__,
                        'trigger' => 'getColumnsForSelectedTable',
                        'change' => '#universalfilter_where_tochange___numor_____numand__',
                        'name' => 'acym_condition[conditions][__numor__][__numand__][universalfilter][wherefield]',
                        'paramFields' => [
                            'table' => 'acym_condition[conditions][__numor__][__numand__][universalfilter][conn_table]',
                            'defaultValue' => 'acym_condition[conditions][__numor__][__numand__][universalfilter][wherefield]',
                        ],
                    ],
                ],
            ]
        );
        $conditions['classic']['universalfilter']->option .= '</div>';
        $conditions['classic']['universalfilter']->option .= '</div>';

        $conditions['classic']['universalfilter']->option .= '<div class="cell grid-x">';
        $conditions['classic']['universalfilter']->option .= '<div class="cell medium-3 large-2 acym_vcenter margin-right-1">';
        $conditions['classic']['universalfilter']->option .= acym_translation('ACYM_IDENTIFIER').acym_info('ACYM_IDENTIFIER_DESC');
        $conditions['classic']['universalfilter']->option .= '</div>';
        $conditions['classic']['universalfilter']->option .= '<div class="cell intext_select_automation" id="universalfilter_column_tochange___numor_____numand__">';
        $conditions['classic']['universalfilter']->option .= '<input 
                                                                type="text" 
                                                                disabled="disabled" 
                                                                name="acym_condition[conditions][__numor__][__numand__][universalfilter][column]" 
                                                                value="" />';
        $conditions['classic']['universalfilter']->option .= '</div>';
        $conditions['classic']['universalfilter']->option .= '</div>';

        $conditions['classic']['universalfilter']->option .= '<div class="cell grid-x">';
        $conditions['classic']['universalfilter']->option .= '<div class="cell medium-3 large-2 acym_vcenter margin-right-1">';
        $conditions['classic']['universalfilter']->option .= acym_translation('ACYM_CONDITION').acym_info('ACYM_CONDITION_DESC');
        $conditions['classic']['universalfilter']->option .= '</div>';
        $conditions['classic']['universalfilter']->option .= '<div class="cell grid-margin-x medium-auto grid-x margin-y">';
        $conditions['classic']['universalfilter']->option .= '<div class="intext_select_automation cell" id="universalfilter_where_tochange___numor_____numand__" style="vertical-align: top;">';
        $conditions['classic']['universalfilter']->option .= '<input 
                                                                type="text" 
                                                                disabled="disabled" 
                                                                name="acym_condition[conditions][__numor__][__numand__][universalfilter][wherefield]" 
                                                                value="" />';
        $conditions['classic']['universalfilter']->option .= '</div>';
        $conditions['classic']['universalfilter']->option .= '<div class="intext_select_automation cell">';
        $conditions['classic']['universalfilter']->option .= $operator->display('acym_condition[conditions][__numor__][__numand__][universalfilter][operator]');
        $conditions['classic']['universalfilter']->option .= '</div>';
        $conditions['classic']['universalfilter']->option .= '<div class="intext_select_automation cell">';
        $conditions['classic']['universalfilter']->option .= '<input 
                                                                type="text" 
                                                                name="acym_condition[conditions][__numor__][__numand__][universalfilter][wherevalue]" 
                                                                placeholder="'.acym_translation('ACYM_WHERE_VALUE', true).'"/>';
        $conditions['classic']['universalfilter']->option .= '</div>';
        $conditions['classic']['universalfilter']->option .= '</div>';
        $conditions['classic']['universalfilter']->option .= '</div>';
        $conditions['classic']['universalfilter']->option .= '</div>';


        $conditions['classic']['sqladvanced'] = new stdClass();
        $conditions['classic']['sqladvanced']->name = acym_translation('ACYM_ADVANCED_QUERY');
        $conditions['classic']['sqladvanced']->option = '<div class="cell grid-x grid-margin-x margin-y">';

        $conditions['classic']['sqladvanced']->option .= '<div class="cell">';
        $conditions['classic']['sqladvanced']->option .= '<div class="intext_select_automation margin-right-1">';
        $conditions['classic']['sqladvanced']->option .= acym_select(
            [
                'email' => 'ACYM_EMAIL',
                'cms_id' => 'ACYM_USER_CMSID',
                'id' => 'ACYM_USER_ID',
            ],
            'acym_condition[conditions][__numor__][__numand__][sqladvanced][source]',
            null,
            ['class' => 'acym__select'],
            'value',
            'text',
            false,
            true
        );
        $conditions['classic']['sqladvanced']->option .= '</div>';
        $operatorIn = new OperatorInType();
        $conditions['classic']['sqladvanced']->option .= '<div class="intext_select_automation">';
        $conditions['classic']['sqladvanced']->option .= $operatorIn->display('acym_condition[conditions][__numor__][__numand__][sqladvanced][type]');
        $conditions['classic']['sqladvanced']->option .= '</div>';
        $conditions['classic']['sqladvanced']->option .= '</div>';

        $conditions['classic']['sqladvanced']->option .= '<div class="cell large-8 xlarge-6 xxlarge-5">';
        $conditions['classic']['sqladvanced']->option .= '<textarea name="acym_condition[conditions][__numor__][__numand__][sqladvanced][query]" placeholder="SELECT email FROM ..."></textarea>';
        $conditions['classic']['sqladvanced']->option .= '</div>';

        $conditions['classic']['sqladvanced']->option .= '<div class="cell">'.acym_translation('ACYM_EXTERNAL_SERVER_DESC').'</div>';

        $conditions['classic']['sqladvanced']->option .= '<div class="cell"><div class="intext_select_automation"><input 
                                                                type="text" 
                                                                name="acym_condition[conditions][__numor__][__numand__][sqladvanced][conn_host]" 
                                                                placeholder="'.acym_translation('ACYM_SMTP_SERVER', true).'"/></div></div>';
        $conditions['classic']['sqladvanced']->option .= '<div class="cell"><div class="intext_select_automation"><input 
                                                                type="text" 
                                                                name="acym_condition[conditions][__numor__][__numand__][sqladvanced][conn_db]" 
                                                                placeholder="'.acym_translation('ACYM_DATABASE', true).'"/></div></div>';
        $conditions['classic']['sqladvanced']->option .= '<div class="cell"><div class="intext_select_automation"><input 
                                                                type="text" 
                                                                name="acym_condition[conditions][__numor__][__numand__][sqladvanced][conn_login]" 
                                                                placeholder="'.acym_translation('ACYM_USERNAME', true).'"/></div></div>';
        $conditions['classic']['sqladvanced']->option .= '<div class="cell"><div class="intext_select_automation"><input 
                                                                type="text" 
                                                                name="acym_condition[conditions][__numor__][__numand__][sqladvanced][conn_pwd]" 
                                                                placeholder="'.acym_translation('ACYM_PASSWORD', true).'"/></div></div>';
        $conditions['classic']['sqladvanced']->option .= '</div>';


        $conditions['classic']['extractaddresses'] = new stdClass();
        $conditions['classic']['extractaddresses']->name = acym_translation('ACYM_EXTRACT_ADDRESSES');
        $conditions['classic']['extractaddresses']->option = '<div class="cell grid-x grid-margin-x margin-y">';

        $conditions['classic']['extractaddresses']->option .= '<div class="cell large-8 xlarge-6 xxlarge-5">';
        $conditions['classic']['extractaddresses']->option .= '<textarea 
                                                                name="acym_condition[conditions][__numor__][__numand__][extractaddresses][text]" 
                                                                placeholder="'.acym_translation('ACYM_EXTRACT_ADDRESSES_DESC', true).'"></textarea>';
        $conditions['classic']['extractaddresses']->option .= '</div>';
        $conditions['classic']['extractaddresses']->option .= '</div>';
    }

    public function onAcymProcessCondition_universalfilter(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_universalfilter($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_universalfilter(&$query, $options, $num)
    {
        if (empty($options['conn_table']) || !in_array($options['conn_table'], acym_getTableList()) || empty($options['column'])) return;

        $columns = acym_getColumns($options['conn_table'], false, false);
        if (empty($columns) || !in_array($options['column'], $columns)) return;

        $acyOperator = in_array(strtolower($options['column']), ['email', 'e-mail', 'mail', 'e_mail', 'courriel', 'user_email']) ? 'email' : 'cms_id';
        $options['conn_table'] = acym_secureDBColumn($options['conn_table']);
        $options['column'] = acym_secureDBColumn($options['column']);
        $query->join['universalfilter'.$num] = $options['conn_table'].' AS universalfiltertable'.$num.' ON universalfiltertable'.$num.'.'.$options['column'].' = user.'.$acyOperator;


        if (!empty($options['wherefield']) && in_array($options['wherefield'], $columns)) {
            $options['wherevalue'] = acym_replaceDate($options['wherevalue']);

            $query->where[] = $query->convertQuery('universalfiltertable'.$num, $options['wherefield'], $options['operator'], $options['wherevalue']);
        }
    }

    public function onAcymProcessCondition_sqladvanced(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_sqladvanced($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function filterError(&$query, $options, $message)
    {
        if (empty($options['countTotal'])) {
            acym_display(acym_translation($message), 'error');
        }
        $query->where[] = '1 = 0';
    }

    private function processConditionFilter_sqladvanced(&$query, $options, $num)
    {
        if (empty($options['query'])) return;
        $sqlQuery = strtolower($options['query']);

        if (strpos($sqlQuery, 'select') !== 0) {
            $this->filterError($query, $options, 'ACYM_WRONG_SQL_QUERY');

            return;
        }

        $blacklistedTerms = ['truncate', 'drop', 'alter', 'update', 'insert'];
        foreach ($blacklistedTerms as $blacklistedTerm) {
            if (strpos($sqlQuery, $blacklistedTerm) !== false) {
                $this->filterError($query, $options, 'ACYM_WRONG_SQL_QUERY');

                return;
            }
        }

        if (!in_array($options['source'], ['id', 'email', 'cms_id'])) $options['source'] = 'cms_id';
        $options['query'] = acym_replaceDate($options['query']);

        $where = 'user.'.$options['source'];
        $where .= $options['type'] == 'in' ? ' IN ' : ' NOT IN ';

        // Connecting to the current db
        if (empty($options['conn_host'])) {
            $query->where[] = $where.'('.$options['query'].')';

            return;
        }

        if (empty($options['conn_db']) || empty($options['conn_login']) || empty($options['conn_pwd'])) {
            $this->filterError($query, $options, 'ACYM_MISSING_PARAMETERS');

            return;
        }


        // We need to connect to another database
        $conn = @mysqli_connect($options['conn_host'], $options['conn_login'], $options['conn_pwd'], $options['conn_db']);
        if (empty($conn)) {
            $this->filterError($query, $options, acym_translationSprintf('ACYM_ERROR_CONNECTING', $options['conn_host'].' '.$options['conn_db']).': '.mysqli_connect_error());

            return;
        }

        $myQuery = mysqli_query($conn, $options['query']);
        if (!$myQuery) {
            $this->filterError($query, $options, acym_translation('ACYM_ERROR').': '.mysqli_error($conn));

            return;
        }

        $resultArray = [];
        while ($row = mysqli_fetch_row($myQuery)) {
            $resultArray[] = acym_escapeDB($row[0]);
        }
        if (empty($resultArray)) $resultArray = ['"-1"'];

        $myQuery->close();
        $conn->close();

        $query->where[] = $where.' ('.implode(',', $resultArray).') ';
    }

    public function onAcymProcessCondition_extractaddresses(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_extractaddresses($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_extractaddresses(&$query, $options, $num)
    {
        if (empty($options['text'])) return;

        preg_match_all('/'.acym_getEmailRegex().'/i', $options['text'], $results);
        $allEmails = empty($results[0]) ? ['-1'] : $results[0];
        $query->where[] = 'user.email IN ("'.implode('","', $allEmails).'")';
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        if (!empty($automationCondition['universalfilter'])) {
            $finalText = acym_translationSprintf('ACYM_UNIVERSAL_FILTER_SUMMARY', $automationCondition['universalfilter']['conn_table']);

            if (!empty($automationCondition['universalfilter']['wherefield'])) {
                $finalText .= '<br/>'.acym_translationSprintf(
                        'ACYM_CONDITION_ACY_FIELD_SUMMARY',
                        $automationCondition['universalfilter']['wherefield'],
                        $automationCondition['universalfilter']['operator'],
                        $automationCondition['universalfilter']['wherevalue']
                    );
            }

            $automationCondition = $finalText;
        }

        if (!empty($automationCondition['sqladvanced'])) {
            $automationCondition = acym_translation('ACYM_ADVANCED_QUERY_SUMMARY');
        }

        if (!empty($automationCondition['extractaddresses'])) {
            $automationCondition = acym_translation('ACYM_EXTRACT_ADDRESSES_SUMMARY');
        }

        if (!empty($automationCondition['import'])) {
            $automationCondition = acym_translation('ACYM_IMPORT_FILTER_SUMMARY');
        }
    }

    public function onAcymDeclareFilters(&$filters)
    {
        $this->filtersFromConditions($filters);

        $filters['import'] = new stdClass();
        $filters['import']->name = acym_translation('ACYM_IMPORT_DATABASE');
        $filters['import']->option = '<div class="cell grid-x grid-margin-x margin-y acym__filter__nocount acym__filter__noseeuser">';

        $filters['import']->option .= '<div class="cell">';
        $filters['import']->option .= '<div class="acym__title acym__title__secondary">'.acym_translation('ACYM_IMPORT_DATABASE').'</div>';
        $filters['import']->option .= '</div>';

        $filters['import']->option .= '<div class="cell grid-x">';
        $filters['import']->option .= '<div class="cell medium-3 large-2 xlarge-1 acym_vcenter margin-right-1">';
        $filters['import']->option .= acym_translation('ACYM_TABLE_NAME').'*';
        $filters['import']->option .= '</div>';

        // When selecting the table, all the dropdowns will be loaded to let the user select the column he wants
        $tableSelectRefresh = [
            [
                'plugin' => __CLASS__,
                'trigger' => 'getColumnsForSelectedTable',
                'change' => '#import_where_tochange___numor_____numand__',
                'name' => 'acym_action[filters][__numor__][__numand__][import][wherefield]',
                'paramFields' => [
                    'table' => 'acym_action[filters][__numor__][__numand__][import][conn_table]',
                    'defaultValue' => 'acym_action[filters][__numor__][__numand__][import][wherefield]',
                ],
            ],
        ];

        $columnsAssignmentOptions = '';
        $userFields = acym_getColumns('user');

        // Show the email field first
        unset($userFields[array_search('email', $userFields)]);
        array_unshift($userFields, 'email');

        foreach ($userFields as $oneUserField) {
            if (in_array($oneUserField, ['id', 'key', 'automation'])) continue;

            $tableSelectRefresh[] = [
                'plugin' => __CLASS__,
                'trigger' => 'getColumnsForSelectedTable',
                'change' => '#import_'.$oneUserField.'_tochange___numor_____numand__',
                'name' => 'acym_action[filters][__numor__][__numand__][import]['.$oneUserField.']',
                'paramFields' => [
                    'table' => 'acym_action[filters][__numor__][__numand__][import][conn_table]',
                    'defaultValue' => 'acym_action[filters][__numor__][__numand__][import]['.$oneUserField.']',
                ],
            ];

            $columnsAssignmentOptions .= '<div class="cell grid-x">';
            $columnsAssignmentOptions .= '<div class="cell medium-3 large-2 xlarge-1 acym_vcenter margin-right-1">';
            $columnsAssignmentOptions .= $oneUserField.($oneUserField === 'email' ? '*' : '');
            $columnsAssignmentOptions .= '</div>';

            $columnsAssignmentOptions .= '<div class="intext_select_automation cell" id="import_'.$oneUserField.'_tochange___numor_____numand__">';
            $columnsAssignmentOptions .= '<input 
                                            type="text" 
                                            disabled="disabled" 
                                            name="acym_action[filters][__numor__][__numand__][import]['.$oneUserField.']" 
                                            value="'.acym_translation('ACYM_UNASSIGNED', true).'" />';
            $columnsAssignmentOptions .= '</div>';
            $columnsAssignmentOptions .= '</div>';
        }

        $firstTable = new stdClass();
        $firstTable->text = acym_translation('ACYM_SELECT_TABLE');
        $firstTable->value = 0;

        $tableChoices = [];
        $tableChoices[] = $firstTable;

        $tables = acym_getTableList();
        foreach ($tables as $oneTable) {
            $oneChoice = new stdClass();
            $oneChoice->text = $oneTable;
            $oneChoice->value = $oneTable;
            $tableChoices[] = $oneChoice;
        }

        $filters['import']->option .= '<div class="intext_select_automation cell">';
        $filters['import']->option .= acym_select(
            $tableChoices,
            'acym_action[filters][__numor__][__numand__][import][conn_table]',
            null,
            [
                'class' => 'acym__select',
                'acym-automation-reload' => $tableSelectRefresh,
            ]
        );
        $filters['import']->option .= '</div>';
        $filters['import']->option .= '</div>';
        $filters['import']->option .= '<div class="cell grid-x">';
        $filters['import']->option .= '<div class="cell medium-3 large-2 xlarge-1 acym_vcenter margin-right-1">';
        $filters['import']->option .= acym_translation('ACYM_WHERE');
        $filters['import']->option .= '</div>';
        $filters['import']->option .= '<div class="intext_select_automation cell margin-right-1" id="import_where_tochange___numor_____numand__">';
        $filters['import']->option .= '<input 
                                        type="text" 
                                        disabled="disabled" 
                                        name="acym_action[filters][__numor__][__numand__][import][wherefield]" 
                                        value="'.acym_translation('ACYM_CHOOSE_COLUMN', true).'" />';
        $filters['import']->option .= '</div>';
        $filters['import']->option .= '<div class="intext_select_automation cell margin-right-1">';
        $operator = new OperatorType();
        $filters['import']->option .= $operator->display('acym_action[filters][__numor__][__numand__][import][operator]');
        $filters['import']->option .= '</div>';
        $filters['import']->option .= '<div class="intext_select_automation cell">';
        $filters['import']->option .= '<input 
                                        type="text" 
                                        name="acym_action[filters][__numor__][__numand__][import][wherevalue]" 
                                        placeholder="'.acym_translation('ACYM_WHERE_VALUE', true).'"/>';
        $filters['import']->option .= '</div>';
        $filters['import']->option .= '</div>';

        $filters['import']->option .= '<div class="cell margin-top-1">';
        $filters['import']->option .= '<div class="acym__title acym__title__secondary">'.acym_translation('ACYM_FIELD_MATCHING').'</div>';
        $filters['import']->option .= '</div>';
        $filters['import']->option .= $columnsAssignmentOptions;

        $filters['import']->option .= '</div>';
    }

    public function onAcymProcessFilterCount_universalfilter(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_universalfilter($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_universalfilter(&$query, $options, $num)
    {
        $this->processConditionFilter_universalfilter($query, $options, $num);
    }

    public function onAcymProcessFilterCount_sqladvanced(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_sqladvanced($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_sqladvanced(&$query, $options, $num)
    {
        $this->processConditionFilter_sqladvanced($query, $options, $num);
    }

    public function onAcymProcessFilterCount_extractaddresses(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_extractaddresses($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_extractaddresses(&$query, $options, $num)
    {
        $this->processConditionFilter_extractaddresses($query, $options, $num);
    }

    public function onAcymProcessFilterCount_import(&$query, $options, $num)
    {
        return acym_translation('ACYM_NO_PREVIEW_AVAILABLE');
    }

    public function onAcymProcessFilter_import(&$query, $options, $num)
    {
        // Make sure we have the needed information
        if (empty($options['conn_table']) || empty($options['email']) || !empty($options['countTotal'])) {
            $query->where[] = '1 = 0';

            return;
        }
        $columns = acym_getColumns($options['conn_table'], false, false);
        if (empty($columns)) {
            $query->where[] = '1 = 0';

            return;
        }

        $import = new ImportHelper();
        $import->tableName = acym_secureDBColumn($options['conn_table']);

        // Filter the imported users in case the user selected some actions
        $newFilter = [];
        $newFilter['query'] = 'SELECT import.`'.acym_secureDBColumn($options['email']).'` FROM '.acym_secureDBColumn($options['conn_table']).' AS import ';
        $newFilter['query'] .= 'WHERE import.`'.acym_secureDBColumn($options['email']).'` LIKE "%@%"';
        $newFilter['type'] = 'in';
        $newFilter['source'] = 'email';

        if (!empty($options['wherefield']) && in_array($options['wherefield'], $columns)) {
            $options['wherevalue'] = acym_replaceDate($options['wherevalue']);
            $newFilter['query'] .= ' AND '.$query->convertQuery('import', $options['wherefield'], $options['operator'], $options['wherevalue']);
            $import->dbWhere[] = $query->convertQuery('', $options['wherefield'], $options['operator'], $options['wherevalue']);
        }

        // Only keep the fields mapping options
        unset($options['conn_table']);
        unset($options['wherefield']);
        unset($options['operator']);
        unset($options['wherevalue']);
        $import->fieldsMap = $options;

        if (!$import->database(true)) {
            $query->where[] = '1 = 0';

            return;
        }

        $this->onAcymProcessFilter_sqladvanced($query, $newFilter, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
