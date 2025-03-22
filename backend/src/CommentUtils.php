<?php

namespace splitbrain\meh;

class CommentUtils
{

    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Process a comment for output
     *
     * @param array $comment original comment
     * @return array enhanced comment
     */
    public function process(array $comment)
    {
        return $this->dropUserID($this->addAvatarUrl($comment));
    }

    /**
     * Ensure the comment has an avatar URL
     *
     * @param array $comment original comment
     * @return array comment with avatar_url added
     */
    public function addAvatarUrl(array $comment): array
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

            $comment['avatar_url'] = $gravatar;
        } else {
            $comment['avatar_url'] = $comment['avatar'];
        }
        return $comment;
    }

    /**
     * Drop the user ID from a comment
     *
     * @param array $comment original comment
     * @return array comment without user ID
     */
    public function dropUserID(array $comment): array
    {
        if (isset($data['user'])) {
            unset($data['user']);
        }
        return $comment;
    }

    /**
     * Determine the initial status of a comment
     *
     * @param array $comment the comment data
     * @param bool $isAdmin true if the user is an admin
     * @return string the initial status
     */
    public function initialStatus(array $comment, bool $isAdmin): string
    {
        if ($isAdmin) {
            return 'approved';
        }

        // if we had a previous comment by the same token, use the same status
        $user = $comment['user'] ?? null;
        if (!$user) return 'pending';
        $last = $this->app->db()->queryValue(
            "SELECT status FROM comments WHERE user = ? AND status != 'deleted' ORDER BY created_at DESC LIMIT 1",
            $user
        );
        if ($last) return $last;

        // if the last comment by this IP was "spam", assume it's a spammer
        $last = $this->app->db()->queryValue(
            "SELECT status FROM comments WHERE ip = ? AND status != 'deleted' ORDER BY created_at DESC LIMIT 1",
            $comment['ip']
        );
        if ($last == 'spam') return 'spam';

        // user with an unknown history
        return 'pending';
    }

}
