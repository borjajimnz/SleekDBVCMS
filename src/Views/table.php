<?php
/** @var \SleekDBVCMS\Core $core */
/** @var string $store */
/** @var array $fields */
/** @var array $items */
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2"><?= ucfirst($store) ?></h1>
        <a href="index.php?p=<?= $store ?>&action=create" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <?php foreach ($fields as $field => $type): ?>
                                <th><?= ucfirst($field) ?></th>
                            <?php endforeach; ?>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= $item['_id'] ?></td>
                                <?php foreach ($fields as $field => $type): ?>
                                    <td>
                                        <?php if ($type === 'image' && !empty($item[$field])): ?>
                                            <img src="<?= $item[$field] ?>" alt="" style="max-height: 50px;">
                                        <?php elseif ($type === 'password'): ?>
                                            ********
                                        <?php else: ?>
                                            <?= htmlspecialchars($item[$field] ?? '') ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="text-end">
                                    <a href="index.php?p=<?= $store ?>&action=edit&id=<?= $item['_id'] ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="post" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this item?')">
                                        <input type="hidden" name="id" value="<?= $item['_id'] ?>">
                                        <button type="submit" name="delete" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
