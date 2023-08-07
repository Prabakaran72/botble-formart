<?php

return [
    [
        'name' => 'Testimonial',
        'flag' => 'testimonials.index',
    ],
    [
        'name' => 'Edit',
        'flag' => 'testimonials.edit',
        'parent_flag' => 'testimonials.index',
    ],
    [
        'name' => 'Delete',
        'flag' => 'testimonials.destroy',
        'parent_flag' => 'testimonials.index',
    ],
];
