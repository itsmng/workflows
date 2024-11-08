
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
    static $rightname = "plugin_workflows";

    static function install() {
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

    static function uninstall() {
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

    static function getTypeName($nb = 0)
    {
        return _n('Workflow', 'Workflows', $nb, 'workflow');
    }

    static function getIcon()
    {
        return 'fas fa-project-diagram';
    }

    static function getMenuContent(): array
    {
        $menu = [
            'title' => self::getTypeName(2),
            'page' => self::getSearchURL(),
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

    function showForm()
    {
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
                        ],
                        __('Description') => [
                            'type' => 'textarea',
                            'name' => 'description',
                            'value' => $this->fields['description'] ?? '',
                            'col_lg' => 12,
                            'col_md' => 12,
                        ],
                        __('Diagram', 'workflow') => [
                            'content' => <<<HTML
                                <div id="bpmn-modeler" class="d-flex w-100">
                                    <div id="canvas" class="flex-grow-1" style="height: 600px; border: 1px solid #ccc;"></div>
                                    <div id="js-properties-panel" style="border: 1px solid #ccc;"></div>
                                </div>
                            HTML,
                            'col_lg' => 12,
                            'col_md' => 12,
                        ],
                    ]
                ]
            ],
        ];
        renderTwigForm($form, '', $this->fields);
        echo Html::css(Plugin::getWebDir('workflows') . '/node_modules/bpmn-js/dist/assets/diagram-js.css');
        echo Html::css(Plugin::getWebDir('workflows') . '/node_modules/bpmn-js/dist/assets/bpmn-js.css');
        echo Html::css(Plugin::getWebDir('workflows') . '/node_modules/bpmn-js/dist/assets/bpmn-font/css/bpmn.css');
        echo Html::css(Plugin::getWebDir('workflows') . '/node_modules/@bpmn-io/properties-panel/dist/assets/properties-panel.css');
        echo Html::script(Plugin::getWebDir('workflows') . '/node_modules/bpmn-js/dist/bpmn-modeler.development.js');
        echo Html::script(Plugin::getWebDir('workflows') . '/node_modules/bpmn-js-properties-panel/dist/bpmn-js-properties-panel.umd.js');
        echo Html::script(Plugin::getWebDir('workflows') . '/js/workflow.js');
    }
}
