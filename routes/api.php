<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\User\HomeApiController;
use App\Http\Controllers\Api\V1\Admin\AuthApiController;
use App\Http\Controllers\Api\V1\Admin\RolesApiController;
use App\Http\Controllers\Api\V1\Admin\UsersApiController;
use App\Http\Controllers\Api\V1\Admin\BannerApiController;
use App\Http\Controllers\Api\V1\Admin\ProfileApiController;
use App\Http\Controllers\Api\V1\Admin\BlogPostApiController;
use App\Http\Controllers\Api\V1\Admin\PermissionsApiController;
use App\Http\Controllers\Api\V1\TestController;
use App\Http\Controllers\Api\V1\User\NoAuth\UserBlogPostApiController;


Route::post('/auth/register', [AuthApiController::class, 'createUser']);
Route::post('/auth/login', [AuthApiController::class, 'loginUser']);
Route::get('/blog-detail/{id}', [HomeApiController::class, 'blogDetail']);

Route::post("/v1/test", [TestController::class, "index"]);


Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/saveToken', [BlogPostApiController::class, 'saveToken'])->name('save-token');
    Route::apiResource('/profiles', ProfileApiController::class);
    //name, email, phone change route 
    //welcome
    Route::post('/profileUpdate', [ProfileApiController::class, 'profileUpdate']);
    //user profile image change route
    Route::post('/profileImgUpdate', [ProfileApiController::class, 'profileImgUpdate']);
    Route::put('/profile/{profile}', [ProfileApiController::class, 'update'])->name('api.profile.update');
    Route::put('/phone-address-change', [ProfileApiController::class, 'PhoneAddressChange']);
    Route::put('/change-password', [ProfileApiController::class, 'changePassword'])->name('changePassword');

    Route::post('/auth/logout', [AuthApiController::class, 'logoutUser']);
    Route::post('/like/{id}', [HomeApiController::class, 'like']);
    Route::post('/comment/create/{id}', [HomeApiController::class, 'addComment']);
    Route::post('/comment/edit/{id}', [HomeApiController::class, 'updateComment']);
    Route::delete('/comment/delete/', [HomeApiController::class, 'deleteComment']);


});
// user
Route::post('/search', [HomeApiController::class, 'search']);
Route::get('/searchBlog/{search}', [HomeApiController::class, 'searchBlog']);

// Route::middleware(['award.points'])->get('/', [HomeApiController::class, 'index'])->name('welcome');
Route::middleware(['auth:sanctum', 'award.points'])->get('/', [HomeApiController::class, 'index'])->name('welcome');

//test
Route::get('/home', [HomeApiController::class, 'home']);
//test

Route::get('/banners', [HomeApiController::class, 'banners']);

//blog post
Route::get('/blog-posts', [UserBlogPostApiController::class, 'index']);

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'App\Http\Controllers\Api\V1\Admin', 'middleware' => ['auth:sanctum']], function () {
    // Permissions
    Route::apiResource('permissions', PermissionsApiController::class);
    // permissions update route
    Route::put('permissions/{permission}', [PermissionsApiController::class, 'update']);

    // Roles
    Route::apiResource('roles', RolesApiController::class);

    // Users
    Route::apiResource('users', UsersApiController::class);

    // profile resource rotues
    // PhoneAddressChange
    Route::put('/phone-address-change', [ProfileApiController::class, 'PhoneAddressChange']);
    Route::put('/change-password', [ProfileApiController::class, 'changePassword'])->name('changePassword');

    // Blog Post
    Route::post('blog-posts/media', [BlogPostApiController::class, 'storeMedia'])->name('blog-posts.storeMedia');

    //blog routes
    Route::apiResource('blog-posts', BlogPostApiController::class);
    Route::get('blog-posts/show/{id}', [BlogPostApiController::class, 'showDetail']);
    Route::post('blog-posts/update/{id}', [BlogPostApiController::class, 'updateBlog']);
    Route::post('blog-posts/media/update/{id}', [BlogPostApiController::class, 'updateMedia']);
    Route::post('blog-posts/media/delete/{id}', [BlogPostApiController::class, 'deleteMedia']);
    Route::get('/short-videos', [BlogPostApiController::class, 'shortVideos']);
    //blog routes

    Route::apiResource('banners', BannerApiController::class);
    Route::post('banners/{banner}', [BannerApiController::class, 'update']);
    Route::post('/banners/statusChange/{id}', [BannerApiController::class, 'statusChange']);
});


