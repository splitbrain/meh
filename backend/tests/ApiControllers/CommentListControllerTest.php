<?php

namespace splitbrain\meh\Tests\ApiControllers;

use splitbrain\meh\ApiControllers\CommentListApiController;
use splitbrain\meh\HttpException;

class CommentListControllerTest extends AbstractApiControllerTestCase
{
    private CommentListApiController $controller;
    private CommentListApiController $adminController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new CommentListApiController($this->app, $this->createTokenPayload());
        $this->adminController = new CommentListApiController(
            $this->app, 
            $this->createTokenPayload(['admin', 'user'])
        );
        
        // Create some test comments
        $this->createTestComments();
    }
    
    private function createTestComments(): void
    {
        // Create approved comments
        $this->app->db()->exec(
            'INSERT INTO comments (post, author, text, html, status, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            ['test-post', 'User 1', 'Comment 1', '<p>Comment 1</p>', 'approved', date('Y-m-d H:i:s')]
        );
        $this->app->db()->exec(
            'INSERT INTO comments (post, author, text, html, status, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            ['test-post', 'User 2', 'Comment 2', '<p>Comment 2</p>', 'approved', date('Y-m-d H:i:s')]
        );
        
        // Create pending comment
        $this->app->db()->exec(
            'INSERT INTO comments (post, author, text, html, status, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            ['test-post', 'User 3', 'Comment 3', '<p>Comment 3</p>', 'pending', date('Y-m-d H:i:s')]
        );
        
        // Create spam comment
        $this->app->db()->exec(
            'INSERT INTO comments (post, author, text, html, status, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            ['test-post', 'User 4', 'Comment 4', '<p>Comment 4</p>', 'spam', date('Y-m-d H:i:s')]
        );
        
        // Create deleted comment
        $this->app->db()->exec(
            'INSERT INTO comments (post, author, text, html, status, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            ['test-post', 'User 5', 'Comment 5', '<p>Comment 5</p>', 'deleted', date('Y-m-d H:i:s')]
        );
        
        // Create comment for different post
        $this->app->db()->exec(
            'INSERT INTO comments (post, author, text, html, status, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            ['other-post', 'User 6', 'Comment 6', '<p>Comment 6</p>', 'approved', date('Y-m-d H:i:s')]
        );
    }
    
    public function testBypostRequiresPostPath(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Post path is required');
        $this->expectExceptionCode(400);
        
        $this->controller->bypost([]);
    }
    
    public function testBypostReturnsOnlyApprovedCommentsForRegularUsers(): void
    {
        $result = $this->controller->bypost(['post' => 'test-post']);
        
        // Should only return approved comments
        $this->assertCount(2, $result);
        
        // Check that all returned comments are approved
        foreach ($result as $comment) {
            $this->assertEquals('approved', $comment['status']);
        }
        
        // Check that we got the right comments
        $authors = array_column($result, 'author');
        $this->assertContains('User 1', $authors);
        $this->assertContains('User 2', $authors);
        $this->assertNotContains('User 3', $authors); // pending
        $this->assertNotContains('User 4', $authors); // spam
        $this->assertNotContains('User 5', $authors); // deleted
    }
    
    public function testBypostReturnsAllNonDeletedCommentsForAdmins(): void
    {
        $result = $this->adminController->bypost(['post' => 'test-post']);
        
        // Should return approved, pending, and spam comments (but not deleted)
        $this->assertCount(4, $result);
        
        // Check that we got the right comments
        $authors = array_column($result, 'author');
        $this->assertContains('User 1', $authors); // approved
        $this->assertContains('User 2', $authors); // approved
        $this->assertContains('User 3', $authors); // pending
        $this->assertContains('User 4', $authors); // spam
        $this->assertNotContains('User 5', $authors); // deleted
    }
    
    public function testBypostFiltersCommentsByPost(): void
    {
        // Test with regular user
        $result = $this->controller->bypost(['post' => 'other-post']);
        
        $this->assertCount(1, $result);
        $this->assertEquals('User 6', $result[0]['author']);
        
        // Test with admin
        $result = $this->adminController->bypost(['post' => 'other-post']);
        
        $this->assertCount(1, $result);
        $this->assertEquals('User 6', $result[0]['author']);
    }
}
