/**
 * Centralized API Client for Funculo Plugin
 *
 * Features:
 * - Request caching to reduce redundant API calls
 * - Request deduplication for simultaneous identical requests
 * - Centralized error handling and logging
 * - Performance monitoring and metrics
 * - Automatic retry logic for failed requests
 * - TypeScript-like JSDoc annotations for better IDE support
 */

/**
 * Custom API Error class for better error handling
 */
export class ApiError extends Error {
  constructor(message, status, data = null, endpoint = null) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.data = data;
    this.endpoint = endpoint;
  }
}

/**
 * Performance monitoring utility
 */
class PerformanceMonitor {
  constructor() {
    this.metrics = new Map();
    this.enabled = window.funculoSettings?.debugMode || false;
  }

  /**
   * Start timing an operation
   * @param {string} operation Operation name
   * @returns {Object} Timer object with end() method
   */
  startTiming(operation) {
    if (!this.enabled) return { end: () => {} };

    const startTime = performance.now();
    return {
      end: () => {
        const duration = performance.now() - startTime;
        this.recordMetric(operation, duration);
        return duration;
      },
    };
  }

  /**
   * Record a performance metric
   * @param {string} operation Operation name
   * @param {number} duration Duration in milliseconds
   */
  recordMetric(operation, duration) {
    if (!this.metrics.has(operation)) {
      this.metrics.set(operation, []);
    }

    this.metrics.get(operation).push({
      duration,
      timestamp: Date.now(),
    });

    // Keep only last 100 metrics per operation
    const metrics = this.metrics.get(operation);
    if (metrics.length > 100) {
      metrics.shift();
    }
  }

  /**
   * Get average time for an operation
   * @param {string} operation Operation name
   * @returns {number} Average duration in milliseconds
   */
  getAverageTime(operation) {
    const times = this.metrics.get(operation) || [];
    if (times.length === 0) return 0;

    const sum = times.reduce((acc, metric) => acc + metric.duration, 0);
    return sum / times.length;
  }

  /**
   * Export all metrics for analysis
   * @returns {Object} Metrics summary
   */
  exportMetrics() {
    const summary = {};
    for (const [operation, metrics] of this.metrics) {
      summary[operation] = {
        count: metrics.length,
        average: this.getAverageTime(operation),
        total: metrics.reduce((acc, m) => acc + m.duration, 0),
        latest: metrics[metrics.length - 1]?.duration || 0,
      };
    }
    return summary;
  }
}

/**
 * Main Funculo API Client
 */
class FunculoApiClient {
  constructor() {
    this.baseUrl = `${window.wpApiSettings.root}funculo/v1`;
    this.nonce = window.wpApiSettings.nonce;

    // Caching system - stores successful responses
    this.cache = new Map();
    this.cacheTimeout = 5 * 60 * 1000; // 5 minutes default

    // Request deduplication - prevents duplicate simultaneous requests
    this.pendingRequests = new Map();

    // Performance monitoring
    this.performanceMonitor = new PerformanceMonitor();

    // Request retry configuration
    this.retryConfig = {
      maxRetries: 3,
      retryDelay: 1000, // Start with 1 second
      retryMultiplier: 2, // Double delay each retry
      retryableStatuses: [403, 408, 429, 500, 502, 503, 504], // Added 403 for nonce failures
    };

    // Statistics tracking
    this.stats = {
      requests: 0,
      cacheHits: 0,
      errors: 0,
      retries: 0,
    };
  }

