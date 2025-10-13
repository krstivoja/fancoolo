# Understanding Blocks in FanCoolo

## What Are Custom Blocks in Gutenberg?

Custom blocks are reusable building pieces that you can add to your WordPress pages and posts through the Gutenberg editor. Think of them as specialized content modules - like a button, a heading, a testimonial slider, or any custom design element you might need. Instead of writing code each time you want to add these elements, you simply insert a block from the editor's block menu.

## How FanCoolo Blocks Work

In FanCoolo, when you create a new block item, you're actually creating a completely new block type that will appear in your Gutenberg editor's block library. Each block you create becomes its own independent block that you and your content editors can use throughout your website.

This is different from creating a single instance of content - you're creating a reusable block type that can be inserted multiple times across different pages and posts.

## What Makes Up a Block?

Each FanCoolo block consists of several components that work together to give you complete control over how your block looks and behaves:

### Content (PHP)

This is the heart of your block - the actual content that visitors see on your website. You write this in PHP (or plain HTML), which gives you flexibility to display dynamic content, pull in data from your database, include symbols (reusable components), and create complex layouts. This is what renders on the frontend when someone visits your page.

#### Writing Your Block Content

You have complete flexibility in how you write your block's content:

**Plain HTML**: You can write standard HTML markup if you don't need any dynamic behavior:

```html
<div class="my-block">
    <h2>Welcome to My Block</h2>
    <p>This is a simple static block.</p>
</div>
```

**PHP Code**: Mix PHP with HTML to create dynamic content:

```php
<div class="my-block">
    <h2><?php echo esc_html($attributes['heading']); ?></h2>
    <p><?php echo esc_html($attributes['description']); ?></p>
</div>
```

**Including Symbols**: Reference reusable symbols (like icons, logos, or design elements) directly in your content:

```php
<div class="card">
    <?php echo get_symbol('company-logo'); ?>
    <h3><?php echo esc_html($attributes['title']); ?></h3>
</div>
```

**Using Block Attributes**: Access any custom fields you've defined in the Attributes Manager:

```php
<div class="hero" style="background-color: <?php echo esc_attr($attributes['backgroundColor']); ?>">
    <h1><?php echo esc_html($attributes['heading']); ?></h1>
    <?php if ($attributes['showButton']) : ?>
        <a href="<?php echo esc_url($attributes['buttonUrl']); ?>" class="btn">
            <?php echo esc_html($attributes['buttonText']); ?>
        </a>
    <?php endif; ?>
</div>
```

#### Built-in Error Prevention

FanCoolo includes intelligent error prevention to protect your site from broken code:

**PHP Syntax Validation**: Before generating your block file, FanCoolo validates that your PHP code is syntactically correct. If there are any PHP errors, you'll see a clear error message explaining what's wrong and where the problem is located.

**Safe File Generation**: Only valid PHP code will be generated as a block file. If your code contains errors, the file won't be created until you fix them. This prevents broken blocks from appearing in your Gutenberg editor.

**Error Messages**: When validation fails, you'll receive helpful error messages that:
- Point to the exact line where the error occurred
- Explain what type of error was detected
- Help you quickly identify and fix the problem

This safety system ensures that your blocks are always working correctly and won't cause PHP errors on your live site.

### Style (CSS)

The Style controls how your block looks, both in the Gutenberg editor and on your live website. Any styling you add here applies universally to your block, ensuring consistency between what you see while editing and what your visitors see on the frontend.

### Editor Style (CSS)

Sometimes you need your block to look different in the editor than it does on the frontend. That's where Editor Style comes in. This CSS only affects how the block appears in the Gutenberg editor.

Why would you need this? Here are some common scenarios:

- You might want to simplify the editing experience by removing complex animations that would make it difficult to select and edit content
- You might need to adjust spacing or layout to make it easier to see where to click and type
- You might want to highlight editable areas more clearly than they appear on the live site

### View (JavaScript)

The View handles any interactive behavior your block needs on the frontend. This is where you add things like:

- Animations that trigger when users scroll
- Interactive elements like tabs or accordions
- Dynamic content that changes based on user actions
- Any JavaScript functionality that makes your block come alive

This is the equivalent of WordPress's native `view.js` file for blocks.

### Attributes Manager

Think of attributes as the customizable settings for your block. This is where you register the fields that appear in the block's settings panel in the Gutenberg editor. For example:

- A text field for a heading
- A color picker for background color
- A toggle to enable or disable a feature
- An image uploader
- Dropdown menus for different style variations

When a content editor inserts your block, they'll use these attribute fields to customize the block for their specific needs.

### Block Configuration (block.json)

Behind the scenes, FanCoolo generates a `block.json` file for each block you create, just like WordPress native blocks. This file contains all the technical configuration that tells WordPress:

- What your block is called
- What category it belongs to
- Which scripts and styles to load
- What attributes are available
- How the block should be registered

You don't need to manually create or edit this file - FanCoolo generates it automatically based on your block settings.

## Why FanCoolo's Approach is Powerful

FanCoolo generates true Gutenberg-native blocks using server-side rendering. This means:

**Performance Optimized**: Your blocks load fast because they're rendered on the server, reducing the amount of processing needed in the user's browser.

**Symbol Integration**: You can easily include reusable symbols (like icons, logos, or repeated design elements) directly in your block's PHP content, making it simple to maintain consistent design elements across your site.

**Native WordPress Experience**: Because these are real Gutenberg blocks, they work seamlessly with all WordPress features, themes, and other plugins. Your content editors get the familiar WordPress editing experience they're used to.

## Getting Started

When you're ready to create a block:

1. Add a new Block item in FanCoolo
2. Write your PHP content to define what displays
3. Add your styling to make it look beautiful
4. Register any attributes you need for customization
5. Add view JavaScript if you need interactivity
6. Save and your block is ready to use in the Gutenberg editor

Each block you create expands your design system, giving you and your team more tools to build engaging, consistent content across your WordPress site.
