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
        return PostTranslationMapper::getTableName();
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
            self::column('id'),
            self::column('timestamp'),
            self::column('comments'),
            self::column('published'),
            self::column('seo'),
            self::column('cover'),
            PostTranslationMapper::column('web_page_id'),
            PostTranslationMapper::column('lang_id'),
            PostTranslationMapper::column('name'),
            PostTranslationMapper::column('introduction'),
            WebPageMapper::column('slug'),
            WebPageMapper::column('changefreq'),
            WebPageMapper::column('priority'),
            CategoryTranslationMapper::column('name') => 'category_name'
        );

        if ($all) {
            $columns = array_merge($columns, array(
                self::column('category_id'),
                self::column('views'),
                self::column('timestamp'),
                PostTranslationMapper::column('title'),
                PostTranslationMapper::column('full'),
                PostTranslationMapper::column('keywords'),
                PostTranslationMapper::column('meta_description')
            ));
        }

        return $columns;
    }

    /**
     * Fetches all posts filtered by pagination
     * 
     * @param integer $page Current page
     * @param integer $itemsPerPage Per page count
     * @param \Closure $orderCallback Callback to generate ORDER BY condition
     * @param array $filters Optional filters
     * @return array
     */
    private function findRecords($page, $itemsPerPage, Closure $orderCallback, array $filters = [])
    {
        $db = $this->db->select($this->getSharedColumns(false))
                       ->from(self::getTableName())
                       // Translation relation
                       ->innerJoin(PostTranslationMapper::getTableName(), array(
                            PostTranslationMapper::column('id') => self::getRawColumn('id')
                       ))
                        // Category translation
                        ->innerJoin(CategoryTranslationMapper::getTableName(), array(
                            self::column('category_id') => CategoryTranslationMapper::getRawColumn('id'),
                            CategoryTranslationMapper::column('lang_id') => PostTranslationMapper::getRawColumn('lang_id')
                        ))
                        // Category relation
                        ->innerJoin(CategoryMapper::getTableName(), array(
                            CategoryMapper::column('id') => CategoryTranslationMapper::getRawColumn('id')
                        ))
                        // Web page relation
                        ->innerJoin(WebPageMapper::getTableName(), array(
                            WebPageMapper::column('id') => PostTranslationMapper::getRawColumn('web_page_id')
                        ))
                        // Filtering condition
                        ->whereEquals(
                            PostTranslationMapper::column('lang_id'), 
                            $this->getLangId()
                        );

        // Filter: Category ID
        if (isset($filters['category_id'])) {
            $db->andWhereEquals(self::column('category_id'), $filters['category_id']);
        }

        // Filter: Published state
        if (isset($filters['published']) && $filters['published'] == true) {
            $db->andWhereEquals(self::column('published'), '1');
        }

        // Filter: Name
        if (isset($filters['name'])) {
            $db->andWhereLike(PostTranslationMapper::column('name'), '%' . $filters['name'] . '%');
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
     * Update settings
     * 
     * @param array $settings
     * @return boolean
     */
    public function updateSettings(array $settings)
    {
        return $this->updateColumns($settings, array('comments', 'seo', 'published'));
    }

    /**
     * Save attached ones
     * 
     * @param array $input
     * @return boolean
     */
    public function saveAttached(array $input)
    {
        if (!empty($input[$this->getPk()])) {
            // UPDATE operation
            // Synchronize relations if provided
            if (isset($input[self::PARAM_COLUMN_ATTACHED])) {
                $this->syncWithJunction(self::getJunctionTableName(), $input[$this->getPk()], $input[self::PARAM_COLUMN_ATTACHED]);
            } else {
                $this->removeFromJunction(self::getJunctionTableName(), $input[$this->getPk()]);
            }
        } else {
            // INSERT operation
            $id = $this->getLastId();

            // Insert relational posts if provided
            if (isset($input[self::PARAM_COLUMN_ATTACHED])) {
                $this->insertIntoJunction(self::getJunctionTableName(), $id, $input[self::PARAM_COLUMN_ATTACHED]);
            }
        }

        return true;
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
                    ->innerJoin(CategoryMapper::getTableName(), array(
                        CategoryMapper::column('id') => self::getRawColumn('category_id')
                    ))
                   // Category translating relation
                   ->innerJoin(CategoryTranslationMapper::getTableName())
                   ->on()
                   ->equals(
                        CategoryTranslationMapper::column('id'), 
                        new RawSqlFragment(self::column('category_id'))
                    );

        if ($withTranslations === false) {
            $db->rawAnd()
               ->equals(
                  CategoryTranslationMapper::column('lang_id'), 
                  new RawSqlFragment(PostTranslationMapper::column('lang_id'))
                );
        }

        $db->whereIn(self::column('id'), $ids);

        if ($withTranslations === false) {
			$db->andWhereEquals(
				PostTranslationMapper::column('lang_id'), 
				$this->getLangId()
			);
		}

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
        $filters = [
            'published' => true
        ];

        return $this->findRecords(null, $limit, function($db){
            $db->orderBy('views')
               ->desc();
        }, $filters);
    }

    /**
     * Find a collection of post IDs attached to category ID
     * 
     * @param string $categoryId
     * @return array
     */
    public function findPostIdsByCategoryId($categoryId)
    {
        return $this->db->select($this->getPk())
                        ->from(self::getTableName())
                        ->whereEquals('category_id', $categoryId)
                        ->queryAll($this->getPk());
    }

    /**
     * Fetches randomly published post
     * 
     * @param int $limit
     * @return array
     */
    public function fetchRandomPublished($limit)
    {
        $filters = [
            'published' => true
        ];

        $rows = $this->findRecords(null, $limit, function($db){
            $db->orderBy()
               ->rand();
        }, $filters);

        return $rows;
    }

    /**
     * Fetches all published posts
     * 
     * @return array
     */
    public function fetchAllPublished()
    {
        $filters = [
            'published' => true
        ];

        return $this->findRecords(null, null, function($db){
            $db->orderBy(array(
                self::column('timestamp') => 'DESC', 
                self::column('id') => 'DESC'
            ));
        }, $filters);
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
        $filters = [
            'published' => true
        ];

        if (is_numeric($categoryId)) {
            $filters['category_id'] = $categoryId;
        }

        return $this->fetchAllByPage(null, $limit, $filters);
    }

    /**
     * Fetches all posts filtered by pagination
     * 
     * @param integer $page Current page
     * @param integer $itemsPerPage Per page count
     * @param array $filtes Optional filters
     * @return array
     */
    public function fetchAllByPage($page, $itemsPerPage, array $filters = [])
    {
        return $this->findRecords($page, $itemsPerPage, function($db) use ($filters){
            // If needed to fetch by published, then sort by time
            if (isset($filters['published'])) {
                $db->orderBy(array(
                        self::column('timestamp') => 'DESC', 
                        self::column('id') => 'DESC'
                    ));
            } else {
                $db->orderBy(self::column('id'))
                   ->desc();
            }
        }, $filters);
    }
}
