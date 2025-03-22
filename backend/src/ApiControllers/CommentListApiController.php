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
        $isAdmin = false;
        try {
            $this->app->checkScopes('admin');
            $isAdmin = true;
        } catch (\Exception $e) {
            // Not an admin, will only show approved comments
        }

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

        $comments = array_map([$this->app->commentUtils(), 'addAvatarUrl'], $comments);

        return $comments;
    }

}
