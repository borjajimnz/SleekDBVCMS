<?php
/** @var \SleekDBVCMS\Core $core */
/** @var array $stores */
?>

<div class="container-fluid">
    <h1 class="h2 mb-4">Dashboard</h1>

    <div class="row">
        <?php foreach ($stores as $store): ?>
            <div class="col-12 col-lg-6 col-xxl-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="card-title mb-0">
                                <?= ucfirst($store['name']) ?>
                            </h5>
                            <a href="index.php?p=<?= $store['name'] ?>" class="btn btn-primary btn-sm">
                                View All
                            </a>
                        </div>
                        <div class="row g-0">
                            <div class="col-6">
                                <div class="p-3 border-end">
                                    <h6>Records</h6>
                                    <h2 class="mb-0"><?= $store['count'] ?></h2>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3">
                                    <h6>Fields</h6>
                                    <h2 class="mb-0"><?= $store['fields'] ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
