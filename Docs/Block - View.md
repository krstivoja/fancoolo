# Block View - Frontend JavaScript

## What is View?

The View section is where you add JavaScript that runs on the frontend of your website. This is for any interactive behavior your block needs - animations, user interactions, dynamic content updates, or any functionality that makes your block come alive for visitors.

## When to Use View

Use the View section when your block needs frontend interactivity:

- Animations triggered by scrolling or user actions
- Interactive elements like tabs, accordions, or sliders
- Dynamic content that changes based on user input
- Click handlers, form submissions, or other user interactions
- Any JavaScript functionality that runs on the live site

## Module Toggle - Important Setting

FanCoolo provides a **Module toggle** that controls how your JavaScript is loaded. This is a critical setting that affects both when and how your script runs.

### Standard Script (Module: OFF)

When the Module toggle is **OFF**, your view.js is loaded as a standard script:

```json
"viewScript": "file:./view.js"
```

**Characteristics:**
- Script loads in the **footer** (bottom of the page)
- Loads after the page content is rendered
- Traditional JavaScript loading
- Use for standard JavaScript functionality

### ES Module Script (Module: ON)

When the Module toggle is **ON**, your view.js is loaded as an ES module:

```json
"viewScriptModule": "file:./view.js"
```

**Characteristics:**
- Script loads in the **header** (top of the page)
- Loads as a modern ES module
- **Required for WordPress Interactivity API**
- Use for modern JavaScript features and Interactivity API

### When to Use Each

**Use Module: OFF (viewScript)** when:
- You're writing standard JavaScript
- You don't need the Interactivity API
- You want the script to load after page content

**Use Module: ON (viewScriptModule)** when:
- You're using the WordPress Interactivity API
- You need ES module features (import/export)
- You need the script to load early in the page

## WordPress Interactivity API

The WordPress Interactivity API is a modern framework for building interactive blocks. If you want to use the Interactivity API, you **must** enable the Module toggle.

The Interactivity API provides:
- Reactive state management
- Server-side rendering support
- Declarative interaction handling
- Built-in performance optimizations

**To use Interactivity API:**
1. Enable the Module toggle
2. Your view.js will load as `viewScriptModule` in the header
3. Write your code using Interactivity API patterns

## Summary

The View section adds frontend JavaScript to your blocks. The Module toggle is a key setting:

- **Module OFF** ’ `viewScript` ’ Loads in footer ’ Standard JavaScript
- **Module ON** ’ `viewScriptModule` ’ Loads in header ’ ES modules + Required for Interactivity API

Choose the right setting based on whether you need standard JavaScript functionality or the WordPress Interactivity API.
