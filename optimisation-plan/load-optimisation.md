Performance Analysis
Root Causes of Slow Loading (200-500ms pause)
Based on the waterfall and code analysis, here are the performance bottlenecks:

1. Monaco Editor Loading (38-48ms for blob workers)
   Monaco Editor spawns 3 web workers (visible as blob: URLs taking 38-48ms)
   Workers load: TypeScript, CSS, PHP, SCSS, JavaScript language services
   editor.main.js loads from disk cache (2ms) but workers initialize after
   This happens on every page load even if editor isn't immediately visible
2. Massive Code Splitting Overhead
   40+ chunk files loaded (chunk-\*.js files)
   Each chunk is tiny (0.2-0.3 kB) causing HTTP request overhead
   Sequential loading pattern instead of parallel (waterfall shows cascading loads)
   esbuild's code splitting is too aggressive for this use case
3. Initial Data Fetching in PHP (200-500ms gap)
   // AdminAssets.php line 68
   $posts_data = $this->getPostsData(); // Blocking synchronous call
   Fetches ALL posts on page load (up to 100 items)
   Groups by taxonomy (loops through posts)
   Gets post terms for each post (N+1 query pattern)
   WordPress cache helps but initial load is slow
4. React Hydration and API Calls
   // useAppData.js lines 43-53
   await Promise.allSettled([
   centralizedApi.getPosts({ per_page: 100 }), // Duplicate data fetch
   centralizedApi.getScssPartials(), // Additional API call
   centralizedApi.getRegisteredBlocks(), // Additional API call
   centralizedApi.getBlockCategories(), // Additional API call
   ]);
   Even though posts are in window.wpApiSettings.posts, React fetches again
   4 parallel API calls on mount causing the 200-500ms pause
   Cache warming happens but doesn't help initial load
5. No Lazy Loading
   Monaco Editor loads immediately even if not needed
   All editor components load upfront
   No route-based code splitting (single entryPoint)
   Performance Optimization Plan
   Claude’s Plan
   Performance Optimization Plan
   Phase 1: Reduce Code Splitting Overhead (Biggest Impact)
   Disable aggressive code splitting in build.js
   Set splitting: false or increase chunk size thresholds
   Bundle related components together
   Target: Reduce 40+ chunks to ~5-10 bundles
   Phase 2: Optimize Initial Data Loading
   Use server-rendered data instead of duplicate API calls
   Modify useAppData.js to check window.wpApiSettings.posts first
   Skip API call for posts if data already exists
   Only fetch additional data (SCSS partials, blocks, categories) if needed
   Target: Eliminate 200-500ms data fetch pause
   Optimize PHP data fetching in AdminAssets.php
   Use single query with JOIN instead of N+1 queries for terms
   Add transient caching for grouped posts (5 min cache)
   Reduce per_page limit (do you need 100 posts on initial load?)
   Phase 3: Lazy Load Monaco Editor
   Load Monaco Editor on demand
   Use dynamic import: const Editor = lazy(() => import('@monaco-editor/react'))
   Load only when user opens a code field
   Configure Monaco CDN loading instead of bundling
   Target: Save 38-48ms on initial load
   Phase 4: HTTP/2 and Resource Hints
   Add resource hints to AdminAssets.php
   Preconnect to REST API endpoint
   Prefetch critical chunks
   Use modulepreload for ESM chunks
   Expected Results:
   Initial load: 200-500ms → 50-100ms
   Total load time: 900ms → 300-400ms
   Chunk requests: 40+ → 5-10
