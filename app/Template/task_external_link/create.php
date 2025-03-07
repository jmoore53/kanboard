<div class="page-header">
    <h2><?= t('Add a new external link') ?></h2>
</div>

<form id="ival-<?= $values['index'] ?>" action="<?= $this->url->href('TaskExternalLinkController', 'save', array('task_id' => $task['id'])) ?>" method="post" autocomplete="off">
    <?= $this->render('task_external_link/form', array('task' => $task, 'dependencies' => $dependencies, 'values' => $values, 'errors' => $errors)) ?>
    <?= $this->modal->submitButtons(array('submitLabel' => "Submit" . $values['index'] )) ?>
</form>
