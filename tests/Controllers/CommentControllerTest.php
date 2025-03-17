<?php

namespace splitbrain\meh\Tests\Controllers;

use PHPUnit\Framework\TestCase;
use splitbrain\meh\App;
use splitbrain\meh\Controllers\CommentController;
use splitbrain\meh\HttpException;
use splitbrain\phpsqlite\SQLite;

class CommentControllerTest extends TestCase
{
    private $app;
    private $db;
    private $controller;

    protected function setUp(): void
    {
        // Create a mock App
        $this->app = $this->createMock(App::class);

        // Create a mock SQLite database
        $this->db = $this->createMock(SQLite::class);

        // Configure the App mock to return the DB mock
        $this->app->method('db')->willReturn($this->db);

        // Create the controller with the mock App
        $this->controller = new CommentController($this->app);
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

        // Expected data to be saved
        $expectedRecord = [
            'post' => 'test-post',
            'author' => 'Test Author',
            'email' => 'test@example.com',
            'website' => '',
            'text' => 'This is a test comment',
            'html' => 'This is a test comment',
            'ip' => '',
            'status' => 'pending',
        ];

        // Expected result after saving
        $expectedResult = array_merge($expectedRecord, ['id' => 1]);

        // Configure the mock to expect saveRecord call and return a result
        $this->db->expects($this->once())
            ->method('saveRecord')
            ->with('comments', $expectedRecord)
            ->willReturn($expectedResult);

        // Call the method
        $result = $this->controller->create($commentData);

        // Assert the result
        $this->assertEquals($expectedResult, $result);
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
        $commentId = 1;
        $expectedComment = [
            'id' => $commentId,
            'post' => 'test-post',
            'author' => 'Test Author',
            'text' => 'Test comment'
        ];

        // Configure the mock to expect queryRecord call and return a result
        $this->db->expects($this->once())
            ->method('queryRecord')
            ->with('SELECT * FROM comments WHERE id = ?', $commentId)
            ->willReturn($expectedComment);

        // Call the method
        $result = $this->controller->get(['id' => $commentId]);

        // Assert the result
        $this->assertEquals($expectedComment, $result);
    }

    public function testGetThrowsExceptionWhenCommentNotFound(): void
    {
        $commentId = 999; // Non-existent ID

        // Configure the mock to expect queryRecord call and return null (not found)
        $this->db->expects($this->once())
            ->method('queryRecord')
            ->with('SELECT * FROM comments WHERE id = ?', $commentId)
            ->willReturn(null);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Comment not found');
        $this->expectExceptionCode(404);

        $this->controller->get(['id' => $commentId]);
    }
}
