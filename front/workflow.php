<?php


include ("../../../inc/includes.php");

// Check if plugin is activated...
if (!(new Plugin())->isActivated('workflows')) {
    Html::displayNotFoundError();
}

$workflow = new PluginWorkflowsWorkflow();

Session::checkRight('plugin_workflows', READ);

Html::header(
    PluginWorkflowsWorkflow::getTypeName(2),
    $_SERVER['PHP_SELF'],
    'admin',
    PluginWorkflowsWorkflow::class,
    'option'
);

Search::show(PluginWorkflowsWorkflow::class);

Html::footer();
