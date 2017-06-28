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

interface PostManagerInterface
{
    /**
     * Returns breadcrumb collection
     * 
     * @param \Blog\Service\PostEntity $post
     * @return array
     */
    public function getBreadcrumbs(PostEntity $post);

    /**
     * Counts all published posts associated with particular category id
     * 
     * @param string $id Category id
     * @return integer
     */
    public function countAllPublishedByCategoryId($id);

    /**
     * Increments view count by post id
     * 
     * @param string $id
     * @return boolean
     */
    public function incrementViewCount($id);

    /**
     * Update settings
     * 
     * @param array $settings
     * @return boolean
     */
    public function updateSettings(array $settings);

    /**
     * Returns time format
     * 
     * @return string
     */
    public function getTimeFormat();

    /**
     * Returns prepared paginator's instance
     * 
     * @return \Krystal\Paginate\Paginator
     */
    public function getPaginator();

    /**
     * Returns last post id
     * 
     * @return integer
     */
    public function getLastId();

    /**
     * Fetches posts ordering by view count
     * 
     * @param integer $limit Limit of records to be fetched
     * @return array
     */
    public function fetchMostlyViewed($limit);

    /**
     * Fetches randomly published post entity
     * 
     * @return \Krystal\Stdlib\VirtualEntity
     */
    public function fetchRandomPublished();

    /**
     * Fetch recent blog post entities
     * 
     * @param integer $limit Limit of rows to be returned
     * @param string $categoryId Optional category ID filter
     * @return array
     */
    public function fetchRecent($limit, $categoryId = null);

    /**
     * Fetches all posts filtered by pagination
     * 
     * @param boolean $published Whether to fetch only published records
     * @param integer $page Current page
     * @param integer $itemsPerPage Items per page count
     * @param integer $categoryId Optional category id filter
     * @return array
     */
    public function fetchAllByPage($published, $page, $itemsPerPage, $categoryId = null);

    /**
     * Fetches all published post bags
     * 
     * @return array
     */
    public function fetchAllPublished();

    /**
     * Adds a post
     * 
     * @param array $form Form data
     * @return boolean
     */
    public function add(array $form);

    /**
     * Updates a post
     * 
     * @param array $form Form data
     * @return boolean
     */
    public function update(array $form);

    /**
     * Fetches post entity by its associated id
     * 
     * @param string $id Post ID
     * @param boolean $withAttached Whether to grab attached entities
     * @param boolean $withTranslations Whether to include translations as well
     * @return \News\Service\PostEntity|boolean|array
     */
    public function fetchById($id, $withAttached, $withTranslations);

    /**
     * Removes a post by its associated id
     * 
     * @param string $id Post's id
     * @return boolean
     */
    public function deleteById($id);

    /**
     * Removes posts by their associated ids
     * 
     * @param array $ids
     * @return boolean
     */
    public function deleteByIds(array $ids);
}
