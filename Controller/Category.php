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

use Krystal\Stdlib\VirtualEntity;

final class Category extends AbstractBlogController
{
    /**
     * Renders blog category
     * 
     * @param string $id Blog category's id
     * @param integer $pageNumber Current page number
     * @param string $code Optional language code
     * @param string $slug Optional slug
     * @return string
     */
    public function indexAction($id = false, $pageNumber = 1, $code = null, $slug = null)
    {
        $category = $this->getCategoryManager()->fetchById($id, false);

        if ($category !== false) {
            $this->loadPlugins($category);
            $config = $this->getConfig();

            $postManager = $this->getPostManager();
            $posts = $postManager->fetchAllByPage($pageNumber, $config->getPerPageCount(), [
                'published' => true,
                'category_id' => $category->getId()
            ]);

            $paginator = $postManager->getPaginator();

            // If $slug isn't null by type, then this controller is invoked manually from Site module
            if ($slug !== null) {
                $this->preparePaginator($paginator, $code, $slug, $pageNumber);
            }

            // Template variables
            $vars = array(
                'page' => $category,
                'category' => $category,
                'posts' => $posts,
                'paginator' => $paginator,
                'languages' => $this->getCategoryManager()->getSwitchUrls($id)
            );

            // Try to find child nodes
            $children = $this->getCategoryManager()->fetchChildrenByParentId($id);

            // If we have at least one nested category
            if (!empty($children)) {
                // Then append them to view templates as well
                $vars['categories'] = $children;
            }

            return $this->view->render('blog-category', $vars);
        } else {
            return false;
        }
    }

    /**
     * Loads required plugins for view
     * 
     * @param \Krystal\Stdlib\VirtualEntity $category
     * @return void
     */
    private function loadPlugins(VirtualEntity $category)
    {
        $this->loadSitePlugins();
        $this->view->getBreadcrumbBag()
                   ->add($this->getCategoryManager()->getBreadcrumbs($category));
    }
}
