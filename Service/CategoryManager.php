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

use Cms\Service\AbstractManager;
use Cms\Service\HistoryManagerInterface;
use Cms\Service\WebPageManagerInterface;
use Blog\Storage\CategoryMapperInterface;
use Blog\Storage\PostMapperInterface;
use Krystal\Stdlib\ArrayUtils;
use Krystal\Tree\AdjacencyList\TreeBuilder;
use Krystal\Tree\AdjacencyList\BreadcrumbBuilder;
use Krystal\Tree\AdjacencyList\Render;
use Krystal\Image\Tool\ImageManagerInterface;

final class CategoryManager extends AbstractManager
{
    /**
     * Any compliant category mapper
     * 
     * @var \Blog\Storage\CategoryMapperInterface
     */
    private $categoryMapper;

    /**
     * Any compliant post mapper
     * 
     * @var \Blog\Storage\PostMapperInterface
     */
    private $postMapper;

    /**
     * Web page manager to deal with slugs
     * 
     * @var \Cms\Service\WebPageManagerInterface
     */
    private $webPageManager;

    /**
     * Category image manager
     * 
     * @var \Krystal\Image\ImageManagerInterface
     */
    private $imageManager;

    /**
     * State initialization
     * 
     * @param \Blog\Storage\CategoryMapperInterface $categoryMapper
     * @param \Blog\Storage\PostMapperInterface $postMapper
     * @param \Cms\Service\WebPageManagerInterface $webPageManager
     * @param \Krystal\Image\ImageManagerInterface $imageManager
     * @return void
     */
    public function __construct(CategoryMapperInterface $categoryMapper, PostMapperInterface $postMapper, WebPageManagerInterface $webPageManager,ImageManagerInterface $imageManager)
    {
        $this->categoryMapper = $categoryMapper;
        $this->postMapper = $postMapper;
        $this->webPageManager = $webPageManager;
        $this->imageManager = $imageManager;
    }

    /**
     * Fetch all categories with their associated posts
     * 
     * @return array
     */
    public function fetchAllWithPosts()
    {
        return ArrayUtils::arrayDropdown($this->categoryMapper->fetchAllWithPosts(), 'category', 'id', 'post');
    }

    /**
     * Returns a tree pre-pending prompt message
     * 
     * @param string $text
     * @return array
     */
    public function getPromtWithCategoriesTree($text)
    {
        $tree = $this->getCategoriesTree(false);
        ArrayUtils::assocPrepend($tree, null, $text);

        return $tree;
    }

    /**
     * Returns albums tree
     * 
     * @param boolean $all Whether to fetch as a pair or a collection
     * @return array
     */
    public function getCategoriesTree($all)
    {
        $rows = $this->categoryMapper->fetchAll();
        $treeBuilder = new TreeBuilder($rows);

        if ($all == true) {
            $rows = $treeBuilder->render(new Render\Merge('name'));

            // @TODO XSS filtering
            foreach ($rows as $index => $row) {
                // Append new "url" key
                $rows[$index]['url'] = $this->webPageManager->surround($row['slug'], $row['lang_id']);
            }

            return $rows;
        } else {
            return $treeBuilder->render(new Render\PhpArray('name'));
        }
    }

    /**
     * Returns breadcrumbs for category by its entity
     * 
     * @param \Blog\Service\CategoryEntity $category
     * @return array
     */
    public function getBreadcrumbs(CategoryEntity $category)
    {
        $builder = new BreadcrumbBuilder($this->categoryMapper->fetchBcData(), $category->getId());
        $wm = $this->webPageManager;

        return $builder->makeAll(function($row) use ($wm) {
            return array(
                'name' => $row['name'],
                'link' => $wm->surround($row['slug'], $row['lang_id'])
            );
        });
    }

    /**
     * {@inheritDoc}
     */
    protected function toEntity(array $category)
    {
        $imageBag = clone $this->imageManager->getImageBag();
        $imageBag->setId((int) $category['id'])
                 ->setCover($category['cover']);

        $entity = new CategoryEntity();
        $entity->setId($category['id'], CategoryEntity::FILTER_INT)
            ->setImageBag($imageBag)
            ->setLangId($category['lang_id'], CategoryEntity::FILTER_INT)
            ->setParentId($category['parent_id'], CategoryEntity::FILTER_INT)
            ->setWebPageId($category['web_page_id'], CategoryEntity::FILTER_INT)
            ->setTitle($category['title'], CategoryEntity::FILTER_HTML)
            ->setName($category['name'], CategoryEntity::FILTER_HTML)
            ->setDescription($category['description'], CategoryEntity::FILTER_SAFE_TAGS)
            ->setSeo($category['seo'], CategoryEntity::FILTER_BOOL)
            ->setSlug(isset($category['slug']) ? $category['slug'] : null)
            ->setOrder($category['order'], CategoryEntity::FILTER_INT)
            ->setKeywords($category['keywords'], CategoryEntity::FILTER_HTML)
            ->setMetaDescription($category['meta_description'], CategoryEntity::FILTER_HTML)
            ->setPermanentUrl('/module/blog/category/'.$entity->getId())
            ->setUrl($this->webPageManager->surround($entity->getSlug(), $entity->getLangId()))
            ->setChangeFreq($category['changefreq'])
            ->setPriority($category['priority'])
            ->setCover($category['cover']);

        if (isset($category['post_count'])) {
            $category->setCount($category['post_count']);
        }

        return $entity;
    }

