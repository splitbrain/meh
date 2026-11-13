<?php

namespace splitbrain\meh\Tests\ApiControllers;

use PHPUnit\Framework\Attributes\DataProvider;
use splitbrain\meh\ApiControllers\CommentApiController;
use splitbrain\meh\HttpException;

class CommentControllerTest extends AbstractApiControllerTestCase
{
    private CommentApiController $controller;
    private CommentApiController $adminController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new CommentApiController($this->app, $this->createTokenPayload());
        $this->adminController = new CommentApiController(
            $this->app,
            $this->createTokenPayload(['admin', 'user'])
        );
    }


    public function testCreateRequiresRequiredFields(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("Field 'post' is required");
        $this->expectExceptionCode(400);

        $this->controller->create([]);
    }

    public function testCreateSavesValidComment(): void
    {
        // Test data
        $commentData = [
            'post' => 'test-post',
            'author' => 'Test Author',
            'email' => 'test@example.com',
            'text' => 'This is a test comment'
        ];

        // Call the method
        $result = $this->controller->create($commentData);

        // Assert the result contains expected data
        $this->assertEquals('test-post', $result['post']);
        $this->assertEquals('Test Author', $result['author']);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('This is a test comment', $result['text']);
        $this->assertEquals('<p>This is a test comment</p>', $result['html']);
        $this->assertEquals('pending', $result['status']);
        $this->assertArrayHasKey('id', $result);

        // Verify the comment was actually saved in the database
        $savedComment = $this->app->db()->queryRecord('SELECT * FROM comments WHERE id = ?', $result['id']);
        $this->assertNotNull($savedComment);
        $this->assertEquals('test-post', $savedComment['post']);
    }

    public function testGetRequiresId(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Comment ID is required');
        $this->expectExceptionCode(400);

        $this->controller->get([]);
    }

    public function testGetReturnsComment(): void
    {
        // First create a comment
        $commentData = [
            'post' => 'test-post',
            'author' => 'Test Author',
            'email' => 'test@example.com',
            'text' => 'Test comment'
        ];

        $createdComment = $this->controller->create($commentData);
        $commentId = $createdComment['id'];

        // Now get the comment
        $result = $this->controller->get(['id' => $commentId]);

        // Assert the result
        $this->assertEquals($commentId, $result['id']);
        $this->assertEquals('test-post', $result['post']);
        $this->assertEquals('Test Author', $result['author']);
        $this->assertEquals('Test comment', $result['text']);
    }

    public function testGetThrowsExceptionWhenCommentNotFound(): void
    {
        $commentId = 999; // Non-existent ID

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Comment not found');
        $this->expectExceptionCode(404);

        $this->controller->get(['id' => $commentId]);
    }

    public function testEditUpdatesComment(): void
    {
        // First create a comment
        $commentData = [
            'post' => 'test-post',
            'author' => 'Test Author',
            'email' => 'test@example.com',
            'text' => 'Original comment'
        ];

        $createdComment = $this->controller->create($commentData);
        $commentId = $createdComment['id'];

        // Now edit the comment
        $editData = [
            'id' => $commentId,
            'author' => 'Updated Author',
            'email' => 'updated@example.com',
            'website' => 'https://example.com',
            'text' => 'Updated comment'
        ];

        $result = $this->controller->edit($editData);

        // Assert the result
        $this->assertEquals($commentId, $result['id']);
        $this->assertEquals('test-post', $result['post']); // Unchanged
        $this->assertEquals('Updated Author', $result['author']); // Changed
        $this->assertEquals('updated@example.com', $result['email']); // Changed
        $this->assertEquals('https://example.com', $result['website']); // Changed
        $this->assertEquals('Updated comment', $result['text']); // Changed
        $this->assertEquals('<p>Updated comment</p>', $result['html']); // Changed

        // Verify the comment was actually updated in the database
        $savedComment = $this->app->db()->queryRecord('SELECT * FROM comments WHERE id = ?', $commentId);
        $this->assertEquals('Updated Author', $savedComment['author']);
        $this->assertEquals('Updated comment', $savedComment['text']);
    }

    /**
     * Website inputs and the values that are expected to be stored
     *
     * @return array<string, array{string, string}>
     */
    public static function websiteProvider(): array
    {
        return [
            'empty' => ['', ''],
            'http' => ['http://example.com', 'http://example.com'],
            'https' => ['https://example.com/~jane', 'https://example.com/~jane'],
            'uppercase scheme' => ['HTTPS://example.com', 'HTTPS://example.com'],
            'whitespace' => ['  https://example.com ', 'https://example.com'],
            'no scheme' => ['example.com', ''],
            'protocol relative' => ['//example.com', ''],
            'no scheme with port' => ['example.com:8080/foo', ''],
            'unparsable' => ['http:///x', ''],
            'javascript' => ['javascript:alert(1)', ''],
            'javascript uppercase' => ['JavaScript:alert(1)', ''],
            'javascript with tab' => ["java\tscript:alert(1)", ''],
            'data' => ['data:text/html,foo', ''],
            'ftp' => ['ftp://example.com', ''],
        ];
    }

    /**
     * Check that only http and https websites are stored
     *
     * @param string $input The website as sent by the user
     * @param string $expected The website as it is expected to be stored
     */
    #[DataProvider('websiteProvider')]
    public function testCreateCleansWebsite(string $input, string $expected): void
    {
        $result = $this->adminController->create([
            'post' => 'test-post',
            'author' => 'Test Author',
            'website' => $input,
            'text' => 'Test comment'
        ]);
        $this->assertSame($expected, $result['website']);
    }

    /**
     * Check that author and email are stored without surrounding whitespace
     */
    public function testCreateTrimsAuthorAndEmail(): void
    {
        $result = $this->adminController->create([
            'post' => 'test-post',
            'author' => "  Test Author\n",
            'email' => ' test@example.com ',
            'text' => 'Test comment'
        ]);
        $this->assertSame('Test Author', $result['author']);
        $this->assertSame('test@example.com', $result['email']);
    }

    /**
     * Check that an author name of only whitespace is rejected
     */
    public function testCreateRejectsWhitespaceAuthor(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("Field 'author' is required");

        $this->adminController->create([
            'post' => 'test-post',
            'author' => '   ',
            'text' => 'Test comment'
        ]);
    }

    /**
     * Check that edit cleans the input and stores missing optional fields as empty
     */
    public function testEditCleansInputAndReplacesAllFields(): void
    {
        $created = $this->adminController->create([
            'post' => 'test-post',
            'author' => 'Test Author',
            'email' => 'test@example.com',
            'website' => 'https://example.com',
            'text' => 'Test comment'
        ]);

        $result = $this->adminController->edit([
            'id' => $created['id'],
            'author' => ' Updated Author ',
            'website' => 'javascript:alert(1)',
            'text' => 'Updated comment'
        ]);

        $this->assertSame('Updated Author', $result['author']);
        $this->assertSame('', $result['email']);
        $this->assertSame('', $result['website']);
    }

    /**
     * Check that edit keeps the stored HTML when the text is unchanged
     */
    public function testEditKeepsHtmlForUnchangedText(): void
    {
        $created = $this->adminController->create([
            'post' => 'test-post',
            'author' => 'Test Author',
            'text' => 'Imported comment'
        ]);
        $this->app->db()->exec(
            'UPDATE comments SET html = ? WHERE id = ?',
            ['<p><a href="https://example.com">Imported</a> comment</p>', $created['id']]
        );

        $result = $this->adminController->edit([
            'id' => $created['id'],
            'author' => 'Updated Author',
            'text' => 'Imported comment'
        ]);
        $this->assertSame('<p><a href="https://example.com">Imported</a> comment</p>', $result['html']);

        $result = $this->adminController->edit([
            'id' => $created['id'],
            'author' => 'Updated Author',
            'text' => 'Changed comment'
        ]);
        $this->assertSame('<p>Changed comment</p>', $result['html']);
    }

    /**
     * Check that replies keep their parent when the parent comment is edited
     */
    public function testEditKeepsParentOfReplies(): void
    {
        $parent = $this->adminController->create([
            'post' => 'test-post',
            'author' => 'Test Author',
            'text' => 'Parent comment'
        ]);
        $reply = $this->adminController->create([
            'post' => 'test-post',
            'author' => 'Test Author',
            'text' => 'Reply',
            'parent' => $parent['id']
        ]);

        $this->adminController->edit([
            'id' => $parent['id'],
            'author' => 'Test Author',
            'text' => 'Edited parent comment'
        ]);

        $saved = $this->app->db()->queryRecord('SELECT * FROM comments WHERE id = ?', $reply['id']);
        $this->assertEquals($parent['id'], $saved['parent']);
    }

    /**
     * Check that edit rejects a request without an author
     */
    public function testEditRequiresAuthor(): void
    {
        $created = $this->adminController->create([
            'post' => 'test-post',
            'author' => 'Test Author',
            'text' => 'Test comment'
        ]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("Field 'author' is required");

        $this->adminController->edit(['id' => $created['id'], 'text' => 'Updated comment']);
    }

    public function testStatusUpdatesCommentStatus(): void
    {
        // First create a comment
        $commentData = [
            'post' => 'test-post',
            'author' => 'Test Author',
            'text' => 'Test comment'
        ];

        $createdComment = $this->controller->create($commentData);
        $commentId = $createdComment['id'];

        // Now update the status
        $result = $this->controller->status([
            'id' => $commentId,
            'status' => 'approved'
        ]);

        // Assert the result
        $this->assertEquals($commentId, $result['id']);
        $this->assertEquals('approved', $result['status']);

        // Verify the status was actually updated in the database
        $savedComment = $this->app->db()->queryRecord('SELECT * FROM comments WHERE id = ?', $commentId);
        $this->assertEquals('approved', $savedComment['status']);
    }

    public function testDeleteRemovesComment(): void
    {
        // First create a comment
        $commentData = [
            'post' => 'test-post',
            'author' => 'Test Author',
            'text' => 'Test comment'
        ];

        $createdComment = $this->controller->create($commentData);
        $commentId = $createdComment['id'];

        // Now delete the comment
        $result = $this->controller->delete(['id' => $commentId]);

        // Assert the result
        $this->assertEquals(1, $result); // 1 row affected

        // Verify the comment was actually deleted from the database
        $savedComment = $this->app->db()->queryRecord('SELECT * FROM comments WHERE id = ?', $commentId);
        $this->assertNull($savedComment);
    }

    public function testCreateProcessesMarkdownToHtml(): void
    {
        $commentData = [
            'post' => 'test-post',
            'author' => 'Test Author',
            'email' => 'test@example.com',
            'text' => "# Heading\n\nParagraph with **bold** text."
        ];

        $result = $this->controller->create($commentData);

        // Check that HTML was properly generated from Markdown
        $this->assertStringContainsString('<h1>Heading</h1>', $result['html']);
        $this->assertStringContainsString('<p>Paragraph with <strong>bold</strong> text.</p>', $result['html']);
    }

    public function testCreateSetsApprovedStatusForAdminComments(): void
    {
        $commentData = [
            'post' => 'test-post',
            'author' => 'Admin Author',
            'email' => 'admin@example.com',
            'text' => 'This is an admin comment'
        ];

        // Create comment as admin
        $result = $this->adminController->create($commentData);

        // Check that status is approved
        $this->assertEquals('approved', $result['status']);

        // Verify in database
        $savedComment = $this->app->db()->queryRecord('SELECT * FROM comments WHERE id = ?', $result['id']);
        $this->assertEquals('approved', $savedComment['status']);
    }

    public function testCreateEnforcesRateLimiting(): void
    {
        // Create a specific user token with a recent timestamp
        // (90 seconds ago, which is valid for posting)
        $specificUser = $this->createTokenPayload(['user'], -90, 'rate-limited-user');
        $userController = new CommentApiController($this->app, $specificUser);

        // Try to post another comment - should fail due to pending comment limit
        $commentData = [
            'post' => 'test-post',
            'author' => 'Test Author',
            'email' => 'test@example.com',
            'text' => 'First comment'
        ];
        $userController->create($commentData);

        // Try to post another comment - should fail due to pending comment limit
        $commentData = [
            'post' => 'test-post',
            'author' => 'Test Author',
            'email' => 'test@example.com',
            'text' => 'Second comment'
        ];

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('`pending` Your previous comment is still pending approval');
        $userController->create($commentData);
    }
}
