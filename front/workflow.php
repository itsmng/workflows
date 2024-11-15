<?php


include ("../../../inc/includes.php");

// Check if plugin is activated...
if (!(new Plugin())->isActivated('workflows')) {
    Html::displayNotFoundError();
} else if (!PluginWorkflowsWorkflow::checkConnection()) {
    Html::displayErrorAndDie(__('Connection to BPMN engine failed', 'workflows'));
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
