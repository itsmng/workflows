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
                  ('key', ''),
                  ('use_proxy', 0);
            SQL;
            $DB->queryOrDie($initQuery, $DB->error());
        }
        if (!$DB->fieldExists($table, 'use_proxy')) {
            $query = "INSERT INTO `$table` (`name`, `value`) VALUES ('use_proxy', '0')";
            $DB->queryOrDie($query, $DB->error());
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
                        __('Use proxy', 'workflows') => [
                            'type' => 'checkbox',
                            'name' => 'use_proxy',
                            'value' => $config['use_proxy'] ?? 0,
                        ]
                    ],
                ],
            ],
        ];
        renderTwigForm($form, '', $this->fields);
        
        // BPMN server status check view
        echo '<hr>';
        echo '<div id="bpmn_status">';
        echo '<button id="check_bpmn_status" class="btn btn-secondary">'. __('Check BPMN Server Connection','workflows') .'</button>';
        echo '<div id="bpmn_status_result" style="margin-top:10px;"></div>';
        echo '</div>';
        echo '<script>';
        echo <<<JS
        (function() {
          $('#check_bpmn_status').on('click', function() {
            $('#bpmn_status_result').html("<em>" + __('Loading...','workflows') + "</em>");
            const host = $('input[name="host"]').val();
            const port = $('input[name="port"]').val();
            const useProxy = $('input[name="use_proxy"]').is(':checked');
            let apiUrl;
            if (useProxy) {
                apiUrl = '/plugins/workflows/front/proxy.php?path=' + encodeURIComponent('/api/status');
            } else {
                apiUrl = host + (port ? ':' + port : '') + '/api/status';
            }
            fetch(apiUrl)
              .then(function(response) {
                if (!response.ok) { throw new Error(response.statusText); }
                return response.json();
              })
              .then(function(data) {
                let html;
                if (data.status) {
                  html = '<ul>' +
                    '<li>' + __('Version:', 'workflows') + ' ' + data.status.version + '</li>' +
                    '<li>' + __('Engine Running:', 'workflows') + ' ' + data.status.engineRunning + '</li>' +
                    '<li>' + __('Engine Calls:', 'workflows') + ' ' + data.status.engineCalls + '</li>' +
                    '<li>' + __('Memory Usage:', 'workflows') + ' ' + (data.status.memoryUsage !== null ? data.status.memoryUsage : '---') + '</li>' +
                    '</ul>';
                } else if (data.error) {
                  html = '<span class="error">' + data.error + '</span>';
                } else {
                  html = '<span class="error">' + __('Invalid response','workflows') + '</span>';
                }
                $('#bpmn_status_result').html(html);
              })
              .catch(function(error) {
                $('#bpmn_status_result').html('<span class="error">' + error.toString() + ' (Check console for details)</span>');
              });
          });
        })();
        JS;
        echo '</script>';
    }
}
