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
        return self::getWithPrefix('bono_module_blog_categories_translations');
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
            self::getFullColumnName('web_page_id', self::getTranslationTable()),
            self::getFullColumnName('lang_id', self::getTranslationTable()),
            self::getFullColumnName('name', self::getTranslationTable()),
            self::getFullColumnName('seo'),
            WebPageMapper::getFullColumnName('slug'),
        );

        if ($all) {
            $columns = array_merge($columns, array(
                self::getFullColumnName('title', self::getTranslationTable()),
                self::getFullColumnName('description', self::getTranslationTable()),
                self::getFullColumnName('keywords', self::getTranslationTable()),
                self::getFullColumnName('meta_description', self::getTranslationTable()),
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
                            PostMapper::getFullColumnName('name', PostMapper::getTranslationTable()) => 'post',
                            self::getFullColumnName('name', self::getTranslationTable()) => 'category'
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
                        ->innerJoin(PostMapper::getTranslationTable())
                        ->on()
                        ->equals(
                            PostMapper::getFullColumnName('id'), 
                            new RawSqlFragment(self::getFullColumnName('id', PostMapper::getTranslationTable()))
                        )
                        // Category translation relation
                        ->innerJoin(self::getTranslationTable())
                        ->on()
                        ->equals(
                            self::getFullColumnName('id'), 
                            new RawSqlFragment(self::getFullColumnName('id', self::getTranslationTable()))
                        )
                        // Filtering condition
                        ->whereEquals(self::getFullColumnName('lang_id', self::getTranslationTable()), $this->getLangId())
                        ->queryAll();
    }

    /**
     * Fetches breadcrumb data
     * 
     * @return array
     */
    public function fetchBcData()
    {
        return $this->db->select(array(
                            self::getFullColumnName('name', self::getTranslationTable()),
                            self::getFullColumnName('web_page_id', self::getTranslationTable()),
                            self::getFullColumnName('lang_id', self::getTranslationTable()),
                            self::getFullColumnName('id'),
                            self::getFullColumnName('parent_id'),
                        ))
                        ->from(self::getTableName())
                        // Translation relation
                        ->innerJoin(self::getTranslationTable())
                        ->on()
                        ->equals(
                            self::getFullColumnName('id', self::getTranslationTable()), 
                            new RawSqlFragment(self::getFullColumnName('id'))
                        )
                        // Filtering condition
                        ->whereEquals(self::getFullColumnName('lang_id', self::getTranslationTable()), $this->getLangId())
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
        return $this->db->select('*')
                        ->from(self::getTableName())
                        // Translation relation
                        ->innerJoin(self::getTranslationTable())
                        ->on()
                        ->equals(
                            self::getFullColumnName('id', self::getTranslationTable()), 
                            new RawSqlFragment(self::getFullColumnName('id'))
                        )
                        // Filtering condition
                        ->whereEquals(self::getFullColumnName('parent_id'), $parentId)
                        ->queryAll();
    }

    /**
     * Fetches as a list
     * 
     * @return array
     */
    public function fetchList()
    {
        return $this->db->select(array(
                            self::getFullColumnName('id', self::getTranslationTable()), 
                            self::getFullColumnName('name', self::getTranslationTable()))
                        )
                        ->from(self::getTranslationTable())
                        // Filtering condition
                        ->whereEquals(self::getFullColumnName('lang_id', self::getTranslationTable()), $this->getLangId())
                        ->queryAll();
    }

    /**
     * Fetches data for breadcrumbs
     * 
     * @param string $id
     * @return array
     */
    public function fetchBcDataById($id)
    {
        return $this->db->select(array(
                            self::getFullColumnName('id', self::getTranslationTable()),
                            self::getFullColumnName('name', self::getTranslationTable()))
                        )
                        ->from(self::getTableName())
                        ->whereEquals(self::getFullColumnName('id', self::getTranslationTable()), $id)
                        ->andWhereEquals(self::getFullColumnName('lang_id', self::getTranslationTable()), $this->getLangId())
                        ->query();
    }

    /**
     * Fetches all basic data about categories
     * 
     * @return array
     */
    public function fetchAllBasic()
    {
        return $this->fetchBcData();
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
     * @return array
     */
    public function fetchAll()
    {
        return $this->createWebPageSelect($this->getSharedColumns(false))
                    ->whereEquals(self::getFullColumnName('lang_id', self::getTranslationTable()), $this->getLangId())
                    ->queryAll();
    }
}
