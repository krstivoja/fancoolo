# Fields Reference - Detailed Guide

This document provides detailed information about each field type available in FanCoolo's Attributes Manager.

---

## Text

A simple single-line text input field for short text content.

The Text field is the most commonly used field type. It's perfect for titles, names, labels, and any short text that doesn't need multiple lines. Users will see a standard text input box in the Gutenberg sidebar where they can type their content. The value is stored as a string and can be easily displayed in your block's render template.

**Technical Information:**

| Property      | Value                                                     |
| ------------- | --------------------------------------------------------- |
| Gutenberg UI  | Text input                                                |
| Data Type     | `string`                                                  |
| Default Value | `""` (empty string)                                       |
| Best For      | Short text content                                        |
| Common Uses   | Titles, names, labels, IDs, CSS classes                   |
| PHP Usage     | `<?php echo esc_html($attributes['fieldName']); ?>`       |
| Escaping      | Use `esc_html()` for content, `esc_attr()` for attributes |

---

## Textarea

A multi-line text input field for longer text content.

The Textarea field provides a larger text area that allows line breaks and longer content. It's ideal f◊or descriptions, paragraphs, or any text content that might span multiple lines. Users will see a resizable text box in the Gutenberg sidebar, making it easy to write and edit longer content. The field preserves line breaks if needed.

**Technical Information:**

| Property      | Value                                                |
| ------------- | ---------------------------------------------------- |
| Gutenberg UI  | Multi-line text box                                  |
| Data Type     | `string`                                             |
| Default Value | `""` (empty string)                                  |
| Best For      | Long-form text content                               |
| Common Uses   | Descriptions, quotes, paragraphs, instructions       |
| PHP Usage     | `<?php echo esc_html($attributes['fieldName']); ?>`  |
| Escaping      | Use `esc_html()` or `wp_kses_post()` if HTML allowed |

---

## Number

A numeric input field with increment/decrement controls.

The Number field allows users to enter numeric values using either the keyboard or spinner buttons (up/down arrows). This field automatically validates that only numbers are entered, preventing invalid input. It's useful for any numeric value like counts, sizes, dimensions, or quantities. The value is stored as a number type, making it easy to use in calculations or comparisons.

**Technical Information:**

| Property      | Value                                             |
| ------------- | ------------------------------------------------- |
| Gutenberg UI  | Number input with spinner                         |
| Data Type     | `number`                                          |
| Default Value | `0`                                               |
| Best For      | Numeric values without constraints                |
| Common Uses   | Counts, quantities, dimensions, sizes             |
| PHP Usage     | `<?php echo intval($attributes['fieldName']); ?>` |
| Validation    | Use `intval()` or `floatval()` depending on needs |

---

## Range

A slider control for selecting numeric values within a defined range.

The Range field displays a visual slider that users can drag to select a value. This provides an intuitive way to set numeric values within specific boundaries. It's particularly useful for values like opacity, ratings, or percentages where you want to limit the range and provide visual feedback. The slider makes it easy for users to find the right value without typing.

**Technical Information:**

| Property      | Value                                              |
| ------------- | -------------------------------------------------- |
| Gutenberg UI  | Slider control                                     |
| Data Type     | `number`                                           |
| Default Value | `0`                                                |
| Min Value     | `0` (set by default)                               |
| Max Value     | Required - you set this in Attributes Manager      |
| Best For      | Constrained numeric values                         |
| Common Uses   | Ratings (1-5), opacity (0-100), volume, brightness |
| PHP Usage     | `<?php echo intval($attributes['fieldName']); ?>`  |
| Configuration | Set Max value only; Min is always 0                |

---

## Date

A date picker control for selecting dates.

The Date field provides a calendar interface for selecting dates. Users can click to open a date picker and choose a date visually, which is much easier than typing dates manually. The field ensures proper date formatting and validation. It's perfect for event dates, deadlines, publication dates, or any content that needs date information.

**Technical Information:**

| Property      | Value                                               |
| ------------- | --------------------------------------------------- |
| Gutenberg UI  | Date picker calendar                                |
| Data Type     | `string` (ISO format)                               |
| Format        | `YYYY-MM-DD`                                        |
| Default Value | `""` (empty string)                                 |
| Best For      | Date selection                                      |
| Common Uses   | Event dates, deadlines, publish dates, schedules    |
| PHP Usage     | `<?php echo esc_html($attributes['fieldName']); ?>` |
| Formatting    | Use PHP `date()` or WordPress functions to format   |

---

## Image

A media selector for choosing images from the WordPress media library.

