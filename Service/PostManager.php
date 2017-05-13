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

use Cms\Service\WebPageManagerInterface;
use Cms\Service\AbstractManager;
use Cms\Service\HistoryManagerInterface;
use Blog\Storage\PostMapperInterface;
use Blog\Storage\CategoryMapperInterface;
use Menu\Contract\MenuAwareManager;
use Krystal\Security\Filter;
use Krystal\Stdlib\ArrayUtils;
use Krystal\Tree\AdjacencyList\BreadcrumbBuilder;

final class PostManager extends AbstractManager implements PostManagerInterface, MenuAwareManager
{
    /**
     * Any compliant post mapper
     * 
     * @var \Blog\Storage\PostMapperInterface
     */
    private $postMapper;

    /**
     * Any compliant category mapper
     * 
     * @var \Blog\Storage\CategoryMapperInterface
     */
    private $categoryMapper;

    /**
     * Web page manager
     * 
     * @var \Cms\Service\WebPageManagerInterface
     */
    private $webPageManager;

    /**
     * History manager to keep track
     * 
     * @var \Cms\Service\HistoryManagerInterface
     */
    private $historyManager;

    /**
     * State initialization
     * 
     * @param \Blog\Storage\PostMapperInterface $postMapper
     * @param \Blog\Storage\CategoryMapperInterface $categoryMapper
     * @param \Cms\Service\WebPageManagerInterface $webPageManager
     * @param \Cms\Service\HistoryManagerInterface $historyManager
     * @return void
     */
    public function __construct(
        PostMapperInterface $postMapper, 
        CategoryMapperInterface $categoryMapper,
        WebPageManagerInterface $webPageManager,
        HistoryManagerInterface $historyManager
    ){
        $this->postMapper = $postMapper;
        $this->categoryMapper = $categoryMapper;
        $this->webPageManager = $webPageManager;
        $this->historyManager = $historyManager;
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
     * Gets category breadcrumbs with appends
     * 
     * @param string $id Category's id
     * @param array $appends
     * @return array
     */
    private function getWithCategoryBreadcrumbsById($id, array $appends)
    {
        return array_merge($this->createBreadcrumbs($id), $appends);
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
     * Returns breadcrumb collection
     * 
     * @param \Blog\Service\PostEntity $post
     * @return array
     */
    public function getBreadcrumbs(PostEntity $post)
    {
        return $this->getWithCategoryBreadcrumbsById($post->getCategoryId(), array(
            array(
                'name' => $post->getName(),
                'link' => '#'
            )
        ));
    }

    /**
     * Counts all published posts associated with particular category id
     * 
     * @param string $id Category id
     * @return integer
     */
    public function countAllPublishedByCategoryId($id)
    {
        return $this->postMapper->countAllPublishedByCategoryId($id);
    }

    /**
     * Increments view count by post id
     * 
     * @param string $id
     * @return boolean
     */
    public function incrementViewCount($id)
    {
        return $this->postMapper->incrementViewCount($id);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchNameByWebPageId($webPageId)
    {
        return $this->postMapper->fetchNameByWebPageId($webPageId);
    }

    /**
     * Updates SEO states by their associated ids
     * 
     * @param array $pair
     * @return boolean
     */
    public function updateSeo(array $pair)
    {
        foreach ($pair as $id => $seo) {
            if (!$this->postMapper->updateSeo($id, $seo)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Update comments. Enabled or disable for particular post
     * 
     * @param array $pair
     * @return boolean
     */
    public function updateComments(array $pair)
    {
        foreach ($pair as $id => $comments) {
            if (!$this->postMapper->updateComments($id, $comments)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Updates published state by their associated ids
     * 
     * @param array $pair
     * @return boolean
     */
    public function updatePublished(array $pair)
    {
        foreach ($pair as $id => $published) {
            if (!$this->postMapper->updatePublished($id, $published)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns time format
     * 
     * @return string
     */
    public function getTimeFormat()
    {
        return 'm/d/Y';
    }

    /**
     * {@inheritDoc}
     */
    protected function toEntity(array $post)
    {
        $entity = new PostEntity();
        $entity->setId($post['id'], PostEntity::FILTER_INT)
            ->setLangId($post['lang_id'], PostEntity::FILTER_INT)
            ->setWebPageId($post['web_page_id'], PostEntity::FILTER_INT)
            ->setCategoryTitle($this->categoryMapper->fetchNameById($post['category_id']), PostEntity::FILTER_HTML)
            ->setTitle($post['title'], PostEntity::FILTER_HTML)
            ->setName($post['name'], PostEntity::FILTER_HTML)
            ->setCategoryId($post['category_id'], PostEntity::FILTER_INT)
            ->setIntroduction($post['introduction'], PostEntity::FILTER_SAFE_TAGS)
            ->setFull($post['full'], PostEntity::FILTER_SAFE_TAGS)
            ->setTimestamp($post['timestamp'], PostEntity::FILTER_INT)
            ->setPublished($post['published'], PostEntity::FILTER_BOOL)
            ->setComments($post['comments'], PostEntity::FILTER_BOOL)
            ->setSeo($post['seo'], PostEntity::FILTER_BOOL)
            ->setSlug($this->webPageManager->fetchSlugByWebPageId($post['web_page_id']))
            ->setKeywords($post['keywords'], PostEntity::FILTER_HTML)
            ->setMetaDescription($post['meta_description'], PostEntity::FILTER_HTML)
            ->setDate(date($this->getTimeFormat(), $entity->getTimestamp()))
            ->setPermanentUrl('/module/blog/post/'.$entity->getId())
            ->setUrl($this->webPageManager->surround($entity->getSlug(), $entity->getLangId()))
            ->setViewsCount($post['views'], PostEntity::FILTER_INT);

        return $entity;
    }

    /**
     * Returns prepared paginator's instance
     * 
     * @return \Krystal\Paginate\Paginator
     */
    public function getPaginator()
    {
        return $this->postMapper->getPaginator();
    }

    /**
     * Returns last post id
     * 
     * @return integer
     */
    public function getLastId()
    {
        return $this->postMapper->getLastId();
    }

    /**
     * Fetches posts ordering by view count
     * 
     * @param integer $limit Limit of records to be fetched
     * @return array
     */
    public function fetchMostlyViewed($limit)
    {
        return $this->prepareResults($this->postMapper->fetchMostlyViewed($limit));
    }

    /**
     * Fetches randomly published post entity
     * 
     * @return \Krystal\Stdlib\VirtualEntity
     */
    public function fetchRandomPublished()
    {
        return $this->prepareResult($this->postMapper->fetchRandomPublished());
    }

    /**
     * Fetches all posts filtered by pagination
     * 
     * @param boolean $published Whether to fetch only published records
     * @param integer $page Current page
     * @param integer $itemsPerPage Items per page count
     * @param integer $categoryId Optional category id filter
     * @return array
     */
    public function fetchAllByPage($published, $page, $itemsPerPage, $categoryId = null)
    {
        return $this->prepareResults($this->postMapper->fetchAllByPage($published, $page, $itemsPerPage, $categoryId));
    }

    /**
     * Fetches all published post bags
     * 
     * @return array
     */
    public function fetchAllPublished()
    {
        return $this->prepareResults($this->postMapper->fetchAllPublished());
    }

    /**
     * Prepares raw input data before sending to the mapper
     * 
     * @param array $input
     * @return array
     */
    private function prepareInput(array $input)
    {
        // Empty slug is always taken from the name
        if (empty($input['slug'])) {
            $input['slug'] = $input['name'];
        }

        // Take empty title from the name
        if (empty($input['title'])) {
            $input['title'] = $input['name'];
        }

        $input['slug'] = $this->webPageManager->sluggify($input['slug']);
        $input['timestamp'] = strtotime($input['date']);

        // Safe type-casting
        $input['web_page_id'] = (int) $input['web_page_id'];

        return $input;
    }

    /**
     * Adds a post
     * 
     * @param array $input Raw input data
     * @return boolean
     */
    public function add(array $input)
    {
        $input = $this->prepareInput($input);
        $input['views'] = '0';

        if ($this->postMapper->insert(ArrayUtils::arrayWithout($input, array('date', 'slug')))) {
            $id = $this->getLastId();

            $this->track('Post "%s" has been added', $input['name']);
            $this->webPageManager->add($id, $input['slug'], 'Blog (Posts)', 'Blog:Post@indexAction', $this->postMapper);
        }

        return true;
    }

    /**
     * Updates a post
     * 
     * @param array $input Raw input data
     * @return boolean
     */
    public function update(array $input)
    {
        $input = $this->prepareInput($input);
        $this->webPageManager->update($input['web_page_id'], $input['slug']);

        $this->track('Post "%s" has been updated', $input['name']);
        return $this->postMapper->update(ArrayUtils::arrayWithout($input, array('date', 'slug')));
    }

    /**
     * Fetches post entity by its associated id
     * 
     * @param string $id
     * @return array
     */
    public function fetchById($id)
    {
        return $this->prepareResult($this->postMapper->fetchById($id));
    }

    /**
     * Removes a web page by post's associated id
     * 
     * @param string $id Post's id
     * @return boolean
     */
    private function removeWebPageById($id)
    {
        $webPageId = $this->postMapper->fetchWebPageIdById($id);
        return $this->webPageManager->deleteById($webPageId);
    }

    /**
     * Removes all by post's associated id
     * 
     * @param string $id Post's id
     * @return boolean
     */
    private function removeAllById($id)
    {
        $this->removeWebPageById($id);
        $this->postMapper->deleteById($id);

        return true;
    }

    /**
     * Removes a post by its associated id
     * 
     * @param string $id Post's id
     * @return boolean
     */
    public function deleteById($id)
    {
        $name = Filter::escape($this->postMapper->fetchNameById($id));

        if ($this->removeAllById($id)) {
            $this->track('Post "%s" has been removed', $name);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Removes posts by their associated ids
     * 
     * @param array $ids
     * @return boolean
     */
    public function deleteByIds(array $ids)
    {
        foreach ($ids as $id) {
            if (!$this->removeAllById($id)) {
                return false;
            }
        }

        $this->track('Batch removal of %s posts', count($ids));
        return true;
    }
}
