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
     * @param \Krystal\Stdlib\VirtualEntity $post
     * @param string $title
     * @return string
     */
    private function createForm(VirtualEntity $post, $title)
    {
        // Load view plugins
        $this->view->getPluginBag()->appendScript('@Blog/admin/post.form.js')
                                   ->load(array($this->getWysiwygPluginName(), 'datepicker'));

        // Append breadcrumbs
        $this->view->getBreadcrumbBag()->addOne('Blog', 'Blog:Admin:Browser@indexAction')
                                       ->addOne($title);

        return $this->view->render('post.form', array(
            'categories' => $this->getCategoryManager()->fetchList(),
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
        $post = $this->getPostManager()->fetchById($id);

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
        if ($this->request->hasPost('published', 'seo', 'comments')) {
            // Collect data from the request
            $published = $this->request->getPost('published');
            $seo = $this->request->getPost('seo');
            $comments = $this->request->getPost('comments');

            // Grab a service now
            $postManager = $this->getPostManager();

            // Do the bulk actions
            $postManager->updateSeo($seo);
            $postManager->updatePublished($published);
            $postManager->updateComments($comments);

            $this->flashBag->set('success', 'Post settings have been updated');
            return '1';
        }
    }

    /**
     * Removes selected post by its associated id
     * 
     * @return string
     */
    public function deleteAction()
    {
        if ($this->request->hasPost('toDelete')) {
            $ids = array_keys($this->request->getPost('toDelete'));

            // Do remove now
            $this->getPostManager()->removeByIds($ids);
            $this->flashBag->set('success', 'Selected posts have been removed successfully');

        } else {
            $this->flashBag->set('warning', 'You should select at least one blog post to remove');
        }

        if ($this->request->hasPost('id')) {
            $id = $this->request->getPost('id');

            $this->getPostManager()->removeById($id);
            $this->flashBag->set('success', 'Selected post has been removed successfully');
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

        $formValidator = $this->validatorFactory->build(array(
            'input' => array(
                'source' => $input,
                'definition' => array(
                    'title' => new Pattern\Title(),
                    'introduction' => new Pattern\IntroText(),
                    'full' => new Pattern\FullText(),
                    'date' => new Pattern\DateFormat('m/d/Y')
                )
            )
        ));

        if ($formValidator->isValid()) {
            $postManager = $this->getPostManager();

            if ($input['id']) {
                if ($postManager->update($this->request->getPost('post'))) {
                    $this->flashBag->set('success', 'A post has been updated successfully');
                    return '1';
                }

            } else {
                if ($postManager->add($this->request->getPost('post'))) {
                    $this->flashBag->set('success', 'A post has been created successfully');
                    return $postManager->getLastId();
                }
            }

        } else {
            return $formValidator->getErrors();
        }
    }
}
