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
use Krystal\Security\Filter;
use Krystal\Stdlib\ArrayUtils;
use Krystal\Tree\AdjacencyList\BreadcrumbBuilder;
use Krystal\Image\Tool\ImageManagerInterface;

final class PostManager extends AbstractManager implements PostManagerInterface
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
     * Post image manager
     * 
     * @var \Krystal\Image\ImageManagerInterface
     */
    private $imageManager;

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
     * @param \Krystal\Image\Tool\ImageManagerInterface $imageManager
     * @param \Cms\Service\HistoryManagerInterface $historyManager
     * @return void
     */
    public function __construct(
        PostMapperInterface $postMapper, 
        CategoryMapperInterface $categoryMapper,
        WebPageManagerInterface $webPageManager,
        ImageManagerInterface $imageManager,
        HistoryManagerInterface $historyManager
    ){
        $this->postMapper = $postMapper;
        $this->categoryMapper = $categoryMapper;
        $this->webPageManager = $webPageManager;
        $this->imageManager = $imageManager;
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
     * Returns breadcrumb collection
     * 
     * @param \Blog\Service\PostEntity $post
     * @return array
     */
    public function getBreadcrumbs(PostEntity $post)
    {
        $builder = new BreadcrumbBuilder($this->categoryMapper->fetchBcData(), $post->getCategoryId());
        $wm = $this->webPageManager;

        // Previous breadcrumb
        $breadcrumbs = $builder->makeAll(function($row) use ($wm) {
            return array(
                'name' => $row['name'],
                'link' => $wm->surround($row['slug'], $row['lang_id'])
            );
        });

        // Merge previous ones with last one
        return array_merge($breadcrumbs, array(
            array(
                'name' => $post->getName(),
                'link' => '#'
            )
        ));
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
     * Update settings
     * 
     * @param array $settings
     * @return boolean
     */
    public function updateSettings(array $settings)
    {
        return $this->postMapper->updateSettings($settings);
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
    protected function toEntity(array $post, $full = true)
    {
        $imageBag = clone $this->imageManager->getImageBag();
        $imageBag->setId((int) $post['id'])
                 ->setCover($post['cover']);

        $entity = new PostEntity(false);
        $entity->setId($post['id'], PostEntity::FILTER_INT)
            ->setImageBag($imageBag)
            ->setLangId($post['lang_id'], PostEntity::FILTER_INT)
            ->setWebPageId($post['web_page_id'], PostEntity::FILTER_INT)
            ->setName($post['name'], PostEntity::FILTER_HTML)
            ->setCategoryName($post['category_name'], PostEntity::FILTER_HTML)
            ->setTimestamp($post['timestamp'], PostEntity::FILTER_INT)
            ->setPublished($post['published'], PostEntity::FILTER_BOOL)
            ->setComments($post['comments'], PostEntity::FILTER_BOOL)
            ->setSeo($post['seo'], PostEntity::FILTER_BOOL)
            ->setCover($post['cover'])
            ->setSlug($post['slug'])
            ->setUrl($this->webPageManager->surround($entity->getSlug(), $entity->getLangId()));

        if ($full === true) {
            // Attached ones if available
            if (isset($post[PostMapperInterface::PARAM_COLUMN_ATTACHED])) {
                $entity->setAttachedIds(ArrayUtils::arrayList($post[PostMapperInterface::PARAM_COLUMN_ATTACHED], 'id', 'id'));
            }

            $entity->setTitle($post['title'], PostEntity::FILTER_HTML)
                   ->setKeywords($post['keywords'], PostEntity::FILTER_HTML)
                   ->setMetaDescription($post['meta_description'], PostEntity::FILTER_HTML)
                   ->setDate(date($this->getTimeFormat(), $entity->getTimestamp()))
                   ->setCategoryId($post['category_id'], PostEntity::FILTER_INT)
                   ->setViewsCount($post['views'], PostEntity::FILTER_INT)
                   ->setPermanentUrl('/module/blog/post/'.$entity->getId())
                   ->setIntroduction($post['introduction'], PostEntity::FILTER_SAFE_TAGS)
                   ->setFull($post['full'], PostEntity::FILTER_SAFE_TAGS);
        }

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
        return $this->prepareResults($this->postMapper->fetchMostlyViewed($limit), false);
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
     * Fetch recent blog post entities
     * 
     * @param integer $limit Limit of rows to be returned
     * @param string $categoryId Optional category ID filter
     * @return array
     */
    public function fetchRecent($limit, $categoryId = null)
    {
        return $this->prepareResults($this->postMapper->fetchRecent($limit, $categoryId), false);
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
        return $this->prepareResults($this->postMapper->fetchAllByPage($published, $page, $itemsPerPage, $categoryId), false);
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
     * Returns a collection of switching URLs
     * 
     * @param string $id Post ID
     * @return array
     */
    public function getSwitchUrls($id)
    {
        return $this->postMapper->createSwitchUrls($id, 'Blog (Posts)', 'Blog:Post@indexAction');
    }

    /**
     * Saves a page
     * 
     * @param array $input
     * @return boolean
     */
    private function savePage(array $input)
    {
        $post =& $input['data']['post'];
        $translations =& $input['data']['translation'];

        // Convert a date to UNIX-timestamp
        $post['timestamp'] = (int) strtotime($post['date']);

        // No views by defaults
        if (!isset($post['views'])) {
            $post['views'] = 0;
        }

        // Remove extra keys
        $post = ArrayUtils::arrayWithout($post, array('date', 'slug', 'remove_cover'));

        return $this->postMapper->savePage('Blog (Posts)', 'Blog:Post@indexAction', $post, $translations);
    }

    /**
     * Adds a post
     * 
     * @param array $input Raw input data
     * @return boolean
     */
    public function add(array $input)
    {
        // Form data reference
        $post =& $input['data']['post'];

        // If there's a file, then it needs to uploaded as a cover
        if (!empty($input['files']['file'])) {
            $file =& $input['files']['file'];
            $this->filterFileInput($file);

            // Override empty cover's value now
            $post['cover'] = $file[0]->getName();
        } else {
            $post['cover'] = '';
        }

        $this->savePage($input);

        // Do upload if has a cover
        if (!empty($input['files']['file'])) {
            $this->imageManager->upload($this->getLastId(), $input['files']['file']);
        }

        #$this->track('Post "%s" has been added', $input['name']);
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
        $post =& $input['data']['post'];

        // Allow to remove a cover, only it case it exists and checkbox was checked
        if (isset($post['remove_cover'])) {
            // Remove a cover, but not a dir itself
            $this->imageManager->delete($post['id']);
            $post['cover'] = '';
        } else {
            if (!empty($input['files']['file'])) {
                $file =& $input['files']['file'];
                // If we have a previous cover's image, then we need to remove it
                if (!empty($post['cover'])) {
                    if (!$this->imageManager->delete($post['id'], $post['cover'])) {
                        // If failed, then exit this method immediately
                        return false;
                    }
                }

                // And now upload a new one
                $this->filterFileInput($file);
                $post['cover'] = $file[0]->getName();

                $this->imageManager->upload($post['id'], $file);
            }
        }

        #$this->track('Category "%s" has been updated', $category['name']);
        return $this->savePage($input);
    }

    /**
     * Fetches post entity by its associated id
     * 
     * @param string $id Post ID
     * @param boolean $withAttached Whether to grab attached entities
     * @param boolean $withTranslations Whether to include translations as well
     * @return \News\Service\PostEntity|boolean|array
     */
    public function fetchById($id, $withAttached, $withTranslations)
    {
        if ($withTranslations) {
            return $this->prepareResults($this->postMapper->fetchById($id, true));

        } else {
            $entity = $this->prepareResult($this->postMapper->fetchById($id));

            if ($entity !== false) {
                if ($withAttached === true) {
                    $rows = $this->postMapper->fetchByIds($entity->getAttachedIds());
                    $entity->setAttachedPosts($this->prepareResults($rows, false));
                }

                return $entity;
            } else {
                return false;
            }
        }
    }

    /**
     * Removes a post completely
     * 
     * @param integer $id Post ID
     * @return boolean
     */
    private function removePost($id)
    {
        // Remove a post with its translations
        $this->postMapper->deletePage($id);

        // Remove a cover if present as well
        $this->imageManager->delete($id);

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
        #$name = Filter::escape($this->postMapper->fetchNameById($id));

        if ($this->removePost($id)) {
            #$this->track('Post "%s" has been removed', $name);
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
            if (!$this->removePost($id)) {
                return false;
            }
        }

        #$this->track('Batch removal of %s posts', count($ids));
        return true;
    }
}
