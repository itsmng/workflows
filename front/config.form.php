<?php
include("../../../inc/includes.php");
require_once(Plugin::getPhpDir('workflows') . "/inc/config.class.php");

$plugin = new Plugin();

if($plugin->isActivated("workflows")) {
    $config = new PluginWorkflowsConfig();
    if(isset($_POST["update"])) {
        Session::checkRight("plugin_workflows_config", UPDATE);
        $config::updateConfigValues($_POST);
    } else {
        if (!Session::haveRight("plugin_workflows_config", READ | UPDATE)) {
            Html::displayRightError();
            return;
        }
        Html::header("Okta", $_SERVER["PHP_SELF"], "config", Plugin::class);
        $config->showConfigForm();
    }
} else {
    Html::header("settings", '', "config", "plugins");
    echo "<div class='center'><br><br><img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\" alt='warning'><br><br>";
    echo "<b>Please enable the plugin before configuring it</b></div>";
    Html::footer();
}

Html::footer();
