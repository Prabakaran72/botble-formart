<?php

namespace Botble\Testimonial\Models;

use Botble\Base\Casts\SafeContent;
use Botble\Base\Supports\Avatar;
use Botble\Testimonial\Enums\TestimonialStatusEnum;
use Botble\Base\Models\BaseModel;
use Exception;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Botble\Media\Facades\RvMedia;

class Testimonial extends BaseModel
{
    protected $table = 'testimonials';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'subject',
        'content',
        'status',
    ];

    protected $casts = [
        'status' => TestimonialStatusEnum::class,
        'name' => SafeContent::class,
        'address' => SafeContent::class,
        'subject' => SafeContent::class,
        'content' => SafeContent::class,
    ];

    public function replies(): HasMany
    {
        return $this->hasMany(TestimonialReply::class);
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                try {
                    return (new Avatar())->create($this->name)->toBase64();
                } catch (Exception) {
                    return RvMedia::getDefaultImage();
                }
            },
        );
    }
}
