<?php

namespace Botble\Testimonial\Models;

use Botble\Base\Casts\SafeContent;
use Botble\Base\Models\BaseModel;

class TestimonialReply extends BaseModel
{
    protected $table = 'testimonial_replies';

    protected $fillable = [
        'message',
        'testimonial_id',
    ];

    protected $casts = [
        'message' => SafeContent::class,
    ];
}
