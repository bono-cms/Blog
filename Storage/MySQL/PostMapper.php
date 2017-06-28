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
use Cms\Storage\MySQL\WebPageMapper;
use Blog\Storage\PostMapperInterface;
use Krystal\Db\Sql\RawSqlFragment;
use Krystal\Stdlib\ArrayUtils;
use Closure;

final class PostMapper extends AbstractMapper implements PostMapperInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getTableName()
    {
        return self::getWithPrefix('bono_module_blog_posts');
    }

    /**
     * {@inheritDoc}
     */
    public static function getTranslationTable()
    {
        return self::getWithPrefix('bono_module_blog_posts_translations');
    }

    /**
     * {@inheritDoc}
     */
    public static function getJunctionTableName()
    {
        return self::getWithPrefix('bono_module_blog_posts_attached');
    }

    /**
     * Returns a collection of shared columns to be selected
     * 
     * @param boolean $all Whether to select all columns or not
     * @return array
     */
    private function getSharedColumns($all)
    {
        // Basic columns to be selected
        $columns = array(
            self::getFullColumnName('id'),
            self::getFullColumnName('timestamp'),
            self::getFullColumnName('comments'),
            self::getFullColumnName('published'),
            self::getFullColumnName('seo'),
            self::getFullColumnName('web_page_id', self::getTranslationTable()),
            self::getFullColumnName('lang_id', self::getTranslationTable()),
            self::getFullColumnName('name', self::getTranslationTable()),
            WebPageMapper::getFullColumnName('slug'),
            self::getFullColumnName('name', CategoryMapper::getTranslationTable()) => 'category_name'
        );

        if ($all) {
            $columns = array_merge($columns, array(
                self::getFullColumnName('category_id'),
                self::getFullColumnName('views'),
                self::getFullColumnName('timestamp'),
                self::getFullColumnName('title', self::getTranslationTable()),
                self::getFullColumnName('introduction', self::getTranslationTable()),
                self::getFullColumnName('full', self::getTranslationTable()),
                self::getFullColumnName('keywords', self::getTranslationTable()),
                self::getFullColumnName('meta_description', self::getTranslationTable())
            ));
        }

        return $columns;
    }

    /**
     * Fetches all posts filtered by pagination
     * 
     * @param string $categoryId Category ID
     * @param boolean $published Whether to fetch only published records
     * @param integer $page Current page
     * @param integer $itemsPerPage Per page count
     * @param \Closure $orderCallback Callback to generate ORDER BY condition
     * @return array
     */
    private function findRecords($categoryId, $published, $page, $itemsPerPage, Closure $orderCallback)
    {
        $db = $this->createWebPageSelect($this->getSharedColumns(false))
                   // Category translating relation
                   ->innerJoin(CategoryMapper::getTranslationTable())
                   ->on()
                   ->equals(
                        CategoryMapper::getFullColumnName('id', CategoryMapper::getTranslationTable()), 
                        new RawSqlFragment(self::getFullColumnName('category_id'))
                    )
                    // Filtering condition
                    ->whereEquals(
                        self::getFullColumnName('lang_id', self::getTranslationTable()), 
                        $this->getLangId()
                    );

        // Append category ID if provided
        if ($categoryId !== null) {
            $db->andWhereEquals(self::getFullColumnName('category_id'), $categoryId);
        }

        if ($published) {
            $db->andWhereEquals(self::getFullColumnName('published'), '1');
        }

        // Apply order callback
        $orderCallback($db);

        // If page number and per page count provided, apply pagination
        if ($page !== null && $itemsPerPage !== null) {
            $db->paginate($page, $itemsPerPage);
        }

        // If only per page count provided, apply limit only
        if ($page === null && $itemsPerPage !== null) {
            $db->limit($itemsPerPage);
        }

        return $db->queryAll();
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
                       ->from(self::getTableName())
                       ->whereEquals('category_id', $categoryId);

        if ($published === true) {
            $db->andWhereEquals('published', '1');
        }

        return (int) $db->query('count');
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
                        ->from(self::getTableName())
                        ->whereEquals('category_id', $id)
                        ->queryAll('web_page_id');
    }

    /**
     * Fetches post name by its associated id
     * 
     * @param string $id Post's id
     * @return string
     */
    public function fetchNameById($id)
    {
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
        $this->persist($this->getWithLang(ArrayUtils::arrayWithout($input, array(self::PARAM_COLUMN_ATTACHED))));
        $id = $this->getLastId();

        // Insert relational posts if provided
        if (isset($input[self::PARAM_COLUMN_ATTACHED])) {
            $this->insertIntoJunction(self::getJunctionTableName(), $id, $input[self::PARAM_COLUMN_ATTACHED]);
        }

        return true;
    }

    /**
     * Updates a post
     * 
     * @param array $input Raw input data
     * @return boolean
     */
    public function update(array $input)
    {
        // Synchronize relations if provided
        if (isset($input[self::PARAM_COLUMN_ATTACHED])) {
            $this->syncWithJunction(self::getJunctionTableName(), $input[$this->getPk()], $input[self::PARAM_COLUMN_ATTACHED]);
        } else {
            $this->removeFromJunction(self::getJunctionTableName(), $input[$this->getPk()]);
        }

        return $this->persist(ArrayUtils::arrayWithout($input, array(self::PARAM_COLUMN_ATTACHED)));
    }

    /**
     * Fetches post data by associated IDs
     * 
     * @param array $ids A collection of post IDs
     * @param boolean $relational Whether to include relational data
     * @param boolean $withTranslations Whether to include translations as well
     * @return array
     */
    public function fetchByIds(array $ids, $relational = false, $withTranslations = false)
    {
        $db = $this->createWebPageSelect($this->getSharedColumns(true))
                    // Category relation
                    ->innerJoin(CategoryMapper::getTableName())
                    ->on()
                    ->equals(
                        CategoryMapper::getFullColumnName('id'), 
                        new RawSqlFragment(self::getFullColumnName('category_id'))
                    )
                   // Category translating relation
                   ->innerJoin(CategoryMapper::getTranslationTable())
                   ->on()
                   ->equals(
                        CategoryMapper::getFullColumnName('id', CategoryMapper::getTranslationTable()), 
                        new RawSqlFragment(self::getFullColumnName('category_id'))
                    )
                    ->whereIn(self::getFullColumnName('id'), $ids);

        if ($relational === true) {
            $db->asManyToMany(self::PARAM_COLUMN_ATTACHED, self::getJunctionTableName(), self::PARAM_JUNCTION_MASTER_COLUMN, self::getTableName(), 'id', 'id');
        }

        return $db->queryAll();
    }

    /**
     * Fetches post data by its associated id
     * 
     * @param string $id
     * @param boolean $withTranslations Whether to include translations as well
     * @return array
     */
    public function fetchById($id, $withTranslations = false)
    {
        $row = $this->fetchByIds(array($id), true, $withTranslations);

        if ($withTranslations == true) {
            return $row;
        }

        if (isset($row[0])) {
            return $row[0];
        } else {
            return array();
        }
    }

    /**
     * Deletes a post by its associated id
     * 
     * @param string $id Post id
     * @return boolean
     */
    public function deleteById($id)
    {
        return $this->removeFromJunction(self::getJunctionTableName(), $id) && $this->deletePage($id);
    }

    /**
     * Deletes all posts associated with provided category id
     * 
     * @param string $categoryId
     * @return boolean
     */
    public function deleteByCategoryId($categoryId)
    {
        $this->removeFromJunction(self::getJunctionTableName(), $this->findPostIdsByCategoryId($categoryId));
        return $this->deleteByColumn('category_id', $categoryId);
    }

    /**
     * Fetches posts ordering by view count
     * 
     * @param integer $limit Limit of records to be fetched
     * @return array
     */
    public function fetchMostlyViewed($limit)
    {
        return $this->findRecords(null, true, null, $limit, function($db){
            $db->orderBy('views')
               ->desc();
        });
    }

    /**
     * Find a collection of post IDs attached to category ID
     * 
     * @param string $categoryId
     * @return array
     */
    private function findPostIdsByCategoryId($categoryId)
    {
        return $this->db->select($this->getPk())
                        ->from(self::getTableName())
                        ->whereEquals('category_id', $categoryId)
                        ->queryAll($this->getPk());
    }

    /**
     * Fetches randomly published post
     * 
     * @return array
     */
    public function fetchRandomPublished()
    {
        $rows = $this->findRecords(null, true, null, 1, function($db){
            $db->orderBy()
               ->rand();
        });

        return isset($rows[0]) ? $rows[0] : array();
    }

    /**
     * Fetches all published posts
     * 
     * @return array
     */
    public function fetchAllPublished()
    {
        return $this->findRecords(null, true, null, null, function($db){
            $db->orderBy(array(
                self::getFullColumnName('timestamp') => 'DESC', 
                self::getFullColumnName('id') => 'DESC'
            ));
        });
    }

    /**
     * Fetch recent blog posts
     * 
     * @param integer $limit Limit of rows to be returned
     * @param string $categoryId Optional category ID filter
     * @return array
     */
    public function fetchRecent($limit, $categoryId = null)
    {
        return $this->fetchAllByPage(true, null, $limit, $categoryId);
    }

    /**
     * Fetches all posts filtered by pagination
     * 
     * @param boolean $published Whether to fetch only published records
     * @param integer $page Current page
     * @param integer $itemsPerPage Per page count
     * @param string $categoryId Optional category id filter
     * @return array
     */
    public function fetchAllByPage($published, $page, $itemsPerPage, $categoryId)
    {
        return $this->findRecords($categoryId, $published, $page, $itemsPerPage, function($db) use ($published){
            // If needed to fetch by published, then sort by time
            if ($published) {
                $db->orderBy(array(
                        self::getFullColumnName('timestamp') => 'DESC', 
                        self::getFullColumnName('id') => 'DESC'
                    ));
            } else {
                $db->orderBy(self::getFullColumnName('id'))
                   ->desc();
            }
        });
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
