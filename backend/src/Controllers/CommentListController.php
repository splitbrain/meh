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

        // Check if admin scope is present
        $isAdmin = false;
        try {
            $this->app->checkScopes('admin');
            $isAdmin = true;
        } catch (\Exception $e) {
            // Not an admin, will only show approved comments
        }

        if ($isAdmin) {
            // Admin can see all comments regardless of status
            $comments = $this->app->db()->queryAll(
                'SELECT * FROM comments WHERE post = ? ORDER BY created_at ASC',
                [$postPath]
            );
        } else {
            // Regular users only see approved comments
            $comments = $this->app->db()->queryAll(
                'SELECT * FROM comments WHERE post = ? AND status = ? ORDER BY created_at ASC',
                [$postPath, 'approved']
            );
        }

        $comments = array_map([$this, 'commentEnhance'], $comments);

        return $comments;
    }

    /**
     * Add missing data to a comment
     *
     * Currently gravatar handling only
     *
     * @param array $comment
     * @return array
     */
    public function commentEnhance(array $comment): array
    {
        if (empty($comment['avatar'])) {
            if (!empty($comment['email'])) {
                $ident = strtolower(trim($comment['email']));
            } else {
                $ident = strtolower(trim($comment['author']));
            }

            $gravatar = 'https://www.gravatar.com/avatar/' . md5($ident) . '?s=256';
            $gravatar .= '&d=' . urlencode($this->app->conf('gravatar_fallback'));
            $gravatar .= '&r=' . $this->app->conf('gravatar_rating');
            if ($this->app->conf('gravatar_fallback') == 'initials') {
                $gravatar .= '&name=' . urlencode($comment['author']);
            }

            $comment['avatar'] = $gravatar;
        }
        return $comment;
    }
}
