# Block Variations Documentation

## Table of Contents
1. [What are Block Variations?](#what-are-block-variations)
2. [Creating Variations](#creating-variations)
3. [Variation Types](#variation-types)
4. [Real-World Examples](#real-world-examples)
5. [Best Practices](#best-practices)

---

## What are Block Variations?

**Block Variations** allow you to create multiple pre-configured versions of a single block with different:

- Default attribute values
- Visual styles
- Icons and labels
- Use cases

Think of variations as "presets" or "templates" for your blocks.

### Benefits

- **Reduced block clutter** - One button block instead of 10 separate blocks
- **Consistent design** - Enforce design system patterns
- **Better UX** - Users pick the right variant from the start
- **Easier maintenance** - Update one block, all variations benefit

---

## Creating Variations

### Method 1: JavaScript Registration (Recommended)

In your block's **JavaScript** code (edit.js or view.js), use `registerBlockVariation()`:

```javascript
import { registerBlockVariation } from '@wordpress/blocks';

// Register variations for a button block
registerBlockVariation('fancoolo/button', {
    name: 'button-primary',
    title: 'Primary Button',
    description: 'A primary call-to-action button',
    icon: 'button',
    attributes: {
        variant: 'primary',
        size: 'medium',
        className: 'btn-primary'
    },
    isDefault: true,
});

registerBlockVariation('fancoolo/button', {
    name: 'button-outline',
    title: 'Outline Button',
    description: 'A secondary outlined button',
    icon: 'button',
    attributes: {
        variant: 'outline',
        size: 'medium',
        className: 'btn-outline'
    }
});

registerBlockVariation('fancoolo/button', {
    name: 'button-ghost',
    title: 'Ghost Button',
    description: 'A minimal ghost button',
    icon: 'button',
    attributes: {
        variant: 'ghost',
        size: 'medium',
        className: 'btn-ghost'
    }
});
```

### Method 2: PHP Registration

In your block's **block.json** or via `register_block_type()`:

```php
register_block_type('fancoolo/button', [
    'render_callback' => 'render_button_block',
    'variations' => [
        [
            'name' => 'button-primary',
            'title' => 'Primary Button',
            'attributes' => [
                'variant' => 'primary',
                'className' => 'btn-primary'
            ],
            'isDefault' => true,
        ],
        [
            'name' => 'button-outline',
            'title' => 'Outline Button',
            'attributes' => [
                'variant' => 'outline',
                'className' => 'btn-outline'
            ],
        ]
    ]
]);
```

### Method 3: block.json (Static)

In your `block.json` file:

```json
{
  "name": "fancoolo/button",
  "title": "Button",
  "variations": [
    {
      "name": "button-primary",
      "title": "Primary Button",
      "icon": "button",
      "attributes": {
        "variant": "primary"
      },
      "isDefault": true
    },
    {
      "name": "button-outline",
      "title": "Outline Button",
      "icon": "button",
      "attributes": {
        "variant": "outline"
      }
    }
  ]
}
```

---

## Variation Types

### 1. Style Variations (Visual Appearance)

Different visual styles of the same component:

```javascript
// Primary, Secondary, Tertiary buttons
registerBlockVariation('fancoolo/button', {
    name: 'button-primary',
    title: 'Primary Button',
    attributes: {
        variant: 'primary',
        backgroundColor: '#3B82F6',
        textColor: '#FFFFFF'
    },
    isDefault: true
});

registerBlockVariation('fancoolo/button', {
    name: 'button-secondary',
    title: 'Secondary Button',
    attributes: {
        variant: 'secondary',
        backgroundColor: '#6B7280',
        textColor: '#FFFFFF'
    }
});
```

### 2. Functional Variations (Different Purposes)

Same component, different use cases:

```javascript
// Call-to-action vs. Link button
registerBlockVariation('fancoolo/button', {
    name: 'button-cta',
    title: 'Call to Action',
    attributes: {
        variant: 'primary',
        size: 'large',
        text: 'Get Started',
        isAction: true
    }
});

registerBlockVariation('fancoolo/button', {
    name: 'button-link',
    title: 'Text Link',
    attributes: {
        variant: 'ghost',
        size: 'small',
        text: 'Learn More',
        isLink: true
    }
});
```

### 3. Layout Variations

Different structural layouts:

```javascript
// Card layouts
registerBlockVariation('fancoolo/card', {
    name: 'card-horizontal',
    title: 'Horizontal Card',
    attributes: {
        layout: 'horizontal',
        imagePosition: 'left'
    }
});

registerBlockVariation('fancoolo/card', {
    name: 'card-vertical',
    title: 'Vertical Card',
    attributes: {
        layout: 'vertical',
        imagePosition: 'top'
    }
});
```

### 4. Content Variations (Pre-filled Content)

Pre-populated with specific content:

```javascript
// Social share buttons
registerBlockVariation('fancoolo/share-button', {
    name: 'share-twitter',
    title: 'Share on Twitter',
    icon: 'twitter',
    attributes: {
        platform: 'twitter',
        text: 'Share on Twitter',
        icon: 'twitter',
        color: '#1DA1F2'
    }
});

registerBlockVariation('fancoolo/share-button', {
    name: 'share-facebook',
    title: 'Share on Facebook',
    icon: 'facebook',
    attributes: {
        platform: 'facebook',
        text: 'Share on Facebook',
        icon: 'facebook',
        color: '#4267B2'
    }
});
```

---

## Real-World Examples

### Example 1: Button Block with Variants

**1. Define Block Attributes** (in Block Settings metabox):

```json
{
  "variant": {
    "type": "string",
    "default": "primary"
  },
  "size": {
    "type": "string",
    "default": "medium"
  },
  "text": {
    "type": "string",
    "default": "Button"
  },
  "url": {
    "type": "string",
    "default": ""
  },
  "isPermalink": {
    "type": "boolean",
    "default": false
  }
}
```

**2. Create Block JavaScript** (in Block JS metabox):

```javascript
import { registerBlockVariation } from '@wordpress/blocks';

// Primary Button
registerBlockVariation('fancoolo/button', {
    name: 'primary',
    title: 'Primary Button',
    description: 'Main call-to-action button',
    icon: 'button',
    attributes: {
        variant: 'primary',
        size: 'medium',
        text: 'Get Started'
    },
    isDefault: true,
    scope: ['inserter', 'block']
});

// Outline Button
registerBlockVariation('fancoolo/button', {
    name: 'outline',
    title: 'Outline Button',
    description: 'Secondary button with border',
    icon: 'button',
    attributes: {
        variant: 'outline',
        size: 'medium',
        text: 'Learn More'
    },
    scope: ['inserter', 'block']
});

// Ghost Button
registerBlockVariation('fancoolo/button', {
    name: 'ghost',
    title: 'Ghost Button',
    description: 'Minimal text-only button',
    icon: 'button',
    attributes: {
        variant: 'ghost',
        size: 'medium',
        text: 'Read More'
    },
    scope: ['inserter', 'block']
});
```

**3. Block PHP Render** (in Block PHP metabox):

```php
<?php
$variant = $block_attributes['variant'] ?? 'primary';
$size = $block_attributes['size'] ?? 'medium';
$text = $block_attributes['text'] ?? 'Button';
$url = $block_attributes['url'] ?? '';
$isPermalink = $block_attributes['isPermalink'] ?? false;

// If isPermalink is true, use the current post's permalink
if ($isPermalink && function_exists('get_permalink')) {
    $url = get_permalink();
}
?>

<Button
    text="<?php echo esc_attr($text); ?>"
    type="<?php echo esc_attr($variant); ?>"
    size="<?php echo esc_attr($size); ?>"
    url="<?php echo esc_url($url); ?>"
/>
```

**4. Block SCSS** (in Block SCSS metabox):

```scss
.btn {
    @apply inline-block px-4 py-2 rounded-md font-medium transition-colors;

    // Primary variant
    &.btn-primary {
        @apply bg-blue-600 text-white hover:bg-blue-700;
    }

    // Outline variant
    &.btn-outline {
        @apply border-2 border-blue-600 text-blue-600 bg-transparent hover:bg-blue-50;
    }

    // Ghost variant
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
}
```

**Result**: Users can pick from 3 button styles in the block inserter!

---

### Example 2: Button with Permalink Variation

For buttons that link to the current post:

```javascript
registerBlockVariation('fancoolo/button', {
    name: 'button-permalink',
    title: 'Read More Button',
    description: 'Links to the current post',
    icon: 'admin-links',
    attributes: {
        variant: 'primary',
        text: 'Read More',
        isPermalink: true, // This triggers automatic permalink
        url: '' // Will be populated automatically
    },
    scope: ['inserter']
});
```

In the block's PHP render:

```php
<?php
$isPermalink = $block_attributes['isPermalink'] ?? false;
$url = $block_attributes['url'] ?? '';

if ($isPermalink) {
    // Automatically use current post's permalink
    $url = get_permalink();
}
?>

<Button
    text="<?php echo esc_attr($text); ?>"
    url="<?php echo esc_url($url); ?>"
/>
```

---

### Example 3: Card Block with Layout Variations

**Block Attributes:**

```json
{
  "layout": {
    "type": "string",
    "default": "vertical"
  },
  "title": {
    "type": "string",
    "default": ""
  },
  "description": {
    "type": "string",
    "default": ""
  },
  "imageUrl": {
    "type": "string",
    "default": ""
  }
}
```

**JavaScript Variations:**

```javascript
// Vertical Card
registerBlockVariation('fancoolo/card', {
    name: 'card-vertical',
    title: 'Vertical Card',
    icon: 'align-center',
    attributes: {
        layout: 'vertical'
    },
    isDefault: true
});

// Horizontal Card
registerBlockVariation('fancoolo/card', {
    name: 'card-horizontal',
    title: 'Horizontal Card',
    icon: 'align-left',
    attributes: {
        layout: 'horizontal'
    }
});

// Minimal Card
registerBlockVariation('fancoolo/card', {
    name: 'card-minimal',
    title: 'Minimal Card',
    icon: 'minus',
    attributes: {
        layout: 'minimal',
        showImage: false
    }
});
```

**PHP Render:**

```php
<?php
$layout = $block_attributes['layout'] ?? 'vertical';
$title = $block_attributes['title'] ?? '';
$description = $block_attributes['description'] ?? '';
$imageUrl = $block_attributes['imageUrl'] ?? '';
?>

<Card
    title="<?php echo esc_attr($title); ?>"
    subtitle="<?php echo esc_attr($description); ?>"
    variant="<?php echo esc_attr($layout); ?>"
    image="<?php echo esc_url($imageUrl); ?>"
/>
```

---

### Example 4: Social Share Buttons

**Block Attributes:**

```json
{
  "platform": {
    "type": "string",
    "default": "twitter"
  },
  "text": {
    "type": "string",
    "default": "Share"
  }
}
```

**JavaScript Variations:**

```javascript
const platforms = [
    { name: 'twitter', title: 'Twitter', icon: 'twitter', color: '#1DA1F2' },
    { name: 'facebook', title: 'Facebook', icon: 'facebook', color: '#4267B2' },
    { name: 'linkedin', title: 'LinkedIn', icon: 'linkedin', color: '#0077B5' },
    { name: 'reddit', title: 'Reddit', icon: 'reddit', color: '#FF4500' }
];

platforms.forEach(platform => {
    registerBlockVariation('fancoolo/share-button', {
        name: `share-${platform.name}`,
        title: `Share on ${platform.title}`,
        icon: platform.icon,
        attributes: {
            platform: platform.name,
            text: `Share on ${platform.title}`,
            color: platform.color
        }
    });
});
```

**PHP Render:**

```php
<?php
$platform = $block_attributes['platform'] ?? 'twitter';
$text = $block_attributes['text'] ?? 'Share';
$currentUrl = get_permalink();
$shareUrls = [
    'twitter' => 'https://twitter.com/intent/tweet?url=' . urlencode($currentUrl),
    'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($currentUrl),
    'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($currentUrl),
    'reddit' => 'https://reddit.com/submit?url=' . urlencode($currentUrl)
];

$shareUrl = $shareUrls[$platform] ?? $shareUrls['twitter'];
?>

<Button
    text="<?php echo esc_attr($text); ?>"
    type="outline"
    url="<?php echo esc_url($shareUrl); ?>"
    class="share-button share-<?php echo esc_attr($platform); ?>"
/>
```

---

## Best Practices

### 1. Use `isDefault` for Primary Variation

```javascript
registerBlockVariation('fancoolo/button', {
    name: 'primary',
    title: 'Primary Button',
    isDefault: true, // This is the default when inserting
    // ...
});
```

### 2. Provide Clear Titles and Descriptions

```javascript
// GOOD ✅
{
    title: 'Primary Button',
    description: 'Main call-to-action button with bold styling'
}

// BAD ❌
{
    title: 'Button 1',
    description: 'A button'
}
```

### 3. Use Descriptive Icons

```javascript
// GOOD ✅
registerBlockVariation('fancoolo/card', {
    name: 'card-horizontal',
    icon: 'align-left', // Suggests horizontal layout
    // ...
});

// BAD ❌
registerBlockVariation('fancoolo/card', {
    name: 'card-horizontal',
    icon: 'button', // Not relevant
    // ...
});
```

### 4. Set Appropriate Scopes

```javascript
// Show in both inserter and transform menu
scope: ['inserter', 'block']

// Show only in inserter
scope: ['inserter']

// Show only in transform menu (style switcher)
scope: ['block']
```

### 5. Don't Overdo It

```javascript
// GOOD ✅ - 3-5 meaningful variations
- Primary Button
- Outline Button
- Ghost Button

// BAD ❌ - Too many variations
- Primary Button
- Primary Large Button
- Primary Small Button
- Primary Blue Button
- Primary Green Button
// ... (15 more variations)
```

Instead, use attributes for size/color and variations for semantic differences.

### 6. Combine with Symbols

Use variations to set up different symbol configurations:

```javascript
registerBlockVariation('fancoolo/feature-box', {
    name: 'feature-with-icon',
    title: 'Feature with Icon',
    attributes: {
        showIcon: true,
        iconName: 'star',
        layout: 'centered'
    }
});
```

Then in PHP:

```php
<?php
$showIcon = $block_attributes['showIcon'] ?? false;
$iconName = $block_attributes['iconName'] ?? 'star';
?>

<?php if ($showIcon): ?>
    <Icon name="<?php echo esc_attr($iconName); ?>" size="32" />
<?php endif; ?>
```

---

## Advanced: Dynamic Variations

### Loading Variations from WordPress Data

```javascript
import { useSelect } from '@wordpress/data';
import { registerBlockVariation } from '@wordpress/blocks';

// Register variations from post categories
const categories = useSelect(select =>
    select('core').getEntityRecords('taxonomy', 'category')
);

categories?.forEach(category => {
    registerBlockVariation('fancoolo/category-card', {
        name: `category-${category.slug}`,
        title: category.name,
        attributes: {
            categoryId: category.id,
            categoryName: category.name,
            categorySlug: category.slug
        }
    });
});
```

### Conditional Variations

```javascript
// Only show "Buy Now" button variation for WooCommerce sites
if (window.woocommerce) {
    registerBlockVariation('fancoolo/button', {
        name: 'button-buy-now',
        title: 'Buy Now Button',
        attributes: {
            variant: 'primary',
            text: 'Buy Now',
            isWooCommerceButton: true
        }
    });
}
```

---

## Troubleshooting

### Variations Not Showing

1. Check JavaScript is being loaded in the editor
2. Verify block name matches exactly (case-sensitive)
3. Ensure `scope` includes `'inserter'`
4. Check browser console for errors

### Default Variation Not Working

1. Only ONE variation can have `isDefault: true`
2. Must be registered before other variations
3. Scope must include `'inserter'`

### Attributes Not Applying

1. Verify attribute names match between variation and block definition
2. Check attribute types match (string vs boolean vs number)
3. Ensure attributes are defined in block.json or Block Attributes metabox

---

## Summary

Block Variations allow you to:

✅ Create multiple presets from one block
✅ Reduce block clutter in the inserter
✅ Enforce design system patterns
✅ Provide better UX with semantic choices
✅ Combine with Symbols for powerful flexibility

Start creating variations today to give users better block options!
