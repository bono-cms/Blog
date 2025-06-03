<?php

/**
 * This file is part of the Bono CMS
 * 
 * Copyright (c) No Global State Lab
 * 
 * For the full copyright and license information, please view
 * the license file that was distributed with this source code.
 */

namespace Blog\Service;

use Cms\Service\AbstractManager;
use Cms\Service\WebPageManagerInterface;
use Krystal\Stdlib\VirtualEntity;

final class SiteService extends AbstractManager
{
    /**
     * CategoryService
     * 
     * @var \Blog\Service\CategoryService
     */
    private $categoryManager;

    /**
     * Post management service
     * 
     * @var \Blog\Service\PostManager
     */
    private $postManager;

    /**
     * Web page manager
     * 
     * @var \Cms\Service\WebPageManagerInterface
     */
    private $webPageManager;

    /**
     * State initialization
     * 
     * @param \Blog\Service\CategoryManager $categoryManager
     * @param \Blog\Service\PostManager $postManager
     * @param \Cms\Service\WebPageManager $webPageManager
     * @return void
     */
    public function __construct(
        CategoryManager $categoryManager,
        PostManager $postManager,
        WebPageManagerInterface $webPageManager
    ){
        $this->categoryManager = $categoryManager;
        $this->postManager = $postManager;
        $this->webPageManager = $webPageManager;
    }

    /**
     * Gets random post entity
     * 
     * @param int $limit
     * @return array
     */
    public function getRandom($limit)
    {
        return $this->postManager->fetchRandomPublished($limit);
    }

    /**
     * Returns recent blog post entities
     * 
     * @param integer $limit Limit of posts to be returned
     * @param string $categoryId Optional category ID filter
     * @return array
     */
    public function getRecent($limit, $categoryId = null)
    {
        return $this->postManager->fetchRecent($limit, $categoryId);
    }

    /**
     * Fetches posts ordering by view count
     * 
     * @param integer $limit Limit of records to be fetched
     * @return array
     */
    public function getMostlyViewed($limit)
    {
        return $this->postManager->fetchMostlyViewed($limit);
    }

    /**
     * Returns an array of categories with count of posts
     * 
     * @return array
     */
    public function getAllCategoriesWithCount()
    {
        return $this->categoryManager->fetchAll(true);
    }
}