  /**
   * Make a generic API request with all optimizations
   * @param {string} endpoint API endpoint (relative to base URL)
   * @param {Object} options Request options
   * @returns {Promise} API response data
   */
  async request(endpoint, options = {}) {
    const timer = this.performanceMonitor.startTiming(`request:${endpoint}`);
    this.stats.requests++;

    const { noCache = false, ...requestOptions } = options;

    try {
      // Generate cache key for this request
      const cacheKey = this.generateCacheKey(endpoint, requestOptions);
      const useCache = !noCache && this.shouldUseCache(requestOptions.method);

      // Check cache first (only for GET requests)
      if (useCache) {
        const cachedData = this.getFromCache(cacheKey);
        if (cachedData) {
          this.stats.cacheHits++;
          // console.log(`🎯 Cache hit for ${endpoint}`, cachedData);
          timer.end();
          return cachedData;
        }
      }

      // Check for pending identical requests (deduplication)
      if (this.pendingRequests.has(cacheKey)) {
        return this.pendingRequests.get(cacheKey);
      }

      // Make the actual request with retry logic
      const requestPromise = this.makeRequestWithRetry(endpoint, requestOptions);
      this.pendingRequests.set(cacheKey, requestPromise);

      try {
        const response = await requestPromise;

        // Cache successful GET responses
        if (useCache && response) {
          this.setInCache(cacheKey, response);
        }

        timer.end();
        return response;
      } finally {
        // Always clean up pending requests
        this.pendingRequests.delete(cacheKey);
      }
    } catch (error) {
      this.stats.errors++;
      timer.end();
      throw error;
    }
  }

  /**
   * Make request with retry logic
   * @param {string} endpoint API endpoint
   * @param {Object} options Request options
   * @returns {Promise} API response data
   */
  async makeRequestWithRetry(endpoint, options) {
    let lastError;

    for (let attempt = 0; attempt <= this.retryConfig.maxRetries; attempt++) {
      try {
        if (attempt > 0) {
          this.stats.retries++;
          const delay =
            this.retryConfig.retryDelay *
            Math.pow(this.retryConfig.retryMultiplier, attempt - 1);
          console.log(
            `🔄 Retrying ${endpoint} (attempt ${attempt}/${this.retryConfig.maxRetries}) after ${delay}ms`
          );
          await this.sleep(delay);
        }

        return await this.makeRawRequest(endpoint, options);
      } catch (error) {
        lastError = error;

        // Special handling for nonce errors (403 with cookie check failure)
        if (error.status === 403 && error.message?.includes("Cookie check failed")) {
          console.warn("⚠️ Nonce expired. Retrying with fresh nonce...");
          // Continue to retry - the makeRawRequest will use updated nonce
          continue;
        }

        // Don't retry on client errors (4xx) except specific ones
        if (
          error.status &&
          !this.retryConfig.retryableStatuses.includes(error.status)
        ) {
          break;
        }
      }
    }

    throw lastError;
  }

  /**
   * Make the actual HTTP request
   * @param {string} endpoint API endpoint
   * @param {Object} options Request options
   * @returns {Promise} API response data
   */
  async makeRawRequest(endpoint, options) {
    const url = `${this.baseUrl}${endpoint}`;

    // Prepare request configuration
    const config = {
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": this.nonce,
        ...options.headers,
      },
      ...options,
    };

    const response = await fetch(url, config);

    // Check if nonce needs refresh
    const newNonce = response.headers.get("X-WP-Nonce");
    if (newNonce && newNonce !== this.nonce) {
      console.log("🔄 Refreshing WordPress nonce");
      this.nonce = newNonce;
      window.wpApiSettings.nonce = newNonce;
    }

    // Handle non-JSON responses
    const contentType = response.headers.get("content-type");
    if (!contentType || !contentType.includes("application/json")) {
      if (!response.ok) {
        throw new ApiError(
          `HTTP ${response.status}: ${response.statusText}`,
          response.status,
          null,
          endpoint
        );
      }
      return response.text();
    }

    const data = await response.json();

    if (!response.ok) {
      throw new ApiError(
        data.message || `HTTP ${response.status}`,
        response.status,
        data,
        endpoint
      );
    }

