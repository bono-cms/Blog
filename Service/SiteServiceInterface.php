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

interface SiteServiceInterface
{
    /**
     * Returns recent blog post entities
     * 
     * @param integer $limit Limit of posts to be returned
     * @param string $categoryId Optional category ID filter
     * @return array
     */
    public function getRecent($limit, $categoryId = null);

    /**
     * Fetches posts ordering by view count
     * 
     * @param integer $limit Limit of records to be fetched
     * @return array
     */
    public function getMostlyViewed($limit);

    /**
     * Returns an array of categories with count of posts
     * 
     * @return array
     */
    public function getAllCategoriesWithCount();
}
