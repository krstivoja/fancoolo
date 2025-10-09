import React, { useState, useEffect } from "react";
import { Toggle, Input } from "../ui";

const ScssPartialSettings = ({ selectedPost, metaData, onMetaChange }) => {
  const [isGlobal, setIsGlobal] = useState(false);
  const [globalOrder, setGlobalOrder] = useState(1);

  const scssPartials = metaData?.scss_partials || {};

  // Load global settings from metaData
  useEffect(() => {
    if (scssPartials.is_global !== undefined) {
      // Handle string '1'/'0', number 1/0, or boolean true/false
      const isGlobalValue =
        scssPartials.is_global === "1" ||
        scssPartials.is_global === 1 ||
        scssPartials.is_global === true;
      setIsGlobal(isGlobalValue);
    } else {
      // Default to false if not set
      setIsGlobal(false);
    }

    if (scssPartials.global_order !== undefined) {
      const order = parseInt(scssPartials.global_order) || 1;
      setGlobalOrder(order);
    } else {
      // Default to 1 if not set
      setGlobalOrder(1);
    }
  }, [scssPartials.is_global, scssPartials.global_order, selectedPost?.id]);

  // Update metaData when settings change (requires save button - no immediate API call)
  const updateGlobalSettings = (newIsGlobal, newGlobalOrder) => {
    if (onMetaChange) {
      // Update the is_global field
      onMetaChange("scss_partials", "is_global", newIsGlobal ? "1" : "0");
      // Update the global_order field
      onMetaChange("scss_partials", "global_order", String(newGlobalOrder));
    }
  };

  const handleGlobalToggle = () => {
    const newGlobalValue = !isGlobal;
    setIsGlobal(newGlobalValue);
    updateGlobalSettings(newGlobalValue, globalOrder);
  };

  const handleGlobalOrderChange = (e) => {
    const newOrder = parseInt(e.target.value) || 1;
    setGlobalOrder(newOrder);
    updateGlobalSettings(isGlobal, newOrder);
  };

  return (
    <div className="space-y-4">
      <h4 className="font-medium text-highlight">Global SCSS Partials</h4>

      <div className="space-y-3">
        <Toggle
          checked={isGlobal}
          onChange={handleGlobalToggle}
          label="Include in all blocks"
        />

        {isGlobal && (
          <div className="mt-4">
            <label className="block text-sm font-medium text-contrast mb-1">
              Load Order
            </label>
            <Input
              type="number"
              value={globalOrder}
              onChange={handleGlobalOrderChange}
              min="1"
            />
            <p className="text-xs text-contrast mt-1">
              Lower numbers load first
            </p>
          </div>
        )}
      </div>
    </div>
  );
};

export default ScssPartialSettings;
