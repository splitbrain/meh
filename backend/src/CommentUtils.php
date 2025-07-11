<?php

namespace splitbrain\meh;

class CommentUtils
{

    public function __construct(protected App $app)
    {
    }

    /**
     * Process a comment for output
     *
     * @param array $comment original comment
     * @return array enhanced comment
     */
    public function process(array $comment)
    {
        $this->dropUserID($comment);
        $this->addAvatarUrl($comment);
        $this->addTimezone($comment);
        return $comment;
    }

    /**
     * Ensure the comment has an avatar URL
     *
     * @param array $comment original comment, will be modified
     */
    public function addAvatarUrl(array &$comment): void
    {
        if (!empty($comment['email'])) {
            $ident = strtolower(trim((string)$comment['email']));
        } else {
            $ident = strtolower(trim((string)$comment['author']));
        }
        $ident = md5($ident);
        $comment['avatar_id'] = $ident; // for the frontend


    }

    /**
     * Drop the user ID from a comment
     *
     * @param array $comment original comment, will be modified
     */
    public function dropUserID(array &$comment): void
    {
        if (isset($comment['user'])) {
            unset($comment['user']);
        }
    }

    /**
     * Add a timezone to a comment
     *
     * SQLite stores all timestamps in UTC, but we want to display them in the user's timezone
     *
     * @param array $comment original comment, will be modified
     */
    public function addTimezone(array &$comment): void
    {
        $dt = new \DateTimeImmutable($comment['created_at'], new \DateTimeZone('UTC'));
        $comment['created_at'] = $dt->format(\DateTimeInterface::ATOM);
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
        if ($user) return 'pending';
        $last = $this->app->db()->queryValue(
            "SELECT status FROM comments WHERE user = ? AND status != 'deleted' ORDER BY created_at DESC LIMIT 1",
            $user
        );
        if ($last) return $last;

        // if the last comment by this IP was "spam", assume it's a spammer but still create it because IPs are reused
        $last = $this->app->db()->queryValue(
            "SELECT status FROM comments WHERE ip = ? AND status != 'deleted' ORDER BY created_at DESC LIMIT 1",
            $comment['ip']
        );
        if ($last == 'spam') return 'spam';

        // user with an unknown history
        return 'pending';
    }

    /**
     * Check preconditions if a comment should be accepted
     *
     * @param array $comment
     * @param int $iat
     * @return void
     * @throws HttpException when any preconditions are not met
     */
    public function checkPostLimit(array $comment, int $iat): void
    {
        // we issue a new token on form load. Users should not post immediately
        if ((time() - $iat) < 30) {
            throw new HttpException('`toosoon` You posted really fast. Did you even read the article?', 503);
        }
        // if they idle too long, we want them to get a new token
        if ((time() - $iat) > 60 * 60 * 2) {
            throw new HttpException('`toolate` Sorry you waited too long. Please reload and try again', 503);
        }

        // if there is a pending comment by this user, don't allow another one
        $user = $comment['user'] ?? null;
        if ($user) {
            $last = $this->app->db()->queryValue(
                "SELECT status FROM comments WHERE user = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1",
                $user
            );
            if ($last == 'pending') {
                throw new HttpException('`pending` Your previous comment is still pending approval', 503);
            }
        }

        // if the last comment by this IP was "spam", assume it's a spammer but still create it because IPs are reused
        $last = $this->app->db()->queryValue(
            "SELECT status FROM comments WHERE ip = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1",
            $comment['ip']
        );
        if ($last == 'pending') {
            throw new HttpException('`pending` Your previous comment is still pending approval', 503);
        }
    }

}
