<?php
use App\Http\Controllers\TestFormController;
use Illuminate\Routing\Route;

Route::get('/create', [TestFormController::class, 'create'])->name('create');
Route::post('/store', [TestFormController::class, 'store'])->name('store');
