Blog module
==========
The Blog module allows you to easily manage and display blog posts on your website. It provides features for creating, editing, and organizing posts by categories. This module also supports templates for displaying posts, managing metadata, and customizing layouts, making it a flexible solution for adding a blog to any website.

## Available methods

    // Returns the name of the post.
    $post->getName(); 
    
    // Returns the introduction text of the post.
    $post->getIntroduction(); 
    
    // Returns the full text of the post.
    $post->getFull(); 
    
    // Returns the timestamp of the post's creation date.
    $post->getTimestamp(); 
    
    // Returns the URL of the post.
    $post->getUrl(); 
    
    // Returns view count
    $post->getViewsCount();
    
    // Returns TRUE if post has a cover
    $post->hasCover();

    // Return path to uploaded cover image
    $post->getImageUrl('dimension'); 




## Templates

### Category template


The category template should be named `blog-category.phtml` and stored in the current theme's directory.

In this template, you'll automatically have access to an array of post entities, called `$posts`:

    <?php if (!empty($posts)): ?>
    <div class="row">
	    <?php foreach ($posts as $post): ?>
	    <div class="col-lg-4">
		    <h2 class="mb-3"><?p= $post->getName(); ?></h2>
		    <div><?= $post->getIntroduction(); ?></div>
		    <a href="<?= $post->getUrl(); ?>">Learn more</a>
	    </div>
	    <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p>Sorry, no posts at the moment</p>
    <?php endif; ?>

### Post template

The post template should be named `blog-post.phtml` and stored in the current theme's directory.

Basic example:

    <article>
    	<h1><?= $post->getName(); ?></h1>
        <?= $post->getFull(); ?>
    </artcle>

Basic example with post gallery:

    <article>
    	<h1><?= $post->getName(); ?></h1>
        <?= $post->getFull(); ?>
    </artcle>

    <?php if ($post->getGallery()): ?>
    <div class="row">
        <?php foreach ($post->getGallery() as $image): ?>
        <div class="col-lg-4">
            <img src="<?= $image->getImageUrl('dimension'); ?>" class="img-fluid">
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

## Recent posts on home page

In case you want to showcase recent posts on your home page, you need to override default controller. To do so, open a file at root foder located at `/config/app.php`

Then find `paramBag` key and override `home_controller` to `Blog:Home@indexAction`

## Comments

Most websites today integrate social plugins (such as Facebook, Disqus, etc.) for comments instead of implementing their own comment systems. As a result, there is currently no built-in comment system.
