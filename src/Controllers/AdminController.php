<?php

namespace SleekDBVCMS\Controllers;

use SleekDBVCMS\Core;

class AdminController
{
    private Core $core;

    public function __construct(Core $core)
    {
        $this->core = $core;
    }

    public function handleRequest(): void
    {
        if (!$this->core->getAuth()->isLoggedIn()) {
            $this->handleLogin();
            return;
        }

        if (isset($_GET['logout'])) {
            $this->core->getAuth()->logout();
            $this->core->redirect('index.php');
            return;
        }

        if (isset($_GET['lang'])) {
            $this->core->getAuth()->setLanguage($_GET['lang']);
        }

        $this->handleAdminAction();
    }

    private function handleLogin(): void
    {
        $error = null;
        if (isset($_POST['login'])) {
            if ($this->core->getAuth()->login($_POST['username'], $_POST['password'])) {
                $this->core->redirect('index.php');
                return;
            }
            $error = 'Invalid user/password';
        }
        $this->renderView('login', ['error' => $error]);
    }

    private function handleAdminAction(): void
    {
        $page = $_GET['p'] ?? null;

        if (isset($_POST['update_row'])) {
            $this->handleStoreUpdate($page, 'update_row');
        }
        if (isset($_POST['insert_row'])) {
            $this->handleStoreUpdate($page, 'insert_row');
        }
        if (isset($_POST['delete'])) {
            $this->handleStoreDelete($page);
        }
        if (isset($_POST['update_config'])) {
            $this->handleConfigUpdate();
        }
        if (isset($_GET['backup'])) {
            $this->handleBackup();
        }

        if ($page === null) {
            $this->renderDashboard();
            return;
        }

        $this->handleStoreView($page);
    }

    private function renderDashboard(): void
    {
        $stores = $this->core->getConfig()->getStores();
        $db = $this->core->getDatabase();

        $stats = [];
        foreach ($stores as $name => $fields) {
            try {
                $count = count($db->findAll($name));
            } catch (\Throwable $e) {
                $count = 0;
            }
            $stats[] = ['name' => $name, 'count' => $count, 'fields' => count($fields)];
        }

        $configFile = $this->core->getRootPath() . '/.default_stores';
        $json = file_exists($configFile) ? file_get_contents($configFile) : '';

        $msg = $_SESSION['config_msg'] ?? null;
        $backupMsg = $_SESSION['backup_msg'] ?? null;
        unset($_SESSION['config_msg'], $_SESSION['backup_msg']);

        $users = [];
        try {
            $users = $db->findAll('users', ['_id' => 'desc']);
        } catch (\Throwable $e) {
        }

        $this->renderView('dashboard', [
            'stats' => $stats,
            'json' => $json,
            'msg' => $msg,
            'backupMsg' => $backupMsg,
            'users' => $users,
        ]);
    }

    private function handleStoreUpdate(string $table, string $action): void
    {
        $data = $_POST;
        $files = $_FILES;
        $stores = $this->core->getConfig()->getStores();

        $update = [];
        foreach ($stores[$table] ?? [] as $name => $value) {
            if (is_array($value)) {
                if (isset($value['join']) && array_key_exists($name, $data)) {
                    $update[$name] = $data[$name] !== '' ? (int)$data[$name] : null;
                }
                continue;
            }

            if (array_key_exists($name, $data)) {
                $update[$name] = $data[$name];
            }

            if ($name === 'password' && !empty($data[$name])) {
                $update[$name] = password_hash($data[$name], PASSWORD_DEFAULT);
            }

            if ($value === 'image' && isset($files[$name])) {
                $uploaded = $this->core->getFileManager()->uploadFile($files, $name);
                if ($uploaded !== null) {
                    $update[$name] = $uploaded;
                }
            }
        }

        // Auto-generate slug from title for the pages store.
        if ($table === 'pages' && (empty($update['slug']) || trim($update['slug']) === '')) {
            $source = $update['title'] ?? $data['title'] ?? 'page';
            $update['slug'] = $this->slugify($source);
        }

        // Pages store per-page module instances; keep them, convert bare ids.
        if ($table === 'pages' && array_key_exists('modules', $update)) {
            $decoded = json_decode((string)$update['modules'], true);
            $instances = [];
            if (is_array($decoded)) {
                foreach ($decoded as $entry) {
                    if (is_array($entry)) {
                        // Module images are uploaded client-side as data URIs;
                        // persist them so the front serves a cached file instead
                        // of a huge inline base64 blob.
                        foreach ($entry as $field => $value) {
                            if ($field === 'image' && is_string($value) && strncmp($value, 'data:image/', 11) === 0) {
                                $stored = $this->core->getFileManager()->uploadDataUri($value);
                                if ($stored !== null) {
                                    $entry[$field] = $stored;
                                }
                            }
                        }
                        $instances[] = $entry;
                    } elseif (is_numeric($entry)) {
                        $id = (int)$entry;
                        if ($id > 0) {
                            $instances[] = ['_module_id' => $id];
                        }
                    }
                }
            }
            $update['modules'] = json_encode($instances);
        }

        if ($action === 'update_row' && isset($data['id']) && !empty($data['id'])) {
            $update['_id'] = (int)$data['id'];
            $this->core->getDatabase()->update($table, $update);
        } else {
            $this->core->getDatabase()->insert($table, $update);
        }

        $this->core->redirect('index.php?p=' . urlencode($table));
    }

    private function handleStoreDelete(string $table): void
    {
        // Protected stores cannot have their records deleted.
        if ($this->core->getConfig()->isProtected($table)) {
            $this->core->redirect('index.php?p=' . urlencode($table));
            return;
        }
        $this->core->getDatabase()->delete($table, (int)$_POST['id']);
        $this->core->redirect('index.php?p=' . urlencode($table));
    }

