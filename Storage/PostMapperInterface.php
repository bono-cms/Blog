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

interface PostMapperInterface
{
    const PARAM_COLUMN_ATTACHED = 'attached';

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
     * Inserts a post
     * 
     * @param array $data Post data
     * @return boolean
     */
    public function insert(array $data);

    /**
     * Updates a post
     * 
     * @param array $data Post data
     * @return boolean
     */
    public function update(array $data);

    /**
     * Fetches post data by associated IDs
     * 
     * @param array $ids A collection of post IDs
     * @param boolean $relational Whether to include relational data
     * @param boolean $withTranslations Whether to include translations as well
     * @return array
     */
    public function fetchByIds(array $ids, $relational = false, $withTranslations = false);

    /**
     * Fetches post data by its associated id
     * 
     * @param string $id
     * @param boolean $withTranslations Whether to include translations as well
     * @return array
     */
    public function fetchById($id, $withTranslations = false);

    /**
     * Deletes a post by its associated id
     * 
     * @param string $id Post id
     * @return boolean
     */
    public function deleteById($id);

    /**
     * Deletes all posts associated with provided category id
     * 
     * @param string $categoryId
     * @return boolean
     */
    public function deleteByCategoryId($categoryId);

    /**
     * Fetches posts ordering by view count
     * 
     * @param integer $limit Limit of records to be fetched
     * @return array
     */
    public function fetchMostlyViewed($limit);

    /**
     * Fetches randomly published post
     * 
     * @return array
     */
    public function fetchRandomPublished();

    /**
     * Find a collection of post IDs attached to category ID
     * 
     * @param string $categoryId
     * @return array
     */
    public function findPostIdsByCategoryId($categoryId);

    /**
     * Fetches all published posts
     * 
     * @return array
     */
    public function fetchAllPublished();

    /**
     * Fetch recent blog posts
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
     * @param integer $itemsPerPage Per page count
     * @param string $categoryId Optional category id filter
     * @return array
     */
    public function fetchAllByPage($published, $page, $itemsPerPage, $categoryId);
}
