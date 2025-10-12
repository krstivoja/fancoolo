# FanCoolo Fields Reference

## What Are Fields?

**Fields** (also called **Attributes**) are the editable options that appear in the Gutenberg Editor sidebar when someone uses your block.

For example, if you create a "Testimonial" block, you might want these fields:
- Author name (text field)
- Quote text (textarea)
- Show photo (on/off toggle)
- Star rating (slider from 1-5)

When users insert your block into a page, they'll see these fields in the sidebar and can edit them. The values they enter are automatically saved and available in your block's PHP render code.

---

## Block Attributes Manager

The **Attributes Manager** is where you create these fields using a simple visual interface - no JSON editing required.

### How It Works

1. Open the **Attributes** tab in the FanCoolo editor
2. Click **"+ Add attribute"** to create a new field
3. Choose the **Type** (Text, Number, Toggle, etc.) from dropdown
4. Enter the **Attribute name** (e.g., "authorName", "quoteText", "showPhoto")
5. FanCoolo automatically generates `block.json` with your attributes
6. Your block appears in Gutenberg with these fields in the sidebar
7. Users edit the fields → WordPress saves the data
8. Your PHP code accesses the data via `$attributes`
9. **Copy the PHP example** shown below each attribute to use it in your code

### Adding an Attribute

1. Click **"+ Add attribute"** button
2. Select **Type** from dropdown
3. Enter **Attribute name** (use camelCase like "authorName")
4. For Select/Radio: Click **"+ Add Option"** to add choices
5. For Range: Set **Max value**
6. **Copy the PHP code example** shown below the attribute
7. **Paste it into your PHP render code** to use the field

---

## Available Field Types

| Type | Gutenberg UI | Best For | Example |
|------|--------------|----------|---------|
| **Text** | Text input | Short text | Author name, title, label |
| **Textarea** | Multi-line box | Long text | Quote, description, content |
| **Number** | Number spinner | Numbers | Count, width, quantity |
| **Range** | Slider | Numbers with limits | Rating (1-5), opacity (0-100) |
| **Date** | Date picker | Dates | Event date, deadline |
| **Image** | Media button | Images | Author photo, background |
| **Link** | URL input | URLs | Button link, website URL |
| **Color** | Color picker | Colors | Background, text color |
| **Select** | Dropdown | Single choice from list | Size (S/M/L), alignment |
| **Toggle** | On/off switch | Enable/disable | Show author, enable feature |
| **Checkbox** | Checkbox | True/false | Accept terms, featured |
| **Radio** | Radio buttons | Single choice (visual) | Layout style, theme |

---

## Using Fields in Your Code

### Step 1: Create Field in Attributes Manager

In FanCoolo, add a "Text" attribute named "authorName"

### Step 2: Copy the PHP Example

Below each attribute, you'll see a PHP code example like:

```php
<?php echo esc_html($attributes['authorName']); ?>
```

### Step 3: Paste in Your Render Code

Use it in your block's PHP template:

```php
<div class="testimonial">
    <p class="author">By <?php echo esc_html($attributes['authorName']); ?></p>
</div>
```

### Examples by Type

**Text/Textarea:**
```php
<h2><?php echo esc_html($attributes['title']); ?></h2>
```

**Toggle/Checkbox:**
```php
<?php if ($attributes['showPhoto']): ?>
    <img src="..." alt="">
<?php endif; ?>
```

**Number/Range:**
```php
<div class="rating-<?php echo intval($attributes['rating']); ?>">
    ★ <?php echo intval($attributes['rating']); ?>
</div>
```

**Select/Radio:**
```php
<div class="size-<?php echo esc_attr($attributes['size']); ?>">
    Content here
</div>
```

**Image:**
```php
<?php if ($attributes['imageUrl']): ?>
    <img src="<?php echo esc_url($attributes['imageUrl']); ?>" alt="">
<?php endif; ?>
```

**Color:**
```php
<div style="background-color: <?php echo esc_attr($attributes['backgroundColor']); ?>">
    Content
</div>
```

---

## Adding Options (Select/Radio)

For **Select** and **Radio** types, you need to define the choices:

### In Attributes Manager:

1. Choose "Select" or "Radio" type
2. Click **"+ Add Option"**
3. Enter **Label** (what user sees in Gutenberg)
4. Enter **Value** (what your code receives)

### Example:

| Label | Value | Result |
|-------|-------|--------|
| Small | `sm` | User sees "Small", code gets "sm" |
| Medium | `md` | User sees "Medium", code gets "md" |
| Large | `lg` | User sees "Large", code gets "lg" |

### In Your PHP Code:

```php
<div class="button-<?php echo esc_attr($attributes['buttonSize']); ?>">
    <!-- If user selected "Small", this outputs: <div class="button-sm"> -->
</div>
```

---

## Setting Range (Range type)

For **Range** type, set the maximum value:

### In Attributes Manager:

1. Choose "Range" type
2. Set **Max value** (e.g., 100)
3. User gets slider from 0 to your max

### Example - Star Rating (1-5):

- Type: Range
- Max: 5
- User drags slider from 0 to 5

### In Your PHP Code:

```php
<div class="stars">
    <?php for ($i = 0; $i < $attributes['rating']; $i++): ?>
        ★
    <?php endfor; ?>
</div>
```

---

## All Block Fields Reference

### `_funculo_block_php`

PHP code that renders your block on the frontend. This is where you use `$attributes`.

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
}
```

### `_funculo_block_editor_scss`

Styles for the block in the WordPress editor only.

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

Created using the **Attributes Manager UI** - defines editable fields.

### `_funculo_block_settings`

Set in the **Settings** tab:

- **Description** - What the block does
- **Category** - Where it appears in inserter (text, media, design)
- **Icon** - Dashicon name
- **View Script Module** - Load JS as ES6 module (toggle)

### `_funculo_block_selected_partials`

Select SCSS partials to include from the **Partials** tab.

### `_funculo_block_inner_blocks_settings`

Set in the **Inner Blocks** tab:

- **Supports Inner Blocks** - Enable/disable nested blocks
- **Allowed Blocks** - Which blocks can be inserted inside
- **Template** - Default blocks to show

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
}
```

### `_funculo_scss_is_global`

Toggle to auto-include in all blocks.

### `_funculo_scss_global_order`

Load order (lower = loads first).

---

## Quick Reference

| Field | Where to Edit | What It Does |
|-------|---------------|--------------|
| `_funculo_block_php` | PHP tab | Render template with `$attributes` |
| `_funculo_block_scss` | SCSS tab | Frontend styles |
| `_funculo_block_editor_scss` | Editor SCSS tab | Editor-only styles |
| `_funculo_block_js` | JS tab | Frontend JavaScript |
| `_funculo_block_attributes` | **Attributes tab (UI)** | **Editable fields in Gutenberg** |
| `_funculo_block_settings` | Settings tab | Category, icon, description |
| `_funculo_block_selected_partials` | Partials tab | Include SCSS partials |
| `_funculo_block_inner_blocks_settings` | Inner Blocks tab | Nested blocks config |

---

## Tips

✅ **Copy the PHP examples** shown below each attribute in the Attributes Manager
✅ Attribute names should be **camelCase** (e.g., `authorName`, `showPhoto`)
✅ Use **Select/Radio** for predefined choices
✅ Use **Toggle/Checkbox** for on/off options
✅ Use **Range** for numbers with min/max (like ratings)
✅ Fields automatically appear in Gutenberg - no extra code needed
✅ Always escape output: `esc_html()`, `esc_attr()`, `esc_url()`