    private function slugify(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text !== '' ? $text : 'page-' . time();
    }

    private function handleConfigUpdate(): void
    {
        $json = $_POST['config_file'] ?? '';
        $ok = $this->core->getConfig()->saveStoresFromJson($json);
        $_SESSION['config_msg'] = $ok
            ? 'JSON saved.'
            : 'Invalid JSON, no possible to save this file.';
        $this->core->redirect('index.php');
    }

    private function handleBackup(): void
    {
        $this->createBackup();
        $_SESSION['backup_msg'] = 'BACKUP saved ' . date('Y-m-d H:i:s') . '.';
        $this->core->redirect('index.php');
    }

    private function createBackup(): void
    {
        $source = $this->core->getStoragePath();
        if (!extension_loaded('zip') || !file_exists($source)) {
            return;
        }

        $destination = $this->core->getRootPath() . '/backups/' . time() . '.zip';
        $zip = new \ZipArchive();
        if (!$zip->open($destination, \ZipArchive::CREATE)) {
            return;
        }

        $source = str_replace('\\', '/', realpath($source));
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $file = str_replace('\\', '/', $file);
            if (in_array(substr($file, strrpos($file, '/') + 1), ['.', '..'])) {
                continue;
            }
            $real = realpath($file);
            if (is_dir($real)) {
                $zip->addEmptyDir(str_replace($source . '/', '', $real . '/'));
            } elseif (is_file($real)) {
                $zip->addFromString(str_replace($source . '/', '', $real), file_get_contents($real));
            }
        }
        $zip->close();
    }

    private function handleStoreView(string $table): void
    {
        $stores = $this->core->getConfig()->getStores();
        if (!isset($stores[$table])) {
            $this->core->redirect('index.php');
        }

        if (isset($_GET['insert'])) {
            $this->renderForm($table, null, 'insert_row');
            return;
        }
        if (isset($_GET['update'])) {
            $this->renderForm($table, (int)$_GET['id'], 'update_row');
            return;
        }
        if (isset($_GET['view'])) {
            $this->renderForm($table, (int)$_GET['id'], 'view_row');
            return;
        }

        $this->renderTable($table);
    }

    private function renderTable(string $table): void
    {
        $stores = $this->core->getConfig()->getStores();
        $fields = $stores[$table];
        $search = $_POST['search'] ?? null;

        $rows = $this->fetchRows($table, $fields, $search);
        $rows = $this->resolveJoins($table, $fields, $rows);

        $this->renderView('table', [
            'table' => $table,
            'fields' => $fields,
            'rows' => $rows,
            'search' => $search,
        ]);
    }

    private function fetchRows(string $table, array $fields, ?string $search): array
    {
        $db = $this->core->getDatabase();

        if ($search) {
            $searchable = [];
            foreach ($fields as $name => $type) {
                if (!is_array($type)) {
                    $searchable[] = $name;
                }
            }
            return $db->store($table)->search($searchable, $search, ['_id' => 'desc']);
        }

        return $db->findAll($table, ['_id' => 'desc']);
    }

    private function resolveJoins(string $table, array $fields, array $rows): array
    {
        $db = $this->core->getDatabase();
        $cache = [];

        foreach ($rows as &$row) {
            foreach ($fields as $name => $value) {
                if (!is_array($value) || !isset($value['join'])) {
                    continue;
                }
                $join = $value['join'];
                $foreignTable = $join['foreing_table'];
                $foreignKey = $join['foreing_key'] ?? '_id';
                $key = (int)($row[$join['key']] ?? 0);

                if ($key === 0) {
                    $row['_join_' . $name] = '';
                    continue;
                }

                if (!isset($cache[$foreignTable][$key])) {
                    $cache[$foreignTable][$key] = $db->store($foreignTable)
                        ->findOneBy([$foreignKey, '=', $key]);
                }

                $foreign = $cache[$foreignTable][$key] ?? null;
                $display = '';
                if ($foreign) {
                    foreach ($join['foreing_display'] as $displayField) {
                        if (isset($foreign[$displayField])) {
                            $display .= $foreign[$displayField] . ' ';
                        }
                    }
                }
                $row['_join_' . $name] = trim($display);
            }
        }

        return $rows;
    }

    private function renderForm(string $table, ?int $id, string $action): void
    {
        $stores = $this->core->getConfig()->getStores();
        $fields = $stores[$table];

        $data = [];
        if ($id !== null) {
            $data = $this->core->getDatabase()->findById($table, $id) ?: [];
        }

        $joinData = $this->buildJoinOptions($fields);

        $this->renderView('form', [
            'table' => $table,
            'fields' => $fields,
            'data' => $data,
            'action' => $action,
            'joinData' => $joinData,
        ]);
    }

    private function buildJoinOptions(array $fields): array
    {
        $db = $this->core->getDatabase();
        $joinData = [];

        foreach ($fields as $name => $value) {
            if (!is_array($value) || !isset($value['join'])) {
                continue;
            }
            $foreignTable = $value['join']['foreing_table'];
            $options = $db->findAll($foreignTable);
            $joinData[$name] = $options;
        }

        return $joinData;
    }

    private function renderView(string $view, array $data = []): void
    {
        extract($data);
        $core = $this->core;

        if ($view === 'login') {
            require __DIR__ . "/../Views/{$view}.php";
            return;
        }

        ob_start();
        require __DIR__ . "/../Views/{$view}.php";
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layout.php';
    }
}
