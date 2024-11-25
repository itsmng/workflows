<?php

global $CFG_GLPI;
// Version of the plugin (major.minor.bugfix)
define('WORKFLOWS_VERSION', '1.0.0');

define('WORKFLOWS_ITSMNG_MIN_VERSION', '2.0');

/**
 * Define the plugin's version and informations
 *
 * @return Array [name, version, author, homepage, license, minGlpiVersion]
 */
function plugin_version_workflows()
{
    $requirements = [
       'name'           => 'Workflows Plugin',
       'version'        => WORKFLOWS_VERSION,
       'author'         => 'ITSMNG Team',
       'homepage'       => 'https://github.com/itsmng/plugin-workflows',
       'license'        => '<a href="../plugins/plugin-workflows/LICENSE" target="_blank">GPLv3</a>',
    ];
    return $requirements;
}

/**
 * Initialize all classes and generic variables of the plugin
 */
function plugin_init_workflows()
{
    global $PLUGIN_HOOKS;

    // Set the plugin CSRF compliance (required since GLPI 0.84)
    $PLUGIN_HOOKS['csrf_compliant']['workflows'] = true;

    // Register profile rights
    Plugin::registerClass(PluginWorkflowsProfile::class, ['addtabon' => 'Profile']);
    $PLUGIN_HOOKS['change_profile']['workflows'] = [PluginWorkflowsProfile::class, 'changeProfile'];

    if (Session::haveRight('plugin_workflows', READ)) {
        $PLUGIN_HOOKS['config_page']['workflows'] = 'front/config.form.php';
        $PLUGIN_HOOKS['menu_toadd']['workflows']['admin'] = PluginWorkflowsWorkflow::class;
    }
}

/**
 * Check plugin's prerequisites before installation
 *
 * @return boolean
 */
function workflows_check_prerequisites()
{
    $prerequisitesSuccess = true;

    if (version_compare(ITSM_VERSION, WORKFLOWS_ITSMNG_MIN_VERSION, 'lt')) {
        echo "This plugin requires ITSM >= " . WORKFLOWS_ITSMNG_MIN_VERSION . "<br>";
        $prerequisitesSuccess = false;
    }

    return $prerequisitesSuccess;
}

/**
 * Check plugin's config before activation (if needed)
 *
 * @param string $verbose Set true to show all messages (false by default)
 * @return boolean
 */
function workflows_check_config($verbose = false)
{
    if ($verbose) {
        echo "Checking plugin configuration<br>";
    }
    return true;
}