The Image field opens the WordPress media library when clicked, allowing users to select an existing image or upload a new one. This provides a seamless integration with WordPress's built-in media management. The field stores the image URL, making it easy to display the selected image in your block. Users get a visual preview of the selected image right in the sidebar.

**Technical Information:**

| Property      | Value                                                                 |
| ------------- | --------------------------------------------------------------------- |
| Gutenberg UI  | Media upload button with preview                                      |
| Data Type     | `string` (URL)                                                        |
| Stores        | Image URL                                                             |
| Default Value | `""` (empty string)                                                   |
| Best For      | Single image selection                                                |
| Common Uses   | Featured images, backgrounds, icons, avatars                          |
| PHP Usage     | `<img src="<?php echo esc_url($attributes['fieldName']); ?>" alt="">` |
| Escaping      | Always use `esc_url()` for src attribute                              |

---

## Link

A URL input field with three separate fields for complete link information.

The Link field provides a comprehensive solution for links by offering three separate inputs: URL, link text, and a toggle for opening in a new tab. This gives users full control over how links appear and behave. The URL field accepts any valid web address, the text field allows custom link text, and the "open in new tab" toggle adds the target="\_blank" attribute when enabled.

**Technical Information:**

| Property      | Value                                                               |
| ------------- | ------------------------------------------------------------------- |
| Gutenberg UI  | Three fields: URL input, text input, and toggle                     |
| Data Type     | `object`                                                            |
| Contains      | `url` (string), `text` (string), `opensInNewTab` (boolean)          |
| Default Value | `{"url": "", "text": "", "opensInNewTab": false}`                   |
| Best For      | Complete link configuration                                         |
| Common Uses   | Button links, external links, navigation items                      |
| PHP Usage     | See example below                                                   |
| Escaping      | `esc_url()` for URL, `esc_html()` for text, `esc_attr()` for target |

**PHP Example:**

```php
<?php if (!empty($attributes['link']['url'])): ?>
    <a href="<?php echo esc_url($attributes['link']['url']); ?>"
       <?php if ($attributes['link']['opensInNewTab']): ?>target="_blank" rel="noopener"<?php endif; ?>>
        <?php echo esc_html($attributes['link']['text'] ?: 'Read More'); ?>
    </a>
<?php endif; ?>
```

---

## Color

A color picker for selecting colors from your theme's color palette.

The Color field provides an easy way for users to select colors that match your site's design. For simplicity, FanCoolo uses your theme's predefined color palette rather than allowing custom color input. This ensures consistency across your site and makes it easier for users to choose appropriate colors. The field displays color swatches from your theme that users can click to select.

**Technical Information:**

| Property      | Value                                                                    |
| ------------- | ------------------------------------------------------------------------ |
| Gutenberg UI  | Color swatches from theme palette                                        |
| Data Type     | `string` (hex color code)                                                |
| Default Value | `""` (empty string)                                                      |
| Color Source  | **Theme colors only** (no custom colors for simplicity)                  |
| Best For      | Theme-consistent color selection                                         |
| Common Uses   | Background colors, text colors, borders, accents                         |
| PHP Usage     | `<div style="color: <?php echo esc_attr($attributes['fieldName']); ?>">` |
| Escaping      | Use `esc_attr()` when used in style attributes                           |

---

## Select

A dropdown menu for selecting a single option from a predefined list.

The Select field displays a dropdown menu with options you define in the Attributes Manager. Users can click to open the dropdown and select one option from the list. This is perfect for cases where you have multiple choices but want a compact interface. The field stores a single value as a string, making it easy to use in conditional logic or class names in your PHP code.

**Technical Information:**

| Property      | Value                                                                  |
| ------------- | ---------------------------------------------------------------------- |
| Gutenberg UI  | Dropdown menu                                                          |
| Data Type     | `string` (selected value)                                              |
| Selection     | **Single selection only** (returns one value)                          |
| Returns       | Array with one selected value                                          |
| Default Value | First option value or `""`                                             |
| Requires      | Options list (label + value pairs)                                     |
| Best For      | Single choice from multiple options                                    |
| Common Uses   | Size selection, alignment, layout style, theme                         |
| PHP Usage     | `<div class="size-<?php echo esc_attr($attributes['fieldName']); ?>">` |
| Configuration | Add options with Label (shown to user) and Value (used in code)        |

**Example Options:**

- Label: "Small" → Value: "sm"
- Label: "Medium" → Value: "md"
- Label: "Large" → Value: "lg"

---

## Toggle

An on/off switch for boolean (true/false) values.

The Toggle field provides a visual switch that users can click to turn a feature on or off. It's the most intuitive way to handle boolean values because the switch clearly shows the current state (on or off). When enabled, the value is `true`; when disabled, it's `false`. This makes it perfect for enabling or disabling features, showing or hiding content, or any yes/no decision.

