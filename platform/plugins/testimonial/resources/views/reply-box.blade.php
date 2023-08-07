@if ($testimonial)
    <div id="reply-wrapper">
        @if (count($testimonial->replies) > 0)
            @foreach($testimonial->replies as $reply)
                <p>{{ trans('plugins/testimonial::testimonial.tables.time') }}: <i>{{ $reply->created_at }}</i></p>
                <p>{{ trans('plugins/testimonial::testimonial.tables.content') }}:</p>
                <pre class="message-content">{!! BaseHelper::clean($reply->message) !!}</pre>
            @endforeach
        @else
            <p>{{ trans('plugins/testimonial::testimonial.no_reply') }}</p>
        @endif
    </div>

    <p><button class="btn btn-info answer-trigger-button">{{ trans('plugins/testimonial::testimonial.reply') }}</button></p>

    <div class="answer-wrapper">
        <div class="form-group mb-3">
            {!! Form::editor('message', null, ['without-buttons' => true, 'class' => 'form-control']) !!}
        </div>

        <div class="form-group mb-3">
            <input type="hidden" value="{{ $testimonial->id }}" id="input_testimonial_id">
            <button class="btn btn-success answer-send-button"><i class="fas fa-reply"></i> {{ trans('plugins/testimonial::testimonial.send') }}</button>
        </div>
    </div>
@endif
