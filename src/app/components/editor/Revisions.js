import React, { useState, useEffect } from "react";
import { Button, Input } from "../ui";
import { TrashIcon } from "../icons";
import { useRevisions } from "../../hooks";

const Revisions = ({ selectedPost, onRevisionApply }) => {
  const { revisions, loading, applying, loadRevisions, createRevision, applyRevision, deleteRevision } = useRevisions(
    selectedPost?.id
  );

  const [revisionName, setRevisionName] = useState("");
  const [selectedRevision, setSelectedRevision] = useState("current");
  const [isCreating, setIsCreating] = useState(false);

  // Load revisions when post changes
  useEffect(() => {
    if (selectedPost?.id) {
      loadRevisions();
    }
  }, [selectedPost?.id, loadRevisions]);

  // Reset selected revision to current when post changes
  useEffect(() => {
    setSelectedRevision("current");
  }, [selectedPost?.id]);

  const handleCreateRevision = async () => {
    if (!revisionName.trim()) {
      alert("Please enter a revision name");
      return;
    }

    setIsCreating(true);

    try {
      await createRevision(revisionName.trim());
      setRevisionName("");
      alert("Revision created successfully");
    } catch (error) {
      alert("Failed to create revision: " + (error.message || "Unknown error"));
    } finally {
      setIsCreating(false);
    }
  };

  const handleApplyRevision = async () => {
    if (selectedRevision === "current") {
      return;
    }

    const confirmed = window.confirm(
      "Are you sure you want to apply this revision? This will overwrite your current changes."
    );

    if (!confirmed) {
      return;
    }

    try {
      await applyRevision(selectedRevision);
      alert("Revision applied successfully");

      // Notify parent to reload post data
      if (onRevisionApply) {
        onRevisionApply(selectedPost.id);
      }

      // Reset to current
      setSelectedRevision("current");
    } catch (error) {
      alert("Failed to apply revision: " + (error.message || "Unknown error"));
    }
  };

  const handleDeleteRevision = async (revisionId, revisionName) => {
    const confirmed = window.confirm(`Are you sure you want to delete the revision "${revisionName}"?`);

    if (!confirmed) {
      return;
    }

    try {
      await deleteRevision(revisionId);

      // If we had this revision selected, reset to current
      if (selectedRevision === revisionId) {
        setSelectedRevision("current");
      }
    } catch (error) {
      alert("Failed to delete revision: " + (error.message || "Unknown error"));
    }
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  };

  if (!selectedPost) {
    return null;
  }

  return (
    <div className="flex-1 p-4 overflow-y-auto">
      <div className="space-y-6">
        {/* Create Revision Section */}
        <div className="space-y-3">
          <h4 className="font-medium text-highlight">Create Revision</h4>
          <div className="space-y-2">
            <Input
              type="text"
              value={revisionName}
              onChange={(e) => setRevisionName(e.target.value)}
              placeholder="Enter revision name..."
              disabled={isCreating}
            />
            <Button
              onClick={handleCreateRevision}
              disabled={isCreating || !revisionName.trim()}
              variant="primary"
              className="w-full"
            >
              {isCreating ? "Creating..." : "Save Revision"}
            </Button>
          </div>
        </div>

        {/* Revisions List */}
        <div className="space-y-3">
          <h4 className="font-medium text-highlight">Revision History</h4>

          {loading && <p className="text-sm text-contrast">Loading revisions...</p>}

          {!loading && (
            <div className="space-y-2">
              {/* Current State */}
              <label className="flex items-center gap-3 p-3 border border-solid border-outline rounded-md cursor-pointer hover:bg-base-2 transition-colors">
                <input
                  type="radio"
                  name="revision"
                  value="current"
                  checked={selectedRevision === "current"}
                  onChange={(e) => setSelectedRevision(e.target.value)}
                  className="flex-shrink-0"
                />
                <span className="flex-1 font-medium text-highlight">Current</span>
              </label>

              {/* Saved Revisions */}
              {revisions.length === 0 && (
                <p className="text-sm text-contrast p-3 text-center">No revisions yet. Create one to get started!</p>
              )}

              {revisions.map((revision) => (
                <label
                  key={revision.id}
                  className="flex items-center gap-3 p-3 border border-solid border-outline rounded-md cursor-pointer hover:bg-base-2 transition-colors"
                >
                  <input
                    type="radio"
                    name="revision"
                    value={revision.id}
                    checked={selectedRevision === revision.id}
                    onChange={(e) => setSelectedRevision(parseInt(e.target.value))}
                    className="flex-shrink-0"
                  />
                  <div className="flex-1 min-w-0">
                    <div className="font-medium text-highlight truncate">{revision.revision_name}</div>
                    <div className="text-xs text-contrast">{formatDate(revision.created_at)}</div>
                  </div>
                  <button
                    onClick={(e) => {
                      e.preventDefault();
                      handleDeleteRevision(revision.id, revision.revision_name);
                    }}
                    className="flex-shrink-0 p-2 text-contrast hover:text-highlight transition-colors"
                    title="Delete revision"
                  >
                    <TrashIcon className="w-4 h-4" />
                  </button>
                </label>
              ))}
            </div>
          )}
        </div>

        {/* Apply Button */}
        {selectedRevision !== "current" && (
          <Button onClick={handleApplyRevision} disabled={applying} variant="primary" className="w-full">
            {applying ? "Applying..." : "Apply State"}
          </Button>
        )}
      </div>
    </div>
  );
};

export default Revisions;
