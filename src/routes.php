<?php

use splitbrain\meh\API\CommentsController;
use splitbrain\meh\App;

/**
 * Define all application routes
 * 
 * @param AltoRouter $router The router instance
 * @param App $app The application container
 * @return void
 */
function registerRoutes(AltoRouter $router, App $app) {
    // API Routes
    $router->map('GET', '/api/comments', [CommentsController::class, 'getComments'], 'get_comments');
    $router->map('POST', '/api/comments', [CommentsController::class, 'createComment'], 'create_comment');
    $router->map('PUT', '/api/comments', [CommentsController::class, 'updateCommentStatus'], 'update_comment');
    $router->map('DELETE', '/api/comments', [CommentsController::class, 'deleteComment'], 'delete_comment');
    
    // You can add more routes here as needed
}
