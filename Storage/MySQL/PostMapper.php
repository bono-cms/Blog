<?php

/**
 * This file is part of the Bono CMS
 * 
 * Copyright (c) No Global State Lab
 * 
 * For the full copyright and license information, please view
 * the license file that was distributed with this source code.
 */

namespace Blog\Storage\MySQL;

use Cms\Storage\MySQL\AbstractMapper;
use Blog\Storage\PostMapperInterface;

final class PostMapper extends AbstractMapper implements PostMapperInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getTableName()
    {
        return 'bono_module_blog_posts';
    }

    /**
     * Returns shared select query
     * 
     * @param boolean $published Whether to sort only published records
     * @param string $sort Column name to sort by
     * @param string $categoryId Optional category id
     * @return \Krystal\Db\Sql\Db
     */
    private function getSelectQuery($published, $sort, $categoryId = null)
    {
        $db = $this->db->select('*')
                       ->from(static::getTableName())
                       ->whereEquals('lang_id', $this->getLangId());

        if ($published == true) {
            $db->andWhereEquals('published', '1');
        }

        if ($categoryId !== null) {
            $db->andWhereEquals('category_id', $categoryId);
        }

        if ($sort !== 'rand') {
            $db->orderBy($sort);
        } else {
            $db->orderBy()
               ->rand();
        }

        return $db;
    }

    /**
     * Queries for a result
     * 
     * @param integer $page Current page number
     * @param integer $itemsPerPage Per page count
     * @param boolean $published Whether to sort only published records
     * @param string $sort Column name to sort by
     * @param string $categoryId Optional category id
     * @return array
     */
    private function getResults($page, $itemsPerPage, $published, $sort, $categoryId = null)
    {
        return $this->getSelectQuery($published, $sort, $categoryId)
                    ->paginate($page, $itemsPerPage)
                    ->queryAll();
    }

    /**
     * Counts amount of categories
     * 
     * @param string $categoryId
     * @param boolean $published Whether to include published in calculation
     * @return integer
     */
    private function getCount($categoryId, $published)
    {
        $db = $this->db->select()
                       ->count('id', 'count')
                       ->from(static::getTableName())
                       ->whereEquals('category_id', $categoryId);

        if ($published === true) {
            $db->andWhereEquals('published', '1');
        }

        return (int) $db->query('count');
    }

    /**
     * Decides which column to use depending on published state
     * 
     * @param boolean $published
     * @return array
     */
    private function getSortingColumn($published)
    {
        $published = (bool) $published;

        if ($published) {
            // This method for the site
            return array(
                'timestamp' => 'DESC',
                'id' => 'DESC'
            );

        } else {
            // This method for the administration area
            return array(
                'id' => 'DESC'
            );
        }
    }

    /**
     * Increments view count by post id
     * 
     * @param string $id
     * @return boolean
     */
    public function incrementViewCount($id)
    {
        return $this->incrementColumnByPk($id, 'views');
    }

    /**
     * Fetches web page ids by associated category id
     * 
     * @param string $id Category's id
     * @return array
     */
    public function fetchWebPageIdsByCategoryId($id)
    {
        return $this->db->select('web_page_id')
                        ->from(static::getTableName())
                        ->whereEquals('category_id', $id)
                        ->queryAll('web_page_id');
    }

    /**
     * Fetches post name by its associated id
     * 
     * @param string $id Post's id
     * @return string
     */
    public function fetchTitleById($id)
    {
        return $this->findColumnByPk($id, 'title');
    }

    /**
     * Updates post's published state
     * 
     * @param string $id Post id
     * @param string $published Either 0 or 1
     * @return boolean
     */
    public function updatePublished($id, $published)
    {
        return $this->updateColumnByPk($id, 'published', $published);
    }

    /**
     * Update post comment's state, if they are enabled or not
     * 
     * @param string $id Post id
     * @param string $comments Either 0 or 1
     * @return boolean
     */
    public function updateComments($id, $comments)
    {
        return $this->updateColumnByPk($id, 'comments', $comments);
    }

    /**
     * Updates post seo's state, if must be indexed or not
     * 
     * @parma string $id Post id
     * @param string $seo Either 0 or 1
     * @return boolean
     */
    public function updateSeo($id, $seo)
    {
        return $this->updateColumnByPk($id, 'seo', $seo);
    }

    /**
     * Adds a post
     * 
     * @param array $input Raw input data
     * @return boolean
     */
    public function insert(array $input)
    {
        return $this->persist($this->getWithLang($input));
    }

    /**
     * Updates a post
     * 
     * @param array $input Raw input data
     * @return boolean
     */
    public function update(array $input)
    {
        return $this->persist($input);
    }

    /**
     * Fetches post data by its associated id
     * 
     * @param string $id Post id
     * @return array
     */
    public function fetchById($id)
    {
        return $this->findByPk($id);
    }

    /**
     * Deletes a post by its associated id
     * 
     * @param string $id Post id
     * @return boolean
     */
    public function deleteById($id)
    {
        return $this->deleteByPk($id);
    }

    /**
     * Deletes all posts associated with provided category id
     * 
     * @param string $categoryId
     * @return boolean
     */
    public function deleteByCategoryId($categoryId)
    {
        return $this->deleteByColumn('category_id', $categoryId);
    }

    /**
     * Fetches randomly published post
     * 
     * @return array
     */
    public function fetchRandomPublished()
    {
        return $this->getSelectQuery(true, 'rand')
                    ->query();
    }

    /**
     * Fetches all published posts
     * 
     * @return array
     */
    public function fetchAllPublished()
    {
        return $this->getSelectQuery(true, 'timestamp', null)
                    ->queryAll();
    }

    /**
     * Fetches all posts associated with given category id and filtered by pagination
     * 
     * @param string $categoryId
     * @param boolean $published Whether to fetch only published records
     * @param integer $page Current page
     * @param integer $itemsPerPage Per page count
     * @return array
     */
    public function fetchAllByCategoryIdAndPage($categoryId, $published, $page, $itemsPerPage)
    {
        return $this->getResults($page, $itemsPerPage, $published, $this->getSortingColumn($published), $categoryId);
    }

    /**
     * Fetches all posts filtered by pagination
     * 
     * @param boolean $published Whether to fetch only published records
     * @param integer $page Current page
     * @param integer $itemsPerPage Per page count
     * @return array
     */
    public function fetchAllByPage($published, $page, $itemsPerPage)
    {
        return $this->getResults($page, $itemsPerPage, $published, $this->getSortingColumn($published));
    }

    /**
     * Count all published posts associated with given category id
     * 
     * @param string $categoryId
     * @return integer
     */
    public function countAllPublishedByCategoryId($categoryId)
    {
        return $this->getCount($categoryId, true);
    }

    /**
     * Counts all posts associated with given category id
     * 
     * @param string $categoryId
     * @return integer
     */
    public function countAllByCategoryId($categoryId)
    {
        return $this->getCount($categoryId, false);
    }
}
