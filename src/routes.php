<?php

use splitbrain\meh\Controllers\CommentController;
use splitbrain\meh\Controllers\CommentListController;

/**
 * Define all application routes
 *
 * @param AltoRouter $router The router instance
 * @return void
 * @throws Exception
 */
function registerRoutes(AltoRouter $router): void
{
    // API Routes
    $router->map('GET', '/comments', [CommentListController::class, 'bypost'], 'comments.bypost');

    $router->map('POST', '/comment', [CommentController::class, 'create'], 'comment.create');
    $router->map('GET', '/comment/[i:id]', [CommentController::class, 'get'], 'comment.get');
    $router->map('PATCH', '/comment/[i:id]', [CommentController::class, 'edit'], 'comment.edit');
    $router->map('DELETE', '/comment/[i:id]', [CommentController::class, 'delete'], 'comment.delete');
    $router->map('PUT', '/comment/[i:id]/[s:status]', [CommentController::class, 'status'], 'comment.status');

    // You can add more routes here as needed
}
