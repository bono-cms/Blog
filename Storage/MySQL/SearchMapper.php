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
use Krystal\Db\Sql\QueryBuilderInterface;

final class SearchMapper extends AbstractMapper
{
    /**
     * {@inheritDoc}
     */
    public function appendQuery(QueryBuilderInterface $queryBuilder, $placeholder)
    {
        // Columns to be selected
        $columns = array(
            PostMapper::getFullColumnName('id'),
            PostTranslationMapper::getFullColumnName('web_page_id'),
            PostTranslationMapper::getFullColumnName('lang_id'),
            PostTranslationMapper::getFullColumnName('title'),
            PostTranslationMapper::getFullColumnName('introduction') => 'content',
            PostTranslationMapper::getFullColumnName('name')
        );

        $queryBuilder->select($columns)
                     ->from(PostMapper::getTableName())
                     // Translation relation
                     ->innerJoin(PostTranslationMapper::getTableName())
                     ->on()
                     ->equals(
                        PostMapper::getFullColumnName('id'),
                        PostTranslationMapper::getFullColumnName('id')
                     )
                     // Constraints
                     ->whereEquals(PostTranslationMapper::getFullColumnName('lang_id'), "'{$this->getLangId()}'")
                     ->andWhereEquals(PostMapper::getFullColumnName('published'), '1')
                     // Search
                     ->andWhereLike(PostTranslationMapper::getFullColumnName('name'), $placeholder)
                     ->orWhereLike(PostTranslationMapper::getFullColumnName('full'), $placeholder);
    }
}
