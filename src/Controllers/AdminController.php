<?php

namespace SleekDBVCMS\Controllers;

use SleekDBVCMS\Core;

class AdminController
{
    private Core $core;
    private array $config;
    private string $currentPage;

    public function __construct(Core $core)
    {
        $this->core = $core;
        $this->config = $core->getConfig()->get('stores', []);
        $this->currentPage = $_GET['p'] ?? 'dashboard';
    }

    public function handleRequest(): void
    {
        if (!$this->core->getAuth()->isLoggedIn()) {
            $this->handleLogin();
            return;
        }

        if (isset($_GET['logout'])) {
            $this->core->getAuth()->logout();
            $this->redirect('index.php');
            return;
        }

        $this->handleAdminAction();
    }

    private function handleLogin(): void
    {
        $error = null;
        if (isset($_POST['login'])) {
            if ($this->core->getAuth()->login($_POST['username'], $_POST['password'])) {
                $this->redirect('index.php');
                return;
            }
            $error = 'Invalid username or password';
        }
        
        $this->renderView('login', ['error' => $error]);
    }

    private function handleAdminAction(): void
    {
        // Handle store actions
        if (isset($_POST['update_row']) || isset($_POST['insert_row'])) {
            $this->handleStoreUpdate();
        }

        if (isset($_POST['delete'])) {
            $this->handleStoreDelete();
        }

        // Handle view rendering
        switch ($this->currentPage) {
            case 'dashboard':
                $this->renderDashboard();
                break;
            default:
                $this->handleStoreView();
                break;
        }
    }

    private function handleStoreUpdate(): void
    {
        $store = $_GET['p'];
        $data = $_POST;
        $files = $_FILES;

        // Handle file uploads
        foreach ($files as $field => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $uploadedPath = $this->core->getFileManager()->uploadFile(
                    $file,
                    'uploads/' . date('Y/m')
                );
                if ($uploadedPath) {
                    $data[$field] = $uploadedPath;
                }
            }
        }

        // Handle password fields
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        // Update or insert
        if (isset($data['id'])) {
            $this->core->getDatabase()->update($store, $data);
        } else {
            $this->core->getDatabase()->insert($store, $data);
        }

        $this->redirect("index.php?p={$store}");
    }

    private function handleStoreDelete(): void
    {
        $store = $_GET['p'];
        $id = $_POST['id'];
        $this->core->getDatabase()->delete($store, $id);
        $this->redirect("index.php?p={$store}");
    }

    private function handleStoreView(): void
    {
        $store = $this->currentPage;
        $action = $_GET['action'] ?? 'list';
        $id = $_GET['id'] ?? null;

        switch ($action) {
            case 'edit':
            case 'create':
                $data = $id ? $this->core->getDatabase()->findById($store, $id) : [];
                $this->renderView('form', [
                    'store' => $store,
                    'fields' => $this->config[$store],
                    'data' => $data,
                    'action' => $action
                ]);
                break;
            default:
                $items = $this->core->getDatabase()->store($store)->fetch();
                $this->renderView('table', [
                    'store' => $store,
                    'fields' => $this->config[$store],
                    'items' => $items
                ]);
                break;
        }
    }

    private function renderDashboard(): void
    {
        $stores = [];
        foreach ($this->config as $name => $fields) {
            $count = count($this->core->getDatabase()->store($name)->fetch());
            $stores[] = [
                'name' => $name,
                'count' => $count,
                'fields' => count($fields)
            ];
        }

        $this->renderView('dashboard', ['stores' => $stores]);
    }

    private function renderView(string $view, array $data = []): void
    {
        extract($data);
        require __DIR__ . "/../Views/{$view}.php";
    }

    private function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}
