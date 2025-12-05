
<?php
/**
 * ---------------------------------------------------------------------
 * ITSM-NG
 * Copyright (C) 2022 ITSM-NG and contributors.
 *
 * https://www.itsm-ng.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of ITSM-NG.
 *
 * ITSM-NG is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ITSM-NG is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ITSM-NG. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

class PluginWorkflowsWorkflow extends CommonDBTM
{
    public static $rightname = "plugin_workflows";

    public static function install()
    {
        global $DB;

        $table = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = <<<SQL
              CREATE TABLE `$table` (
                  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'RELATION to glpi_profiles (id)' ,
                  `name` VARCHAR(255) collate utf8_unicode_ci NOT NULL,
                  `description` TEXT collate utf8_unicode_ci,
                  `content` LONGTEXT collate utf8_unicode_ci,
                  PRIMARY KEY (`id`)
              ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

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

    public static function getTypeName($nb = 0)
    {
        return _n('Workflow', 'Workflows', $nb, 'workflow');
    }

    public static function getIcon()
    {
        return 'fas fa-project-diagram';
    }

    public static function getMenuContent(): array
    {
        $menu = [
            'title' => self::getTypeName(2),
            'page' => self::getSearchURL(false),
            'icon' => self::getIcon(),
            'links' => [
                'search' => PluginWorkflowsWorkflow::getSearchURL(),
            ],
        ];
        if (Session::haveRight('plugin_workflows', CREATE)) {
            $menu['links']['add'] = PluginWorkflowsWorkflow::getFormURL(false);
        }

        return $menu;
    }

    public static function request($url = '', $method = 'GET', $data = null, $async = false)
    {
        $config = PluginWorkflowsConfig::getConfigValues();
        $endpoint = $config['host'] . ':' . $config['port'] . '/api/' . $url;
        $key = $config['key'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, !$async); // no wait if async
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method === 'POST' && $data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        if ($key) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $key,
                'x-api-key: ' . $key,
            ]);
        }

        if ($async) {
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100); // small timeout to trigger the request
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 100);
            curl_exec($ch); // do not capture output
            curl_close($ch);
            return true;
        }

        $result = curl_exec($ch);
        curl_close($ch);
        if ($json = json_decode($result, true)) {
            return $json;
        }
        return $result;
    }

    public static function checkConnection()
    {
        $return = self::request('status');

        if (isset($return['ok']) && $return['ok']) {
            return true;
        }
        return true/*false*/;
    }

    public function run($data)
    {
        $ret = self::request('engine/start', 'POST', [
            'data' => $data,
            'name' => $this->fields['name'],
            'options' => [],
            'startNodeId' => null,
            'userId'=> null,
        ], true);
        return !empty($ret);
    }

    public function showForm()
    {
        $config = PluginWorkflowsConfig::getConfigValues();

        if (!empty($this->fields['name'])) {
            if ($config['use_proxy']) {
                $url = Plugin::getWebDir('workflows') . '/front/proxy.php?path=' . urlencode('/model/edit/' . $this->fields['name']);
                echo <<<HTML
                    <div class="containter">
                        <iframe id="workflow-iframe" src="{$url}" style="height: 100vh; width: 100%;"></iframe>
                    </div>
                    <script>
                        $(function() {
                            if (window.location.protocol === 'https:') {
                                var iframe = $('#workflow-iframe');
                                var src = iframe.attr('src');
                                if (src.indexOf('?') === -1) {
                                    src += '?https';
                                } else {
                                    src += '&https';
                                }
                                iframe.attr('src', src);
                            }
                        });
                    </script>
                HTML;
            } else {
                $url = $config['host'] . ':' . $config['port'] . '/model/edit/' . $this->fields['name'];
                echo <<<HTML
                    <div class="containter">
                        <iframe src="{$url}" style="height: 100vh; width: 100%;"></iframe>
                    </div>
                    HTML;
            }
        }

        $form = [
            'action' => self::getFormURL(),
            'itemtype' => self::getType(),
            'content' => [
                $this->getTypeName() => [
                    'visible' => true,
                    'inputs' => [
                        __('Name') => [
                            'type' => 'text',
                            'name' => 'name',
                            'value' => $this->fields['name'] ?? '',
                            'col_lg' => 12,
                            'col_md' => 12,
                            'required' => true,
                        ],
                        __('Description') => [
                            'type' => 'textarea',
                            'name' => 'description',
                            'value' => $this->fields['description'] ?? '',
                            'col_lg' => 12,
                            'col_md' => 12,
                        ],
                    ]
                ]
            ],
        ];
        renderTwigForm($form, '', $this->fields);
    }
}
