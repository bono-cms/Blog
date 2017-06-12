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
     * Fetches as a list
     * 
     * @return array
     */
    public function fetchList();

    /**
     * Inserts a category
     * 
     * @param array $data Category data
     * @return boolean
     */
    public function insert(array $data);

    /**
     * Updates a category
     * 
     * @param array $data Category data
     * @return boolean
     */
    public function update(array $data);

    /**
     * Deletes a category by its associated id
     * 
     * @param string $id Category id
     * @return boolean
     */
    public function deleteById($id);

    /**
     * Fetches category name by its associated id
     * 
     * @param string $id Category id
     * @return string
     */
    public function fetchNameById($id);

    /**
     * Fetches category data by its associated id
     * 
     * @param string $id Category id
     * @return array
     */
    public function fetchById($id);

    /**
     * Fetches all categories
     * 
     * @return array
     */
    public function fetchAll();
}
