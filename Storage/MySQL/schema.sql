
DROP TABLE IF EXISTS `bono_module_blog_categories`;
CREATE TABLE `bono_module_blog_categories` (
    
    `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `parent_id` INT NOT NULL COMMENT 'Parent category id',
	`lang_id` INT NOT NULL COMMENT 'Language id this category belongs to',
	`web_page_id` INT NOT NULL COMMENT 'Web page id this category belongs to',
	`title` varchar(255) NOT NULL COMMENT 'Title of the category',
	`name` varchar(255) NOT NULL COMMENT 'Name of the category',
	`description` LONGTEXT NOT NULL COMMENT 'Description of the category',
	`seo` varchar(1) NOT NULL COMMENT 'Whether SEO enabled or not',
	`order` INT NOT NULL COMMENT 'Sort order',
	`keywords` TEXT NOT NULL COMMENT 'Meta keywords for SEO',
	`meta_description` TEXT NOT NULL COMMENT 'Meta description for SEO',
    `cover` varchar(50) NOT NULL COMMENT 'Image file basename'
	
) DEFAULT CHARSET = UTF8;


DROP TABLE IF EXISTS `bono_module_blog_posts`;
CREATE TABLE `bono_module_blog_posts` (
	
	`id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`lang_id` INT NOT NULL,
	`web_page_id` INT NOT NULL,
	`category_id` INT NOT NULL,
	`title` varchar(254) NOT NULL,
	`name` varchar(254) NOT NULL,
	`introduction` LONGTEXT NOT NULL,
	`full` LONGTEXT NOT NULL,
	`timestamp` INT(10) NOT NULL,
	`published` varchar(1) NOT NULL,
	`comments` varchar(1) NOT NULL,
	`seo` varchar(1) NOT NULL,
	`keywords` TEXT NOT NULL,
	`meta_description` TEXT NOT NULL,
	`views` INT NOT NULL

) DEFAULT CHARSET = UTF8;

DROP TABLE IF EXISTS `bono_module_blog_posts_attached`;
CREATE TABLE `bono_module_blog_posts_attached` (
    `master_id` INT NOT NULL COMMENT 'Post ID',
    `slave_id` INT NOT NULL COMMENT 'Attached post ID'
) DEFAULT CHARSET = UTF8;
