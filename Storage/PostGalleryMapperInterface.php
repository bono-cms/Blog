<?php

/**
 * This file is part of the Bono CMS
 * 
 * Copyright (c) No Global State Lab
 * 
 * For the full copyright and license information, please view
 * the license file that was distributed with this source code.
 */

namespace Blog\Storage;

interface PostGalleryMapperInterface
{
    /**
     * Fetch images by attached post ID
     * 
     * @param int $postId
     * @return array
     */
    public function fetchAllByPostId($postId);

    /**
     * Fetches post image by its ID
     * 
     * @param int $id Image ID
     * @return array
     */
    public function fetchById($id);
}
