# FanCoolo Fields Reference

## Block Attributes Manager

The **Attributes Manager** is the most important tool in FanCoolo. It lets you define what data your block can store and edit - all through a simple UI, no JSON editing required.

### How It Works

1. Open the **Attributes** tab in the FanCoolo editor
2. Click **"+ Add attribute"** to create a new field
3. Choose the type and name using dropdown and text input
4. FanCoolo automatically generates `block.json` with your attributes
5. Your block appears in Gutenberg with editable fields in the sidebar
6. Users edit the fields and WordPress saves the data
7. Your PHP render code accesses the data via `$attributes`

### Using the Attributes Manager

#### Adding an Attribute

1. Click **"+ Add attribute"** button
2. Select the **Type** from dropdown (Text, Number, Toggle, etc.)
3. Enter the **Attribute name** (e.g., "title", "showButton", "imageUrl")
4. Add options if using Select/Radio type
5. Set range if using Range type

#### Available Attribute Types

| Type | UI Control | Best For | Example Use |
|------|-----------|----------|-------------|
| **Text** | Text input | Short text | Title, name, label |
| **Textarea** | Multi-line text | Long text | Description, content |
| **Number** | Number input | Numbers | Count, size, width |
| **Range** | Slider | Numbers with limits | Opacity, font size |
| **Date** | Date picker | Dates | Event date, publish date |
| **Image** | Media selector | Images | Featured image, icon |
| **Link** | URL input | URLs | Button link, external URL |
| **Color** | Color picker | Colors | Background, text color |
| **Select** | Dropdown menu | Single choice | Layout style, alignment |
| **Toggle** | On/off switch | True/false | Show/hide feature |
| **Checkbox** | Checkbox | True/false | Enable option |
| **Radio** | Radio buttons | Single choice | Size option |

#### Adding Options (Select/Radio)

For Select and Radio types:
1. Click **"+ Add Option"**
2. Enter **Label** (what user sees)
3. Enter **Value** (what code uses)
4. Add more options as needed

Example:
- Label: "Small" → Value: "sm"
- Label: "Medium" → Value: "md"
- Label: "Large" → Value: "lg"

#### Setting Range (Range type)

For Range type:
1. Set **Max value** (e.g., 100)
2. User can slide from 0 to your max value

### What Gets Generated

When you add attributes using the UI, FanCoolo creates this in `block.json`:

```json
{
    "name": "fancoolo/my-block",
    "title": "My Block",
    "attributes": {
        "title": {
            "type": "string",
            "default": ""
        },
        "showButton": {
            "type": "boolean",
            "default": false
        },
        "buttonSize": {
            "type": "string",
            "default": "md"
        }
    }
}
```

### Using Attributes in Your PHP Code

Access attributes in your render template:

```php
<div class="my-block">
    <h2><?php echo esc_html($attributes['title']); ?></h2>

    <?php if ($attributes['showButton']): ?>
        <button class="btn-<?php echo esc_attr($attributes['buttonSize']); ?>">
            Click me
        </button>
    <?php endif; ?>
</div>
```

### Editing in Gutenberg

Once you add attributes in FanCoolo, they automatically appear as editable fields in the Gutenberg sidebar when users insert your block:

- **Text/Textarea** → Text input fields
- **Number** → Number spinner
- **Range** → Slider control
- **Toggle/Checkbox** → On/off switch
- **Select** → Dropdown menu
- **Radio** → Radio button group
- **Color** → Color picker
- **Image** → Media upload button
- **Link** → URL input field
- **Date** → Date picker

---

## All Block Fields

### `_funculo_block_php`

PHP code that renders your block on the frontend.

```php
<div class="my-block">
    <h2><?php echo esc_html($attributes['title']); ?></h2>
</div>
```

### `_funculo_block_scss`

Frontend styles for your block.

```scss
.my-block {
    padding: 2rem;
    h2 { color: #333; }
}
```

### `_funculo_block_editor_scss`

Styles for the block in the WordPress editor.

```scss
.wp-block-fancoolo-my-block {
    border: 2px dashed #ccc;
}
```

### `_funculo_block_js`

Frontend JavaScript for interactivity.

```javascript
document.querySelectorAll('.my-block').forEach(block => {
    block.addEventListener('click', () => {
        console.log('Clicked!');
    });
});
```

### `_funculo_block_attributes`

Defines the data structure using the **Attributes Manager UI** (see section above).

### `_funculo_block_settings`

General block configuration - set in the Settings tab:

- **Description** - What the block does
- **Category** - Where it appears in inserter (text, media, design, etc.)
- **Icon** - Dashicon name for block icon
- **View Script Module** - Load JS as ES6 module (toggle on/off)

### `_funculo_block_selected_partials`

SCSS partials to include - select from the Partials tab.

### `_funculo_block_inner_blocks_settings`

Configuration for nested blocks - set in the Inner Blocks tab:

- **Supports Inner Blocks** - Enable/disable (toggle)
- **Allowed Blocks** - Which blocks can be inserted inside
- **Template** - Default blocks to show when first inserted

---

## Symbol Fields

### `_funculo_symbol_php`

Reusable PHP components like icons or UI elements.

```php
<svg class="icon" viewBox="0 0 24 24">
    <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12"/>
</svg>
```

---

## SCSS Partial Fields

### `_funculo_scss_partial_scss`

Reusable SCSS code shared across blocks.

```scss
@mixin button-primary {
    background: #007cba;
    color: white;
    padding: 0.5rem 1rem;
}
```

### `_funculo_scss_is_global`

Toggle to auto-include in all blocks.

### `_funculo_scss_global_order`

Load order for global partials (lower numbers load first).

---

## Quick Reference

| Field | Type | How to Edit |
|-------|------|-------------|
| `_funculo_block_php` | Text | PHP tab - code editor |
| `_funculo_block_scss` | Text | SCSS tab - code editor |
| `_funculo_block_editor_scss` | Text | Editor SCSS tab - code editor |
| `_funculo_block_js` | Text | JS tab - code editor |
| `_funculo_block_attributes` | JSON | **Attributes tab - visual UI** |
| `_funculo_block_settings` | JSON | Settings tab - form fields |
| `_funculo_block_selected_partials` | Array | Partials tab - checkboxes |
| `_funculo_block_inner_blocks_settings` | JSON | Inner Blocks tab - form |
| `_funculo_symbol_php` | Text | Code editor |
| `_funculo_scss_partial_scss` | Text | Code editor |
| `_funculo_scss_is_global` | Boolean | Toggle switch |
| `_funculo_scss_global_order` | Integer | Number input |

---

## Tips

- **Attribute names** should be camelCase (e.g., `showButton`, `imageUrl`)
- Use the **code example** shown below each attribute to see how to use it in PHP
- Attributes are automatically available in Gutenberg - no extra code needed
- Use **Select/Radio** for predefined choices
- Use **Toggle/Checkbox** for true/false options
- Use **Range** for numeric values with min/max limits
