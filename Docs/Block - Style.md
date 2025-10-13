# Block Style - Styling Your Blocks

## What is Block Style?

The Style section is where you define how your block looks. This is SCSS-based styling that applies to both the Gutenberg editor and your live website frontend, ensuring consistency between editing and viewing experiences.

## SCSS-Based Styling

FanCoolo uses SCSS (Sass) for block styling. You write your styles in SCSS, and FanCoolo automatically compiles them into CSS files that are loaded with your blocks.

**Key Benefits:**
- Write modern SCSS with nesting, variables, and mixins
- Automatically compiled to optimized CSS
- Applies to both editor and frontend
- Source maps generated for easy debugging

## Using SCSS Partials

SCSS Partials are reusable style fragments that you can include in your block styles. You manage these partials in the **SCSS Partials Tab** within FanCoolo.

### What Are SCSS Partials?

Partials are reusable pieces of SCSS code that can be shared across multiple blocks. Common uses include:

- Color variables and theme colors
- Typography settings and font mixins
- Reusable button styles
- Layout mixins (flexbox, grid patterns)
- Spacing variables
- Animation definitions

### How to Use Partials

SCSS Partials you create in the SCSS Partials tab are **automatically included** in your block styles. You don't need to manually import them - just create your partials and they'll be available to use in all your blocks.

This allows you to maintain consistent styling across all your blocks and avoid repeating code.

## Where Styles Apply

Styles you write in this section apply to **both**:

1. **Gutenberg Editor** - How your block looks while editing in WordPress
2. **Frontend** - How your block looks on your live website

This ensures what you see in the editor matches what visitors see on your site.

If you need styles that only apply to the editor (not the frontend), use the **Editor Style** section instead.

## Built-in Error Prevention

FanCoolo includes SCSS error handling to protect your site from broken styles.

### SCSS Syntax Validation

Before compiling your styles, FanCoolo validates that your SCSS code is correct. If there are syntax errors, you'll receive clear error messages.

**What gets validated:**
- SCSS syntax errors
- Missing brackets or semicolons
- Invalid property names
- Malformed selectors
- Import errors for partials

### Safe Compilation

Only valid SCSS will be compiled to CSS files. If your code contains errors, the compilation will be blocked and you'll see error messages explaining:

- The exact line where the error occurred
- What type of error was detected
- How to fix the problem

**Protection you get:**
- Prevents broken styles from reaching your site
- Keeps your blocks looking correct in the editor
- Clear, actionable error messages
- No CSS files generated until errors are fixed

This safety system ensures that your block styles always work correctly and won't cause styling issues on your live site.

## Summary

Block Style is your SCSS-powered styling solution that applies to both the editor and frontend. With SCSS partials for reusable styles and built-in error handling, you can confidently create beautiful, maintainable block styles.

**Remember:**
- All styles are SCSS-based
- Use the SCSS Partials tab to create reusable style fragments
- Styles apply to both editor and frontend
- Error messages will guide you if something goes wrong
- Only valid SCSS gets compiled to CSS
