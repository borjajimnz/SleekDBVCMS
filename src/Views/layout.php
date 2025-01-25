<?php
/** @var \SleekDBVCMS\Core $core */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $core->getConfig()->get('app_name', 'SleekDBVCMS') ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php if ($core->getAuth()->isLoggedIn()): ?>
        <nav class="navbar navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">
                    <?= $core->getConfig()->get('app_name', 'SleekDBVCMS') ?>
                </a>
                <div class="d-flex">
                    <a href="index.php?logout=1" class="btn btn-outline-light">Logout</a>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="row">
                <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                    <div class="position-sticky pt-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="index.php">
                                    <i class="fas fa-home"></i> Dashboard
                                </a>
                            </li>
                            <?php foreach ($core->getConfig()->get('stores', []) as $store => $fields): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="index.php?p=<?= $store ?>">
                                        <i class="fas fa-table"></i> <?= ucfirst($store) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </nav>

                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                    <?= $content ?>
                </main>
            </div>
        </div>
    <?php else: ?>
        <?= $content ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js"></script>
    <script>
        // Initialize TinyMCE for rich text areas
        tinymce.init({
            selector: 'textarea.rich-editor',
            height: 300,
            menubar: false,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste code help wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic backcolor | \
                alignleft aligncenter alignright alignjustify | \
                bullist numlist outdent indent | removeformat | help'
        });
    </script>
</body>
</html>
