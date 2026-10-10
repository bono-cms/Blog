<?php

/**
 * This file is part of the Bono CMS
 * 
 * For the full copyright and license information, please view
 * the license file that was distributed with this source code.
 */

namespace Blog\Controller\Admin;

use Krystal\Validation\Validator;
use Cms\Controller\Admin\AbstractConfigController;

final class Config extends AbstractConfigController
{
    /**
     * {@inheritDoc}
     */
    protected function configureValidator(Validator $validator)
    {
        $validator->field('config.per_page_count', 'Per page count')
                  ->required()
                  ->addRule('numeric')
                  ->addRule('greaterthan', null, ['min' => 0]);
    }
}
