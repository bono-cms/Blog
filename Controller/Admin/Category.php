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

use Blog\Controller\Admin\AbstractAdminController;
use Krystal\Validate\Pattern;
use Krystal\Stdlib\VirtualEntity;

final class Category extends AbstractAdminController
{
    /**
     * Returns album tree with an empty prompt
     * 
     * @return array
     */
    private function createCategoriesTree()
    {
        $text = sprintf('— %s —', $this->translator->translate('None'));
        return $this->getCategoryManager()->getPromtWithCategoriesTree($text);
    }

    /**
     * Creates a form
     * 
     * @param \Krystal\Stdlib\VirtualEntity $category
     * @param string $title
     * @return string
     */
    private function createForm(VirtualEntity $category, $title)
    {
        // Load view plugins
        $this->loadMenuWidget();
        $this->view->getPluginBag()->load($this->getWysiwygPluginName());

        // Append breadcrumbs
        $this->view->getBreadcrumbBag()->addOne('Blog', 'Blog:Admin:Browser@indexAction')
                                       ->addOne($title);

        return $this->view->render('category.form', array(
            'category' => $category,
            'categories' => $this->createCategoriesTree()
        ));
    }

    /**
     * Renders empty form
     * 
     * @return string
     */
    public function addAction()
    {
        $this->view->getPluginBag()->load('preview');

        $category = new VirtualEntity();
        $category->setSeo(true);

        return $this->createForm($category, 'Add a category');
    }

    /**
     * Renders edit form
     * 
     * @param string $id
     * @return string
     */
    public function editAction($id)
    {
        $category = $this->getCategoryManager()->fetchById($id);

        if ($category !== false) {
            return $this->createForm($category, 'Edit the category');
        } else {
            return false;
        }
    }

    /**
     * Removes a category by its associated id
     * 
     * @param string $id
     * @return string
     */
    public function deleteAction($id)
    {
        $service = $this->getModuleService('categoryManager');
        $service->deleteById($id);

        $this->flashBag->set('success', 'Selected element has been removed successfully');
        return '1';
    }

    /**
     * Persists a category
     * 
     * @return string
     */
    public function saveAction()
    {
        $input = $this->request->getAll();
        $data = $input['data']['category'];

        $formValidator = $this->createValidator(array(
            'input' => array(
                'source' => $data,
                'definition' => array(
                    'name' => new Pattern\Name()
                )
            )
        ));

        if ($formValidator->isValid()) {
            $service = $this->getModuleService('categoryManager');

            // Update
            if (!empty($data['id'])) {
                if ($service->update($input)) {
                    $this->flashBag->set('success', 'The element has been updated successfully');
                    return '1';
                }

            } else {
                // Create
                if ($service->add($input)) {
                    $this->flashBag->set('success', 'The element has been created successfully');
                    return $service->getLastId();
                }
            }

        } else {
            return $formValidator->getErrors();
        }
    }
}
