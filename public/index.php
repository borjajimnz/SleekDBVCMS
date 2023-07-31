<?php

require '../Bootstrap.php';
?>
<html>
<html>
    <head>
        <style>
            body {
                background-color: #efefef;
            }
        </style>
        <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet"/>
        <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js"></script>
    </head>
    <body class="bg-gray-100 flex flex-col h-screen justify-between">
        <!-- Menu -->
        <header class="bg-gray-900 text-white">
            <div class="flex justify-between container mx-auto py-4">
                <span class="py-2 text-3xl"><?php print $cms->config['app_name'] ?></span>
            </div>
            <div class="bg-gray-700">
                <div class="flex justify-between container mx-auto py-4">
                    <ul>
                        <li class="inline-block py-2 mr-4 hover:underline">
                            <a href="index.php">
                                Home Page
                            </a>
                        </li>
                    </ul>
                    <ul>
                        <li class="inline-block py-2 px-2 mr-2 bg-white rounded text-gray-800 hover:bg-gray-200">
                            <a href="admin.php">
                                <?php $cms->_('Admin') ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="flex-grow">
            <section class="w-full h-48 bg-gradient-to-r from-blue-600 to-blue-800 text-white flex inline-block justify-center">
                <div class="mt-auto mb-auto pb-10 text-center">
                    <span class="text-6xl block mb-3">
                        Home Page
                    </span>
                </div>
            </section>

            <section class="container mx-auto py-4 bg-white rounded shadow mt-8 px-5 py-8 -mt-10">
                <div>
                    <span class="text-2xl block mb-3">SleekDB Documentation</span>
                    We recommend you to read the
                    <a href="https://sleekdb.github.io/"
                       class="text-blue-500 hover:text-blue-600 hover:underline" target="_blank">SleekDB
                        documentation</a>
                    to basic knowledge.
                </div>

                <div class="mt-8">
                    <span class="text-2xl block mb-3">Registered users</span>
                    <?php $users = $cms->store('users')->findAll(); ?>
                    <?php foreach ($users as $user) print $user['username'] . ' (id: ' . $user['_id'] . ')'; ?>
                </div>
            </section>

        </main>
        <footer class="bg-white p-5 text-gray-800 mt-8 text-center border-t border-gray-300">
            <span>
                Powered by SleekDBVCMS
            </span>
        </footer>
    </body>
</html>