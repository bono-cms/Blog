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
            'categories' => $this->getCategoryManager()->getCategoriesTree(),
            // If you don't ability to attach similar posts, you can comment 'posts' key to reduce DB queries
            'posts' => $this->getCategoryManager()->fetchAllWithPosts(),
            'post' => $post,
            'new' => is_object($post),
        ));
    }

    /**
     * Renders empty form
     * 
     * @return string
     */
    public function addAction()
    {
        $post = new VirtualEntity();
        $post->setDate(date('m/d/Y', time()))
             ->setPublished(true)
             ->setComments(true)
             ->setSeo(true);

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
            return $this->createForm($post, 'Edit the post');
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
        $service = $this->getModuleService('postManager');

        // Batch removal
        if ($this->request->hasPost('toDelete')) {
            $ids = array_keys($this->request->getPost('toDelete'));

            $service->deleteByIds($ids);
            $this->flashBag->set('success', 'Selected elements have been removed successfully');

        } else {
            $this->flashBag->set('warning', 'You should select at least one element to remove');
        }

        // Single removal
        if (!empty($id)) {
            $service->deleteById($id);
            $this->flashBag->set('success', 'Selected element has been removed successfully');
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
            $service = $this->getModuleService('postManager');

            // Update
            if (!empty($input['id'])) {
                if ($service->update($this->request->getPost())) {
                    $this->flashBag->set('success', 'The element has been updated successfully');
                    return '1';
                }

            } else {
                // Create
                if ($service->add($this->request->getPost())) {
                    $this->flashBag->set('success', 'The element has been created successfully');
                    return $service->getLastId();
                }
            }

        } else {
            return $formValidator->getErrors();
        }
    }
}
