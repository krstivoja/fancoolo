<?php

namespace FanCoolo\Database;

/**
 * Repository for managing post revisions
 *
 * Stores snapshots of post meta data and database settings,
 * allowing users to restore previous states.
 */
class RevisionsRepository extends AbstractBulkRepository
{
    /**
     * Create a new revision
     *
     * @param int $post_id Post ID
     * @param string $name Revision name
     * @param array $snapshot Snapshot data
     * @return int Revision ID
     */
    public static function create(int $post_id, string $name, array $snapshot): int
    {
        $post_id = self::validatePostId($post_id);
        $table_name = DatabaseInstaller::getRevisionsTableName();

        $data = [
            'post_id' => $post_id,
            'revision_name' => sanitize_text_field($name),
            'revision_data' => wp_json_encode($snapshot),
        ];

        $formats = ['%d', '%s', '%s'];

        global $wpdb;
        $result = $wpdb->insert($table_name, $data, $formats);

        if ($result === false) {
            self::handleDatabaseError("create revision for post {$post_id}", $wpdb->last_error);
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Get all revisions for a post
     *
     * @param int $post_id Post ID
     * @return array Array of revisions (ordered by created_at DESC)
     */
    public static function getByPostId(int $post_id): array
    {
        $post_id = self::validatePostId($post_id);
        $table_name = DatabaseInstaller::getRevisionsTableName();

        $query = "SELECT * FROM $table_name WHERE post_id = %d ORDER BY created_at DESC";
        $rows = self::executeQuery($query, [$post_id], "get revisions for post {$post_id}");

        return array_map(function($row) {
            return self::processRow($row);
        }, $rows);
    }

    /**
     * Get a specific revision by ID
     *
     * @param int $id Revision ID
     * @return array|null Revision data or null if not found
     */
    public static function getById(int $id): ?array
    {
        $table_name = DatabaseInstaller::getRevisionsTableName();

        $query = "SELECT * FROM $table_name WHERE id = %d";
        $row = self::executeRowQuery($query, [$id], "get revision {$id}");

        if (!$row) {
            return null;
        }

        return self::processRow($row);
    }

    /**
     * Delete a specific revision
     *
     * @param int $id Revision ID
     * @return bool Success status
     */
    public static function delete(int $id): bool
    {
        $table_name = DatabaseInstaller::getRevisionsTableName();

        global $wpdb;
        $result = $wpdb->delete($table_name, ['id' => $id], ['%d']);

        if ($result === false) {
            self::handleDatabaseError("delete revision {$id}", $wpdb->last_error);
        }

        return $result !== false;
    }

    /**
     * Delete all revisions for a post
     *
     * @param int $post_id Post ID
     * @return bool Success status
     */
    public static function deleteByPostId(int $post_id): bool
    {
        $post_id = self::validatePostId($post_id);
        $table_name = DatabaseInstaller::getRevisionsTableName();

        global $wpdb;
        $result = $wpdb->delete($table_name, ['post_id' => $post_id], ['%d']);

        if ($result === false) {
            self::handleDatabaseError("delete revisions for post {$post_id}", $wpdb->last_error);
        }

        return $result !== false;
    }

    /**
     * Get a single revision by post ID (returns most recent revision)
     * Required by BulkRepositoryInterface
     *
     * @param int $post_id Post ID
     * @return array|null Most recent revision or null
     */
    public static function get(int $post_id): ?array
    {
        $revisions = self::getByPostId($post_id);
        return !empty($revisions) ? $revisions[0] : null;
    }

    /**
     * Save method not applicable for revisions
     * Required by BulkRepositoryInterface but use create() instead
     *
     * @param int $post_id Post ID
     * @param array $data Data (must contain 'name' and 'snapshot')
     * @return bool Success status
     */
    public static function save(int $post_id, array $data): bool
    {
        if (!isset($data['name']) || !isset($data['snapshot'])) {
            throw new \InvalidArgumentException('Revision data must contain "name" and "snapshot"');
        }

        self::create($post_id, $data['name'], $data['snapshot']);
        return true;
    }

    /**
     * Get bulk revisions data (required by interface, not commonly used)
     *
     * @param array $post_ids Array of post IDs
     * @return array Associative array with post_id as key
     */
    public static function getBulk(array $post_ids): array
    {
        $post_ids = self::validatePostIds($post_ids);

        if (empty($post_ids)) {
            return [];
        }

        $table_name = DatabaseInstaller::getRevisionsTableName();
        $placeholders = self::createPlaceholders($post_ids);

        $query = "SELECT * FROM $table_name WHERE post_id IN ($placeholders) ORDER BY post_id, created_at DESC";
        $rows = self::executeQuery($query, $post_ids, "bulk get revisions");

        $result = [];
        foreach ($rows as $row) {
            $processed = self::processRow($row);
            $post_id = $processed['post_id'];

            if (!isset($result[$post_id])) {
                $result[$post_id] = [];
            }

            $result[$post_id][] = $processed;
        }

        return $result;
    }

    /**
     * Process a database row into a standardized array
     *
     * @param object $row Database row
     * @return array Processed revision data
     */
    private static function processRow($row): array
    {
        if (is_object($row)) {
            $row = get_object_vars($row);
        }

        if (!is_array($row)) {
            throw new \InvalidArgumentException('Invalid revision row format');
        }

        return [
            'id' => isset($row['id']) ? (int) $row['id'] : 0,
            'post_id' => isset($row['post_id']) ? (int) $row['post_id'] : 0,
            'revision_name' => $row['revision_name'] ?? '',
            'revision_data' => isset($row['revision_data']) ? json_decode($row['revision_data'], true) ?: [] : [],
            'created_at' => $row['created_at'] ?? null,
        ];
    }
}
