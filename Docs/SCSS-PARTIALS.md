# FanCoolo SCSS Partials Documentation

## Table of Contents
1. [Introduction to SCSS](#introduction-to-scss)
2. [What are SCSS Partials?](#what-are-scss-partials)
3. [How SCSS Partials Work](#how-scss-partials-work)
4. [Bidirectional Compilation](#bidirectional-compilation)
5. [Creating an SCSS Partial](#creating-an-scss-partial)
6. [Using SCSS Partials in Blocks](#using-scss-partials-in-blocks)
7. [Global vs Local Partials](#global-vs-local-partials)
8. [Real-World Examples](#real-world-examples)
9. [Best Practices](#best-practices)

---

## Introduction to SCSS

### What is SCSS?

**SCSS (Sassy CSS)** is a CSS preprocessor that extends CSS with powerful features like variables, nesting, mixins, functions, and more. It makes writing and maintaining stylesheets easier and more efficient.

**Key SCSS Features:**

```scss
// Variables - Reusable values
$primary-color: #3B82F6;
$spacing: 1rem;

.button {
    background-color: $primary-color;
    padding: $spacing;
}

// Nesting - Cleaner structure
.card {
    padding: $spacing;

    .card-title {
        font-size: 1.5rem;
        color: $primary-color;
    }

    &:hover {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
}

// Mixins - Reusable style blocks
@mixin flex-center {
    display: flex;
    align-items: center;
    justify-content: center;
}

.centered-content {
    @include flex-center;
}

// Functions - Dynamic calculations
@function calculate-rem($px) {
    @return $px / 16 * 1rem;
}

.text {
    font-size: calculate-rem(18); // 1.125rem
}
```

### Why SCSS?

✅ **Variables** - Define colors, spacing, fonts once and reuse everywhere
✅ **Nesting** - Write cleaner, more organized CSS that mirrors your HTML structure
✅ **Mixins** - Create reusable style patterns without code duplication
✅ **Functions** - Perform calculations and transformations
✅ **Partials** - Split CSS into modular, maintainable files
✅ **Operators** - Use math operations (+, -, *, /) in your styles
✅ **Imports** - Organize styles across multiple files

### SCSS vs CSS

```css
/* Regular CSS - Repetitive */
.button-primary {
    background-color: #3B82F6;
    padding: 1rem 2rem;
    border-radius: 0.5rem;
}

.button-secondary {
    background-color: #6B7280;
    padding: 1rem 2rem;
    border-radius: 0.5rem;
}
```

```scss
// SCSS - DRY (Don't Repeat Yourself)
$color-primary: #3B82F6;
$color-secondary: #6B7280;

@mixin button-base {
    padding: 1rem 2rem;
    border-radius: 0.5rem;
}

.button-primary {
    @include button-base;
    background-color: $color-primary;
}

.button-secondary {
    @include button-base;
    background-color: $color-secondary;
}
```

### What are Partials in SCSS?

In traditional SCSS, **partials** are SCSS files that start with an underscore (`_variables.scss`, `_mixins.scss`) and are meant to be imported into other SCSS files rather than compiled on their own.

```scss
// _variables.scss - Partial file
$primary-color: #3B82F6;
$spacing: 1rem;

// main.scss - Imports the partial
@import 'variables';

.button {
    background-color: $primary-color; // Uses variable from partial
}
```

**Benefits of traditional SCSS partials:**
- Organize styles into logical modules
- Reuse code across multiple files
- Easier maintenance and updates
- Smaller, focused files instead of one giant stylesheet

---

## What are SCSS Partials?

**FanCoolo SCSS Partials** take the concept of traditional SCSS partials and integrate them into WordPress admin for easy management.

**SCSS Partials** are reusable SCSS code snippets in FanCoolo that you create in the WordPress admin and include in your blocks' styles.

### Key Characteristics

- **Taxonomy-based**: Created as Funculo Items with taxonomy term "scss-partials"
- **Reusable styles**: Write once, use across multiple blocks
- **Global or Local**: Can be automatically included in all blocks (global) or selectively imported (local)
- **Compiled with blocks**: Partials are included in block SCSS compilation
- **Hot reload**: Changes automatically trigger recompilation of affected blocks
- **Order control**: Global partials can be ordered for proper CSS cascade

### Benefits

✅ **DRY Styles** - Define design tokens, mixins, and utilities once
✅ **Consistent Design** - Enforce design system across all blocks
✅ **Easy Updates** - Change partial once, updates all blocks using it
✅ **Performance** - Only affected blocks recompile when partial changes
✅ **Organized** - Separate concerns (variables, mixins, utilities)

---

## How SCSS Partials Work

### The SCSS Partial Workflow

1. **Create Partial** in WordPress Admin (Funculo Items → Add New → Type: SCSS Partials)
2. **Write SCSS Code** (variables, mixins, utilities, etc.)
3. **Set Global/Local** - Choose if it applies to all blocks or specific ones
4. **Save & Generate** - FanCoolo generates `.scss` file
5. **Use in Blocks** - Either automatic (global) or manual (local) inclusion
6. **Compile** - Block SCSS includes the partial and compiles to CSS

### File Structure

```
wp-content/plugins/fancoolo-blocks/
├── my-block/
│   ├── block.json
│   ├── render.php
│   ├── style.scss           # Block's custom SCSS
│   └── style.css            # Compiled (includes global partials + block SCSS)
└── scss-partials/           # Generated partials directory
    ├── variables.scss       # From "Variables" partial post
    ├── mixins.scss          # From "Mixins" partial post
    ├── utilities.scss       # From "Utilities" partial post
    └── buttons.scss         # From "Buttons" partial post
```

### Compilation Order

When a block compiles:

1. **Global partials** (in order: `global_order` field)
2. **Selected local partials** (from block settings)
3. **Block's custom SCSS** (from Block SCSS metabox)

Result: `style.css` with all styles combined

---

## Bidirectional Compilation

One of FanCoolo's most powerful features is **bidirectional compilation** - changes flow in both directions between partials and blocks.

### How Bidirectional Compilation Works

```
┌─────────────────┐         ┌─────────────────┐
│  SCSS Partial   │ ◄─────► │   Block SCSS    │
│   (Variables)   │         │  (Uses vars)    │
└─────────────────┘         └─────────────────┘
        │                           │
        │                           │
        ▼                           ▼
┌─────────────────────────────────────────────┐
│         Automatic Recompilation             │
└─────────────────────────────────────────────┘
```

### Direction 1: Partial → Blocks

**When you save a partial, affected blocks automatically recompile**

#### Example Scenario:

1. You have a global **Variables** partial:
   ```scss
   $primary-color: #3B82F6; // Blue
   ```

2. 10 blocks use this variable:
   ```scss
   .my-block {
       background-color: $primary-color;
   }
   ```

3. You change the variable to green:
   ```scss
   $primary-color: #10B981; // Green
   ```

4. **Click "Update" on the partial**

5. ✨ **Magic happens**:
   - FanCoolo detects the partial changed
   - Finds all blocks using this partial (via junction table)
   - Automatically recompiles ALL 10 blocks
   - All blocks now have green background
   - **No manual recompilation needed!**

#### Smart Detection

FanCoolo intelligently determines which blocks to recompile:

**Global Partial Changed:**
```
Variables partial saved
    ↓
ALL blocks recompile (because it's global)
    ↓
Every block's style.css updated
```

**Local Partial Changed:**
```
Button Styles partial saved
    ↓
Only blocks that selected this partial recompile
    ↓
3 blocks updated (others unchanged)
```

### Direction 2: Block → Partials Selected

**When you select/deselect partials in a block, the block recompiles**

#### Example Scenario:

1. You're editing a **CTA Block**
2. Block doesn't have button styles yet
3. You select **"Button Styles"** partial in Block Settings
4. **Click "Update" on block**
5. ✨ **Magic happens**:
   - FanCoolo detects partial selection changed
   - Includes Button Styles partial in compilation
   - Recompiles block with new styles
   - Block now has button styles available

### Real-Time Updates

Changes propagate **instantly** in the editor:

```
Save Partial
    ↓
Backend: Recompile affected blocks (< 1 second)
    ↓
Frontend: Hot reload detects changes
    ↓
Editor: Blocks refresh with new styles
    ↓
You see changes immediately! ✨
```

### Performance Optimization

FanCoolo uses a **junction table** to track which blocks use which partials:

```sql
-- fancoolo_partials_usage table
+---------+-----------+
| post_id | partial_id|
+---------+-----------+
|   42    |    10     |  -- Block 42 uses Partial 10
|   42    |    11     |  -- Block 42 uses Partial 11
|   43    |    10     |  -- Block 43 uses Partial 10
+---------+-----------+
```

**Why this is fast:**

❌ **Without junction table**: Check every block to see if it contains partial reference (slow)
✅ **With junction table**: Instant SQL query to find affected blocks (fast)

```php
// Fast lookup - only recompile affected blocks
SELECT post_id FROM fancoolo_partials_usage WHERE partial_id = 10;
// Returns: [42, 43] - only these blocks recompile
```

### Practical Examples

#### Example 1: Global Color Change

```scss
// Before: Variables partial
$primary-color: #3B82F6; // Blue

// 5 blocks use this:
.hero { background: $primary-color; }
.cta { border-color: $primary-color; }
.button { background: $primary-color; }
```

**You change to:**
```scss
$primary-color: #10B981; // Green
```

**Save partial → ALL 5 blocks recompile automatically → All now green! 🎉**

#### Example 2: Adding Mixin to Partial

```scss
// Before: Mixins partial
@mixin flex-center {
    display: flex;
    align-items: center;
    justify-content: center;
}
```

**You add:**
```scss
@mixin hover-lift {
    transition: transform 0.2s;

    &:hover {
        transform: translateY(-2px);
    }
}
```

**Save partial → All blocks get new mixin → Can use @include hover-lift immediately! 🎉**

#### Example 3: Removing Partial from Block

```
Block uses:
- Variables (global)
- Mixins (global)
- Button Styles (local) ← You deselect this

Save block → Recompiles WITHOUT Button Styles → Smaller CSS file! 🎉
```

### Benefits of Bidirectional Compilation

✅ **Instant updates** - See changes immediately without manual work
✅ **Smart recompilation** - Only affected blocks recompile (fast)
✅ **Design system changes** - Update once, apply everywhere
✅ **No manual tracking** - System knows dependencies automatically
✅ **Safe updates** - Preview in editor before publishing
✅ **Performance** - Junction table makes lookups instant

### Visual Flow

```
┌─────────────────────────────────────────────────────────┐
│                 SCSS Partial Saved                      │
└─────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────┐
│  FanCoolo detects change & checks partial type          │
└─────────────────────────────────────────────────────────┘
                        ↓
        ┌───────────────┴───────────────┐
        ↓                               ↓
┌──────────────────┐          ┌──────────────────┐
│  Global Partial  │          │  Local Partial   │
└──────────────────┘          └──────────────────┘
        ↓                               ↓
┌──────────────────┐          ┌──────────────────┐
│ Get ALL blocks   │          │ Query junction   │
│                  │          │ table for blocks │
└──────────────────┘          └──────────────────┘
        ↓                               ↓
        └───────────────┬───────────────┘
                        ↓
┌─────────────────────────────────────────────────────────┐
│         Recompile affected blocks in parallel           │
└─────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────┐
│    Generate new style.css for each affected block       │
└─────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────┐
│      Hot reload detects changes → Editor updates        │
└─────────────────────────────────────────────────────────┘
```

### What Triggers Recompilation?

**Partial Side:**
- ✅ Saving/updating partial content
- ✅ Changing partial from local to global (or vice versa)
- ✅ Changing global order
- ✅ Creating new partial (no recompile until blocks use it)

**Block Side:**
- ✅ Selecting/deselecting local partials
- ✅ Saving block SCSS content
- ✅ Updating block settings

**What DOESN'T trigger recompilation:**
- ❌ Just viewing a partial (no changes)
- ❌ Drafting changes without saving
- ❌ Changing partial title/slug (only content matters)

---

## Creating an SCSS Partial

### Step 1: Create Partial Post

1. Go to WordPress Admin → **Funculo Items** → **Add New**
2. Enter partial name: "Variables", "Mixins", "Utilities", etc.
3. Set **Type** taxonomy to **"SCSS Partials"**
4. Save draft

### Step 2: Write SCSS Code

In the **SCSS Partial Components** meta box, write your SCSS:

```scss
// Variables partial
$primary-color: #3B82F6;
$secondary-color: #6B7280;
$success-color: #10B981;
$error-color: #EF4444;

$font-primary: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
$font-mono: 'SF Mono', Monaco, 'Courier New', monospace;

$spacing-xs: 0.25rem;
$spacing-sm: 0.5rem;
$spacing-md: 1rem;
$spacing-lg: 1.5rem;
$spacing-xl: 2rem;

$border-radius-sm: 0.25rem;
$border-radius-md: 0.5rem;
$border-radius-lg: 1rem;
```

### Step 3: Configure Settings

In the **SCSS Partial Settings** section:

- **Is Global**: Toggle ON if this should be included in ALL blocks
- **Global Order**: Set order (lower numbers = loaded first)

### Step 4: Save & Generate

1. Click **Update** to save
2. FanCoolo automatically generates:
   ```
   wp-content/plugins/fancoolo-blocks/scss-partials/variables.scss
   ```

### Step 5: Verify

Check that the file exists:
```bash
ls wp-content/plugins/fancoolo-blocks/scss-partials/
# Output: variables.scss
```

---

## Using SCSS Partials in Blocks

### Method 1: Global Partials (Automatic)

If a partial is marked as **Global**:

✅ Automatically included in **ALL** blocks
✅ No manual import needed
✅ Loads in order specified by `global_order`

**Example**: Variables and mixins are typically global

### Method 2: Local Partials (Manual Selection)

If a partial is NOT global:

1. Edit your block
2. In **Block Settings** → **SCSS Partials** section
3. Select partials to include
4. Save block

**Example**: Component-specific styles like "Button Styles" only for blocks that need buttons

### Using Partial Variables/Mixins

Once included, use partial content in your block SCSS:

```scss
// Block SCSS (automatically has access to global partials)
.my-block {
    // Use variables from global "Variables" partial
    background-color: $primary-color;
    padding: $spacing-lg;
    border-radius: $border-radius-md;
    font-family: $font-primary;

    .title {
        color: $secondary-color;
        margin-bottom: $spacing-md;
    }

    &:hover {
        background-color: darken($primary-color, 10%);
    }
}
```

---

## Global vs Local Partials

### Global Partials

**Use for:**
- Design tokens (colors, spacing, typography)
- Mixins used across multiple blocks
- Utility classes (display, spacing, text)
- CSS resets and normalizations
- Brand-specific styles

**Example Global Partials:**

1. **Variables** (order: 1)
2. **Mixins** (order: 2)
3. **Utilities** (order: 3)
4. **Reset** (order: 4)

**Settings:**
- ✅ Is Global: ON
- Global Order: 1-4

### Local Partials

**Use for:**
- Component-specific styles (only some blocks need)
- Third-party library styles (only where needed)
- Experimental styles (testing before making global)
- Block-specific themes

**Example Local Partials:**
- Button Styles (only for blocks with buttons)
- Card Styles (only for card-based blocks)
- Form Styles (only for form blocks)
- Animation Helpers (only for animated blocks)

**Settings:**
- ❌ Is Global: OFF
- Global Order: N/A

---

## Real-World Examples

### Example 1: Design System Variables (Global)

**Create Partial: "Design Tokens"**

**Mark as Global: YES, Order: 1**

**SCSS Code:**

```scss
// ================================================
// DESIGN TOKENS - GLOBAL PARTIAL
// ================================================

// Colors
$color-primary: #3B82F6;
$color-primary-dark: #2563EB;
$color-primary-light: #60A5FA;

$color-secondary: #6B7280;
$color-secondary-dark: #4B5563;
$color-secondary-light: #9CA3AF;

$color-success: #10B981;
$color-warning: #F59E0B;
$color-error: #EF4444;
$color-info: #3B82F6;

// Neutrals
$color-white: #FFFFFF;
$color-black: #000000;
$color-gray-50: #F9FAFB;
$color-gray-100: #F3F4F6;
$color-gray-200: #E5E7EB;
$color-gray-300: #D1D5DB;
$color-gray-400: #9CA3AF;
$color-gray-500: #6B7280;
$color-gray-600: #4B5563;
$color-gray-700: #374151;
$color-gray-800: #1F2937;
$color-gray-900: #111827;

// Typography
$font-sans: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
$font-serif: Georgia, Cambria, 'Times New Roman', Times, serif;
$font-mono: 'SF Mono', Monaco, Consolas, 'Courier New', monospace;

$font-size-xs: 0.75rem;    // 12px
$font-size-sm: 0.875rem;   // 14px
$font-size-base: 1rem;     // 16px
$font-size-lg: 1.125rem;   // 18px
$font-size-xl: 1.25rem;    // 20px
$font-size-2xl: 1.5rem;    // 24px
$font-size-3xl: 1.875rem;  // 30px
$font-size-4xl: 2.25rem;   // 36px

$font-weight-normal: 400;
$font-weight-medium: 500;
$font-weight-semibold: 600;
$font-weight-bold: 700;

$line-height-tight: 1.25;
$line-height-normal: 1.5;
$line-height-relaxed: 1.75;

// Spacing
$spacing-0: 0;
$spacing-1: 0.25rem;  // 4px
$spacing-2: 0.5rem;   // 8px
$spacing-3: 0.75rem;  // 12px
$spacing-4: 1rem;     // 16px
$spacing-5: 1.25rem;  // 20px
$spacing-6: 1.5rem;   // 24px
$spacing-8: 2rem;     // 32px
$spacing-10: 2.5rem;  // 40px
$spacing-12: 3rem;    // 48px
$spacing-16: 4rem;    // 64px

// Border Radius
$radius-none: 0;
$radius-sm: 0.125rem;  // 2px
$radius-base: 0.25rem; // 4px
$radius-md: 0.375rem;  // 6px
$radius-lg: 0.5rem;    // 8px
$radius-xl: 0.75rem;   // 12px
$radius-2xl: 1rem;     // 16px
$radius-full: 9999px;

// Shadows
$shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
$shadow-base: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
$shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
$shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
$shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);

// Breakpoints
$breakpoint-sm: 640px;
$breakpoint-md: 768px;
$breakpoint-lg: 1024px;
$breakpoint-xl: 1280px;
$breakpoint-2xl: 1536px;

// Transitions
$transition-fast: 150ms ease;
$transition-base: 200ms ease;
$transition-slow: 300ms ease;

// Z-index
$z-dropdown: 1000;
$z-sticky: 1020;
$z-fixed: 1030;
$z-modal-backdrop: 1040;
$z-modal: 1050;
$z-popover: 1060;
$z-tooltip: 1070;
```

**Usage in any block:**

```scss
.my-block {
    background-color: $color-primary;
    padding: $spacing-6;
    border-radius: $radius-lg;
    box-shadow: $shadow-md;
    font-family: $font-sans;
    transition: all $transition-base;

    @media (min-width: $breakpoint-md) {
        padding: $spacing-8;
    }
}
```

---

### Example 2: Mixins Library (Global)

**Create Partial: "Mixins"**

**Mark as Global: YES, Order: 2**

**SCSS Code:**

```scss
// ================================================
// MIXINS LIBRARY - GLOBAL PARTIAL
// ================================================

// Flexbox utilities
@mixin flex-center {
    display: flex;
    align-items: center;
    justify-content: center;
}

@mixin flex-between {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

@mixin flex-column {
    display: flex;
    flex-direction: column;
}

// Responsive breakpoints
@mixin sm {
    @media (min-width: $breakpoint-sm) {
        @content;
    }
}

@mixin md {
    @media (min-width: $breakpoint-md) {
        @content;
    }
}

@mixin lg {
    @media (min-width: $breakpoint-lg) {
        @content;
    }
}

@mixin xl {
    @media (min-width: $breakpoint-xl) {
        @content;
    }
}

// Typography
@mixin heading($size: 'lg') {
    font-family: $font-sans;
    font-weight: $font-weight-bold;
    line-height: $line-height-tight;

    @if $size == 'sm' {
        font-size: $font-size-xl;
    } @else if $size == 'md' {
        font-size: $font-size-2xl;
    } @else if $size == 'lg' {
        font-size: $font-size-3xl;
    } @else if $size == 'xl' {
        font-size: $font-size-4xl;
    }
}

@mixin text-ellipsis {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

// Visual effects
@mixin card {
    background-color: $color-white;
    border-radius: $radius-lg;
    box-shadow: $shadow-base;
    padding: $spacing-6;
}

@mixin hover-lift {
    transition: transform $transition-base, box-shadow $transition-base;

    &:hover {
        transform: translateY(-2px);
        box-shadow: $shadow-lg;
    }
}

@mixin focus-ring {
    outline: 2px solid transparent;
    outline-offset: 2px;

    &:focus {
        outline-color: $color-primary;
    }
}

// Layout
@mixin container($max-width: $breakpoint-xl) {
    max-width: $max-width;
    margin-left: auto;
    margin-right: auto;
    padding-left: $spacing-4;
    padding-right: $spacing-4;

    @include md {
        padding-left: $spacing-6;
        padding-right: $spacing-6;
    }

    @include lg {
        padding-left: $spacing-8;
        padding-right: $spacing-8;
    }
}

// Buttons
@mixin button-base {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: $spacing-3 $spacing-6;
    border-radius: $radius-md;
    font-weight: $font-weight-medium;
    font-size: $font-size-base;
    line-height: $line-height-normal;
    transition: all $transition-base;
    cursor: pointer;
    border: none;
    text-decoration: none;

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }
}

@mixin button-variant($bg-color, $text-color: $color-white) {
    @include button-base;
    background-color: $bg-color;
    color: $text-color;

    &:hover {
        background-color: darken($bg-color, 10%);
    }

    &:active {
        background-color: darken($bg-color, 15%);
    }
}

// Animations
@mixin fade-in($duration: $transition-base) {
    animation: fadeIn $duration ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@mixin slide-up($duration: $transition-base) {
    animation: slideUp $duration ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
```

**Usage in blocks:**

```scss
.my-card-block {
    @include card;
    @include hover-lift;

    .card-title {
        @include heading('md');
        @include text-ellipsis;
    }

    .card-content {
        @include flex-column;
        gap: $spacing-4;
    }

    @include md {
        @include flex-between;
    }
}

.my-button {
    @include button-variant($color-primary);
    @include focus-ring;
}
```

---

### Example 3: Button Styles (Local)

**Create Partial: "Button Styles"**

**Mark as Global: NO** (only for blocks that need buttons)

**SCSS Code:**

```scss
// ================================================
// BUTTON STYLES - LOCAL PARTIAL
// ================================================

.btn {
    @include button-base;

    // Primary variant
    &.btn-primary {
        @include button-variant($color-primary);
    }

    // Secondary variant
    &.btn-secondary {
        @include button-variant($color-secondary);
    }

    // Success variant
    &.btn-success {
        @include button-variant($color-success);
    }

    // Danger variant
    &.btn-danger {
        @include button-variant($color-error);
    }

    // Outline variant
    &.btn-outline {
        @include button-base;
        background-color: transparent;
        border: 2px solid $color-primary;
        color: $color-primary;

        &:hover {
            background-color: $color-primary;
            color: $color-white;
        }
    }

    // Ghost variant
    &.btn-ghost {
        @include button-base;
        background-color: transparent;
        color: $color-primary;

        &:hover {
            background-color: rgba($color-primary, 0.1);
        }
    }

    // Sizes
    &.btn-sm {
        padding: $spacing-2 $spacing-4;
        font-size: $font-size-sm;
    }

    &.btn-lg {
        padding: $spacing-4 $spacing-8;
        font-size: $font-size-lg;
    }

    &.btn-full {
        width: 100%;
    }
}
```

**Usage:**

1. Edit a block that needs button styles
2. Go to **Block Settings** → **SCSS Partials**
3. Select "Button Styles" from available partials
4. Save block
5. Use button classes in block SCSS:

```scss
.cta-block {
    .button-wrapper {
        @include flex-center;
        gap: $spacing-4;
    }

    // Button styles automatically available
    .btn {
        // Styles from "Button Styles" partial
    }
}
```

---

### Example 4: Utility Classes (Global)

**Create Partial: "Utilities"**

**Mark as Global: YES, Order: 3**

**SCSS Code:**

```scss
// ================================================
// UTILITY CLASSES - GLOBAL PARTIAL
// ================================================

// Display
.block { display: block; }
.inline-block { display: inline-block; }
.inline { display: inline; }
.flex { display: flex; }
.inline-flex { display: inline-flex; }
.grid { display: grid; }
.hidden { display: none; }

// Flex utilities
.flex-row { flex-direction: row; }
.flex-col { flex-direction: column; }
.flex-wrap { flex-wrap: wrap; }
.items-start { align-items: flex-start; }
.items-center { align-items: center; }
.items-end { align-items: flex-end; }
.justify-start { justify-content: flex-start; }
.justify-center { justify-content: center; }
.justify-end { justify-content: flex-end; }
.justify-between { justify-content: space-between; }

// Spacing
.m-0 { margin: 0; }
.m-1 { margin: $spacing-1; }
.m-2 { margin: $spacing-2; }
.m-4 { margin: $spacing-4; }
.m-6 { margin: $spacing-6; }
.m-8 { margin: $spacing-8; }

.mt-0 { margin-top: 0; }
.mt-2 { margin-top: $spacing-2; }
.mt-4 { margin-top: $spacing-4; }
.mt-6 { margin-top: $spacing-6; }

.mb-0 { margin-bottom: 0; }
.mb-2 { margin-bottom: $spacing-2; }
.mb-4 { margin-bottom: $spacing-4; }
.mb-6 { margin-bottom: $spacing-6; }

.p-0 { padding: 0; }
.p-2 { padding: $spacing-2; }
.p-4 { padding: $spacing-4; }
.p-6 { padding: $spacing-6; }
.p-8 { padding: $spacing-8; }

// Text
.text-left { text-align: left; }
.text-center { text-align: center; }
.text-right { text-align: right; }

.text-xs { font-size: $font-size-xs; }
.text-sm { font-size: $font-size-sm; }
.text-base { font-size: $font-size-base; }
.text-lg { font-size: $font-size-lg; }
.text-xl { font-size: $font-size-xl; }

.font-normal { font-weight: $font-weight-normal; }
.font-medium { font-weight: $font-weight-medium; }
.font-semibold { font-weight: $font-weight-semibold; }
.font-bold { font-weight: $font-weight-bold; }

// Colors
.text-primary { color: $color-primary; }
.text-secondary { color: $color-secondary; }
.text-success { color: $color-success; }
.text-error { color: $color-error; }

.bg-primary { background-color: $color-primary; }
.bg-secondary { background-color: $color-secondary; }
.bg-white { background-color: $color-white; }
.bg-gray-50 { background-color: $color-gray-50; }
.bg-gray-100 { background-color: $color-gray-100; }

// Border Radius
.rounded-none { border-radius: $radius-none; }
.rounded-sm { border-radius: $radius-sm; }
.rounded { border-radius: $radius-base; }
.rounded-md { border-radius: $radius-md; }
.rounded-lg { border-radius: $radius-lg; }
.rounded-full { border-radius: $radius-full; }

// Shadow
.shadow-none { box-shadow: none; }
.shadow-sm { box-shadow: $shadow-sm; }
.shadow { box-shadow: $shadow-base; }
.shadow-md { box-shadow: $shadow-md; }
.shadow-lg { box-shadow: $shadow-lg; }
```

**Usage in any block:**

```html
<div class="flex items-center justify-between p-6 bg-white rounded-lg shadow-md">
    <h2 class="text-xl font-bold text-primary mb-0">Title</h2>
    <button class="btn btn-primary">Action</button>
</div>
```

---

### Example 5: Complex Block Using Partials

**Block: "Feature Grid"**

**Selected Partials:**
- ✅ Design Tokens (global - automatic)
- ✅ Mixins (global - automatic)
- ✅ Utilities (global - automatic)
- ✅ Button Styles (local - selected)

**Block SCSS:**

```scss
// All global partials automatically available
// Button Styles partial manually selected

.feature-grid {
    @include container;
    padding-top: $spacing-16;
    padding-bottom: $spacing-16;

    .grid-title {
        @include heading('xl');
        text-align: center;
        margin-bottom: $spacing-12;
        color: $color-gray-900;
    }

    .features {
        display: grid;
        grid-template-columns: 1fr;
        gap: $spacing-8;

        @include md {
            grid-template-columns: repeat(2, 1fr);
        }

        @include lg {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    .feature-card {
        @include card;
        @include hover-lift;
        @include fade-in;

        .icon {
            width: 48px;
            height: 48px;
            @include flex-center;
            background-color: rgba($color-primary, 0.1);
            border-radius: $radius-full;
            color: $color-primary;
            margin-bottom: $spacing-4;
        }

        .title {
            @include heading('sm');
            margin-bottom: $spacing-3;
            @include text-ellipsis;
        }

        .description {
            color: $color-gray-600;
            line-height: $line-height-relaxed;
            margin-bottom: $spacing-6;
        }

        .cta-button {
            // Button styles from "Button Styles" partial
            @extend .btn;
            @extend .btn-outline;
            @extend .btn-sm;
        }
    }
}
```

**Result**: Clean block SCSS leveraging global design system and component-specific styles!

---

## Best Practices

### 1. Organize Partials by Purpose

```
Global Partials:
├── 1. Design Tokens (variables)
├── 2. Mixins (functions)
├── 3. Utilities (classes)
└── 4. Reset (base styles)

Local Partials:
├── Button Styles
├── Card Styles
├── Form Styles
├── Animation Helpers
└── Typography Helpers
```

### 2. Use Proper Global Order

Load in dependency order:

1. **Variables first** (other partials depend on them)
2. **Mixins second** (use variables, needed by utilities)
3. **Utilities third** (use variables and mixins)
4. **Component styles last**

### 3. Naming Conventions

```scss
// GOOD ✅ - Descriptive names
$color-primary
$spacing-lg
$breakpoint-md
@mixin flex-center
.btn-primary

// BAD ❌ - Generic names
$blue
$space3
$bp2
@mixin fc
.button1
```

### 4. Don't Repeat Global Content

```scss
// GOOD ✅ - Variables in global partial
// In "Design Tokens" global partial:
$color-primary: #3B82F6;

// In block SCSS:
.my-block {
    color: $color-primary;
}

// BAD ❌ - Redefining in every block
// In block SCSS:
$color-primary: #3B82F6; // Already in global partial!
```

### 5. Keep Partials Focused

```scss
// GOOD ✅ - Focused partials
// variables.scss - only variables
// mixins.scss - only mixins
// buttons.scss - only button styles

// BAD ❌ - Mixed concerns
// styles.scss - variables, mixins, components, utilities all in one
```

### 6. Document Your Partials

```scss
// ================================================
// DESIGN TOKENS
// Color palette, spacing, typography
// Global Order: 1
// ================================================

// Colors - Primary palette
$color-primary: #3B82F6;      // Blue - main brand color
$color-secondary: #6B7280;    // Gray - secondary actions

// Spacing scale - Based on 4px grid
$spacing-1: 0.25rem;  // 4px
$spacing-2: 0.5rem;   // 8px
```

### 7. Test Changes Before Making Global

1. Create partial as **local** first
2. Test in a few blocks
3. Once stable, mark as **global**

### 8. Use Semantic Names for Local Partials

```scss
// GOOD ✅
button-styles.scss
form-elements.scss
card-components.scss

// BAD ❌
styles1.scss
custom.scss
temp.scss
```

---

## Advanced Topics

### Hot Reload Behavior

When you save an SCSS partial:

- **Global partial changed**: ALL blocks recompile automatically
- **Local partial changed**: Only blocks using that partial recompile
- **Very fast**: FanCoolo uses junction table to track partial usage

### Performance Optimization

```scss
// GOOD ✅ - Only include what you need
// For a simple block, don't select heavy animation or form partials

// BAD ❌ - Including everything
// Selecting all 20 local partials when you only need 2
```

### Tailwind Integration

You can use Tailwind AND SCSS partials together:

```scss
// In block SCSS
.my-block {
    // Use Tailwind classes
    @apply flex items-center gap-4 p-6;

    // Use custom SCSS variables
    background-color: $color-primary;

    // Use custom mixins
    @include hover-lift;
}
```

### Conditional Compilation

```scss
// In partial - use feature queries
@supports (display: grid) {
    .grid-layout {
        display: grid;
    }
}

// In partial - use media queries
@mixin responsive-container {
    width: 100%;

    @media (min-width: $breakpoint-lg) {
        max-width: $breakpoint-lg;
    }
}
```

---

## Troubleshooting

### Partial Variables Not Available

If variables from partial aren't working:

1. **Check global status**: Is the partial marked as global?
2. **Check selection**: If local, is it selected in block settings?
3. **Check order**: Are variables loaded before usage? (global_order)
4. **Regenerate block**: Click "Update" on block to recompile

### Compilation Errors

If SCSS won't compile:

1. **Check syntax**: Look for SCSS syntax errors in partial
2. **Check dependencies**: Variables used before they're defined
3. **Check order**: Global partials loading in wrong order
4. **Review logs**: Check browser console for compilation errors

### Changes Not Appearing

If partial changes don't show:

1. **Save partial**: Click "Update" on partial post
2. **Clear cache**: Clear browser and WordPress cache
3. **Hard refresh**: Cmd/Ctrl + Shift + R in browser
4. **Check file**: Verify `.scss` file updated in `fancoolo-blocks/scss-partials/`

### Performance Issues

If compilation is slow:

1. **Too many globals**: Consider making some partials local
2. **Large partials**: Split mega-partials into focused smaller ones
3. **Unused code**: Remove unused variables, mixins, classes
4. **Check nesting**: Deep SCSS nesting can slow compilation

---

## Summary

### SCSS Partials Quick Reference

1. **Create**: WordPress Admin → Funculo Items → Type: SCSS Partials
2. **Write SCSS**: Variables, mixins, utilities, component styles
3. **Set Global/Local**: Choose scope and order
4. **Save**: Generates `.scss` file in `fancoolo-blocks/scss-partials/`
5. **Use**: Automatic (global) or manual selection (local)
6. **Compile**: Block SCSS includes partials → compiles to CSS

### Key Takeaways

✅ **Partials are reusable SCSS** code snippets
✅ **Global partials** apply to all blocks automatically
✅ **Local partials** selected per-block for specific needs
✅ **Order matters** for global partials (dependencies)
✅ **Hot reload** automatically recompiles affected blocks
✅ **Organize by purpose** (variables, mixins, utilities, components)
✅ **Document everything** for team collaboration

Start building your SCSS partial library for a consistent, maintainable design system! 🎨
