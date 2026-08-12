<?php

// ============================================================
// Public front-end configuration (SleekDBVCMS)
// Consumes the CMS stores via Bootstrap + SleekDB.
// Edit menu, header, footer, home layout here.
// ============================================================

return [

    'site_name' => 'My Sleek Site',
    'tagline'   => 'A website powered by SleekDBVCMS',

    // Home page behaviour
    'home' => [
        // store to feature the latest items of, or false to show all stores
        'featured_store' => 'posts',
        // max items to show per store on home
        'per_store' => 4,
    ],

    // Navigation menu: list of stores shown in the header nav.
    // Only stores listed here are publicly reachable via the front.
    // Use '*' to expose all stores (be careful: this would expose 'users').
    'menu' => ['posts', 'products', 'categories', 'subcategories', 'statuses', 'media'],

    // Store labels shown in nav (override display names)
    'labels' => [
        'posts' => 'Blog',
        'products' => 'Products',
        'categories' => 'Categories',
        'subcategories' => 'Subcategories',
        'statuses' => 'Statuses',
        'roles' => 'Roles',
        'media' => 'Media',
        'users' => 'Users',
    ],

    // Extra header/footer content (raw HTML)
    'header_html' => '',
    'footer_html' => '© ' . date('Y') . ' My Sleek Site. All rights reserved.',

    // Field types NOT rendered as text in cards/details
    'image_fields' => ['image', 'avatar', 'file', 'logo', 'thumbnail'],
    'html_fields'  => ['rich_textarea'],

    // Dark mode default on first visit (auto | light | dark)
    'theme' => 'auto',
];
