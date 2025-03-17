<?php

namespace splitbrain\meh\API;

use splitbrain\meh\Controller;

class CommentsController extends Controller
{

    public function test($data)
    {
        return ['jo'=>'jo', 'data'=>$data];
    }


    /**
     * Get all comments for a post
     *
     * @param array $data Request data
     * @return array Serializable data
     * @throws \Exception If post path is missing
     */
    public function getComments($data)
    {
        $db = $this->getDatabase();
        $postPath = isset($data['post']) ? $data['post'] : null;

        if (!$postPath) {
            throw new \Exception('Post path is required', 400);
        }

        $comments = $db->queryAll('SELECT * FROM comments WHERE post = ? AND status = ? ORDER BY created_at ASC',
            [$postPath, 'approved']);

        return ['comments' => $comments];
    }

    /**
     * Create a new comment
     *
     * @param array $data Comment data
     * @return array Serializable data
     * @throws \Exception If required fields are missing or on database error
     */
    public function createComment($data)
    {
        $db = $this->getDatabase();

        // Validate required fields
        $requiredFields = ['post', 'author', 'text'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \Exception("Field '$field' is required", 400);
            }
        }

        // Prepare data for insertion
        $post = $data['post'];
        $author = $data['author'];
        $email = isset($data['email']) ? $data['email'] : '';
        $website = isset($data['website']) ? $data['website'] : '';
        $text = $data['text'];
        $html = $text; // Simple text for now, could add Markdown processing
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        try {
            $db->query(
                'INSERT INTO comments 
                (post, author, ip, email, website, text, html, status, created_at) 
                VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $post,
                    $author,
                    $ip,
                    $email,
                    $website,
                    $text,
                    $html,
                    'pending', // New comments are pending by default
                    date('Y-m-d H:i:s')
                ]
            );

            $id = $db->lastInsertId();
            return ['success' => true, 'id' => $id];
        } catch (\Exception $e) {
            throw new \Exception('Failed to create comment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update a comment's status
     *
     * @param array $data Update data
     * @return array Serializable data
     * @throws \Exception If required fields are missing or on database error
     */
    public function updateCommentStatus($data)
    {
        $db = $this->getDatabase();

        if (!isset($data['id']) || !isset($data['status'])) {
            throw new \Exception('Comment ID and status are required', 400);
        }

        $id = $data['id'];
        $status = $data['status'];

        // Validate status
        $validStatuses = ['pending', 'approved', 'spam', 'deleted'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Invalid status. Must be one of: ' . implode(', ', $validStatuses), 400);
        }

        try {
            $db->query('UPDATE comments SET status = ? WHERE id = ?', [$status, $id]);
            return ['success' => true];
        } catch (\Exception $e) {
            throw new \Exception('Failed to update comment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a comment
     *
     * @param array $data Request data
     * @return array Serializable data
     * @throws \Exception If comment ID is missing or on database error
     */
    public function deleteComment($data)
    {
        $db = $this->getDatabase();

        if (!isset($data['id'])) {
            throw new \Exception('Comment ID is required', 400);
        }

        $id = $data['id'];

        try {
            $db->query('DELETE FROM comments WHERE id = ?', [$id]);
            return ['success' => true];
        } catch (\Exception $e) {
            throw new \Exception('Failed to delete comment: ' . $e->getMessage(), 500);
        }
    }
}
