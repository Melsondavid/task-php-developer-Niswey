<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ContactController::class, 'index'])->name('contacts.index');
Route::post('/contacts/import', [ImportController::class, 'importXML'])->name('contacts.import');
Route::put('/contacts/{id}', [ImportController::class, 'updateContact']);
Route::delete('/contacts/{id}', [ImportController::class, 'deleteContact']);