    return data;
  }

  // ===========================================
  // POSTS API METHODS - Batch-Optimized
  // ===========================================

  /**
   * Get paginated posts list (optimized with bulk queries)
   * @param {Object} params Query parameters
   * @returns {Promise<Object>} Posts data with pagination
   */
  async getPosts(params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const endpoint = `/posts${queryString ? `?${queryString}` : ""}`;
    const response = await this.request(endpoint);

    // Handle new unified API response format
    if (response.success !== undefined && response.data !== undefined) {
      // New format: extract posts from data array
      const posts = this.normalizePostCollection(response.data);
      return {
        posts,
        total: response.meta?.pagination?.total || 0,
        total_pages: response.meta?.pagination?.total_pages || 0,
        current_page: response.meta?.pagination?.current_page || 1,
      };
    }

    // Old format: return as-is
    return response;
  }

  /**
   * Get single post by ID (always uses batch endpoint for consistency)
   * @param {number} id Post ID
   * @returns {Promise<Object>} Post data
   */
  async getPost(id) {
    const response = await this.request(`/post/${id}`);

    // Handle new unified API response format
    if (response.success !== undefined && response.data !== undefined) {
      return this.normalizePost(response.data);
    }

    // Old format: return as-is
    return response;
  }

  /**
   * Get multiple posts by IDs (primary method)
   * @param {Array} postIds Array of post IDs to fetch
   * @param {Object} options Fetch options
   * @returns {Promise<Object>} Batch posts result
   */
  async getBatchPosts(postIds, options = {}) {
    const { includeMeta = true } = options;
    const response = await this.request("/posts/batch", {
      method: "POST",
      body: JSON.stringify({
        post_ids: postIds,
        include_meta: includeMeta,
      }),
    });

    // Handle new unified API response format
    if (response.success !== undefined && response.data !== undefined) {
      const postsPayload = response.data.posts || response.data;
      const posts = this.normalizePostCollection(postsPayload);
      return {
        posts,
        found: response.data.found || response.meta?.found || 0,
        not_found: response.data.not_found || [],
      };
    }

    // Old format: return as-is
    return response;
  }

  /**
   * Create a new post
   * @param {Object} postData Post creation data
   * @returns {Promise<Object>} Created post data
   */
  async createPost(postData) {
    this.invalidateCache("/posts");
    const response = await this.request("/posts", {
      method: "POST",
      body: JSON.stringify(postData),
    });

    // Handle new unified API response format
    if (response.success !== undefined && response.data !== undefined) {
      return response.data;
    }

    // Old format: return as-is
    return response;
  }

  /**
   * Update single post (uses batch endpoint for consistency)
   * @param {number} id Post ID
   * @param {Object} updateData Update data
   * @returns {Promise<Object>} Updated post data
   */
  async updatePost(id, updateData) {
    const result = await this.batchUpdatePosts([{ post_id: id, ...updateData }]);
    if (result.successful && result.successful.length > 0) {
      return result.successful[0];
    }
    if (result.failed && result.failed.length > 0) {
      throw new Error(result.failed[0].error);
    }
    throw new Error("Update failed");
  }

  /**
   * Update multiple posts (primary method)
   * @param {Array} updates Array of post updates
   * @returns {Promise<Object>} Bulk update result
   */
  async batchUpdatePosts(updates) {
    this.invalidateCache("/posts");
    const response = await this.request("/posts/batch-update", {
      method: "PUT",
      body: JSON.stringify({ updates }),
    });

    // Handle new unified API response format
    if (response.success !== undefined && response.data !== undefined) {
      return {
        successful: response.data.successful || [],
        failed: response.data.failed || [],
        total: response.meta?.total || updates.length,
      };
    }

    // Old format: return as-is
    return response;
  }

  /**
   * Delete a post (uses bulk operations for consistency)
   * @param {number} id Post ID
   * @returns {Promise<Object>} Deletion result
   */
  async deletePost(id) {
    this.invalidateCache("/posts");
    const response = await this.request(`/post/${id}`, {
      method: "DELETE",
    });

    // Handle new unified API response format
    if (response.success !== undefined) {
      return {
        success: response.success,
        message:
          response.meta?.message ||
          response.data?.message ||
          "Post deleted successfully",
      };
    }

    // Old format: return as-is
    return response;
  }

  // ===========================================
  // SCSS COMPILER API METHODS
  // ===========================================

  /**
   * Get SCSS partials
   * @returns {Promise<Object>} SCSS partials data
   */
  async getScssPartials() {
    const response = await this.request("/scss-partials");

    // Handle new unified API response format
    if (response.success !== undefined && response.data !== undefined) {
      return response.data;
    }

    // Old format: return as-is
    return response;
  }

  /**
   * Get SCSS content for a post
   * @param {number} id Post ID
   * @returns {Promise<Object>} SCSS content data
   */
  async getScssContent(id) {
    const response = await this.request(`/post/${id}/scss`);

    // Handle new unified API response format
    if (response.success !== undefined && response.data !== undefined) {
      return response.data;
    }

    return response;
  }

  /**
   * Save compiled SCSS and CSS
   * @param {number} id Post ID
   * @param {Object} data SCSS/CSS data
   * @returns {Promise<Object>} Save result
   */
  async saveScssContent(id, data) {
    this.invalidateCache(`/post/${id}/scss`); // Clear SCSS cache
    const response = await this.request(`/post/${id}/scss`, {
      method: "POST",
      body: JSON.stringify(data),
    });

    // Handle new unified API response format
    if (response.success !== undefined && response.data !== undefined) {
      return response.data;
    }

    return response;
  }

  /**
   * Save editor SCSS content and compiled CSS
   * @param {number} id Post ID
   * @param {Object} data Editor SCSS and CSS data
   * @returns {Promise<Object>} Save result
   */
  async saveEditorScssContent(id, data) {
    this.invalidateCache(`/post/${id}/editor-scss`); // Clear editor SCSS cache
    const response = await this.request(`/post/${id}/editor-scss`, {
      method: "POST",
      body: JSON.stringify(data),
    });

    // Handle new unified API response format
    if (response.success !== undefined && response.data !== undefined) {
      return response.data;
    }

    return response;
  }

  /**
   * Update partial global settings
   * @param {number} id Post ID
   * @param {Object} settings Global settings
   * @returns {Promise<Object>} Update result
   */
  async updatePartialGlobalSettings(id, settings) {
    this.invalidateCache("/scss-partials"); // Clear partials cache
    const response = await this.request(`/scss-partial/${id}/global-setting`, {
      method: "POST",
      body: JSON.stringify(settings),
    });

    // Handle new unified API response format
    if (response.success !== undefined && response.data !== undefined) {
      return response.data;
    }

    return response;
  }

  // ===========================================
  // FILE GENERATION API METHODS
  // ===========================================

  /**
   * Regenerate all files
   * @returns {Promise<Object>} Regeneration result
   */
  async regenerateFiles() {
    const response = await this.request("/regenerate-files", {
      method: "POST",
    });

    // Handle new unified API response format
    if (response.success !== undefined) {
      return {
        success: response.success,
        message:
          response.meta?.message ||
          response.data?.message ||
          "Files regenerated successfully",
      };
    }

    return response;
  }

  /**
   * Force regenerate all files
   * @returns {Promise<Object>} Force regeneration result
   */
  async forceRegenerateAll() {
    const response = await this.request("/force-regenerate-all", {
      method: "POST",
    });

    // Handle new unified API response format
    if (response.success !== undefined) {
      return {
        success: response.success,
        message:
          response.meta?.message ||
          response.data?.message ||
          "All files forcefully regenerated",
      };
    }

    return response;
  }

  // ===========================================
  // OTHER API METHODS
  // ===========================================

  /**
   * Get block categories
   * @returns {Promise<Array>} Block categories
   */
  async getBlockCategories() {
    const response = await this.request("/block-categories");

    // Handle new unified API response format
    if (response.success !== undefined && response.data !== undefined) {
      return response.data;
    }

    // Old format: return as-is
    return response;
  }

  /**
   * Get taxonomy terms
   * @returns {Promise<Array>} Taxonomy terms
   */
  async getTaxonomyTerms() {
    const response = await this.request("/taxonomy");

    // Handle new unified API response format
    if (response.success !== undefined && response.data !== undefined) {
      return response.data;
    }

    // Old format: return as-is
    return response;
  }

  /**
   * Get all registered blocks
   * @returns {Promise<Object>} All registered blocks data
   */
  async getRegisteredBlocks() {
    const response = await this.request("/registered-blocks");

    // Handle new unified API response format
    if (response.success !== undefined && response.data !== undefined) {
      return response.data;
    }

    // Old format: return as-is
    return response;
  }

  // ===========================================
  // PRIMARY BATCH OPERATIONS - Optimized Interface
  // ===========================================

  /**
   * Get post with all related data (primary method for single post)
   * @param {number} id Post ID
   * @returns {Promise<Object>} Post with all related data
   */
  async getPostWithRelated(id) {
    try {
      const response = await this.request(`/post/${id}/with-related`);

      // Handle new unified API response format
      if (response.success !== undefined && response.data !== undefined) {
        const normalized = this.normalizePostWithRelatedPayload(response.data);
        return normalized;
      }

      return response;
    } catch (error) {
      console.error('FunculoApiClient getPostWithRelated error:', error);
      throw error;
    }
  }

  /**
   * Get multiple posts with their partials (primary method for multiple posts)
   * @param {Array} postIds Array of post IDs
   * @returns {Promise<Object>} Posts with partials data
   */
  async getPostsWithPartials(postIds) {
    const postsResult = await this.getBatchPosts(postIds, {
      includeMeta: true,
    });
    const partialsData = await this.getScssPartials();

    return {
      posts: postsResult.posts,
      partials: partialsData,
      found: postsResult.found,
      not_found: postsResult.not_found,
    };
  }

  /**
   * Save post with related operations (primary save method)
   * Combines multiple operations into a single request
   * @param {number} postId Post ID
   * @param {Object} metaData Meta data to update
   * @param {boolean} regenerateFiles Whether to regenerate files
   * @returns {Promise<Object>} Combined operation result
   */
  async savePostWithOperations(postId, metaData, regenerateFiles = true) {
    const operations = [
      {
        type: "update_meta",
        data: { post_id: postId, meta: metaData },
      },
    ];

    if (regenerateFiles) {
      operations.push({
        type: "regenerate_files",
        data: { post_id: postId },
      });
    }

    return this.executeBulkOperations(operations);
  }

  /**
   * Batch compile multiple SCSS files (primary compilation method)
   * @param {Array} compilations Array of SCSS compilation data
   * @returns {Promise<Object>} Batch compilation result
   */
  async batchCompileScss(compilations) {
    const response = await this.request("/scss/compile-batch", {
      method: "POST",
      body: JSON.stringify({ compilations }),
    });

    // Handle new unified API response format
    if (response.success !== undefined && response.data !== undefined) {
      return {
        successful: response.data.successful || [],
        failed: response.data.failed || [],
        total: response.meta?.total || compilations.length,
      };
    }

    return response;
  }

  /**
   * Execute multiple operations in a single request (core batch method)
   * @param {Array} operations Array of operations to execute
   * @returns {Promise<Object>} Bulk operations result
   */
  async executeBulkOperations(operations) {
    const response = await this.request("/operations/bulk", {
      method: "POST",
      body: JSON.stringify({ operations }),
    });

    // Handle new unified API response format
    if (response.success !== undefined && response.data !== undefined) {
      return {
        successful: response.data.successful || [],
        failed: response.data.failed || [],
        total: response.meta?.total || operations.length,
      };
    }

    return response;
  }

  /**
   * Smart batch update with automatic grouping
   * Groups multiple pending updates and sends them efficiently
   * @param {number} postId Post ID
   * @param {Object} updateData Update data
   * @param {Object} options Update options
   * @returns {Promise<Object>} Update result
   */
  async smartUpdatePost(postId, updateData, options = {}) {
    const { batchDelay = 50, regenerateFiles = false } = options; // Reduced delay for new plugin

    if (!this.batchQueue) {
      this.batchQueue = new Map();
      this.batchTimeouts = new Map();
    }

    this.batchQueue.set(postId, {
      ...updateData,
      regenerate_files: regenerateFiles,
    });

    if (this.batchTimeouts.has(postId)) {
      clearTimeout(this.batchTimeouts.get(postId));
    }

    return new Promise((resolve, reject) => {
      const timeoutId = setTimeout(async () => {
        try {
          const updates = Array.from(this.batchQueue.entries()).map(
            ([id, data]) => ({
              id,
              ...data,
            })
          );

          this.batchQueue.clear();
          this.batchTimeouts.clear();

          const result = await this.batchUpdatePosts(updates);
          resolve(result);
        } catch (error) {
          reject(error);
        }
      }, batchDelay);

      this.batchTimeouts.set(postId, timeoutId);
    });
  }

  // ===========================================
  // CACHE MANAGEMENT
  // ===========================================

  /**
   * Generate cache key for a request
   * @param {string} endpoint API endpoint
   * @param {Object} options Request options
   * @returns {string} Cache key
   */
  generateCacheKey(endpoint, options) {
    const method = options.method || "GET";
    const body = options.body || "";
    return `${method}:${endpoint}:${btoa(body).slice(0, 10)}`;
  }

  /**
   * Check if request should use cache
   * @param {string} method HTTP method
   * @returns {boolean} Should use cache
   */
  shouldUseCache(method) {
    return !method || method === "GET";
  }

  /**
   * Get data from cache
   * @param {string} cacheKey Cache key
   * @returns {*} Cached data or null
   */
  getFromCache(cacheKey) {
    const cached = this.cache.get(cacheKey);
    if (!cached) return null;

    // Check if cache has expired
    if (Date.now() > cached.expires) {
      this.cache.delete(cacheKey);
      return null;
    }

    return cached.data;
  }

  /**
   * Store data in cache
   * @param {string} cacheKey Cache key
   * @param {*} data Data to cache
   * @param {number} ttl Time to live in milliseconds
   */
  setInCache(cacheKey, data, ttl = this.cacheTimeout) {
    this.cache.set(cacheKey, {
      data: data,
      expires: Date.now() + ttl,
    });
  }

  /**
   * Invalidate cache entries matching pattern
   * @param {string} pattern Pattern to match against cache keys
   */
  invalidateCache(pattern) {
    let invalidated = 0;
    for (const key of this.cache.keys()) {
      if (key.includes(pattern)) {
        this.cache.delete(key);
        invalidated++;
      }
    }
  }

  /**
   * Clear all cache
   */
  clearCache() {
    this.cache.clear();
  }

  /**
   * Ensure both camelCase and snake_case keys are exposed on block meta
   * @param {Object} blocksMeta Blocks meta object
   * @returns {Object} Normalized blocks meta
   */
  normalizeBlocksMeta(blocksMeta) {
    if (!blocksMeta || typeof blocksMeta !== "object") {
      return blocksMeta;
    }

    const normalized = { ...blocksMeta };

    if (
      normalized.selected_partials === undefined &&
      normalized.selectedPartials !== undefined
    ) {
      normalized.selected_partials = normalized.selectedPartials;
    }

    if (
      normalized.selectedPartials === undefined &&
      normalized.selected_partials !== undefined
    ) {
      normalized.selectedPartials = normalized.selected_partials;
    }

    if (
      normalized.editor_selected_partials === undefined &&
      normalized.editorSelectedPartials !== undefined
    ) {
      normalized.editor_selected_partials =
        normalized.editorSelectedPartials;
    }

    if (
      normalized.editorSelectedPartials === undefined &&
      normalized.editor_selected_partials !== undefined
    ) {
      normalized.editorSelectedPartials =
        normalized.editor_selected_partials;
    }

    return normalized;
  }

  /**
   * Normalize meta object to expose consistent key casing for consumers
   * @param {Object} meta Post meta object
   * @returns {Object} Normalized meta
   */
  normalizeMeta(meta) {
    if (!meta || typeof meta !== "object") {
      return meta;
    }

    if (!meta.blocks) {
      return { ...meta };
    }

    return {
      ...meta,
      blocks: this.normalizeBlocksMeta(meta.blocks),
    };
  }

  /**
   * Normalize a single post object
   * @param {Object} post Post object
   * @returns {Object} Normalized post
   */
  normalizePost(post) {
    if (!post || typeof post !== "object") {
      return post;
    }

    if (!post.meta) {
      return { ...post };
    }

    return {
      ...post,
      meta: this.normalizeMeta(post.meta),
    };
  }

  /**
   * Normalize a collection of posts
   * @param {Array} posts Posts array
   * @returns {Array} Normalized posts
   */
  normalizePostCollection(posts) {
    if (!Array.isArray(posts)) {
      return posts;
    }

    return posts.map((post) => this.normalizePost(post));
  }

  /**
   * Normalize payloads that contain a `post` property with meta
   * @param {Object} payload API payload
   * @returns {Object} Normalized payload
   */
  normalizePostWithRelatedPayload(payload) {
    if (!payload || typeof payload !== "object") {
      return payload;
    }

    if (!payload.post) {
      return payload;
    }

    const normalizedPost = this.normalizePost(payload.post);

    // Merge global_settings from related data into post.meta.scss_partials
    // Note: API response keys are transformed to camelCase (is_global → isGlobal)
    const globalSettings = payload.related?.globalSettings || payload.related?.global_settings;

    if (globalSettings) {
      if (!normalizedPost.meta) {
        normalizedPost.meta = {};
      }
      if (!normalizedPost.meta.scss_partials) {
        normalizedPost.meta.scss_partials = {};
      }

      // Handle both camelCase (from API transformation) and snake_case (if transformation disabled)
      const isGlobal = globalSettings.isGlobal !== undefined ? globalSettings.isGlobal : globalSettings.is_global;
      const globalOrder = globalSettings.globalOrder !== undefined ? globalSettings.globalOrder : globalSettings.global_order;

      // Convert to strings that React component expects
      normalizedPost.meta.scss_partials.is_global = (isGlobal === true || isGlobal === '1' || isGlobal === 1) ? '1' : '0';
      normalizedPost.meta.scss_partials.global_order = String(globalOrder || 1);
    }

    return {
      ...payload,
      post: normalizedPost,
    };
  }

  // ===========================================
  // UTILITIES
  // ===========================================

  /**
   * Sleep utility for retry delays
   * @param {number} ms Milliseconds to sleep
   * @returns {Promise} Promise that resolves after delay
   */
  sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }

  /**
   * Get API client statistics
   * @returns {Object} Statistics object
   */
  getStats() {
    return {
      ...this.stats,
      cacheSize: this.cache.size,
      pendingRequests: this.pendingRequests.size,
      cacheHitRate:
        this.stats.requests > 0
          ? ((this.stats.cacheHits / this.stats.requests) * 100).toFixed(2) +
            "%"
          : "0%",
      performance: this.performanceMonitor.exportMetrics(),
    };
  }

  /**
   * Log current statistics to console
   */
  logStats() {}
}

// Create and export singleton instance
export const apiClient = new FunculoApiClient();

// Expose to global scope for debugging
window.funculoApiClient = apiClient;

export default apiClient;
