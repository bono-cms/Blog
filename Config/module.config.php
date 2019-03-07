<?php

/**
 * Module configuration container
 */

return array(
    'description' => 'Blog module allows you to create a personal blog on your site',
    'menu' => array(
        'name' => 'Blog',
        'icon' => 'fas fa-box-open',
        'items' => array(
            array(
                'route' => 'Blog:Admin:Browser@indexAction',
                'name' => 'View all posts'
            ),
            array(
                'route' => 'Blog:Admin:Post@addAction',
                'name' => 'Add a post'
            ),
            array(
                'route' => 'Blog:Admin:Category@addAction',
                'name' => 'Add category'
            ),
            array(
                'route' => 'Blog:Admin:Config@indexAction',
                'name' => 'Configuration'
            )
        )
    )
);