**Technical Information:**

| Property        | Value                                                       |
| --------------- | ----------------------------------------------------------- |
| Gutenberg UI    | On/off switch                                               |
| Data Type       | `boolean`                                                   |
| Values          | `true` or `false`                                           |
| Default Value   | `false`                                                     |
| Best For        | Enable/disable features                                     |
| Common Uses     | Show/hide sections, enable features, display options        |
| PHP Usage       | `<?php if ($attributes['fieldName']): ?>...<?php endif; ?>` |
| Visual Feedback | Switch changes color and position based on state            |

---

## Checkbox

A checkbox input for boolean (true/false) values.

The Checkbox field provides a traditional checkbox interface for boolean values. Users can check or uncheck the box to set the value to `true` or `false`. While similar to the Toggle field in functionality, checkboxes are more traditional and work well when you have multiple related options in a list. The checkbox can also have an associated label that describes what checking the box means.

**Technical Information:**

| Property               | Value                                                       |
| ---------------------- | ----------------------------------------------------------- |
| Gutenberg UI           | Checkbox with label                                         |
| Data Type              | `boolean`                                                   |
| Values                 | `true` (checked) or `false` (unchecked)                     |
| Default Value          | `false`                                                     |
| Best For               | True/false options, agreements, feature flags               |
| Common Uses            | Accept terms, featured content, enable option               |
| PHP Usage              | `<?php if ($attributes['fieldName']): ?>...<?php endif; ?>` |
| Difference from Toggle | More traditional UI, better for lists of options            |

---

## Radio

Radio buttons for selecting a single option with visual presentation.

The Radio field displays a list of options as radio buttons, where users can select only one choice. Unlike the Select dropdown, radio buttons show all options at once, making them better when you want users to see all choices without clicking. This is ideal when you have 2-5 options that should be visually compared before selection. The field stores a single value as a string, similar to Select.

**Technical Information:**

| Property               | Value                                                                   |
| ---------------------- | ----------------------------------------------------------------------- |
| Gutenberg UI           | Radio button group                                                      |
| Data Type              | `string` (selected value)                                               |
| Selection              | **Single selection only** (returns one value)                           |
| Returns                | Array with one selected value                                           |
| Default Value          | First option value or `""`                                              |
| Requires               | Options list (label + value pairs)                                      |
| Best For               | Single choice from 2-5 options                                          |
| Common Uses            | Layout variants, style options, size selection                          |
| PHP Usage              | `<div class="style-<?php echo esc_attr($attributes['fieldName']); ?>">` |
| Difference from Select | Shows all options at once (no dropdown)                                 |
| Configuration          | Add options with Label (shown to user) and Value (used in code)         |

**Example Options:**

- Label: "Card Layout" → Value: "card"
- Label: "List Layout" → Value: "list"
- Label: "Grid Layout" → Value: "grid"

---

## Summary Table

| Field        | UI Control     | Data Type    | Selection Type | Requires Options | Notes                           |
| ------------ | -------------- | ------------ | -------------- | ---------------- | ------------------------------- |
| **Text**     | Text input     | string       | N/A            | No               | Single line                     |
| **Textarea** | Multi-line box | string       | N/A            | No               | Multiple lines                  |
| **Number**   | Number spinner | number       | N/A            | No               | Any numeric value               |
| **Range**    | Slider         | number       | N/A            | No               | Min: 0 (default), Max: required |
| **Date**     | Date picker    | string       | N/A            | No               | ISO format                      |
| **Image**    | Media button   | string (URL) | N/A            | No               | Opens media library             |
| **Link**     | Three inputs   | object       | N/A            | No               | URL + text + opensInNewTab      |
| **Color**    | Color swatches | string (hex) | N/A            | No               | Theme colors only               |
| **Select**   | Dropdown       | string       | Single         | Yes              | Compact interface               |
| **Toggle**   | Switch         | boolean      | N/A            | No               | Visual on/off                   |
| **Checkbox** | Checkbox       | boolean      | N/A            | No               | Traditional UI                  |
| **Radio**    | Radio buttons  | string       | Single         | Yes              | All options visible             |

---

## Quick Tips

✅ **Select vs Radio**: Use Select for compact dropdowns, Radio when you want all options visible
✅ **Toggle vs Checkbox**: Use Toggle for primary on/off features, Checkbox for agreements or lists
✅ **Range**: Always set Max value; Min is automatically 0
✅ **Color**: Uses theme colors only for design consistency
✅ **Link**: Returns an object with three properties: `url`, `text`, and `opensInNewTab`
✅ **Single Selection**: Select and Radio both return a single value (stored as array with one item)
