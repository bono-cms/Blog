<?php

/**
 * This file is part of the Bono CMS
 * 
 * Copyright (c) No Global State Lab
 * 
 * For the full copyright and license information, please view
 * the license file that was distributed with this source code.
 */

namespace Blog\Controller\Admin;

final class Browser extends AbstractAdminController
{
    /**
     * Creates a grid
     * 
     * @param array $posts
     * @param string $url
     * @param string $categoryId
     * @return string
     */
    private function createGrid(array $posts, $url, $categoryId)
    {
        $paginator = $this->getPostManager()->getPaginator();
        $paginator->setUrl($url);

        // Load view plugins
        $this->view->getPluginBag()
                   ->appendScript('@Blog/admin/browser.js');

        // Append a breadcrumb
        $this->view->getBreadcrumbBag()
                   ->addOne('Blog');

        return $this->view->render('browser', array(
            'categoryId' => $categoryId,
            'posts' => $posts,
            'paginator' => $paginator,
            'taskManager' => $this->getTaskManager(),
            'categories' => $this->getCategoryManager()->fetchAll()
        ));
    }

    /**
     * Renders a grid
     * 
     * @param integer $page Current page
     * @return string
     */
    public function indexAction($page = 1)
    {
        $posts = $this->getPostManager()->fetchAllByPage(false, $page, $this->getSharedPerPageCount());
        $url = $this->createUrl('Blog:Admin:Browser@indexAction', array(), 1);

        return $this->createGrid($posts, $url, null);
    }

    /**
     * Renders a grid filtered by particular category id
     * 
     * @param string $id Category id
     * @param integer $page
     * @return string
     */
    public function categoryAction($id, $page = 1)
    {
        $posts = $this->getPostManager()->fetchAllByPage(false, $page, $this->getSharedPerPageCount(), $id);
        $url = $this->createUrl('Blog:Admin:Browser@categoryAction', array($id), 1);

        return $this->createGrid($posts, $url, $id);
    }
}
