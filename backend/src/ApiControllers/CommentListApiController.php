<?php

namespace splitbrain\meh\ApiControllers;

use splitbrain\meh\ApiController;
use splitbrain\meh\HttpException;

class CommentListApiController extends ApiController
{
    /**
     * Get all comments for a post
     *
     * @param array $data Request data
     * @return array Serializable data
     * @throws \Exception If post path is missing
     */
    public function bypost($data)
    {
        $postPath = $data['post'] ?? null;

        if (!$postPath) {
            throw new HttpException('Post path is required', 400);
        }

        // Check if admin scope is present
        $isAdmin = $this->checkScopes('admin');

        if ($isAdmin) {
            // Admin can see all comments except deleted ones
            $comments = $this->app->db()->queryAll(
                'SELECT * FROM comments WHERE post = ? AND status != ? ORDER BY created_at ASC',
                [$postPath, 'deleted']
            );
        } else {
            // Regular users only see approved comments
            $comments = $this->app->db()->queryAll(
                'SELECT * FROM comments WHERE post = ? AND status = ? ORDER BY created_at ASC',
                [$postPath, 'approved']
            );
        }

        return array_map([$this->app->commentUtils(), 'process'], $comments);
    }

    /**
     * Count comments for a post
     *
     * @param array $data Request data
     * @return int Number of comments
     * @throws \Exception If post path is missing
     */
    public function count($data)
    {
        $postPath = $data['post'] ?? null;

        if (!$postPath) {
            throw new HttpException('Post path is required', 400);
        }

        return $this->app->db()->queryValue(
            'SELECT COUNT(*) FROM comments WHERE post = ? AND status = ?',
            [$postPath, 'approved']
        );
    }
}
