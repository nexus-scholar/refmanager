<?php

use Illuminate\Support\Facades\Route;
use Nexus\RefManager\Http\Controllers\DeduplicationController;
use Nexus\RefManager\Http\Controllers\DocumentsController;
use Nexus\RefManager\Http\Controllers\ImportController;

Route::get('/documents', [DocumentsController::class, 'index']);
Route::get('/documents/{id}', [DocumentsController::class, 'show']);
Route::patch('/documents/{id}', [DocumentsController::class, 'update']);
Route::delete('/documents/{id}', [DocumentsController::class, 'destroy']);

Route::post('/import', [ImportController::class, 'store']);

Route::post('/deduplicate/scan', [DeduplicationController::class, 'scan']);
Route::get('/duplicates', [DeduplicationController::class, 'index']);
Route::post('/duplicates/resolve', [DeduplicationController::class, 'resolve']);

