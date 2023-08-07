<?php

return [
    'name' => 'plugins/testimonial::testimonial.settings.email.title',
    'description' => 'plugins/testimonial::testimonial.settings.email.description',
    'templates' => [
        'notice' => [
            'title' => 'plugins/testimonial::testimonial.settings.email.templates.notice_title',
            'description' => 'plugins/testimonial::testimonial.settings.email.templates.notice_description',
            'subject' => 'Message sent via your testimonial form from {{ site_title }}',
            'can_off' => true,
            'variables' => [
                'testimonial_name' => 'Testimonial name',
                'testimonial_subject' => 'Testimonial subject',
                'testimonial_email' => 'Testimonial email',
                'testimonial_phone' => 'Testimonial phone',
                'testimonial_address' => 'Testimonial address',
                'testimonial_content' => 'Testimonial content',
            ],
        ],
    ],
];
