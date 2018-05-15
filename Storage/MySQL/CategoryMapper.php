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
use Blog\Storage\CategoryMapperInterface;
use Krystal\Db\Sql\RawSqlFragment;

final class CategoryMapper extends AbstractMapper implements CategoryMapperInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getTableName()
    {
        return self::getWithPrefix('bono_module_blog_categories');
    }

    /**
     * {@inheritDoc}
     */
    public static function getTranslationTable()
    {
        return CategoryTranslationMapper::getTableName();
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
            self::getFullColumnName('parent_id'),
            CategoryTranslationMapper::getFullColumnName('web_page_id'),
            CategoryTranslationMapper::getFullColumnName('lang_id'),
            CategoryTranslationMapper::getFullColumnName('name'),
            self::getFullColumnName('seo'),
            self::getFullColumnName('cover'),
            self::getFullColumnName('order'),
            WebPageMapper::getFullColumnName('slug'),
        );

        if ($all) {
            $columns = array_merge($columns, array(
                CategoryTranslationMapper::getFullColumnName('title'),
                CategoryTranslationMapper::getFullColumnName('description'),
                CategoryTranslationMapper::getFullColumnName('keywords'),
                CategoryTranslationMapper::getFullColumnName('meta_description'),
            ));
        }

        return $columns;
    }

    /**
     * Fetch all categories with their attached post names
     * 
     * @return array
     */
    public function fetchAllWithPosts()
    {
        return $this->db->select(array(
                            PostMapper::getFullColumnName('id'),
                            PostTranslationMapper::getFullColumnName('name') => 'post',
                            CategoryTranslationMapper::getFullColumnName('name') => 'category'
                        ))
                        ->from(PostMapper::getTableName())
                        // Category relation
                        ->innerJoin(self::getTableName())
                        ->on()
                        ->equals(
                            PostMapper::getFullColumnName('category_id'), 
                            new RawSqlFragment(self::getFullColumnName('id'))
                        )
                        // Post translation relation
                        ->innerJoin(PostTranslationMapper::getTableName())
                        ->on()
                        ->equals(
                            PostMapper::getFullColumnName('id'), 
                            new RawSqlFragment(PostTranslationMapper::getFullColumnName('id'))
                        )
                        // Category translation relation
                        ->innerJoin(CategoryTranslationMapper::getTableName())
                        ->on()
                        ->equals(
                            self::getFullColumnName('id'), 
                            new RawSqlFragment(CategoryTranslationMapper::getFullColumnName('id'))
                        )
                        // Filtering condition
                        ->whereEquals(CategoryTranslationMapper::getFullColumnName('lang_id'), $this->getLangId())
                        ->queryAll();
    }

    /**
     * Fetches breadcrumb data
     * 
     * @return array
     */
    public function fetchBcData()
    {
        return $this->db->select($this->getSharedColumns(false))
                        ->from(self::getTableName())
                        // Translation relation
                        ->innerJoin(self::getTranslationTable())
                        ->on()
                        ->equals(
                            CategoryTranslationMapper::getFullColumnName('id'), 
                            new RawSqlFragment(self::getFullColumnName('id'))
                        )
                        // Web page relation
                        ->innerJoin(WebPageMapper::getTableName())
                        ->on()
                        ->equals(
                            WebPageMapper::getFullColumnName('id'),
                            new RawSqlFragment(CategoryTranslationMapper::getFullColumnName('web_page_id'))
                        )
                        ->rawAnd()
                        ->equals(
                            WebPageMapper::getFullColumnName('lang_id'),
                            new RawSqlFragment(CategoryTranslationMapper::getFullColumnName('lang_id'))
                        )
                        // Filtering condition
                        ->whereEquals(CategoryTranslationMapper::getFullColumnName('lang_id'), $this->getLangId())
                        ->queryAll();
    }

    /**
     * Fetches child albums by parent id
     * 
     * @param string $parentId
     * @return array
     */
    public function fetchChildrenByParentId($parentId)
    {
        return $this->createWebPageSelect($this->getSharedColumns(true))
                    // Filtering condition
                    ->whereEquals(self::getFullColumnName('parent_id'), $parentId)
                    ->andWhereEquals(CategoryTranslationMapper::getFullColumnName('lang_id'), $this->getLangId())
                    ->queryAll();
    }

    /**
     * Inserts a category
     * 
     * @param array $input Raw input data
     * @return boolean
     */
    public function insert(array $input)
    {
        return $this->persist($this->getWithlang($input));
    }

    /**
     * Updates a category
     * 
     * @param array $input Raw input data
     * @return boolean
     */
    public function update(array $input)
    {
        return $this->persist($input);
    }

    /**
     * Deletes a category by its associated id
     * 
     * @param string $id Category id
     * @return boolean
     */
    public function deleteById($id)
    {
        return $this->deleteByPk($id);
    }

    /**
     * Fetches category name by its associated id
     * 
     * @param string $id Category id
     * @return string
     */
    public function fetchNameById($id)
    {
    }

    /**
     * Fetches category data by its associated id
     * 
     * @param string $id Category id
     * @param boolean $withTranslations Whether to fetch translations or not
     * @return array
     */
    public function fetchById($id, $withTranslations)
    {
        return $this->findWebPage($this->getSharedColumns(true), $id, $withTranslations);
    }

    /**
     * Fetches all categories
     * 
     * @param boolean $countOnlyPublished Whether to count all or only published posts in categories
     * @return array
     */
    public function fetchAll($countOnlyPublished = false)
    {
        $columns = $this->getSharedColumns(false);

        $db = $this->db->select($columns)
                        ->count(PostMapper::getFullColumnName('id'), 'post_count')
                        ->from(self::getTableName())

                        // Category relation
                        ->leftJoin(PostMapper::getTableName())
                        ->on()
                        ->equals(
                            self::getFullColumnName('id'), 
                            new RawSqlFragment(PostMapper::getFullColumnName('category_id'))
                        )
                        // Translation relation
                        ->innerJoin(self::getTranslationTable())
                        ->on()
                        ->equals(
                            CategoryTranslationMapper::getFullColumnName('id'),
                            new RawSqlFragment(self::getFullColumnName('id'))
                        )
                        ->rawAnd()
                        ->equals(
                            CategoryTranslationMapper::getFullColumnName('lang_id'),
                            $this->getLangId()
                        )
                        // Web page relation
                        ->innerJoin(WebPageMapper::getTableName())
                        ->on()
                        ->equals(
                            WebPageMapper::getFullColumnName('id'),
                            new RawSqlFragment(CategoryTranslationMapper::getFullColumnName('web_page_id'))
                        )
                        ->rawAnd()
                        ->equals(
                            WebPageMapper::getFullColumnName('lang_id'),
                            new RawSqlFragment(CategoryTranslationMapper::getFullColumnName('lang_id'))
                        );

        if ($countOnlyPublished == true) {
            $db->whereEquals(PostMapper::getFullColumnName('published'), '1');
        }

        // Aggregate grouping
        return $db->groupBy($columns)
                  ->queryAll();
    }
}
