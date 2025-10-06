import { useState, useCallback } from "react";
import centralizedApi from "../../utils/api/CentralizedApiService";

/**
 * Hook for managing post revisions
 *
 * Provides state and methods for creating, loading, applying, and deleting revisions.
 */
const useRevisions = (postId) => {
  const [revisions, setRevisions] = useState([]);
  const [loading, setLoading] = useState(false);
  const [applying, setApplying] = useState(false);
  const [error, setError] = useState(null);

  /**
   * Load revisions for current post
   */
  const loadRevisions = useCallback(async () => {
    if (!postId) {
      setRevisions([]);
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const response = await centralizedApi.getRevisions(postId);
      setRevisions(response?.data || []);
    } catch (err) {
      console.error("Error loading revisions:", err);
      setError(err.message || "Failed to load revisions");
      setRevisions([]);
    } finally {
      setLoading(false);
    }
  }, [postId]);

  /**
   * Create a new revision
   */
  const createRevision = useCallback(
    async (name) => {
      if (!postId || !name) {
        throw new Error("Post ID and revision name are required");
      }

      setError(null);

      try {
        const response = await centralizedApi.createRevision(postId, name);

        // Reload revisions to include the new one
        await loadRevisions();

        return response;
      } catch (err) {
        console.error("Error creating revision:", err);
        setError(err.message || "Failed to create revision");
        throw err;
      }
    },
    [postId, loadRevisions]
  );

  /**
   * Apply a revision to restore previous state
   */
  const applyRevision = useCallback(async (revisionId) => {
    if (!revisionId) {
      throw new Error("Revision ID is required");
    }

    setApplying(true);
    setError(null);

    try {
      const response = await centralizedApi.applyRevision(revisionId);
      return response;
    } catch (err) {
      console.error("Error applying revision:", err);
      setError(err.message || "Failed to apply revision");
      throw err;
    } finally {
      setApplying(false);
    }
  }, []);

  /**
   * Delete a revision
   */
  const deleteRevision = useCallback(
    async (revisionId) => {
      if (!revisionId) {
        throw new Error("Revision ID is required");
      }

      setError(null);

      try {
        await centralizedApi.deleteRevision(revisionId);

        // Reload revisions to reflect deletion
        await loadRevisions();
      } catch (err) {
        console.error("Error deleting revision:", err);
        setError(err.message || "Failed to delete revision");
        throw err;
      }
    },
    [loadRevisions]
  );

  return {
    revisions,
    loading,
    applying,
    error,
    loadRevisions,
    createRevision,
    applyRevision,
    deleteRevision,
  };
};

export default useRevisions;
