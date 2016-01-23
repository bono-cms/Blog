<?php

/**
 * This file is part of the Bono CMS
 * 
 * Copyright (c) No Global State Lab
 * 
 * For the full copyright and license information, please view
 * the license file that was distributed with this source code.
 */

namespace Blog\Service;

use Krystal\Stdlib\VirtualEntity;

final class PostEntity extends VirtualEntity
{
    /**
     * Returns custom date format
     * 
     * @param string $format
     * @return string
     */
    public function getCustomDateFormat($format)
    {
        return date($format, $this->getTimestamp());
    }
}
