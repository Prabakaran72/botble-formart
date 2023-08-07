<?php

namespace Botble\Testimonial\Http\Requests;

use Botble\Testimonial\Enums\TestimonialStatusEnum;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class EditTestimonialRequest extends Request
{
    public function rules(): array
    {
        return [
            'status' => Rule::in(TestimonialStatusEnum::values()),
        ];
    }
}
