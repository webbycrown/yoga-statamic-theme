<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\NewsLetterController;
use Statamic\Facades\Site;

Site::all()->each(function (Statamic\Sites\Site $site) {
	Route::prefix($site->url())->group(function () {
		Route::statamic('/class/category/{category_slug}', 'class-category');
	});
});

Route::get('/blog-search', [BlogController::class, 'search'])->name('blog.search');
Route::get('/header-search', [BlogController::class, 'headerSearch'])->name('header.search');
Route::get('/classes-search', [BlogController::class, 'classesSearch'])->name('classes.search');

// Route to handle newsletter subscription form submissions
Route::get('/newsLetter', [NewsLetterController::class, 'newsLetter'])->name('newsLetter');