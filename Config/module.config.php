<?php

/**
 * Module configuration container
 */

return [
    'description' => 'Blog module allows you to create a personal blog on your site',
    'menu' => [
        'name' => 'Blog',
        'icon' => 'fas fa-box-open',
        'items' => [
            [
                'route' => 'Blog:Admin:Browser@indexAction',
                'name' => 'View all posts'
            ],
            [
                'route' => 'Blog:Admin:Post@addAction',
                'name' => 'Add a post'
            ],
            [
                'route' => 'Blog:Admin:Category@addAction',
                'name' => 'Add category'
            ],
            [
                'route' => 'Blog:Admin:Config@indexAction',
                'name' => 'Configuration'
            ]
        ]
    ]
];