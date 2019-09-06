<?php

/**
 * This file is part of the Bono CMS
 * 
 * Copyright (c) No Global State Lab
 * 
 * For the full copyright and license information, please view
 * the license file that was distributed with this source code.
 */

namespace Blog\Controller\Admin;

use Krystal\Validate\Pattern;
use Krystal\Stdlib\VirtualEntity;

final class Post extends AbstractAdminController
{
    /**
     * Creates a form
     * 
     * @param \Krystal\Stdlib\VirtualEntity|array $post
     * @param string $title
     * @return string
     */
    private function createForm($post, $title)
    {
        // Load view plugins
        $this->view->getPluginBag()->appendScript('@Blog/admin/post.form.js')
                                   ->load(array($this->getWysiwygPluginName(), 'datepicker', 'chosen'));

        // Append breadcrumbs
        $this->view->getBreadcrumbBag()->addOne('Blog', 'Blog:Admin:Browser@indexAction')
                                       ->addOne($title);

        return $this->view->render('post.form', array(
            'categories' => $this->getCategoryManager()->getCategoriesTree(false),
            // If you don't ability to attach similar posts, you can comment 'posts' key to reduce DB queries
            'posts' => $this->getCategoryManager()->fetchAllWithPosts(),
            'post' => $post
        ));
    }

    /**
     * Renders empty form
     * 
     * @return string
     */
    public function addAction()
    {
        // Load preview plugin
        $this->view->getPluginBag()
                   ->load('preview');

        // CMS configuration object
        $config = $this->getService('Cms', 'configManager')->getEntity();

        $post = new VirtualEntity();
        $post->setDate(date('m/d/Y', time()))
             ->setPublished(true)
             ->setComments(true)
             ->setSeo(true)
             ->setChangeFreq($config->getSitemapFrequency())
             ->setPriority($config->getSitemapPriority());

        return $this->createForm($post, 'Add a post');
    }

    /**
     * Renders edit form
     * 
     * @param string $id
     * @return string
     */
    public function editAction($id)
    {
        $post = $this->getPostManager()->fetchById($id, false, true);

        if ($post !== false) {
            $name = $this->getCurrentProperty($post, 'name');
            return $this->createForm($post, $this->translator->translate('Edit the post "%s"', $name));
        } else {
            return false;
        }
    }

    /**
     * Saves changes from a table
     * 
     * @return string
     */
    public function tweakAction()
    {
        if ($this->request->isPost()) {
            // Grab a service now
            $postManager = $this->getPostManager();
            $postManager->updateSettings($this->request->getPost());

            $this->flashBag->set('success', 'Post settings have been updated');
            return '1';
        }
    }

    /**
     * Removes selected post by its associated id
     * 
     * @param string $id
     * @return string
     */
    public function deleteAction($id)
    {
        $historyService = $this->getService('Cms', 'historyManager');
        $service = $this->getModuleService('postManager');

        // Batch removal
        if ($this->request->hasPost('batch')) {
            $ids = array_keys($this->request->getPost('batch'));

            $service->deleteByIds($ids);
            $this->flashBag->set('success', 'Selected elements have been removed successfully');

            // Save in the history
            $historyService->write('Blog', 'Batch removal of %s posts', count($ids));

        } else {
            $this->flashBag->set('warning', 'You should select at least one element to remove');
        }

        // Single removal
        if (!empty($id)) {
            $post = $this->getPostManager()->fetchById($id, false, false);

            $service->deleteById($id);
            $this->flashBag->set('success', 'Selected element has been removed successfully');

            // Save in the history
            $historyService->write('Blog', 'Post "%s" has been removed', $post->getName());
        }

        return '1';
    }

    /**
     * Persists a post
     * 
     * @return string
     */
    public function saveAction()
    {
        $input = $this->request->getPost('post');

        $formValidator = $this->createValidator(array(
            'input' => array(
                'source' => $input,
                'definition' => array(
                    'name' => new Pattern\Name(),
                    'introduction' => new Pattern\IntroText(),
                    'full' => new Pattern\FullText(),
                    'date' => new Pattern\DateFormat('m/d/Y')
                )
            )
        ));

        if (1) {
            // Current page name
            $name = $this->getCurrentProperty($this->request->getPost('translation'), 'name');

            $service = $this->getModuleService('postManager');
            $historyService = $this->getService('Cms', 'historyManager');

            // Update
            if (!empty($input['id'])) {
                if ($service->update($this->request->getAll())) {
                    $this->flashBag->set('success', 'The element has been updated successfully');

                    $historyService->write('Blog', 'Post "%s" has been updated', $name);
                    return '1';
                }

            } else {
                // Create
                if ($service->add($this->request->getAll())) {
                    $this->flashBag->set('success', 'The element has been created successfully');

                    $historyService->write('Blog', 'Post "%s" has been added', $name);
                    return $service->getLastId();
                }
            }

        } else {
            return $formValidator->getErrors();
        }
    }
}
