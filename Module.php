<?php

/**
 * This file is part of the Bono CMS
 * 
 * Copyright (c) No Global State Lab
 * 
 * For the full copyright and license information, please view
 * the license file that was distributed with this source code.
 */

namespace Blog;

use Cms\AbstractCmsModule;
use Blog\Service\PostManager;
use Blog\Service\CategoryManager;
use Blog\Service\TaskManager;
use Blog\Service\SiteService;
use Krystal\Image\Tool\ImageManager;

final class Module extends AbstractCmsModule
{
    /**
     * Returns album image manager
     * 
     * @return \Krystal\Image\ImageManager
     */
    private function createCategoryImageManager()
    {
        $plugins = array(
            'thumb' => array(
                'dimensions' => array(
                    // Dimensions for administration panel
                    array(200, 200),
                    // Dimensions for the site
                    array(500, 500)
                )
            )
        );

        return new ImageManager(
            '/data/uploads/module/blog/categories',
            $this->appConfig->getRootDir(),
            $this->appConfig->getRootUrl(),
            $plugins
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getServiceProviders()
    {
        $postMapper = $this->getMapper('/Blog/Storage/MySQL/PostMapper');
        $categoryMapper = $this->getMapper('/Blog/Storage/MySQL/CategoryMapper');

        $webPageManager = $this->getWebPageManager();
        $historyManager = $this->getHistoryManager();

        $postManager = new PostManager($postMapper, $categoryMapper, $webPageManager, $historyManager);
        $categoryManager = new CategoryManager(
            $categoryMapper, 
            $postMapper, 
            $webPageManager, 
            $this->createCategoryImageManager(), 
            $historyManager, 
            $this->getMenuWidget()
        );

        $siteService = new SiteService($categoryMapper, $postManager, $webPageManager);

        return array(
            'siteService' => $siteService,
            'configManager' => $this->createConfigService(),
            'taskManager' => new TaskManager($postMapper),
            'postManager' => $postManager,
            'categoryManager' => $categoryManager
        );
    }
}
