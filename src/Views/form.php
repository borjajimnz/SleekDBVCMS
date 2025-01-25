<?php
/** @var \SleekDBVCMS\Core $core */
/** @var string $store */
/** @var array $fields */
/** @var array $data */
/** @var string $action */
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <?= $action === 'create' ? 'Create' : 'Edit' ?> <?= ucfirst($store) ?>
        </h1>
        <a href="index.php?p=<?= $store ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <?php if (isset($data['_id'])): ?>
                    <input type="hidden" name="id" value="<?= $data['_id'] ?>">
                <?php endif; ?>

                <?php foreach ($fields as $field => $type): ?>
                    <div class="mb-3">
                        <label for="<?= $field ?>" class="form-label">
                            <?= ucfirst($field) ?>
                        </label>

                        <?php switch ($type):
                            case 'textarea': ?>
                                <textarea name="<?= $field ?>" id="<?= $field ?>" 
                                    class="form-control" rows="3"
                                ><?= htmlspecialchars($data[$field] ?? '') ?></textarea>
                                <?php break; ?>

                            <?php case 'rich_textarea': ?>
                                <textarea name="<?= $field ?>" id="<?= $field ?>" 
                                    class="form-control rich-editor"
                                ><?= htmlspecialchars($data[$field] ?? '') ?></textarea>
                                <?php break; ?>

                            <?php case 'image': ?>
                                <?php if (!empty($data[$field])): ?>
                                    <div class="mb-2">
                                        <img src="<?= $data[$field] ?>" alt="" style="max-height: 200px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="<?= $field ?>" id="<?= $field ?>" 
                                    class="form-control" accept="image/*">
                                <?php break; ?>

                            <?php case 'password': ?>
                                <input type="password" name="<?= $field ?>" id="<?= $field ?>" 
                                    class="form-control" 
                                    <?= $action === 'create' ? 'required' : '' ?>>
                                <?php if ($action === 'edit'): ?>
                                    <small class="text-muted">
                                        Leave blank to keep current password
                                    </small>
                                <?php endif; ?>
                                <?php break; ?>

                            <?php case 'number': ?>
                                <input type="number" name="<?= $field ?>" id="<?= $field ?>" 
                                    class="form-control" value="<?= $data[$field] ?? '' ?>">
                                <?php break; ?>

                            <?php case 'email': ?>
                                <input type="email" name="<?= $field ?>" id="<?= $field ?>" 
                                    class="form-control" value="<?= $data[$field] ?? '' ?>">
                                <?php break; ?>

                            <?php case 'color': ?>
                                <input type="color" name="<?= $field ?>" id="<?= $field ?>" 
                                    class="form-control" value="<?= $data[$field] ?? '#000000' ?>">
                                <?php break; ?>

                            <?php case 'url': ?>
                                <input type="url" name="<?= $field ?>" id="<?= $field ?>" 
                                    class="form-control" value="<?= $data[$field] ?? '' ?>">
                                <?php break; ?>

                            <?php case 'decimal': ?>
                                <input type="number" name="<?= $field ?>" id="<?= $field ?>" 
                                    class="form-control" step="0.01" 
                                    value="<?= $data[$field] ?? '' ?>">
                                <?php break; ?>

                            <?php default: ?>
                                <input type="text" name="<?= $field ?>" id="<?= $field ?>" 
                                    class="form-control" value="<?= $data[$field] ?? '' ?>">
                        <?php endswitch; ?>
                    </div>
                <?php endforeach; ?>

                <div class="mt-4">
                    <button type="submit" name="<?= $action === 'create' ? 'insert_row' : 'update_row' ?>" 
                            class="btn btn-primary">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
