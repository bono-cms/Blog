<?php

/**
 * This file is part of the Bono CMS
 * 
 * Copyright (c) No Global State Lab
 * 
 * For the full copyright and license information, please view
 * the license file that was distributed with this source code.
 */

namespace Blog\Storage;

interface CategoryMapperInterface
{
    /**
     * Fetch all categories with their attached post names
     * 
     * @return array
     */
    public function fetchAllWithPosts();

    /**
     * Fetches breadcrumb data
     * 
     * @return array
     */
    public function fetchBcData();

    /**
     * Fetches child albums by parent id
     * 
     * @param string $parentId
     * @return array
     */
    public function fetchChildrenByParentId($parentId);

    /**
     * Fetches category data by its associated id
     * 
     * @param string $id Category id
     * @param boolean $withTranslations Whether to fetch translations or not
     * @return array
     */
    public function fetchById($id, $withTranslations);

    /**
     * Fetches all categories
     * 
     * @param boolean $countOnlyPublished Whether to count all or only published posts in categories
     * @return array
     */
    public function fetchAll($countOnlyPublished = false);
}
