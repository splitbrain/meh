<?php

namespace splitbrain\meh\Controllers;

use splitbrain\meh\Controller;
use splitbrain\meh\HttpException;

class CommentListController extends Controller
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

        $comments = $this->app->db()->queryAll(
            'SELECT * FROM comments WHERE post = ? AND status = ? ORDER BY created_at ASC',
            [$postPath, 'approved']
        );

        return ['comments' => $comments];
    }
}
