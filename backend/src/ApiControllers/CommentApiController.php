<?php

namespace splitbrain\meh\ApiControllers;

use Parsedown;
use PHPMailer\PHPMailer\PHPMailer;
use splitbrain\meh\ApiController;
use splitbrain\meh\HttpException;


class CommentApiController extends ApiController
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
        if (empty($data['post'])) {
            throw new HttpException("Field 'post' is required", 400);
        }
        $input = $this->prepareInput($data);

        // Get the 'sub' from token payload
        $user = $this->tokenPayload->sub;

        $record = [
            'post' => $data['post'],
            ...$input,
            'ip' => $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '',
            'status' => 'pending',
            'user' => $user,
        ];

        // Check if the comment is a reply to another comment in the same post
        if (!empty($data['parent'])) {
            $parent = $this->app->db()->queryValue(
                'SELECT id FROM comments WHERE id = ? AND post = ?',
                $data['parent'],
                $data['post']
            );
            if (!$parent) {
                throw new HttpException('Parent comment not found', 404);
            }
            $record['parent'] = (int)$parent;
        }

        // check post limits (except for admins)
        if (!$this->checkScopes('admin')) {
            $this->app->commentUtils()->checkPostLimit($record, (int)$this->tokenPayload->iat);
        }

        // determine initial status
        $record['status'] = $this->app->commentUtils()->initialStatus($record, $this->checkScopes('admin'));

        $result = $this->app->db()->saveRecord('comments', $record);
        $this->sendNotification($result);
        return $this->app->commentUtils()->process($result);
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

        return $this->app->commentUtils()->process($record);
    }

    /**
     * Edit a comment
     *
     * All editable fields are replaced. Missing optional fields are stored as empty.
     * The HTML is only rendered again when the text has changed.
     *
     * @param array $data Request data, must contain 'id', 'author' and 'text'
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

        $data = $this->prepareInput($data);

        // keep the stored HTML when the text is unchanged, imported comments have no Markdown source
        if ($data['text'] === $record['text']) {
            $data['html'] = $record['html'];
        }

        // merge with existing record
        $record = array_merge($record, $data);
        $new = $this->app->db()->saveRecord('comments', $record);
        return $this->app->commentUtils()->process($new);
    }

    /**
     * Prepare the user-supplied comment fields for storage
     *
     * Author and e-mail are trimmed, the website is cleaned and the text is rendered from
     * Markdown to HTML. Missing optional fields become empty strings. All other fields are
     * not returned.
     *
     * @param array $data Comment data as given by the user
     * @return array{author: string, email: string, website: string, text: string, html: string} The fields to store
     * @throws HttpException If the author or the text is missing
     */
    protected function prepareInput(array $data): array
    {
        $clean = [
            'author' => trim($data['author'] ?? ''),
            'email' => trim($data['email'] ?? ''),
            'website' => $this->app->commentUtils()->cleanWebsite($data['website'] ?? ''),
            'text' => $data['text'] ?? '',
        ];

        foreach (['author', 'text'] as $field) {
            if (empty($clean[$field])) {
                throw new HttpException("Field '$field' is required", 400);
            }
        }

        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);
        $parsedown->setBreaksEnabled(true);
        $clean['html'] = $parsedown->text($clean['text']);

        return $clean;
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


    /**
     * Send a notification email about a new comment
     *
     * @param array $data The comment data
     */
    protected function sendNotification(array $data): void
    {
        if (!$this->app->conf('notify_email')) {
            return;
        }
        if (!$this->app->conf('smtp_host')) {
            return;
        }

        $mailer = new PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Debugoutput = $this->app->log();
        $mailer->Host = $this->app->conf('smtp_host');
        $mailer->Port = $this->app->conf('smtp_port');
        if ($this->app->conf('smtp_encryption') == 'ssl') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($this->app->conf('smtp_encryption') == 'tls') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        if ($this->app->conf('smtp_user')) {
            $mailer->SMTPAuth = true;
            $mailer->Username = $this->app->conf('smtp_user');
            $mailer->Password = $this->app->conf('smtp_pass');
        }

        if ((bool)$this->app->conf('smtp_verify') === false) {
            $mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        }


        $mailer->addAddress($this->app->conf('notify_email'));
        $mailer->setFrom($this->app->conf('notify_email'), 'Meh');

        $mailer->isHTML(false);
        $mailer->Subject = 'New Comment on ' . $data['post'];

        $body = "A new comment was posted on your blog:\n\n";
        $body .= $this->app->conf('site_url') . $data['post'] . "\n\n";
        $body .= "Status: " . $data['status'] . "\n";
        $body .= "Author: " . $data['author'] . "\n";
        $body .= "E-Mail: " . $data['email'] . "\n";
        $body .= "Website: " . $data['website'] . "\n\n";
        $body .= $data['text'] . "\n\n";

        $mailer->Body = $body;


        try {
            $mailer->send();
        } catch (\Exception $e) {
            $this->app->log()->error('Failed to send notification email', ['exception' => $e]);
        }
    }
}
