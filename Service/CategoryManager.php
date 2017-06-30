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
use Krystal\Security\Filter;
use Krystal\Stdlib\ArrayUtils;
use Krystal\Tree\AdjacencyList\TreeBuilder;
use Krystal\Tree\AdjacencyList\BreadcrumbBuilder;
use Krystal\Tree\AdjacencyList\Render;
use Krystal\Image\Tool\ImageManagerInterface;

final class CategoryManager extends AbstractManager implements CategoryManagerInterface
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
     * History manager to keep tracks
     * 
     * @var \Cms\Service\HistoryManagerInterface
     */
    private $historyManager;

    /**
     * State initialization
     * 
     * @param \Blog\Storage\CategoryMapperInterface $categoryMapper
     * @param \Blog\Storage\PostMapperInterface $postMapper
     * @param \Cms\Service\WebPageManagerInterface $webPageManager
     * @param \Krystal\Image\ImageManagerInterface $imageManager
     * @param \Cms\Service\HistoryManagerInterface $historyManager
     * @return void
     */
    public function __construct(
        CategoryMapperInterface $categoryMapper,
        PostMapperInterface $postMapper,
        WebPageManagerInterface $webPageManager,
        ImageManagerInterface $imageManager,
        HistoryManagerInterface $historyManager
    ){
        $this->categoryMapper = $categoryMapper;
        $this->postMapper = $postMapper;
        $this->webPageManager = $webPageManager;
        $this->imageManager = $imageManager;
        $this->historyManager = $historyManager;
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
     * Tracks activity
     * 
     * @param string $message
     * @param string $placeholder
     * @return boolean
     */
    private function track($message, $placeholder)
    {
        return $this->historyManager->write('Blog', $message, $placeholder);
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
            ->setCover($category['cover']);

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
        $this->removeCategoryById($id);

        // Remove posts
        $this->removePostWebPagesByCategoryId($id);
        $this->postMapper->deleteByCategoryId($id);

        return true;
    }

    /**
     * Removes all web pages associated with category id
     * 
     * @param string $id Category's id
     * @return boolean
     */
    private function removePostWebPagesByCategoryId($id)
    {
        $ids = $this->postMapper->fetchWebPageIdsByCategoryId($id);

        if (!empty($ids)) {
            foreach ($ids as $id) {
                $this->webPageManager->deleteById($id);
            }
        }

        return true;
    }

    /**
     * Removes web page associated with provided category id
     * 
     * @param string $id Category's id
     * @return boolean
     */
    private function removeWebPageByCategoryId($id)
    {
        $webPageId = $this->categoryMapper->fetchWebPageIdById($id);
        return $this->webPageManager->deleteById($webPageId);
    }

    /**
     * Removes a category by its associated id
     * 
     * @param string $id Category's id
     * @return boolean
     */
    private function removeCategoryById($id)
    {
        return $this->removeWebPageByCategoryId($id) && $this->categoryMapper->deleteById($id);
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
        // Grab category's name before we remove it
        $title = Filter::escape($this->categoryMapper->fetchNameById($id));

        // Completely remove the post
        $this->removeChildCategoriesByParentId($id);
        $this->removeAllById($id);

        $this->track('Category "%s" has been removed', $title);
        return true;
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
     * Prepares input before sending to the mapper
     * 
     * @param array $input Raw input data
     * @return array
     */
    private function prepareInput(array $input)
    {
        $category =& $input['data']['category'];

        // Empty slug is always take from a name
        if (empty($category['slug'])) {
            $category['slug'] = $category['name'];
        }

        // Empty title is taken from the name
        if (empty($category['title'])) {
            $category['title'] = $category['name'];
        }

        $category['slug'] = $this->webPageManager->sluggify($category['slug']);

        // Safe type-casting
        $category['web_page_id'] = (int) $category['web_page_id'];
        $category['parent_id'] = (int) $category['parent_id'];
        $category['order'] = (int) $category['order'];

        return $input;
    }

    /**
     * Adds a category
     * 
     * @param array $input Raw input data
     * @return boolean
     */
    public function add(array $input)
    {
        $input = $this->prepareInput($input);

        // Form data reference
        $category =& $input['data']['category'];

        // If we have a cover, then we need to upload it
        if (!empty($input['files']['file'])) {
            $file =& $input['files']['file'];
            $this->filterFileInput($file);

            // Override empty cover's value now
            $category['cover'] = $file[0]->getName();
        }

        if ($this->categoryMapper->insert(ArrayUtils::arrayWithout($category, array('slug')))) {
            $id = $this->getLastId();
            $this->track('Category "%s" has been created', $category['name']);

            // If there's a file, then it needs to uploaded as a cover
            if (!empty($input['files']['file'])) {
                $this->imageManager->upload($id, $input['files']['file']);
            }

            // Add a web page now
            $this->webPageManager->add($id, $category['slug'], 'Blog (Categories)', 'Blog:Category@indexAction', $this->categoryMapper);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Updates a category
     * 
     * @param array $input Raw input data
     * @return boolean
     */
    public function update(array $input)
    {
        $input = $this->prepareInput($input);
        $category =& $input['data']['category'];

        // Allow to remove a cover, only it case it exists and checkbox was checked
        if (isset($category['remove_cover'])) {
            // Remove a cover, but not a dir itself
            $this->imageManager->delete($category['id']);
            $category['cover'] = '';
        } else {
            if (!empty($input['files']['file'])) {
                $file =& $input['files']['file'];
                // If we have a previous cover's image, then we need to remove it
                if (!empty($category['cover'])) {
                    if (!$this->imageManager->delete($category['id'], $category['cover'])) {
                        // If failed, then exit this method immediately
                        return false;
                    }
                }

                // And now upload a new one
                $this->filterFileInput($file);
                $category['cover'] = $file[0]->getName();

                $this->imageManager->upload($category['id'], $file);
            }
        }

        $this->webPageManager->update($category['web_page_id'], $category['slug']);

        $this->track('Category "%s" has been updated', $category['name']);
        return $this->categoryMapper->update(ArrayUtils::arrayWithout($category, array('slug', 'remove_cover')));
    }
}
