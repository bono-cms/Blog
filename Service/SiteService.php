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
use Blog\Storage\CategoryMapperInterface;
use Krystal\Stdlib\VirtualEntity;

final class SiteService extends AbstractManager implements SiteServiceInterface
{
    /**
     * Any-compliant category mapper
     * 
     * @var \Blog\Storage\CategoryMapperInterface
     */
    private $categoryMapper;

    /**
     * Post management service
     * 
     * @var \Blog\Service\PostManagerInterface
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
     * @param \Blog\Storage\CategoryMapperInterface $categoryMapper
     * @param \Blog\Service\PostManagerInterface $postManager
     * @param \Cms\Service\WebPageManagerInterface $webPageManager
     * @return void
     */
    public function __construct(CategoryMapperInterface $categoryMapper, PostManagerInterface $postManager, WebPageManagerInterface $webPageManager)
    {
        $this->categoryMapper = $categoryMapper;
        $this->postManager = $postManager;
        $this->webPageManager = $webPageManager;
    }

    /**
     * {@inheritDoc}
     */
    protected function toEntity(array $category)
    {
        $entity = new VirtualEntity();
        $entity->setId($category['id'])
               ->setLangId($category['lang_id'])
               ->setCount($this->postManager->countAllPublishedByCategoryId($entity->getId()))
               ->setSlug($this->webPageManager->fetchSlugByWebPageId($category['web_page_id']))
               ->setTitle($category['name'] . sprintf(' (%s) ', $entity->getCount()))
               ->setUrl($this->webPageManager->surround($entity->getSlug(), $entity->getLangId()));

        return $entity;
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
        return $this->prepareResults($this->categoryMapper->fetchAllBasic());
    }
}
