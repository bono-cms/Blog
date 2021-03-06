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

use Cms\Controller\Admin\AbstractController;

abstract class AbstractAdminController extends AbstractController
{
    /**
     * Returns configuration manager
     * 
     * @return \Blog\Service\ConfigManager
     */
    final protected function getConfigManager()
    {
        return $this->getModuleService('configManager');
    }

    /**
     * Returns PostManager
     * 
     * @return \Blog\Service\PostManager
     */
    final protected function getPostManager()
    {
        return $this->getModuleService('postManager');
    }

    /**
     * Returns CategoryManager
     * 
     * @return \Blog\Service\CategoryManager
     */
    final protected function getCategoryManager()
    {
        return $this->getModuleService('categoryManager');
    }

    /**
     * Returns task manager
     * 
     * @return \Blog\Service\TaskManager
     */
    final protected function getTaskManager()
    {
        return $this->getModuleService('taskManager');
    }
}
