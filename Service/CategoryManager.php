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
use Menu\Service\MenuWidgetInterface;
use Menu\Contract\MenuAwareManager;
use Krystal\Stdlib\VirtualEntity;
use Krystal\Security\Filter;
use Krystal\Stdlib\ArrayUtils;
use Krystal\Tree\AdjacencyList\TreeBuilder;
use Krystal\Tree\AdjacencyList\BreadcrumbBuilder;
use Krystal\Tree\AdjacencyList\Render\PhpArray;

final class CategoryManager extends AbstractManager implements CategoryManagerInterface, MenuAwareManager
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
     * @param \Cms\Service\HistoryManagerInterface $historyManager
     * @param \Menu\Service\MenuWidgetInterface $menuWidget Optional menu widget service
     * @return void
     */
    public function __construct(
        CategoryMapperInterface $categoryMapper,
        PostMapperInterface $postMapper,
        WebPageManagerInterface $webPageManager,
        HistoryManagerInterface $historyManager,
        MenuWidgetInterface $menuWidget = null
    ){
        $this->categoryMapper = $categoryMapper;
        $this->postMapper = $postMapper;
        $this->webPageManager = $webPageManager;
        $this->historyManager = $historyManager;

        $this->setMenuWidget($menuWidget);
    }

    /**
     * Returns a tree pre-pending prompt message
     * 
     * @param string $text
     * @return array
     */
    public function getPromtWithCategoriesTree($text)
    {
        $tree = $this->getCategoriesTree();
        ArrayUtils::assocPrepend($tree, null, $text);

        return $tree;
    }

    /**
     * Returns albums tree
     * 
     * @return array
     */
    public function getCategoriesTree()
    {
        $treeBuilder = new TreeBuilder($this->categoryMapper->fetchAll());
        return $treeBuilder->render(new PhpArray('name'));
    }

    /**
     * Returns breadcrumbs for category by its entity
     * 
     * @param \Krystal\Stdlib\VirtualEntity $category
     * @return array
     */
    public function getBreadcrumbs(VirtualEntity $category)
    {
        return $this->createBreadcrumbs($category->getId());
    }

    /**
     * Gets all breadcrumbs by associated id
     * 
     * @param string $id Category id
     * @return array
     */
    private function createBreadcrumbs($id)
    {
        $wm = $this->webPageManager;
        $builder = new BreadcrumbBuilder($this->categoryMapper->fetchBcData(), $id);

        return $builder->makeAll(function($breadcrumb) use ($wm) {
            return array(
                'name' => $breadcrumb['name'],
                'link' => $wm->getUrl($breadcrumb['web_page_id'], $breadcrumb['lang_id'])
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
    public function fetchNameByWebPageId($webPageId)
    {
        return $this->categoryMapper->fetchNameByWebPageId($webPageId);
    }

    /**
     * {@inheritDoc}
     */
    protected function toEntity(array $category)
    {
        $entity = new VirtualEntity();
        $entity->setId($category['id'], VirtualEntity::FILTER_INT)
            ->setLangId($category['lang_id'], VirtualEntity::FILTER_INT)
            ->setParentId($category['parent_id'], VirtualEntity::FILTER_INT)
            ->setWebPageId($category['web_page_id'], VirtualEntity::FILTER_INT)
            ->setTitle($category['title'], VirtualEntity::FILTER_HTML)
            ->setName($category['name'], VirtualEntity::FILTER_HTML)
            ->setDescription($category['description'], VirtualEntity::FILTER_SAFE_TAGS)
            ->setSeo($category['seo'], VirtualEntity::FILTER_BOOL)
            ->setSlug($this->webPageManager->fetchSlugByWebPageId($category['web_page_id']))
            ->setOrder($category['order'], VirtualEntity::FILTER_INT)
            ->setKeywords($category['keywords'], VirtualEntity::FILTER_HTML)
            ->setMetaDescription($category['meta_description'], VirtualEntity::FILTER_HTML)
            ->setPermanentUrl('/module/blog/category/'.$entity->getId())
            ->setUrl($this->webPageManager->surround($entity->getSlug(), $entity->getLangId()));

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
        return $this->removeCategoryById($id) && $this->removeAllPostsByCategoryId($id);
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
     * Removes all posts associated with provided category id
     * 
     * @param string $id Post's id
     * @return boolean
     */
    private function removeAllPostsByCategoryId($id)
    {
        return $this->removePostWebPagesByCategoryId($id) && $this->postMapper->deleteByCategoryId($id);
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
     * Fetches as a list
     * 
     * @return array
     */
    public function fetchList()
    {
        return ArrayUtils::arrayList($this->categoryMapper->fetchList(), 'id', 'title');
    }

    /**
     * Fetches category's entity by its associated id
     * 
     * @param string $id
     * @return \Krystal\Stdlib\VirtualEntity|boolean
     */
    public function fetchById($id)
    {
        return $this->prepareResult($this->categoryMapper->fetchById($id));
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
        $category =& $input['category'];

        // Empty slug is always take from a title
        if (empty($category['slug'])) {
            $category['slug'] = $category['name'];
        }

        // Empty title is taken from the name
        if (empty($category['title'])) {
            $category['title'] = $category['name'];
        }

        $category['slug'] = $this->webPageManager->sluggify($category['slug']);
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
        $category =& $input['category'];

        $category['web_page_id'] = '';

        if ($this->categoryMapper->insert(ArrayUtils::arrayWithout($category, array('menu', 'slug')))) {

            $id = $this->getLastId();
            $this->track('Category "%s" has been created', $category['name']);

            // Add a web page now
            if ($this->webPageManager->add($id, $category['slug'], 'Blog (Categories)', 'Blog:Category@indexAction', $this->categoryMapper)){
                // Do the work in case menu widget was injected
                if ($this->hasMenuWidget()) {
                    $this->addMenuItem($this->webPageManager->getLastId(), $category['name'], $input);
                }
            }

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
        $category =& $input['category'];

        $this->webPageManager->update($category['web_page_id'], $category['slug']);

        if ($this->hasMenuWidget() && isset($input['menu'])) {
            $this->updateMenuItem($category['web_page_id'], $category['name'], $input['menu']);
        }

        $this->track('Category "%s" has been updated', $category['name']);
        return $this->categoryMapper->update(ArrayUtils::arrayWithout($category, array('menu', 'slug')));
    }
}
