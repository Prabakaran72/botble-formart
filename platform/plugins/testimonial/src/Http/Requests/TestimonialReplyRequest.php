<?php

namespace Botble\Testimonial\Http\Requests;

use Botble\Support\Http\Requests\Request;

class TestimonialReplyRequest extends Request
{
    public function rules(): array
    {
        return [
            'message' => 'required|string',
        ];
    }
}
