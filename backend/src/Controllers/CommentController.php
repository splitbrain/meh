<?php

namespace splitbrain\meh\Controllers;

use splitbrain\meh\Controller;
use splitbrain\meh\HttpException;

class CommentController extends Controller
{


    /**
     * Create a new comment
     *
     * @param array $data Comment data
     * @return array stored data (including ID)
     * @throws \Exception If required fields are missing or on database error
     */
    public function create(array $data): array
    {
        // Validate required fields
        $requiredFields = ['post', 'author', 'text'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new HttpException("Field '$field' is required", 400);
            }
        }

        $record = [
            'post' => $data['post'],
            'author' => $data['author'],
            'email' => $data['email'] ?? '',
            'website' => $data['website'] ?? '',
            'text' => $data['text'],
            'html' => $data['text'], // Simple text for now, could add Markdown processing
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'status' => 'pending', // New comments are pending by default
        ];

        return $this->app->db()->saveRecord('comments', $record);
    }

    /**
     * Get a single comment
     *
     * @param array $data Request data, must contain 'id'
     * @return array The comment data
     * @throws HttpException
     */
    public function get(array $data): array
    {
        $id = $data['id'] ?? null;
        if (!$id) {
            throw new HttpException('Comment ID is required', 400);
        }

        $record = $this->app->db()->queryRecord(
            'SELECT * FROM comments WHERE id = ?',
            $id
        );

        if (!$record) {
            throw new HttpException('Comment not found', 404);
        }

        return $record;
    }

    /**
     * Edit a comment
     *
     * @param array $data Request data, must contain 'id'
     * @return array The updated comment data
     * @throws HttpException
     */
    public function edit(array $data): array
    {
        $id = $data['id'] ?? null;
        if (!$id) {
            throw new HttpException('Comment ID is required', 400);
        }

        $record = $this->app->db()->queryRecord('SELECT * FROM comments WHERE id = ?', $id);

        if (!$record) {
            throw new HttpException('Comment not found', 404);
        }

        // make sure $data only contains fields that can be updated
        $data = array_intersect_key($data, array_flip(['author', 'email', 'website', 'text']));
        if (isset($data['text'])) {
            $data['html'] = $data['text']; // Simple text for now, could add Markdown processing
        }

        // merge with existing record
        $record = array_merge($record, $data);
        return $this->app->db()->saveRecord('comments', $record);
    }

    /**
     * Update a comment's status
     *
     * @param array $data Update data
     * @return array The new comment
     * @throws \Exception If required fields are missing or on database error
     */
    public function status(array $data): array
    {
        $id = $data['id'] ?? null;
        if (!$id) {
            throw new HttpException('Comment ID is required', 400);
        }

        $status = $data['status'] ?? null;
        $validStatuses = ['pending', 'approved', 'spam', 'deleted'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Invalid status. Must be one of: ' . implode(', ', $validStatuses), 400);
        }

        $rows = $this->app->db()->exec('UPDATE comments SET status = ? WHERE id = ?', [$status, $id]);
        if (!$rows) {
            throw new HttpException('No such comment', 404);
        }
        return $this->get(['id' => $id]);
    }

    /**
     * Delete a comment
     *
     * @param array $data Request data
     * @return int number of deleted comments (1)
     * @throws \Exception If comment ID is missing or on database error
     */
    public function delete(array $data): int
    {
        $id = $data['id'] ?? null;
        if (!$id) {
            throw new HttpException('Comment ID is required', 400);
        }

        $rows = $this->app->db()->exec('DELETE FROM comments WHERE id = ?', [$id]);
        if (!$rows) {
            throw new HttpException('No such comment ID', 404);
        }
        return $rows;
    }
}
