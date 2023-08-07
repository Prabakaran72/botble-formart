<x-core-setting::section
    :title="trans('plugins/testimonial::testimonial.settings.title')"
    :description="trans('plugins/testimonial::testimonial.settings.description')"
>
    <x-core-setting::form-group>
        <label class="text-title-field" for="blacklist_keywords">{{ trans('plugins/testimonial::testimonial.settings.blacklist_keywords') }}</label>
        <textarea data-counter="250" class="next-input tags" name="blacklist_keywords" id="blacklist_keywords" rows="3" placeholder="{{ trans('plugins/testimonial::testimonial.settings.blacklist_keywords_placeholder') }}">{{ setting('blacklist_keywords') }}</textarea>
        {{ Form::helper(trans('plugins/testimonial::testimonial.settings.blacklist_keywords_helper')) }}
    </x-core-setting::form-group>

    <x-core-setting::form-group>
        <label class="text-title-field" for="blacklist_email_domains">{{ trans('plugins/testimonial::testimonial.settings.blacklist_email_domains') }}</label>
        <textarea data-counter="250" class="next-input tags" name="blacklist_email_domains" id="blacklist_email_domains" rows="3" placeholder="{{ trans('plugins/testimonial::testimonial.settings.blacklist_email_domains_placeholder') }}">{{ setting('blacklist_email_domains') }}</textarea>
        {{ Form::helper(trans('plugins/testimonial::testimonial.settings.blacklist_email_domains_helper')) }}
    </x-core-setting::form-group>

    <x-core-setting::checkbox
        name="enable_math_captcha_for_testimonial_form"
        :label="trans('plugins/testimonial::testimonial.settings.enable_math_captcha')"
        :value="setting('enable_math_captcha_for_testimonial_form', false)"
    />
</x-core-setting::section>
