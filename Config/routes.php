<?php

/**
 * This file is part of the Bono CMS
 * 
 * For the full copyright and license information, please view
 * the license file that was distributed with this source code.
 */

return [
    '/module/blog/post/(:var)' => [
        'controller' => 'Post@indexAction'
    ],
    
    '/module/blog/category/(:var)' => [
        'controller' => 'Category@indexAction'
    ],
    
    '/blog' => [
        'controller' => 'Home@indexAction'
    ],
    
    '/blog/pg/(:var)' => [
        'controller' => 'Home@indexAction'
    ],
    
    '/%s/module/blog/config' => [
        'controller' => 'Admin:Config@indexAction'
    ],
    
    '/%s/module/blog/config.ajax' => [
        'controller' => 'Admin:Config@saveAction',
        'disallow' => ['guest']
    ],
    
    '/%s/module/blog' => [
        'controller' => 'Admin:Browser@indexAction'
    ],
    
    '/%s/module/blog/page/(:var)' => [
        'controller' => 'Admin:Browser@indexAction'
    ],
    
    // Post
    '/%s/module/blog/post/add' => [
        'controller' => 'Admin:Post@addAction'
    ],
    
    '/%s/module/blog/post/edit/(:var)' => [
        'controller' => 'Admin:Post@editAction'
    ],
    
    '/%s/module/blog/post/save' => [
        'controller' => 'Admin:Post@saveAction',
        'disallow' => ['guest']
    ],
    
    '/%s/module/blog/post/delete/(:var)' => [
        'controller' => 'Admin:Post@deleteAction',
        'disallow' => ['guest']
    ],
    
    // Post gallery
    '/%s/module/blog/post/gallery/add/(:var)' => [
        'controller' => 'Admin:PostGallery@addAction'
    ],

    '/%s/module/blog/post/gallery/edit/(:var)' => [
        'controller' => 'Admin:PostGallery@editAction'
    ],

    '/%s/module/blog/post/gallery/delete/(:var)' => [
        'controller' => 'Admin:PostGallery@deleteAction'
    ],

    '/%s/module/blog/post/gallery/save' => [
        'controller' => 'Admin:PostGallery@saveAction'
    ],
    
    '/%s/module/blog/tweak' => [
        'controller' => 'Admin:Post@tweakAction',
        'disallow' => ['guest']
    ],
    
    '/%s/module/blog/category/add' => [
        'controller' => 'Admin:Category@addAction'
    ],
    
    '/%s/module/blog/category/edit/(:var)' => [
        'controller' => 'Admin:Category@editAction'
    ],
    
    '/%s/module/blog/category/save' => [
        'controller' => 'Admin:Category@saveAction',
        'disallow' => ['guest']
    ],
    
    '/%s/module/blog/category/delete/(:var)' => [
        'controller' => 'Admin:Category@deleteAction',
        'disallow' => ['guest']
    ],
    
    '/%s/module/blog/category/view/(:var)' => [
        'controller' => 'Admin:Browser@categoryAction'
    ],
    
    '/%s/module/blog/category/view/(:var)/page/(:var)' => [
        'controller' => 'Admin:Browser@categoryAction'
    ]
];