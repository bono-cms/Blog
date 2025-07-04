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
use Blog\Service\PostGalleryManager;
use Blog\Service\CategoryManager;
use Blog\Service\TaskManager;
use Blog\Service\SiteService;
use Krystal\Image\Tool\ImageManager;

final class Module extends AbstractCmsModule
{
    /**
     * Builds gallery image manager service
     * 
     * @return \Krystal\Image\Tool\ImageManager
     */
    private function createGalleryImageManager()
    {
        $plugins = array(
            'thumb' => array(
                'quality' => 75,
                'dimensions' => array(
                    // For administration panel
                    array(400, 400),
                )
            )
        );

        return new ImageManager(
            '/data/uploads/module/blog/gallery/',
            $this->appConfig->getRootDir(),
            $this->appConfig->getRootUrl(),
            $plugins
        );
    }

    /**
     * Returns album image manager for category
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
     * Returns album image manager for post
     * 
     * @return \Krystal\Image\ImageManager
     */
    private function createPostImageManager()
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
            '/data/uploads/module/blog/posts',
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
        $postGalleryMapper = $this->getMapper('/Blog/Storage/MySQL/PostGalleryMapper');

        $webPageManager = $this->getWebPageManager();

        $postManager = new PostManager($postMapper, $categoryMapper, $webPageManager, $this->createPostImageManager());
        $categoryManager = new CategoryManager($categoryMapper, $postMapper, $webPageManager, $this->createCategoryImageManager());

        $siteService = new SiteService($categoryManager, $postManager, $webPageManager);

        return array(
            'siteService' => $siteService,
            'configManager' => $this->createConfigService(),
            'postManager' => $postManager,
            'categoryManager' => $categoryManager,
            'postGalleryManager' => new PostGalleryManager($postGalleryMapper, $this->createGalleryImageManager())
        );
    }
}
