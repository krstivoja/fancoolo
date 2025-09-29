import React, { useState, useEffect } from 'react';
import ScssPartialsManager from './ScssPartialsManager';
import { apiClient } from '../../../utils';
import centralizedApi from '../../../utils/api/CentralizedApiService';

const ScssPartialsCombined = ({ selectedPost, metaData, onMetaChange }) => {
  const [globalPartials, setGlobalPartials] = useState([]);
  const [loading, setLoading] = useState(true);

  // Load global partials data on component mount
  useEffect(() => {
    if (selectedPost?.id) {
      loadGlobalPartials();
    }
  }, [selectedPost?.id]);

  const loadGlobalPartials = async () => {
    try {
      const data = await centralizedApi.getScssPartials();
      console.log('SCSS Partials API Response:', data);
      setGlobalPartials(data.global_partials || []);
    } catch (error) {
      console.error('Error loading partials:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <div className="p-4 text-center text-contrast">Loading partials...</div>;
  }

  return (
    <div className="flex flex-col h-full p-4 space-y-6 overflow-y-auto">
      {/* Global Partials Section - Shown once at the top */}
      {globalPartials.length > 0 && (
        <div>
          <h4 className="font-medium text-highlight mb-3 flex items-center gap-2">
            🌍 Global Partials
            <span className="text-xs bg-action text-white px-2 py-1 rounded">Auto-included</span>
          </h4>
          <div className="space-y-2">
            {globalPartials.map((partial) => (
              <div
                key={partial.id}
                className="flex items-center gap-3 p-3 bg-base-2 border border-outline rounded opacity-75"
              >
                <span className="flex-1 text-sm">{partial.title}</span>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Style Partials Section */}
      <div className="pt-4 border-t border-outline">
        <h4 className="font-medium text-highlight mb-3">Frontend Style Partials</h4>
        <div className="pl-2">
          <ScssPartialsManager
            selectedPost={selectedPost}
            metaData={metaData}
            onMetaChange={onMetaChange}
            mode="style"
            hideGlobalPartials={true}
          />
        </div>
      </div>

      {/* Editor Style Partials Section */}
      <div className="pt-4 border-t border-outline">
        <h4 className="font-medium text-highlight mb-3">Editor Style Partials</h4>
        <div className="pl-2">
          <ScssPartialsManager
            selectedPost={selectedPost}
            metaData={metaData}
            onMetaChange={onMetaChange}
            mode="editorStyle"
            hideGlobalPartials={true}
          />
        </div>
      </div>
    </div>
  );
};

export default ScssPartialsCombined;