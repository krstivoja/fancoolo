# FanCoolo Symbols Documentation

## Table of Contents

1. [What are Symbols?](#what-are-symbols)
2. [How Symbols Work](#how-symbols-work)
3. [Creating a Symbol](#creating-a-symbol)
4. [Using Symbols in Blocks](#using-symbols-in-blocks)
5. [Symbol Variations (Using Attributes)](#symbol-variations-using-attributes)
6. [Real-World Examples](#real-world-examples)
7. [Best Practices](#best-practices)

---

## What are Symbols?

**Symbols** are reusable PHP components in FanCoolo that you create in the WordPress admin and inject into your blocks using React-like syntax.

### Key Characteristics

Taxonomy-based: Created as Funculo Items with taxonomy term "symbols"
React-like syntax: Used in blocks as `<Button />`, `<Card />`, etc.
Server-side rendering: Processed to PHP at render time
Attribute-driven: Accept attributes to create variations (Primary, Outline, Ghost, etc.)
Reusable: One symbol can be used across multiple blocks
Centralized: Changes to a symbol automatically update all blocks using it

### Benefits

DRY Code - Write once, use everywhere
Consistent Design - Enforce design system patterns
Easy Updates - Change symbol once, updates all blocks
Variations - Use attributes to create multiple variants (Primary/Outline/Ghost buttons)
Clean Block Code - Blocks stay simple and readable

---

## How Symbols Work

### The Symbol Workflow

Create Symbol in WordPress Admin (Funculo Items → Add New → Type: Symbols)
Write PHP Code for the symbol component
Save & Generate - FanCoolo generates `fancoolo-blocks/symbols/button.php`
Use in Blocks - Add `<Button type="primary" />` in block PHP code
Render Time - SymbolProcessor converts `<Button />` to the symbol's PHP output

### File Structure

```
wp-content/plugins/
├── fancoolo/                           # Plugin directory
│   └── (your plugin files)
└── fancoolo-blocks/                    # Generated blocks
    ├── my-block/
    │   ├── block.json
    │   ├── render.php                  # Contains <Button /> tags
    │   └── style.css
    └── symbols/                        # Generated symbols
        ├── button.php                  # From "Button" symbol post
        ├── card.php                    # From "Card" symbol post
        └── icon.php                    # From "Icon" symbol post
```

### Naming Convention

| Symbol Post Title | Generated File     | Usage in Block    |
| ----------------- | ------------------ | ----------------- |
| Button            | `button.php`       | `<Button />`      |
| Card              | `card.php`         | `<Card />`        |
| Product Card      | `product-card.php` | `<ProductCard />` |
| Social Icon       | `social-icon.php`  | `<SocialIcon />`  |

Rule: PascalCase in blocks → kebab-case files

---

## Creating a Symbol

### Step 1: Create Symbol Post

1. Go to WordPress Admin → **Funculo Items** → **Add New**
2. Enter symbol name: "Button"
3. Set **Type** taxonomy to **"Symbols"**
4. Save draft

### Step 2: Write Symbol PHP Code

In the **Symbol Components** meta box, write your PHP code:

```php
<?php
/**
 * Button Symbol
 * Supports variations: primary, outline, ghost
 */

// Get attributes with defaults
$type = $symbol_attrs['type'] ?? 'primary';
$text = $symbol_attrs['text'] ?? 'Button';
$url = $symbol_attrs['url'] ?? '';
$size = $symbol_attrs['size'] ?? 'medium';

// Build CSS class
$class = 'btn btn-' . $type . ' btn-' . $size;
?>

<?php if (!empty($url)): ?>
    <a href="<?php echo esc_url($url); ?>" class="<?php echo esc_attr($class); ?>">
        <?php echo esc_html($text); ?>
    </a>
<?php else: ?>
    <button class="<?php echo esc_attr($class); ?>">
        <?php echo esc_html($text); ?>
    </button>
<?php endif; ?>
```

### Step 3: Save & Generate

1. Click **Update** to save
2. FanCoolo automatically generates:
   ```
   wp-content/plugins/fancoolo-blocks/symbols/button.php
   ```

### Step 4: Verify

Check that the file exists:

```bash
ls wp-content/plugins/fancoolo-blocks/symbols/
# Output: button.php
```

---

## Using Symbols in Blocks

### Basic Usage

In your block's **Block PHP** metabox:

```php
<div class="my-block">
    <Button text="Click Me" type="primary" />
</div>
```

At render time, this becomes:

```html
<div class="my-block">
  <button class="btn btn-primary btn-medium">Click Me</button>
</div>
```

### Passing Attributes

Attributes use HTML-like syntax with quotes:

```php
<Button
    text="Get Started"
    type="primary"
    size="large"
/>
```

### Multiple Symbols

```php
<Card title="Welcome" variant="elevated">
    <Icon name="star" size="24" />
    <Button text="Learn More" type="outline" />
</Card>
```

### Dynamic Attributes from Block

Pass block attributes to symbols:

```php
<?php
$buttonText = $block_attributes['buttonText'] ?? 'Click Me';
$buttonType = $block_attributes['buttonType'] ?? 'primary';
?>

<Button
    text="<?php echo esc_attr($buttonText); ?>"
    type="<?php echo esc_attr($buttonType); ?>"
/>
```

---

## Symbol Variations (Using Attributes)

Variations are created using **attributes**, not separate files. One symbol can have multiple visual styles.

### Example: Button Symbol with Variations

**Symbol PHP Code** (button.php):

```php
<?php
/**
 * Button Symbol
 * Variations: primary, outline, ghost
 */

$type = $symbol_attrs['type'] ?? 'primary';
$text = $symbol_attrs['text'] ?? 'Button';
$url = $symbol_attrs['url'] ?? '';
$size = $symbol_attrs['size'] ?? 'medium';
$disabled = $symbol_attrs['disabled'] ?? false;

// Build classes
$classes = ['btn', 'btn-' . $type, 'btn-' . $size];

if ($disabled === 'true' || $disabled === true) {
    $classes[] = 'disabled';
}

$class = implode(' ', $classes);
?>

<?php if (!empty($url)): ?>
    <a
        href="<?php echo esc_url($url); ?>"
        class="<?php echo esc_attr($class); ?>"
    >
        <?php echo esc_html($text); ?>
    </a>
<?php else: ?>
    <button
        class="<?php echo esc_attr($class); ?>"
        <?php echo ($disabled ? 'disabled' : ''); ?>
    >
        <?php echo esc_html($text); ?>
    </button>
<?php endif; ?>
```

**Using Variations in Block:**

```php
<!-- Primary Button (default) -->
<Button text="Submit" />

<!-- Outline Button -->
<Button text="Cancel" type="outline" />

<!-- Ghost Button -->
<Button text="Learn More" type="ghost" />

<!-- Large Primary Button -->
<Button text="Get Started" type="primary" size="large" />

<!-- Link Button -->
<Button text="Read More" type="primary" url="/about" />

<!-- Disabled Button -->
<Button text="Unavailable" disabled="true" />
```

**Corresponding SCSS** (in Block SCSS or SCSS Partial):

```scss
.btn {
  @apply inline-flex items-center justify-center px-4 py-2
           rounded-md font-medium transition-colors duration-200;

  // Primary variation
  &.btn-primary {
    @apply bg-blue-600 text-white hover:bg-blue-700;
  }

  // Outline variation
  &.btn-outline {
    @apply border-2 border-blue-600 text-blue-600 bg-transparent
               hover:bg-blue-50;
  }

  // Ghost variation
  &.btn-ghost {
    @apply text-blue-600 bg-transparent hover:bg-blue-50;
  }

  // Sizes
  &.btn-small {
    @apply px-3 py-1 text-sm;
  }

  &.btn-medium {
    @apply px-4 py-2 text-base;
  }

  &.btn-large {
    @apply px-6 py-3 text-lg;
  }

  // States
  &.disabled {
    @apply opacity-50 cursor-not-allowed pointer-events-none;
  }
}
```

---

## Real-World Examples

### Example 1: Simple Button Symbol

**Create Symbol: "Button"**

**Symbol PHP:**

```php
<?php
$type = $symbol_attrs['type'] ?? 'primary';
$text = $symbol_attrs['text'] ?? 'Button';
$class = 'btn btn-' . $type;
?>
<button class="<?php echo esc_attr($class); ?>">
    <?php echo esc_html($text); ?>
</button>
```

**Use in Block:**

```php
<div class="hero-section">
    <h1><?php echo esc_html($block_attributes['heading']); ?></h1>
    <Button text="Get Started" type="primary" />
    <Button text="Learn More" type="outline" />
</div>
```

---

### Example 2: Card Symbol with Variations

**Create Symbol: "Card"**

**Symbol PHP:**

```php
<?php
$title = $symbol_attrs['title'] ?? '';
$description = $symbol_attrs['description'] ?? '';
$variant = $symbol_attrs['variant'] ?? 'default';
$image = $symbol_attrs['image'] ?? '';

$class = 'card card-' . $variant;
?>

<div class="<?php echo esc_attr($class); ?>">
    <?php if (!empty($image)): ?>
        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" class="card-image" />
    <?php endif; ?>

    <div class="card-content">
        <?php if (!empty($title)): ?>
            <h3 class="card-title"><?php echo esc_html($title); ?></h3>
        <?php endif; ?>

        <?php if (!empty($description)): ?>
            <p class="card-description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
    </div>
</div>
```

**Use in Block:**

```php
<div class="cards-grid">
    <Card
        title="Feature One"
        description="This is a default card"
        variant="default"
    />

    <Card
        title="Feature Two"
        description="This is an outlined card"
        variant="outline"
    />

    <Card
        title="Feature Three"
        description="This is an elevated card with shadow"
        variant="elevated"
        image="https://example.com/image.jpg"
    />
</div>
```

---

### Example 3: Icon Symbol

**Create Symbol: "Icon"**

**Symbol PHP:**

```php
<?php
$name = $symbol_attrs['name'] ?? 'default';
$size = $symbol_attrs['size'] ?? '24';
$color = $symbol_attrs['color'] ?? 'currentColor';

// Icon SVG paths
$icons = [
    'star' => '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>',
    'heart' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
    'check' => '<path d="M20 6L9 17l-5-5"/>',
];

$path = $icons[$name] ?? $icons['star'];
?>

<svg
    width="<?php echo esc_attr($size); ?>"
    height="<?php echo esc_attr($size); ?>"
    viewBox="0 0 24 24"
    fill="none"
    stroke="<?php echo esc_attr($color); ?>"
    stroke-width="2"
>
    <?php echo $path; ?>
</svg>
```

**Use in Block:**

```php
<div class="feature-list">
    <div class="feature">
        <Icon name="check" size="20" color="green" />
        <span>Feature completed</span>
    </div>

    <div class="feature">
        <Icon name="heart" size="20" color="red" />
        <span>Loved by users</span>
    </div>

    <div class="feature">
        <Icon name="star" size="20" color="gold" />
        <span>Top rated</span>
    </div>
</div>
```

---

### Example 4: Combined - CTA Block with Multiple Symbols

**Block Attributes** (in Block Settings):

```json
{
  "heading": {
    "type": "string",
    "default": "Get Started Today"
  },
  "description": {
    "type": "string",
    "default": "Join thousands of happy customers"
  },
  "primaryButtonText": {
    "type": "string",
    "default": "Sign Up"
  },
  "primaryButtonUrl": {
    "type": "string",
    "default": "#"
  },
  "showIcon": {
    "type": "boolean",
    "default": true
  }
}
```

**Block PHP:**

```php
<?php
$heading = $block_attributes['heading'] ?? 'Get Started Today';
$description = $block_attributes['description'] ?? 'Join thousands of happy customers';
$primaryButtonText = $block_attributes['primaryButtonText'] ?? 'Sign Up';
$primaryButtonUrl = $block_attributes['primaryButtonUrl'] ?? '#';
$showIcon = $block_attributes['showIcon'] ?? true;
?>

<div class="cta-block">
    <Card variant="elevated">
        <?php if ($showIcon): ?>
            <Icon name="star" size="48" color="#FFD700" />
        <?php endif; ?>

        <h2><?php echo esc_html($heading); ?></h2>
        <p><?php echo esc_html($description); ?></p>

        <div class="button-group">
            <Button
                text="<?php echo esc_attr($primaryButtonText); ?>"
                type="primary"
                size="large"
                url="<?php echo esc_url($primaryButtonUrl); ?>"
            />
            <Button text="Learn More" type="outline" size="large" />
        </div>
    </Card>
</div>
```

**Result**: A complete CTA block using 3 symbols (Card, Icon, Button) with full customization!

---

### Example 5: Permalink Variation

For buttons that should link to the current post:

**Block Attributes:**

```json
{
  "buttonText": {
    "type": "string",
    "default": "Read More"
  },
  "usePermalink": {
    "type": "boolean",
    "default": false
  },
  "customUrl": {
    "type": "string",
    "default": ""
  }
}
```

**Block PHP:**

```php
<?php
$buttonText = $block_attributes['buttonText'] ?? 'Read More';
$usePermalink = $block_attributes['usePermalink'] ?? false;
$customUrl = $block_attributes['customUrl'] ?? '';

// Use permalink if enabled, otherwise use custom URL
$url = $usePermalink ? get_permalink() : $customUrl;
?>

<div class="post-excerpt">
    <h3><?php the_title(); ?></h3>
    <p><?php the_excerpt(); ?></p>

    <Button
        text="<?php echo esc_attr($buttonText); ?>"
        type="primary"
        url="<?php echo esc_url($url); ?>"
    />
</div>
```

---

## Best Practices

### 1. Always Provide Defaults

```php
// GOOD ✅
$type = $symbol_attrs['type'] ?? 'primary';
$text = $symbol_attrs['text'] ?? 'Button';

// BAD ❌ - Will error if attribute not provided
$type = $symbol_attrs['type'];
$text = $symbol_attrs['text'];
```

### 2. Escape All Output

```php
// GOOD ✅
<button class="<?php echo esc_attr($class); ?>">
    <?php echo esc_html($text); ?>
</button>
<a href="<?php echo esc_url($url); ?>">Link</a>

// BAD ❌ - Security risk
<button class="<?php echo $class; ?>">
    <?php echo $text; ?>
</button>
```

### 3. Handle Boolean Attributes Properly

Booleans are passed as strings:

```php
// GOOD ✅
$disabled = $symbol_attrs['disabled'] ?? false;
if ($disabled === 'true' || $disabled === true) {
    echo 'disabled';
}

// BAD ❌ - String "false" is truthy!
if ($disabled) {
    echo 'disabled';
}
```

### 4. Use Semantic HTML

```php
// GOOD ✅ - Use <a> for navigation, <button> for actions
<?php if (!empty($url)): ?>
    <a href="<?php echo esc_url($url); ?>">...</a>
<?php else: ?>
    <button type="button">...</button>
<?php endif; ?>

// BAD ❌
<button onclick="location.href='...'">...</button>
```

### 5. Keep Symbols Focused

```php
// GOOD ✅ - Single responsibility
Button symbol: handles button rendering
Icon symbol: handles icon rendering
Card symbol: handles card rendering

// BAD ❌ - Mega component doing everything
Component symbol: renders buttons, icons, cards, forms...
```

### 6. Document Available Attributes

```php
<?php
/**
 * Button Symbol
 *
 * Attributes:
 * @param string $type - Variation: primary|outline|ghost (default: primary)
 * @param string $text - Button text (default: Button)
 * @param string $size - Size: small|medium|large (default: medium)
 * @param string $url - Optional link URL (default: '')
 * @param string $disabled - Disabled state: true|false (default: false)
 *
 * Usage:
 * <Button text="Click Me" type="primary" size="large" />
 */

$type = $symbol_attrs['type'] ?? 'primary';
// ... rest of code
?>
```

### 7. Organize Related Symbols

Create a consistent symbol library:

```
symbols/
├── button.php           # Primary, Outline, Ghost variants
├── card.php            # Default, Outline, Elevated variants
├── icon.php            # Various icon types
├── badge.php           # Info, Success, Warning, Error variants
└── alert.php           # Info, Success, Warning, Error variants
```

---

## Advanced Topics

### Conditional Rendering

```php
<?php
$showIcon = $symbol_attrs['showIcon'] ?? 'true';
$iconName = $symbol_attrs['icon'] ?? 'star';
$text = $symbol_attrs['text'] ?? 'Button';
?>

<button class="btn">
    <?php if ($showIcon === 'true'): ?>
        <Icon name="<?php echo esc_attr($iconName); ?>" size="16" />
    <?php endif; ?>
    <?php echo esc_html($text); ?>
</button>
```

### Nested Symbols

Symbols can include other symbols (use sparingly):

```php
<!-- In Card symbol -->
<div class="card">
    <div class="card-header">
        <Icon name="<?php echo esc_attr($icon); ?>" />
        <h3><?php echo esc_html($title); ?></h3>
    </div>
</div>
```

### Dynamic Classes

```php
<?php
$classes = ['btn'];

// Add type
$type = $symbol_attrs['type'] ?? 'primary';
$classes[] = 'btn-' . $type;

// Add size
$size = $symbol_attrs['size'] ?? 'medium';
$classes[] = 'btn-' . $size;

// Conditional classes
if (($symbol_attrs['fullWidth'] ?? 'false') === 'true') {
    $classes[] = 'w-full';
}

if (($symbol_attrs['loading'] ?? 'false') === 'true') {
    $classes[] = 'loading';
}

$class = implode(' ', $classes);
?>

<button class="<?php echo esc_attr($class); ?>">
    <?php echo esc_html($text); ?>
</button>
```

---

## Troubleshooting

### Symbol Not Found

If you see `<!-- Symbol not found: button.php -->`:

1. **Check file exists**:

   ```bash
   ls wp-content/plugins/fancoolo-blocks/symbols/
   ```

2. **Verify symbol saved**: Go to Funculo Items → find your symbol → Update

3. **Check naming**: PascalCase `<Button />` → file `button.php`

4. **Regenerate**: Click "Update" on the symbol post again

### Attributes Not Working

If attributes aren't being passed:

1. **Check syntax**: Must use quotes `text="value"`
2. **Check variable name**: Use `$symbol_attrs` not `$attributes`
3. **Check typos**: `type="primary"` not `type="primar"`

### Symbol Not Rendering

If symbol tag shows in output as text:

1. **Check render.php exists** in block directory
2. **Verify SymbolProcessor called** (automatic in FanCoolo)
3. **Check PHP errors** in symbol file
4. **Review error logs**: Check WordPress debug.log

### Wrong Symbol Loaded

If wrong symbol loads:

1. **Check PascalCase**: `<ProductCard />` → `product-card.php`
2. **Avoid reserved names**: Don't use `InnerBlocks`, `RichText`, etc.
3. **Clear cache**: Some hosting may cache symbol files

---

## Symbol Processor Internals

### How It Works

1. **Block renders** with `<Button text="Click" type="primary" />`
2. **SymbolProcessor detects** PascalCase tags with regex
3. **Converts name**: `Button` → `button.php`
4. **Locates file**: `fancoolo-blocks/symbols/button.php`
5. **Parses attributes**: `text="Click"` → `$symbol_attrs['text'] = 'Click'`
6. **Includes file**: Symbol PHP executes with `$symbol_attrs` available
7. **Returns output**: Symbol's HTML replaces `<Button />` tag

### Reserved Component Names

These names are **reserved** for WordPress/Gutenberg and won't be processed as symbols:

- `InnerBlocks`
- `RichText`
- `MediaUpload`
- `BlockControls`
- `InspectorControls`
- `ColorPalette`
- `PlainText`

Use these directly in your blocks as WordPress components.

---

## Summary

### Symbols Quick Reference

1. **Create**: WordPress Admin → Funculo Items → Type: Symbols
2. **Write PHP**: Use `$symbol_attrs` to access attributes
3. **Save**: Generates `fancoolo-blocks/symbols/{name}.php`
4. **Use**: `<SymbolName attribute="value" />` in block PHP
5. **Variations**: Control via attributes (type, size, variant, etc.)

### Key Takeaways

✅ Symbols are **reusable PHP components**
✅ Created in **WordPress admin** as Funculo Items
✅ Used in blocks with **React-like syntax**
✅ Support **variations via attributes** (Primary, Outline, Ghost)
✅ One symbol file = **multiple visual styles**
✅ Changes propagate to **all blocks using the symbol**
✅ Follow **WordPress security practices** (escaping, sanitization)

Start building your symbol library today for cleaner, more maintainable blocks! 🚀
