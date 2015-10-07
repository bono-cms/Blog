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
	
	'/admin/module/blog/config'	=> array(
		'controller' => 'Admin:Config@indexAction'
	),
	
	'/admin/module/blog/config.ajax' => array(
		'controller' => 'Admin:Config@saveAction',
		'disallow' => array('guest')
	),
	
	'/admin/module/blog' => array(
		'controller' => 'Admin:Browser@indexAction'
	),
	
	'/admin/module/blog/page/(:var)' => array(
		'controller'	=> 'Admin:Browser@indexAction'
	),
	
	'/admin/module/blog/post/add' => array(
		'controller' => 'Admin:Post:Add@indexAction'
	),
	
	'/admin/module/blog/post/add.ajax'	=> array(
		'controller' => 'Admin:Post:Add@addAction',
		'disallow' => array('guest')
	),
	
	'/admin/module/blog/post/edit/(:var)' => array(
		'controller' => 'Admin:Post:Edit@indexAction'
	),
	
	
	'/admin/module/blog/post/edit.ajax'	=> array(
		'controller' => 'Admin:Post:Edit@updateAction',
		'disallow' => array('guest')
	),
	
	
	'/admin/module/blog/post/delete.ajax' => array(
		'controller' => 'Admin:Browser@deleteAction',
		'disallow' => array('guest')
	),
	
	'/admin/module/blog/post/delete-selected.ajax' => array(
		'controller' => 'Admin:Browser@deleteSelectedAction',
		'disallow' => array('guest')
	),
	
	'/admin/module/blog/save.ajax' => array(
		'controller' => 'Admin:Browser@saveAction',
		'disallow' => array('guest')
	),
	
	'/admin/module/blog/category/add' => array(
		'controller' => 'Admin:Category:Add@indexAction'
	),
	
	'/admin/module/blog/category/add.ajax' => array(
		'controller' => 'Admin:Category:Add@addAction',
		'disallow' => array('guest')
	),
	
	'/admin/module/blog/category/edit/(:var)' => array(
		'controller' => 'Admin:Category:Edit@indexAction'
	),
	
	'/admin/module/blog/category/edit.ajax' => array(
		'controller' => 'Admin:Category:Edit@updateAction',
		'disallow' => array('guest')
	),
	
	'/admin/module/blog/category/delete.ajax'	=> array(
		'controller' => 'Admin:Browser@deleteCategoryAction',
		'disallow' => array('guest')
	),
	
	'/admin/module/blog/category/view/(:var)'	=> array(
		'controller' => 'Admin:Browser@categoryAction'
	),
	
	'/admin/module/blog/category/view/(:var)/page/(:var)' => array(
		'controller'	=> 'Admin:Browser@categoryAction'
	)
);
