# Block Content (render.php) - The Heart of Your Block

## What is Block Content?

The Content file (also known as `render.php` in WordPress) is the heart of your block - the actual markup and logic that determines what visitors see on your website when they view a page containing your block. This is where you define the HTML structure, dynamic content, and data output that gets rendered on the frontend.

## Writing Your Block Content

You have complete flexibility in how you write your block's content. FanCoolo supports both plain HTML and dynamic PHP, giving you the power to create anything from simple static blocks to complex, data-driven components.

### Plain HTML

If your block doesn't need any dynamic behavior, you can write standard HTML markup:

```html
<div class="my-block">
    <h2>Welcome to My Block</h2>
    <p>This is a simple static block with hardcoded content.</p>
    <button class="btn">Click Me</button>
</div>
```

**When to use plain HTML:**
- Simple, unchanging content
- Static layouts
- Fixed text or design elements
- When you don't need customization options

### PHP with HTML

Mix PHP with HTML to create dynamic, customizable content:

```php
<div class="my-block">
    <h2><?php echo esc_html($attributes['heading']); ?></h2>
    <p><?php echo esc_html($attributes['description']); ?></p>

    <?php if ($attributes['showButton']) : ?>
        <a href="<?php echo esc_url($attributes['buttonUrl']); ?>" class="btn">
            <?php echo esc_html($attributes['buttonText']); ?>
        </a>
    <?php endif; ?>
</div>
```

**When to use PHP:**
- Content that changes based on block settings
- Dynamic data from your database
- Conditional logic (show/hide elements)
- Processing or formatting data
- Including reusable components (symbols)

## Using Block Attributes (Custom Fields)

Attributes are the custom fields you define in the Attributes Manager. They allow content editors to customize each instance of your block without touching code.

### Accessing Attributes

All your block's attributes are available through the `$attributes` array:

```php
<div class="hero-section">
    <h1><?php echo esc_html($attributes['heading']); ?></h1>
    <p class="subtitle"><?php echo esc_html($attributes['subtitle']); ?></p>
</div>
```

### Different Attribute Types

**Text Attributes:**
```php
<h2><?php echo esc_html($attributes['title']); ?></h2>
<p><?php echo esc_html($attributes['description']); ?></p>
```

**Boolean Attributes (toggles):**
```php
<?php if ($attributes['showImage']) : ?>
    <img src="<?php echo esc_url($attributes['imageUrl']); ?>" alt="">
<?php endif; ?>
```

**Number Attributes:**
```php
<div class="grid" style="grid-template-columns: repeat(<?php echo intval($attributes['columns']); ?>, 1fr);">
    <!-- grid content -->
</div>
```

**Color Attributes:**
```php
<div class="banner" style="background-color: <?php echo esc_attr($attributes['backgroundColor']); ?>">
    <h2 style="color: <?php echo esc_attr($attributes['textColor']); ?>">
        <?php echo esc_html($attributes['heading']); ?>
    </h2>
</div>
```

**Array Attributes (multiple values):**
```php
<ul class="feature-list">
    <?php foreach ($attributes['features'] as $feature) : ?>
        <li><?php echo esc_html($feature); ?></li>
    <?php endforeach; ?>
</ul>
```

## Including Symbols

Symbols are reusable components like icons, logos, or repeated design elements that you can include in your blocks. This makes it easy to maintain consistent design elements across your site.

You include symbols using a self-closing tag syntax with the symbol name.

### Basic Symbol Usage

```php
<div class="card">
    <CompanyLogo />
    <h3><?php echo esc_html($attributes['title']); ?></h3>
    <p><?php echo esc_html($attributes['description']); ?></p>
</div>
```

### Multiple Symbols

```php
<div class="social-share">
    <h4>Share this post:</h4>
    <FacebookIcon />
    <TwitterIcon />
    <LinkedinIcon />
</div>
```

### Conditional Symbols

