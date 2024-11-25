<?php

class PluginWorkflowsConfig extends CommonDBTM
{
    public static function install()
    {
        global $DB;

        $table = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = <<<SQL
              CREATE TABLE `$table` (
                  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'RELATION to glpi_profiles (id)' ,
                  `name` VARCHAR(255) collate utf8_unicode_ci NOT NULL,
                  `value` TEXT collate utf8_unicode_ci default NULL,
                  PRIMARY KEY (`id`)
              ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;
            $DB->queryOrDie($query, $DB->error());

            $initQuery = <<<SQL
              INSERT INTO `$table` (`name`, `value`) VALUES
                  ('host', ''),
                  ('port', ''),
                  ('key', '');
SQL;
            $DB->queryOrDie($initQuery, $DB->error());
        }
        return true;
    }

    public static function uninstall()
    {
        global $DB;

        $table = self::getTable();

        if ($DB->tableExists($table)) {
            $query = <<<SQL
              DROP TABLE `$table`
SQL;

            $DB->queryOrDie($query, $DB->error());
        }

        return true;
    }

    public static function getConfigValues()
    {
        global $DB;

        $table = self::getTable();

        $query = <<<SQL
          SELECT name, value from $table
SQL;

        $results = iterator_to_array($DB->query($query));

        foreach ($results as $id => $result) {
            $value = $result['value'];
            if ($result['name'] == 'key') {
                $value = Toolbox::sodiumDecrypt($value);
            }
            $results[$result['name']] = $value;
            unset($results[$id]);
        }
        return $results;
    }

    public static function updateConfigValues($values)
    {
        global $DB;

        $table = self::getTable();
        $fields = self::getConfigValues();

        if (isset($values['key'])) {
            $values['key'] = Toolbox::sodiumEncrypt($values['key']);
        }

        foreach (array_keys($fields) as $key) {
            $query = <<<SQL
              UPDATE $table
              SET value='{$values[$key]}'
              WHERE name='{$key}'
SQL;
            $DB->query($query);
        }
        return true;
    }

    /**
     * Displays the configuration page for the plugin
     *
     * @return void
     */
    public function showConfigForm()
    {
        $config = self::getConfigValues();
        $form = [
            'action' => self::getFormURL(),
            'buttons' => [
                [
                    'name' => 'update',
                    'type' => 'submit',
                    'class' => 'btn btn-secondary',
                    'value' => __('Save'),
                ],
            ],
            'content' => [
                PluginWorkflowsWorkflow::getTypeName() => [
                    'visible' => true,
                    'inputs' => [
                        __('Host', 'workflows') => [
                            'type' => 'text',
                            'name' => 'host',
                            'value' => $config['host'],
                        ],
                        __('Port', 'workflows') => [
                            'type' => 'text',
                            'name' => 'port',
                            'value' => $config['port'],
                        ],
                        __('Api key', 'workflows') => [
                            'type' => 'text',
                            'name' => 'key',
                            'value' => $config['key'],
                        ],
                    ],
                ],
            ],
        ];
        renderTwigForm($form, '', $this->fields);
    }
}
