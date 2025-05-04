<?php

use Illuminate\Support\Facades\Route;
use RagKit\Http\Controllers\RagChatController;
use RagKit\Http\Controllers\RagDocumentController;

Route::middleware(config('ragkit.routes.middleware', ['web', 'auth']))
    ->prefix(config('ragkit.routes.prefix', 'rag'))
    ->name('ragkit.')
    ->group(function () {
        // Account and collection endpoints
        Route::get('/accounts', [RagChatController::class, 'getAccounts'])->name('accounts');
        Route::get('/collections', [RagChatController::class, 'getCollections'])->name('collections');
        Route::get('/documents', [RagChatController::class, 'getDocuments'])->name('documents');
        
        // Chat endpoints
        Route::post('/chat', [RagChatController::class, 'chat'])->name('chat');
        
        // Document management
        Route::post('/documents/upload', [RagDocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/{uuid}', [RagDocumentController::class, 'show'])->name('documents.show');
        Route::delete('/documents/{uuid}', [RagDocumentController::class, 'destroy'])->name('documents.destroy');
        Route::get('/documents/{uuid}/outline-faq', [RagDocumentController::class, 'getOutlineFaq'])->name('documents.outline-faq');
    }); 