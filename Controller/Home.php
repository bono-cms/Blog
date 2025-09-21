<?php

/**
 * This file is part of the Bono CMS
 * 
 * Copyright (c) No Global State Lab
 * 
 * For the full copyright and license information, please view
 * the license file that was distributed with this source code.
 */

namespace Blog\Controller;

final class Home extends AbstractBlogController
{
    /**
     * Shows all recent posts (on a home page)
     * 
     * @param integer $pageNumber Current page number
     * @return string
     */
    public function indexAction($pageNumber = 1)
    {
        $this->loadSitePlugins();

        // No breadcrumbs on home page's display
        $this->view->getBreadcrumbBag()->clear();

        $postManager = $this->getPostManager();
        $posts = $postManager->fetchAllByPage($pageNumber, $this->getConfig()->getPerPageCount(), [
            'published' => true
        ]);

        // Tweak pagination
        $paginator = $postManager->getPaginator();

        // The pattern /(:var)/page/(:var) is reserved, so another one should be used instead
        $paginator->setUrl($this->createUrl('Blog:Home@indexAction', array(), 1));

        $page = $this->getService('Pages', 'pageManager')->fetchDefault();

        return $this->view->render('blog-category', array(
            'paginator' => $paginator,
            'page' => $page,
            'posts' => $posts,
            'languages' => $this->getService('Cms', 'languageManager')->fetchAll(true)
        ));
    }
}
