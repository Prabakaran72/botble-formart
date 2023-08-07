<?php

use Botble\Base\Facades\BaseHelper;
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Botble\Testimonial\Http\Controllers', 'middleware' => ['web', 'core']], function () {
    Route::group(['prefix' => BaseHelper::getAdminPrefix(), 'middleware' => 'auth'], function () {
        Route::group(['prefix' => 'testimonials', 'as' => 'testimonials.'], function () {
            Route::resource('', 'TestimonialController')->except(['create', 'store'])->parameters(['' => 'testimonial']);

            Route::delete('items/destroy', [
                'as' => 'deletes',
                'uses' => 'TestimonialController@deletes',
                'permission' => 'testimonials.destroy',
            ]);

            Route::post('reply/{id}', [
                'as' => 'reply',
                'uses' => 'TestimonialController@postReply',
                'permission' => 'testimonials.edit',
            ])->wherePrimaryKey();
        });
    });

    Route::group(apply_filters(BASE_FILTER_GROUP_PUBLIC_ROUTE, []), function () {
        Route::post('testimonial/send', [
            'as' => 'public.send.testimonial',
            'uses' => 'PublicController@postSendTestimonial',
        ]);
    });
});
