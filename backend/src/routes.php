<?php

use splitbrain\meh\Controllers\CommentController;
use splitbrain\meh\Controllers\CommentListController;
use splitbrain\meh\Controllers\TokenController;

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
    $router->map('GET', '/comment/[i:id]', [CommentController::class, 'get', 'admin'], 'comment.get');

    // admin scope required
    $router->map('PATCH', '/comment/[i:id]', [CommentController::class, 'edit', 'admin'], 'comment.edit');
    $router->map('DELETE', '/comment/[i:id]', [CommentController::class, 'delete', 'admin'], 'comment.delete');
    $router->map('PUT', '/comment/[i:id]/[a:status]', [CommentController::class, 'status', 'admin'], 'comment.status');

    // Authentication routes
    $router->map('POST', '/token/admin', [TokenController::class, 'admin'], 'token.admin');

    // You can add more routes here as needed
}
