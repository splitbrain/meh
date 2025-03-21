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
}
