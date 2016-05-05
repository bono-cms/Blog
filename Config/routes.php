<?php

/**
 * This file is part of the Bono CMS
 * 
 * Copyright (c) No Global State Lab
 * 
 * For the full copyright and license information, please view
 * the license file that was distributed with this source code.
 */

return array(
    '/module/blog/post/(:var)' => array(
        'controller' => 'Post@indexAction'
    ),
    
    '/module/blog/category/(:var)' => array(
        'controller' => 'Category@indexAction'
    ),
    
    '/blog' => array(
        'controller' => 'Home@indexAction'
    ),
    
    '/blog/pg/(:var)' => array(
        'controller' => 'Home@indexAction'
    ),
    
    '/%s/module/blog/config' => array(
        'controller' => 'Admin:Config@indexAction'
    ),
    
    '/%s/module/blog/config.ajax' => array(
        'controller' => 'Admin:Config@saveAction',
        'disallow' => array('guest')
    ),
    
    '/%s/module/blog' => array(
        'controller' => 'Admin:Browser@indexAction'
    ),
    
    '/%s/module/blog/page/(:var)' => array(
        'controller'    => 'Admin:Browser@indexAction'
    ),
    
    '/%s/module/blog/post/add' => array(
        'controller' => 'Admin:Post@addAction'
    ),
    
    '/%s/module/blog/post/edit/(:var)' => array(
        'controller' => 'Admin:Post@editAction'
    ),
    
    '/%s/module/blog/post/save' => array(
        'controller' => 'Admin:Post@saveAction',
        'disallow' => array('guest')
    ),
    
    '/%s/module/blog/post/delete/(:var)' => array(
        'controller' => 'Admin:Post@deleteAction',
        'disallow' => array('guest')
    ),
    
    '/%s/module/blog/tweak' => array(
        'controller' => 'Admin:Post@tweakAction',
        'disallow' => array('guest')
    ),
    
    '/%s/module/blog/category/add' => array(
        'controller' => 'Admin:Category@addAction'
    ),
    
    '/%s/module/blog/category/edit/(:var)' => array(
        'controller' => 'Admin:Category@editAction'
    ),
    
    '/%s/module/blog/category/save' => array(
        'controller' => 'Admin:Category@saveAction',
        'disallow' => array('guest')
    ),
    
    '/%s/module/blog/category/delete/(:var)' => array(
        'controller' => 'Admin:Category@deleteAction',
        'disallow' => array('guest')
    ),
    
    '/%s/module/blog/category/view/(:var)'   => array(
        'controller' => 'Admin:Browser@categoryAction'
    ),
    
    '/%s/module/blog/category/view/(:var)/page/(:var)' => array(
        'controller'    => 'Admin:Browser@categoryAction'
    )
);
