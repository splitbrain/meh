<?php

namespace splitbrain\meh\Tests\ApiControllers;

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
            'text' => 'Updated comment'
        ];

        $result = $this->controller->edit($editData);

        // Assert the result
        $this->assertEquals($commentId, $result['id']);
        $this->assertEquals('test-post', $result['post']); // Unchanged
        $this->assertEquals('Updated Author', $result['author']); // Changed
        $this->assertEquals('test@example.com', $result['email']); // Unchanged
        $this->assertEquals('Updated comment', $result['text']); // Changed
        $this->assertEquals('Updated comment', $result['html']); // Changed

        // Verify the comment was actually updated in the database
        $savedComment = $this->app->db()->queryRecord('SELECT * FROM comments WHERE id = ?', $commentId);
        $this->assertEquals('Updated Author', $savedComment['author']);
        $this->assertEquals('Updated comment', $savedComment['text']);
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
        
        // Create a comment with 'pending' status
        $this->app->db()->exec(
            'INSERT INTO comments (post, author, text, status, user, created_at) 
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                'test-post',
                'Test Author',
                'First comment',
                'pending',
                'rate-limited-user',
                date('Y-m-d H:i:s')
            ]
        );
        
        // Try to post another comment - should fail due to pending comment limit
        $commentData = [
            'post' => 'test-post',
            'author' => 'Test Author',
            'email' => 'test@example.com',
            'text' => 'Second comment'
        ];
        
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('{pending} Your previous comment is still pending approval');
        $userController->create($commentData);
    }
}
