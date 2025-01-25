<?php
use SleekDBVCMS\Forms\FormBuilder;

/** @var \SleekDBVCMS\Core $core */
/** @var string $store */
/** @var array $fields */
/** @var array $data */
/** @var string $action */
/** @var array $errors */
/** @var array $joinData */

$formBuilder = new FormBuilder($data ?? [], $errors ?? []);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3><?= $action === 'create' ? 'Create' : 'Edit' ?> <?= ucfirst($store) ?></h3>
                </div>
                <div class="card-body">
                    <?= $formBuilder->start("index.php?store={$store}&action=" . ($action === 'create' ? 'create' : 'update'), 'POST') ?>
                    
                    <?php if (isset($data['_id'])): ?>
                        <input type="hidden" name="_id" value="<?= $data['_id'] ?>">
                    <?php endif; ?>

                    <?php foreach ($fields as $field => $type): ?>
                        <?php
                        $attributes = [];
                        if (isset($type['join'])) {
                            $attributes['options'] = $joinData[$field] ?? [];
                            $type = 'select';
                        }
                        echo $formBuilder->field($field, $type, $attributes);
                        ?>
                    <?php endforeach; ?>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="index.php?store=<?= $store ?>&action=list" class="btn btn-secondary">Cancel</a>
                    </div>

                    <?= $formBuilder->end() ?>
                </div>
            </div>
        </div>
    </div>
</div>