    /**
     * Returns last category's id
     * 
     * @return integer
     */
    public function getLastId()
    {
        return $this->categoryMapper->getLastId();
    }

    /**
     * Removes a category and its associated posts
     * 
     * @param string $id Category's id
     * @return boolean
     */
    private function removeAllById($id)
    {
        return $this->categoryMapper->deletePage($id) && $this->postMapper->deletePage($this->postMapper->findPostIdsByCategoryId($id));
    }

    /**
     * Removes child albums that belong to provided id
     * 
     * @param string $parentId
     * @return boolean
     */
    private function removeChildCategoriesByParentId($parentId)
    {
        $treeBuilder = new TreeBuilder($this->categoryMapper->fetchAll());
        $ids = $treeBuilder->findChildNodeIds($parentId);

        // If there's at least one child id, then start working next
        if (!empty($ids)) {
            foreach ($ids as $id) {
                $this->removeAllById($id);
            }
        }

        return true;
    }

    /**
     * Removes a category by its associated id
     * 
     * @param string $id Category's id
     * @return boolean
     */
    public function deleteById($id)
    {
        return $this->removeChildCategoriesByParentId($id) && $this->removeAllById($id);
    }

    /**
     * Fetches child categories by parent id
     * 
     * @param string $parentId
     * @return array
     */
    public function fetchChildrenByParentId($parentId)
    {
        return $this->prepareResults($this->categoryMapper->fetchChildrenByParentId($parentId));
    }

    /**
     * Fetches category's entity by its associated id
     * 
     * @param string $id
     * @param boolean $withTranslations Whether to fetch translations or not
     * @return \Krystal\Stdlib\VirtualEntity|boolean|array
     */
    public function fetchById($id, $withTranslations)
    {
        if ($withTranslations == true) {
            return $this->prepareResults($this->categoryMapper->fetchById($id, true));
        } else {
            return $this->prepareResult($this->categoryMapper->fetchById($id, false));
        }
    }

    /**
     * Fetches all category entities
     * 
     * @return array
     */
    public function fetchAll()
    {
        return $this->prepareResults($this->categoryMapper->fetchAll());
    }

    /**
     * Returns a collection of switching URLs
     * 
     * @param string $id Category ID
     * @return array
     */
    public function getSwitchUrls($id)
    {
        return $this->categoryMapper->createSwitchUrls($id, 'Blog (Categories)', 'Blog:Category@indexAction');
    }

    /**
     * Saves a page
     * 
     * @param array $input
     * @return boolean
     */
    private function savePage(array $input)
    {
        $category =& $input['data']['category'];

        // Strict casting
        $category['parent_id'] = (int) $category['parent_id'];
        $category['order'] = (int) $category['order'];

        $category = ArrayUtils::arrayWithout($category, array('slug', 'remove_cover'));
        return $this->categoryMapper->savePage('Blog (Categories)', 'Blog:Category@indexAction', $category, $input['data']['translation']);
    }

    /**
     * Adds a category
     * 
     * @param array $input Raw input data
     * @return boolean
     */
    public function add(array $input)
    {
        // Form data reference
        $category =& $input['data']['category'];
        $file = isset($input['files']['file']) ? $input['files']['file'] : false;

        // If there's a file, then it needs to uploaded as a cover
        if ($file) {
            $this->imageManager->upload($this->getLastId(), $file);
            // Override empty cover's value now
            $category['cover'] = $file->getUniqueName();
        }

        return $this->savePage($input);
    }

    /**
     * Updates a category
     * 
     * @param array $input Raw input data
     * @return boolean
     */
    public function update(array $input)
    {
        $category =& $input['data']['category'];
        $file = isset($input['files']['file']) ? $input['files']['file'] : false;

        // Allow to remove a cover, only it case it exists and checkbox was checked
        if (isset($category['remove_cover'])) {
            // Remove a cover, but not a dir itself
            $this->imageManager->delete($category['id']);
            $category['cover'] = '';
        } else {
            if ($file) {
                // If we have a previous cover's image, then we need to remove it
                if (!empty($category['cover'])) {
                    if (!$this->imageManager->delete($category['id'], $category['cover'])) {
                        // If failed, then exit this method immediately
                        return false;
                    }
                }

                // And now upload a new one
                $category['cover'] = $file->getUniqueName();
                $this->imageManager->upload($category['id'], $file);
            }
        }

        return $this->savePage($input);
    }
}
