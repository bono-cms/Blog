<?php

/**
 * This file is part of the Bono CMS
 * 
 * For the full copyright and license information, please view
 * the license file that was distributed with this source code.
 */

namespace Blog\Controller;

final class Post extends AbstractBlogController
{
    /**
     * Shows a post by its associated ID
     * 
     * @param string $id Post's ID
     * @return string
     */
    public function indexAction($id)
    {
        $post = $this->getPostManager()->fetchById($id, true, false);

        // If $post isn't false, then $id is valid and $post itself is an instance of entity class
        if ($post !== false) {
            // Set image gallery
            $post->setGallery($this->getModuleService('postGalleryManager')->fetchAllByPostId($id));

            $this->loadSitePlugins();
            $this->view->getBreadcrumbBag()
                       ->add($this->getPostManager()->getBreadcrumbs($post));

            $response = $this->view->render('blog-post', [
                'page' => $post,
                'post' => $post,
                'languages' => $this->getPostManager()->getSwitchUrls($id)
            ]);

            $this->getPostManager()->incrementViewCount($id);
            return $response;

        } else {
            // Returning false triggers 404 error automatically
            return false;
        }
    }
}