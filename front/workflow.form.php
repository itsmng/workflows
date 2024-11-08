<?php

include ("../../../inc/includes.php");

// Check if plugin is activated...
if (!(new Plugin())->isActivated('workflows')) {
   Html::displayNotFoundError();
}

$workflow = new PluginWorkflowsWorkflow();

if (isset($_POST['add'])) {
   // Add a new Form
   Session::checkRight('entity', UPDATE);
   $newID = $workflow->add($_POST);
   Html::redirect(Plugin::getWebDir('workflow') . '/front/workflow.php?id=' . $newID);

} else if (isset($_POST['update'])) {
   // Edit an existing form
   Session::checkRight('entity', UPDATE);
   $workflow->update($_POST);
   Html::back();

} else if (isset($_POST['delete'])) {
   // Delete a form (is_deleted = true)
   Session::checkRight('entity', UPDATE);
   $workflow->delete($_POST);
   $workflow->redirectToList();

} else if (isset($_POST['restore'])) {
   // Restore a deleteted form (is_deleted = false)
   Session::checkRight('entity', UPDATE);
   $workflow->restore($_POST);
   $workflow->redirectToList();

} else if (isset($_POST['purge'])) {
   // Delete defenitively a form from DB and all its datas
   Session::checkRight('entity', UPDATE);
   $workflow->delete($_POST, 1);
   $workflow->redirectToList();
} else if (isset($_GET['id'])) {
   Session::checkRight('plugin_workflows', READ);

   Html::header(
      PluginWorkflowsWorkflow::getTypeName(2),
      $_SERVER['PHP_SELF'],
      'admin',
      'PluginFormcreatorForm',
      'option'
   );

   $_GET['id'] = isset($_GET['id']) ? intval($_GET['id']) : -1;
   $workflow->display($_GET);

   Html::footer();
} else {
   Session::checkRight('plugin_workflows', READ);

   Html::header(
      PluginWorkflowsWorkflow::getTypeName(2),
      $_SERVER['PHP_SELF'],
      'admin',
      'PluginFormcreatorForm',
      'option'
   );

   Search::show(PluginWorkflowsWorkflow::class);

   Html::footer();
}