```php
<div class="status-indicator">
    <?php if ($attributes['status'] === 'success') : ?>
        <CheckIcon />
    <?php else : ?>
        <WarningIcon />
    <?php endif; ?>
    <span><?php echo esc_html($attributes['message']); ?></span>
</div>
```

### Symbols in Loops

```php
<div class="rating">
    <?php for ($i = 0; $i < intval($attributes['rating']); $i++) : ?>
        <StarIcon />
    <?php endfor; ?>
</div>
```

## Built-in Error Prevention & Validation

FanCoolo includes intelligent error prevention to protect your site from broken code. This safety system ensures that only valid, working code makes it to your live site.

### PHP Syntax Validation

Before generating your block's `render.php` file, FanCoolo automatically validates that your PHP code is syntactically correct. This happens every time you save your block.

**What gets validated:**
- PHP syntax errors (missing semicolons, unclosed brackets, etc.)
- Invalid PHP tags
- Function/variable naming issues
- Parse errors

### Safe File Generation

Only valid PHP code will be generated as a block file. If your code contains errors, the file generation process will be blocked and you'll receive clear error messages.

**Protection you get:**
- Prevents broken blocks from appearing in Gutenberg
- Stops PHP fatal errors from reaching your live site
- Ensures your blocks always work correctly
- Protects your content editors from encountering errors

### Clear Error Messages

When validation fails, you'll receive helpful, actionable error messages that include:

**Line Number**: The exact line where the error occurred
```
Error on line 15: Unexpected token ')'
```

**Error Type**: What kind of error was detected
```
Parse Error: syntax error, unexpected ')', expecting ';'
```

**Context**: Information to help you understand and fix the problem
```
You may have a missing semicolon on the previous line, or an extra closing parenthesis.
```

### Example Error Scenarios

**Missing Semicolon:**
```php
<div>
    <?php echo esc_html($attributes['title']) ?> <!-- Missing semicolon -->
</div>
```
**Error Message:** "Parse error: syntax error, unexpected '?>' on line 2"

**Unclosed PHP Tag:**
```php
<div>
    <?php if ($attributes['show']) :
        echo "Content";
    <!-- Missing endif; -->
</div>
```
**Error Message:** "Parse error: syntax error, unexpected end of file"

**Typo in Function Name:**
```php
<div>
    <?php echo esc_httml($attributes['title']); ?> <!-- Typo: esc_httml -->
</div>
```
**Error Message:** "Call to undefined function esc_httml()"

## Best Practices

### Always Escape Output

WordPress provides escaping functions to protect against XSS attacks. Always use them:

```php
<!-- For plain text -->
<?php echo esc_html($attributes['text']); ?>

<!-- For HTML attributes -->
<div class="<?php echo esc_attr($attributes['className']); ?>">

<!-- For URLs -->
<a href="<?php echo esc_url($attributes['link']); ?>">

<!-- For rich HTML content (strips dangerous tags) -->
<?php echo wp_kses_post($attributes['content']); ?>
```

### Use Conditional Logic Wisely

Structure your conditions to be readable and maintainable:

```php
<!-- Good: Clear and easy to read -->
<?php if ($attributes['showHeader']) : ?>
    <header>
        <h1><?php echo esc_html($attributes['title']); ?></h1>
    </header>
<?php endif; ?>

<!-- Avoid: Nested ternary operators that are hard to understand -->
<?php echo $attributes['show'] ? ($attributes['type'] === 'large' ? '<h1>' : '<h2>') : ''; ?>
```

### Keep It Organized

Structure your content logically:

```php
<div class="my-block">

    <!-- Header Section -->
    <?php if ($attributes['showHeader']) : ?>
        <header class="block-header">
            <h2><?php echo esc_html($attributes['heading']); ?></h2>
        </header>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="block-content">
        <?php echo wp_kses_post($attributes['content']); ?>
    </div>

    <!-- Footer Section -->
    <?php if ($attributes['showFooter']) : ?>
        <footer class="block-footer">
            <p><?php echo esc_html($attributes['footerText']); ?></p>
        </footer>
    <?php endif; ?>

</div>
```

