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
        return $this->invokeRemoval('postManager');
    }

    /**
     * Persists a post
     * 
     * @return string
     */
    public function saveAction()
    {
        $input = $this->request->getPost('post');

        return $this->invokeSave('postManager', $input['id'], $input, array(
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
    }
}
