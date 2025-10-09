<?php

namespace FanCoolo\Admin\Api\Controllers;

use FanCoolo\Database\RevisionsRepository;
use FanCoolo\Services\RevisionSnapshotService;

/**
 * Revisions API Controller
 *
 * Provides REST API endpoints for managing post revisions.
 * Handles creating, retrieving, applying, and deleting revision snapshots.
 */
class RevisionsApiController extends BaseApiController
{
    public function registerRoutes()
    {
        // Create a new revision
        register_rest_route('funculo/v1', '/revisions', [
            'methods' => 'POST',
            'callback' => [$this, 'createRevision'],
            'permission_callback' => [$this, 'checkCreatePermissions'],
            'args' => [
                'post_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ],
                'name' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($param) {
                        return !empty(trim($param));
                    }
                ]
            ]
        ]);

        // Get all revisions for a post
        register_rest_route('funculo/v1', '/revisions/(?P<post_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'getRevisions'],
            'permission_callback' => [$this, 'checkPermissions'],
            'args' => [
                'post_id' => [
                    'required' => true,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ]
            ]
        ]);

        // Apply a revision
        register_rest_route('funculo/v1', '/revisions/(?P<id>\d+)/apply', [
            'methods' => 'POST',
            'callback' => [$this, 'applyRevision'],
            'permission_callback' => [$this, 'checkCreatePermissions'],
            'args' => [
                'id' => [
                    'required' => true,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ]
            ]
        ]);

        // Delete a revision
        register_rest_route('funculo/v1', '/revisions/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'deleteRevision'],
            'permission_callback' => [$this, 'checkCreatePermissions'],
            'args' => [
                'id' => [
                    'required' => true,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ]
            ]
        ]);
    }

    /**
     * Create a new revision
     */
    public function createRevision($request)
    {
        try {
            $post_id = $request->get_param('post_id');
            $name = $request->get_param('name');

            error_log('FanCoolo: Creating revision for post ' . $post_id . ' with name: ' . $name);

            // Verify post exists
            $post = get_post($post_id);
            if (!$post) {
                error_log('FanCoolo Error: Post not found - ' . $post_id);
                return $this->responseFormatter->notFound('post', $post_id);
            }

            error_log('FanCoolo: Post found, creating snapshot...');

            // Create snapshot
            try {
                $snapshot = RevisionSnapshotService::createSnapshot($post_id);
                error_log('FanCoolo: Snapshot created successfully: ' . json_encode(array_keys($snapshot)));
            } catch (\Throwable $e) {
                error_log('FanCoolo Error: Snapshot creation failed - ' . $e->getMessage());
                error_log('FanCoolo Error: Snapshot trace - ' . $e->getTraceAsString());
                throw $e;
            }

            error_log('FanCoolo: Snapshot created, saving to database...');

            // Save revision
            $revision_id = RevisionsRepository::create($post_id, $name, $snapshot);

            error_log('FanCoolo: Revision saved with ID: ' . $revision_id);

            // Return created revision
            $revision = RevisionsRepository::getById($revision_id);

            error_log('FanCoolo: Returning revision data');

            return $this->responseFormatter->created($revision, [
                'message' => 'Revision created successfully'
            ]);
        } catch (\Exception $e) {
            error_log('FanCoolo Error: Failed to create revision - ' . $e->getMessage());
            error_log('FanCoolo Error: Stack trace - ' . $e->getTraceAsString());
            return $this->responseFormatter->serverError('Failed to create revision: ' . $e->getMessage());
        }
    }

    /**
     * Get all revisions for a post
     */
    public function getRevisions($request)
    {
        try {
            $post_id = $request->get_param('post_id');

            // Verify post exists
            $post = get_post($post_id);
            if (!$post) {
                return $this->responseFormatter->notFound('post', $post_id);
            }

            // Get revisions
            $revisions = RevisionsRepository::getByPostId($post_id);

            return $this->responseFormatter->collection($revisions, [
                'post_id' => $post_id,
                'count' => count($revisions)
            ]);
        } catch (\Exception $e) {
            error_log('FanCoolo Error: Failed to get revisions - ' . $e->getMessage());
            return $this->responseFormatter->serverError('Failed to get revisions: ' . $e->getMessage());
        }
    }

    /**
     * Apply a revision to restore previous state
     */
    public function applyRevision($request)
    {
        try {
            $revision_id = $request->get_param('id');

            // Get revision
            $revision = RevisionsRepository::getById($revision_id);
            if (!$revision) {
                return $this->responseFormatter->notFound('revision', $revision_id);
            }

            $post_id = $revision['post_id'];

            // Verify post exists
            $post = get_post($post_id);
            if (!$post) {
                return $this->responseFormatter->notFound('post', $post_id);
            }

            // Apply snapshot
            $snapshot = $revision['revision_data'];
            RevisionSnapshotService::applySnapshot($post_id, $snapshot);

            return $this->responseFormatter->updated([
                'post_id' => $post_id,
                'revision_id' => $revision_id,
                'revision_name' => $revision['revision_name']
            ], [
                'message' => 'Revision applied successfully'
            ]);
        } catch (\Exception $e) {
            error_log('FanCoolo Error: Failed to apply revision - ' . $e->getMessage());
            return $this->responseFormatter->serverError('Failed to apply revision: ' . $e->getMessage());
        }
    }

    /**
     * Delete a revision
     */
    public function deleteRevision($request)
    {
        try {
            $revision_id = $request->get_param('id');

            // Get revision to check if it exists
            $revision = RevisionsRepository::getById($revision_id);
            if (!$revision) {
                return $this->responseFormatter->notFound('revision', $revision_id);
            }

            // Delete revision
            RevisionsRepository::delete($revision_id);

            return $this->responseFormatter->deleted('Revision deleted successfully', [
                'revision_id' => $revision_id
            ]);
        } catch (\Exception $e) {
            error_log('FanCoolo Error: Failed to delete revision - ' . $e->getMessage());
            return $this->responseFormatter->serverError('Failed to delete revision: ' . $e->getMessage());
        }
    }
}
