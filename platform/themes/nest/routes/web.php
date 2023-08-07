<?php

// Custom routes
use Theme\Nest\Http\Controllers\NestController;

Route::group(['controller' => NestController::class, 'middleware' => ['web', 'core']], function () {
    Route::group(apply_filters(BASE_FILTER_GROUP_PUBLIC_ROUTE, []), function () {
        Route::group(['prefix' => 'ajax', 'as' => 'public.ajax.'], function () {
            Route::get('cart', 'ajaxCart')
                ->name('cart');

            Route::get('products', 'ajaxGetProducts')
                ->name('products');

            Route::get('product-categories/products', 'ajaxGetProductsByCategoryId')
                ->name('product-category-products');

            Route::get('featured-products', 'getFeaturedProducts')
                ->name('featured-products');

            Route::get('posts', 'ajaxGetPosts')->name('posts');

            Route::get('featured-product-categories', 'getFeaturedProductCategories')
                ->name('featured-product-categories');

            Route::get('featured-brands', 'ajaxGetFeaturedBrands')
                ->name('featured-brands');

            Route::get('related-products/{id}', 'ajaxGetRelatedProducts')
                ->name('related-products');

            Route::get('product-reviews/{id}', 'ajaxGetProductReviews')
                ->name('product-reviews');

            Route::get('get-flash-sales', 'ajaxGetFlashSales')
                ->name('get-flash-sales');

            Route::get('quick-view/{id}', 'getQuickView')
                ->name('quick-view');

            Route::get('product-categories', 'ajaxGetProductCategories')
                ->name('product-categories');

            Route::get('top-products-group', 'ajaxTopProductsGroup')
                ->name('top-products-group');

            Route::get('popular-products', 'ajaxPopularProducts')
                ->name('popular-products');

            Route::get('search-products', 'ajaxSearchProducts')
                ->name('search-products');
        });
    });
});

Theme::routes();
