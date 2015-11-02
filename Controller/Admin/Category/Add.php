<?php

/**
 * This file is part of the Bono CMS
 * 
 * Copyright (c) No Global State Lab
 * 
 * For the full copyright and license information, please view
 * the license file that was distributed with this source code.
 */

namespace Blog\Controller\Admin\Category;

use Krystal\Stdlib\VirtualEntity;

final class Add extends AbstractCategory
{
    /**
     * Shows adding form
     * 
     * @return string
     */
    public function indexAction()
    {
        $this->loadSharedPlugins();
        $this->loadBreadcrumbs('Add a category');

        return $this->view->render($this->getTemplatePath(), array(
            'title' => 'Add a category',
            'category' => new VirtualEntity()
        ));
    }

    /**
     * Saves a category
     * 
     * @return string
     */
    public function addAction()
    {
        $formValidator = $this->getValidator($this->request->getPost('category'));

        if ($formValidator->isValid()) {
            $categoryManager = $this->getCategoryManager();

            if ($categoryManager->add($this->request->getPost())) {
                $this->flashBag->set('success', 'Category has been created successfully');
                return $categoryManager->getLastId();
            }

        } else {
            return $formValidator->getErrors();
        }
    }
}
