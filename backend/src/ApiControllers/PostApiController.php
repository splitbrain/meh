<?php

namespace splitbrain\meh\ApiControllers;

use splitbrain\meh\ApiController;
use splitbrain\meh\HttpException;

class PostApiController extends ApiController
{
    /**
     * Get the newest Mastodon URL for a post
     *
     * @param array $data Request data
     * @return string Mastodon URL
     * @throws \Exception If post path is missing
     */
    public function mastodonUrl(array $data)
    {
        $postPath = $data['post'] ?? null;

        if (!$postPath) {
            throw new HttpException('Post path is required', 400);
        }

        $post = $this->app->db()->queryValue(
            'SELECT url FROM mastodon_threads WHERE post = ? ORDER BY created_at DESC LIMIT 1',
            [$postPath]
        );

        if (!$post) {
            throw new HttpException('Post not found', 404);
        }

        return $post['mastodon_url'];
    }
}
