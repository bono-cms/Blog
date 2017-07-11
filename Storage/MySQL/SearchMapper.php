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
            PostMapper::getFullColumnName('web_page_id', PostMapper::getTranslationTable()),
            PostMapper::getFullColumnName('lang_id', PostMapper::getTranslationTable()),
            PostMapper::getFullColumnName('title', PostMapper::getTranslationTable()),
            PostMapper::getFullColumnName('introduction', PostMapper::getTranslationTable()) => 'content',
            PostMapper::getFullColumnName('name', PostMapper::getTranslationTable())
        );

        $queryBuilder->select($columns)
                     ->from(PostMapper::getTableName())
                     // Translation relation
                     ->innerJoin(PostMapper::getTranslationTable())
                     ->on()
                     ->equals(
                        PostMapper::getFullColumnName('id'),
                        PostMapper::getFullColumnName('id', PostMapper::getTranslationTable())
                     )
                     // Constraints
                     ->whereEquals(PostMapper::getFullColumnName('lang_id', PostMapper::getTranslationTable()), "'{$this->getLangId()}'")
                     ->andWhereEquals(PostMapper::getFullColumnName('published'), '1')
                     // Search
                     ->andWhereLike(PostMapper::getFullColumnName('name', PostMapper::getTranslationTable()), $placeholder)
                     ->orWhereLike(PostMapper::getFullColumnName('full', PostMapper::getTranslationTable()), $placeholder);
    }
}
