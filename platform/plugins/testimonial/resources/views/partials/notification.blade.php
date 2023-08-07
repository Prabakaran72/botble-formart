<li class="dropdown dropdown-extended dropdown-inbox">
    <a href="javascript:;" class="dropdown-toggle dropdown-header-name" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="icon-envelope-open"></i>
        <span class="badge badge-default"> {{ $testimonials->total() }} </span>
    </a>
    <ul class="dropdown-menu dropdown-menu-right">
        <li class="external">
            <h3>{!! BaseHelper::clean(trans('plugins/testimonial::testimonial.new_msg_notice', ['count' => $testimonials->total()])) !!}</h3>
            <a href="{{ route('testimonials.index') }}">{{ trans('plugins/testimonial::testimonial.view_all') }}</a>
        </li>
        <li>
            <ul class="dropdown-menu-list scroller" style="height: {{ $testimonials->total() * 70 }}px;" data-handle-color="#637283">
                @foreach($testimonials as $testimonial)
                    <li>
                        <a href="{{ route('testimonials.edit', $testimonial->id) }}">
                            <span class="photo">
                                <img src="{{ $testimonial->avatar_url }}" class="rounded-circle" alt="{{ $testimonial->name }}">
                            </span>
                            <span class="subject"><span class="from"> {{ $testimonial->name }} </span><span class="time">{{ $testimonial->created_at->toDateTimeString() }} </span></span>
                            <span class="message"> {{ $testimonial->phone }} - {{ $testimonial->email }} </span>
                        </a>
                    </li>
                @endforeach

                @if ($testimonials->total() > 10)
                    <li class="text-center"><a href="{{ route('testimonials.index') }}">{{ trans('plugins/testimonial::testimonial.view_all') }}</a></li>
                @endif
            </ul>
        </li>
    </ul>
</li>
