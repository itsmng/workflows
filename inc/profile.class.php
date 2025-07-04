<?php

class PluginWorkflowsProfile extends CommonDBTM
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

    /**
     * canCreate
     *
     * @return boolean
     */
    public static function canCreate()
    {
        if (isset($_SESSION["profile"])) {
            return ($_SESSION["profile"]['pluginworkflows'] == 'w');
        }
        return false;
    }

    /**
     * canView
     *
     * @return boolean
     */
    public static function canView()
    {
        if (isset($_SESSION["profile"])) {
            return ($_SESSION["profile"]['pluginworkflows'] == 'w' || $_SESSION["profile"]['pluginworkflows'] == 'r');
        }
        return false;
    }

    /**
     * createAdminAccess
     *
     * @param  int $ID
     * @return void
     */
    public static function createAdminAccess($ID)
    {
        $myProf = new self();
        if (!$myProf->getFromDB($ID)) {
            $myProf->add(array('id' => $ID, 'right' => 'w'));
        }
    }

    /**
     * addDefaultProfileInfos
     *
     * @param  int $profiles_id
     * @param  array $rights
     * @return void
     */
    public static function addDefaultProfileInfos($profiles_id, $rights)
    {
        $profileRight = new ProfileRight();

        foreach ($rights as $right => $value) {
            if (!countElementsInTable('glpi_profilerights', ['profiles_id' => $profiles_id, 'name' => $right])) {
                $myright['profiles_id'] = $profiles_id;
                $myright['name']        = $right;
                $myright['rights']      = $value;

                $profileRight->add($myright);

                $_SESSION['glpiactiveprofile'][$right] = $value;
            }
        }
    }

    /**
     * changeProfile
     *
     * @return void
     */
    public static function changeProfile()
    {
        $prof = new self();

        if ($prof->getFromDB($_SESSION['glpiactiveprofile']['id'])) {
            $_SESSION["glpi_plugin_workflows_profile"] = $prof->fields;
        } else {
            unset($_SESSION["glpi_plugin_workflows_profile"]);
        }
    }

    /**
     * getTabNameForItem
     *
     * @param  object $item
     * @param  int $withtemplate
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (Session::haveRight("profile", UPDATE) && $item->getType() == 'Profile') {
            return __('Workflows', 'workflows');
        }

        return '';
    }

    /**
     * displayTabContentForItem
     *
     * @param  object $item
     * @param  int $tabnum
     * @param  int $withtemplate
     * @return boolean
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == 'Profile') {

            $ID = $item->getID();
            $prof = new self();

            foreach (self::getRightsGeneral() as $right) {
                self::addDefaultProfileInfos($ID, [$right['field'] => 0]);
            }

            $prof->showForm($ID);
        }

        return true;
    }

    /**
     * getRightsGeneral
     *
     * @return array
     */
    public static function getRightsGeneral()
    {
        $rights = [
            [
                'itemtype'  => 'PluginWorkflowsProfile',
                'label'     => __('Workflows', 'pluginworkflows'),
                'field'     => 'plugin_workflows',
                'rights'    =>  [
                    UPDATE => __('Allow editing', 'pluginworkflows'),
                    READ => __('Allow viewing', 'pluginworkflows'),
                    CREATE => __('Allow creating', 'pluginworkflows'),
                    PURGE => __('Allow purging', 'pluginworkflows'),
                ],
                'default'   => 23
            ]
        ];

        return $rights;
    }

    /**
     * showForm
     *
     * @param  int $profiles_id
     * @param  boolean $openform
     * @param  boolean $closeform
     * @return void
     */
    public function showForm($profiles_id = 0, $openform = true, $closeform = true)
    {

        if (!Session::haveRight("profile", READ)) {
            return false;
        }

        echo "<div class='firstbloc'>";

        if (($canedit = Session::haveRight('profile', UPDATE)) && $openform) {
            $profile = new Profile();
            echo "<form method='post' action='".$profile->getFormURL()."'>";
        }

        $profile = new Profile();
        $profile->getFromDB($profiles_id);
        $rights = $this->getRightsGeneral();
        $profile->displayRightsChoiceMatrix($rights, ['default_class' => 'tab_bg_2', 'title' => __('General')]);

        if ($canedit && $closeform) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $profiles_id]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
            echo "</div>\n";
            Html::closeForm();
        }

        echo "</div>";
    }
}
