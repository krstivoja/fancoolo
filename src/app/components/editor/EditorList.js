import React, { useState, useEffect } from "react";
import { TAXONOMY_TERMS } from "../../constants/taxonomy";
import { Button } from "../ui";
import CopyIcon from "../icons/CopyIcon";

// Convert slug to PascalCase component name (e.g., "social-icons" -> "SocialIcons")
const formatSlugToComponentName = (slug) => {
  return slug
    .split("-")
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join("");
};

const getPostIcon = (post, termSlug) => {
  // Only show icons for blocks
  if (termSlug !== "blocks") {
    return null;
  }

  // For blocks, check if there's a custom icon in settings
  if (post.meta?.blocks?.settings) {
    try {
      const settings =
        typeof post.meta.blocks.settings === "string"
          ? JSON.parse(post.meta.blocks.settings)
          : post.meta.blocks.settings;

      if (settings?.icon) {
        return `dashicons-${settings.icon}`;
      }
    } catch (e) {
      // Silently handle parsing errors
    }
  }

  // Default icon for blocks
  return "dashicons-search";
};

const EditorList = ({ groupedPosts, selectedPost, onPostSelect }) => {
  const [activeTab, setActiveTab] = useState("blocks");
  const [copiedId, setCopiedId] = useState(null);

  // Sync active tab with selected post's taxonomy type (blocks/symbols/scss-partials)
  useEffect(() => {
    if (selectedPost?.terms?.[0]?.slug) {
      setActiveTab(selectedPost.terms[0].slug);
    }
  }, [selectedPost?.id]);

  const handleCopySymbol = (e, post) => {
    e.stopPropagation();
    const componentName = formatSlugToComponentName(post.slug);
    const formattedComponent = `<${componentName} />`;

    // Try modern clipboard API first, fallback to older method
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard
        .writeText(formattedComponent)
        .then(() => {
          setCopiedId(post.id);
          setTimeout(() => setCopiedId(null), 2000);
        })
        .catch((err) => {
          console.error("Failed to copy:", err);
        });
    } else {
      // Fallback for older browsers or non-secure contexts
      const textarea = document.createElement("textarea");
      textarea.value = formattedComponent;
      textarea.style.position = "fixed";
      textarea.style.opacity = "0";
      document.body.appendChild(textarea);
      textarea.select();
      try {
        document.execCommand("copy");
        setCopiedId(post.id);
        setTimeout(() => setCopiedId(null), 2000);
      } catch (err) {
        console.error("Failed to copy:", err);
      }
      document.body.removeChild(textarea);
    }
  };

  return (
    <aside
      id="editor-list"
      className="flex flex-col h-full border-r border-solid border-outline w-[400px]"
    >
      {/* Tab Navigation */}
      <div className="p-3">
        <div className="flex p-1 border border-solid border-outline rounded-md bg-base-2">
          {TAXONOMY_TERMS.map((term) => {
            const IconComponent = term.icon;
            return (
              <Button
                key={term.slug}
                variant={activeTab === term.slug ? "primary" : "ghost"}
                className="grow flex items-center justify-center gap-2"
                onClick={() => setActiveTab(term.slug)}
              >
                {IconComponent && <IconComponent size={16} />}
                {term.name}
              </Button>
            );
          })}
        </div>
      </div>

      {/* Tab Content */}
      <div className="flex-1 overflow-hidden min-h-0 px-4 pb-4">
        {TAXONOMY_TERMS.map((term) => {
          const posts = groupedPosts[term.slug];
          const isActive = activeTab === term.slug;

          return (
            <div
              key={term.slug}
              className={`h-full ${isActive ? "block" : "hidden"}`}
            >
              {posts.length === 0 ? (
                <p>No {term.name.toLowerCase()} found</p>
              ) : (
                <ul className="overflow-y-auto h-full">
                  {posts.map((post) => {
                    const postTermSlug = post.terms?.[0]?.slug || term.slug;
                    const iconClass = getPostIcon(post, postTermSlug);

                    const isSymbol = postTermSlug === "symbols";
                    const isCopied = copiedId === post.id;

                    return (
                      <div
                        key={post.id}
                        className="flex items-center gap-2 mb-2"
                      >
                        <li
                          onClick={() => onPostSelect(post)}
                          className={`flex-1 cursor-pointer p-2 rounded flex items-center gap-2 !m-0 ${
                            selectedPost?.id === post.id
                              ? "bg-action text-highlight hover:bg-action"
                              : "hover:bg-action/10"
                          }`}
                        >
                          {iconClass && (
                            <span
                              className={`dashicons ${iconClass} text-sm`}
                            ></span>
                          )}
                          <span className="flex-1">{post.title}</span>
                        </li>
                        {isSymbol && (
                          <Button
                            onClick={(e) => handleCopySymbol(e, post)}
                            variant="ghost"
                            className="!p-2 flex-shrink-0"
                            title={isCopied ? "Copied!" : "Copy as component"}
                          >
                            {isCopied ? (
                              <span className="text-xs">✓</span>
                            ) : (
                              <CopyIcon size={16} />
                            )}
                          </Button>
                        )}
                      </div>
                    );
                  })}
                </ul>
              )}
            </div>
          );
        })}
      </div>
    </aside>
  );
};

export default EditorList;
