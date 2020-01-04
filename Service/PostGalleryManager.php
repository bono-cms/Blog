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
use Krystal\Image\Tool\ImageManager;
use Cms\Service\AbstractManager;
use Blog\Storage\PostGalleryMapperInterface;

final class PostGalleryManager extends AbstractManager
{
    /**
     * Any compliant post gallery mapper
     * 
     * @var \Blog\Storage\PostGalleryMapperInterface
     */
    private $postGalleryMapper;

    /**
     * Image manager for posts
     * 
     * @var \Krystal\Image\Tool\ImageManager
     */
    private $imageManager;

    /**
     * State initialization
     * 
     * @param \Blog\Storage\PostGalleryMapperInterface $postGalleryMapper
     * @param \Krystal\Image\Tool\ImageManager $imageManager
     * @return void
     */
    public function __construct(PostGalleryMapperInterface $postGalleryMapper, ImageManager $imageManager)
    {
        $this->postGalleryMapper = $postGalleryMapper;
        $this->imageManager = $imageManager;
    }

    /**
     * {@inheritDoc}
     */
    protected function toEntity(array $row)
    {
        // Configure image bag
        $imageBag = clone $this->imageManager->getImageBag();
        $imageBag->setId((int) $row['id'])
                 ->setCover($row['image']);

        $entity = new ImageEntity();
        $entity->setId($row['id'])
               ->setPostId($row['post_id'])
               ->setOrder($row['order'])
               ->setImage($row['image'])
               ->setImageBag($imageBag);

        return $entity;
    }

    /**
     * Deletes an image by its associated ID
     * 
     * @param int $id Image ID
     * @return boolean
     */
    public function deleteById($id)
    {
        return $this->postGalleryMapper->deleteByPk($id) && $this->imageManager->delete($id);
    }

    /**
     * Returns last ID
     * 
     * @return integer
     */
    public function getLastId()
    {
        return $this->postGalleryMapper->getMaxId();
    }

    /**
     * Fetch images by attached post ID
     * 
     * @param int $postId
     * @return array
     */
    public function fetchAllByPostId($postId)
    {
        return $this->prepareResults($this->postGalleryMapper->fetchAllByPostId($postId));
    }

    /**
     * Finds image by its associated id
     * 
     * @param int $id Image ID
     * @return mixed
     */
    public function fetchById($id)
    {
        return $this->prepareResult($this->postGalleryMapper->fetchById($id));
    }

    /**
     * Adds an image
     * 
     * @param array $input
     * @return boolean
     */
    public function add(array $input)
    {
        $image = $input['data']['image'];
        $file = isset($input['files']['file']) ? $input['files']['file'] : false;

        // Define image attribute
        $image['image'] = $file->getUniqueName();

        // Save image first, because we need to get its ID for image uploading
        $this->postGalleryMapper->persist($image);

        // And now upload image
        $this->imageManager->upload($this->getLastId(), $file);

        return true;
    }

    /**
     * Updates an image
     * 
     * @param array $input
     * @return boolean
     */
    public function update(array $input)
    {
        // Grab a reference to image data
        $image = $input['data']['image'];
        $file = isset($input['files']['file']) ? $input['files']['file'] : false;

        // If file new provided, than start handling
        if ($file) {
            // If we have a previous cover, then we gotta remove it
            $this->imageManager->delete($image['id'], $image['image']);
            $this->imageManager->upload($image['id'], $file);

            // Now override cover's value with file's base name we currently have from user's input
            $image['image'] = $file->getUniqueName();
        }

        return $this->postGalleryMapper->persist($image);
    }
}
