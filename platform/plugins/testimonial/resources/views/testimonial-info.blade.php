@if ($testimonial)
    <p>{{ trans('plugins/testimonial::testimonial.tables.time') }}: <i>{{ $testimonial->created_at }}</i></p>
    <p>{{ trans('plugins/testimonial::testimonial.tables.full_name') }}: <i>{{ $testimonial->name }}</i></p>
    <p>{{ trans('plugins/testimonial::testimonial.tables.email') }}: <i><a href="mailto:{{ $testimonial->email }}">{{ $testimonial->email }}</a></i></p>
    <p>{{ trans('plugins/testimonial::testimonial.tables.phone') }}: <i>@if ($testimonial->phone) <a href="tel:{{ $testimonial->phone }}">{{ $testimonial->phone }}</a> @else N/A @endif</i></p>
    <p>{{ trans('plugins/testimonial::testimonial.tables.address') }}: <i>{{ $testimonial->address ?: 'N/A' }}</i></p>
    <p>{{ trans('plugins/testimonial::testimonial.tables.subject') }}: <i>{{ $testimonial->subject ?: 'N/A' }}</i></p>
    <p>{{ trans('plugins/testimonial::testimonial.tables.content') }}:</p>
    <pre class="message-content">{{ $testimonial->content ?: '...' }}</pre>
@endif