### Comment Complex Logic

Help future you (and others) understand your code:

```php
<?php
// Calculate the number of columns based on screen size setting
$columns = intval($attributes['columns']);
$columnClass = 'grid-cols-' . min($columns, 4); // Max 4 columns

// Show featured image only if URL is provided and feature is enabled
$showImage = $attributes['showFeaturedImage'] && !empty($attributes['imageUrl']);
?>

<div class="<?php echo esc_attr($columnClass); ?>">
    <?php if ($showImage) : ?>
        <img src="<?php echo esc_url($attributes['imageUrl']); ?>" alt="">
    <?php endif; ?>
</div>
```

## Common Patterns

### Looping Through Items

```php
<ul class="item-list">
    <?php foreach ($attributes['items'] as $item) : ?>
        <li class="item">
            <?php echo get_symbol('bullet-icon'); ?>
            <span><?php echo esc_html($item['text']); ?></span>
        </li>
    <?php endforeach; ?>
</ul>
```

### Handling Empty States

```php
<?php if (empty($attributes['items'])) : ?>
    <p class="empty-state">No items to display.</p>
<?php else : ?>
    <ul>
        <?php foreach ($attributes['items'] as $item) : ?>
            <li><?php echo esc_html($item); ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
```

### Multiple Conditional Classes

```php
<?php
$classes = ['my-block'];

if ($attributes['isLarge']) {
    $classes[] = 'is-large';
}

if ($attributes['hasBackground']) {
    $classes[] = 'has-background';
}

if (!empty($attributes['customClass'])) {
    $classes[] = sanitize_html_class($attributes['customClass']);
}
?>

<div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
    <!-- content -->
</div>
```

## Testing Your Content

Before you consider your block complete, test it thoroughly:

1. **Save and Generate**: Save your block and generate the files
2. **Insert in Editor**: Add the block to a test page in Gutenberg
3. **Try Different Settings**: Change attribute values and see how they affect the output
4. **Check Frontend**: View the page on your live site
5. **Test Edge Cases**: Try empty values, very long text, special characters
6. **Verify Symbols**: Ensure any included symbols render correctly
7. **Mobile Testing**: Check how it looks on different screen sizes

## Troubleshooting

### Block Not Appearing in Editor

- Check for PHP syntax errors in the validation messages
- Ensure your block has been generated (saved and files created)
- Verify the block category exists
- Clear your browser cache and refresh the editor

### Attributes Not Showing

- Confirm attributes are registered in the Attributes Manager
- Check that attribute names match exactly (case-sensitive)
- Verify the attribute has a default value set
- Ensure the block has been regenerated after adding attributes

### Symbols Not Rendering

- Verify the symbol exists and is published
- Check the symbol name is spelled correctly
- Ensure you're using the correct function: `get_symbol('symbol-name')`
- Confirm the symbol has been generated

### Content Looks Different in Editor vs Frontend

- This is expected for some styling - use Editor Style to adjust editor appearance
- Check if you have conflicting CSS from your theme
- Verify your Style CSS is being loaded on the frontend
- Use browser dev tools to inspect what styles are being applied

## Summary

The Content file (`render.php`) is where your block comes to life. Whether you're writing simple HTML or complex PHP logic, you have the flexibility to create exactly what you need. Combined with attributes for customization and symbols for reusable elements, you can build powerful, maintainable blocks that integrate seamlessly with WordPress.

Remember:
- Write clean, readable code
- Always escape your output for security
- Use symbols for reusable elements
- Leverage attributes for customization
- Let the validation system catch errors before they reach your site
- Test thoroughly in both the editor and frontend

With FanCoolo's error prevention system, you can code confidently knowing that broken code won't make it to your live site.
