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
            PostMapper::column('id'),
            PostTranslationMapper::column('web_page_id'),
            PostTranslationMapper::column('lang_id'),
            PostTranslationMapper::column('title'),
            PostTranslationMapper::column('introduction') => 'content',
            PostTranslationMapper::column('name')
        );

        $queryBuilder->select($columns)
                     ->from(PostMapper::getTableName())
                     // Translation relation
                     ->innerJoin(PostTranslationMapper::getTableName())
                     ->on()
                     ->equals(
                        PostMapper::column('id'),
                        PostTranslationMapper::column('id')
                     )
                     // Constraints
                     ->whereEquals(PostTranslationMapper::column('lang_id'), "'{$this->getLangId()}'")
                     ->andWhereEquals(PostMapper::column('published'), '1')
                     // Search
                     ->andWhereLike(PostTranslationMapper::column('name'), $placeholder)
                     ->orWhereLike(PostTranslationMapper::column('full'), $placeholder);
    }
}
