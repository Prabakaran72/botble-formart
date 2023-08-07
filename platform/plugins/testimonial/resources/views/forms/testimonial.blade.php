{!! Form::open(['route' => 'public.send.testimonial', 'method' => 'POST', 'class' => 'testimonial-form']) !!}
    <div class="testimonial-form-row">
        {!! apply_filters('pre_testimonial_form', null) !!}

        <div class="testimonial-column-6">
            <div class="testimonial-form-group">
                <label for="testimonial_name" class="testimonial-label required">{{ __('Name') }}</label>
                <input type="text" class="testimonial-form-input" name="name" value="{{ old('name') }}" id="testimonial_name"
                       placeholder="{{ __('Name') }}">
            </div>
        </div>
        <div class="testimonial-column-6">
            <div class="testimonial-form-group">
                <label for="testimonial_email" class="testimonial-label required">{{ __('Email') }}</label>
                <input type="email" class="testimonial-form-input" name="email" value="{{ old('email') }}" id="testimonial_email"
                       placeholder="{{ __('Email') }}">
            </div>
        </div>
    </div>
    <div class="testimonial-form-row">
        <div class="testimonial-column-6">
            <div class="testimonial-form-group">
                <label for="testimonial_address" class="testimonial-label">{{ __('Address') }}</label>
                <input type="text" class="testimonial-form-input" name="address" value="{{ old('address') }}" id="testimonial_address"
                       placeholder="{{ __('Address') }}">
            </div>
        </div>
        <div class="testimonial-column-6">
            <div class="testimonial-form-group">
                <label for="testimonial_phone" class="testimonial-label">{{ __('Phone') }}</label>
                <input type="text" class="testimonial-form-input" name="phone" value="{{ old('phone') }}" id="testimonial_phone"
                       placeholder="{{ __('Phone') }}">
            </div>
        </div>
    </div>
    <div class="testimonial-form-row">
        <div class="testimonial-column-12">
            <div class="testimonial-form-group">
                <label for="testimonial_subject" class="testimonial-label">{{ __('Subject') }}</label>
                <input type="text" class="testimonial-form-input" name="subject" value="{{ old('subject') }}" id="testimonial_subject"
                       placeholder="{{ __('Subject') }}">
            </div>
        </div>
    </div>
    <div class="testimonial-form-row">
        <div class="testimonial-column-12">
            <div class="testimonial-form-group">
                <label for="testimonial_content" class="testimonial-label required">{{ __('Message') }}</label>
                <textarea name="content" id="testimonial_content" class="testimonial-form-input" rows="5" placeholder="{{ __('Message') }}">{{ old('content') }}</textarea>
            </div>
        </div>
    </div>

    @if (is_plugin_active('captcha'))
        @if (Captcha::isEnabled())
            <div class="testimonial-form-row">
                <div class="testimonial-column-12">
                    <div class="testimonial-form-group">
                        {!! Captcha::display() !!}
                    </div>
                </div>
            </div>
        @endif

        @if (setting('enable_math_captcha_for_testimonial_form', 0))
            <div class="testimonial-form-group">
                <label for="math-group" class="testimonial-label required">{{ app('math-captcha')->label() }}</label>
                {!! app('math-captcha')->input(['class' => 'testimonial-form-input', 'id' => 'math-group']) !!}
            </div>
        @endif
    @endif

    {!! apply_filters('after_testimonial_form', null) !!}

    <div class="testimonial-form-group"><p>{!! BaseHelper::clean(__('The field with (<span style="color:#FF0000;">*</span>) is required.')) !!}</p></div>

    <div class="testimonial-form-group">
        <button type="submit" class="testimonial-button">{{ __('SUBMIT') }}</button>
    </div>

    <div class="testimonial-form-group">
        <div class="testimonial-message testimonial-success-message" style="display: none"></div>
        <div class="testimonial-message testimonial-error-message" style="display: none"></div>
    </div>

{!! Form::close() !!}
