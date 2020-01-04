
/* Blog categories */
DROP TABLE IF EXISTS `bono_module_blog_categories`;
CREATE TABLE `bono_module_blog_categories` (
    `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `parent_id` INT NOT NULL COMMENT 'Parent category id',
	`seo` varchar(1) NOT NULL COMMENT 'Whether SEO enabled or not',
	`order` INT NOT NULL COMMENT 'Sort order',
    `cover` varchar(50) NOT NULL COMMENT 'Image file basename'
) ENGINE = InnoDB DEFAULT CHARSET = UTF8;

DROP TABLE IF EXISTS `bono_module_blog_categories_translations`;
CREATE TABLE `bono_module_blog_categories_translations` (
    `id` INT NOT NULL,
	`lang_id` INT NOT NULL COMMENT 'Language id this category belongs to',
	`web_page_id` INT NOT NULL COMMENT 'Web page id this category belongs to',
	`title` varchar(255) NOT NULL COMMENT 'Title of the category',
	`name` varchar(255) NOT NULL COMMENT 'Name of the category',
	`description` LONGTEXT NOT NULL COMMENT 'Description of the category',
	`keywords` TEXT NOT NULL COMMENT 'Meta keywords for SEO',
	`meta_description` TEXT NOT NULL COMMENT 'Meta description for SEO',

    FOREIGN KEY (id) REFERENCES bono_module_blog_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (lang_id) REFERENCES bono_module_cms_languages(id) ON DELETE CASCADE,
    FOREIGN KEY (web_page_id) REFERENCES bono_module_cms_webpages(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = UTF8;

/* Blog posts */
DROP TABLE IF EXISTS `bono_module_blog_posts`;
CREATE TABLE `bono_module_blog_posts` (
    `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `category_id` INT NOT NULL,
    `timestamp` INT(10) NOT NULL,
    `published` varchar(1) NOT NULL,
    `comments` varchar(1) NOT NULL,
    `seo` varchar(1) NOT NULL,
    `cover` varchar(255),
    `views` INT NOT NULL,

    FOREIGN KEY (category_id) REFERENCES bono_module_blog_categories(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = UTF8;

DROP TABLE IF EXISTS `bono_module_blog_posts_translations`;
CREATE TABLE `bono_module_blog_posts_translations` (
    `id` INT NOT NULL,
    `lang_id` INT NOT NULL,
    `web_page_id` INT NOT NULL,
    `title` varchar(254) NOT NULL,
    `name` varchar(254) NOT NULL,
    `introduction` LONGTEXT NOT NULL,
    `full` LONGTEXT NOT NULL,
    `keywords` TEXT NOT NULL,
    `meta_description` TEXT NOT NULL,

    FOREIGN KEY (id) REFERENCES bono_module_blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (lang_id) REFERENCES bono_module_cms_languages(id) ON DELETE CASCADE,
    FOREIGN KEY (web_page_id) REFERENCES bono_module_cms_webpages(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = UTF8;

/* Post attachments */
DROP TABLE IF EXISTS `bono_module_blog_posts_attached`;
CREATE TABLE `bono_module_blog_posts_attached` (
    `master_id` INT NOT NULL COMMENT 'Primary Post ID',
    `slave_id` INT NOT NULL COMMENT 'Attached post ID',

    FOREIGN KEY (`master_id`) REFERENCES bono_module_blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (`slave_id`) REFERENCES bono_module_blog_posts(id) ON DELETE CASCADE
    
) ENGINE = InnoDB DEFAULT CHARSET = UTF8;

/* Post gallery */
DROP TABLE IF EXISTS `bono_module_blog_posts_gallery`;
CREATE TABLE `bono_module_blog_posts_gallery` (
    `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `post_id` INT NOT NULL COMMENT 'Attached post ID',
    `order` INT NOT NULL COMMENT 'Sorting order',
    `image` varchar(255) NOT NULL COMMENT 'Image file',

    FOREIGN KEY (post_id) REFERENCES bono_module_blog_posts(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = UTF8;
