<?php

namespace Botble\Testimonial\Http\Controllers;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Testimonial\Events\SentTestimonialEvent;
use Botble\Testimonial\Http\Requests\TestimonialRequest;
use Botble\Testimonial\Repositories\Interfaces\TestimonialInterface;
use Botble\Base\Facades\EmailHandler;
use Exception;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class PublicController extends Controller
{
    public function __construct(protected TestimonialInterface $testimonialRepository)
    {
    }

    public function postSendTestimonial(TestimonialRequest $request, BaseHttpResponse $response)
    {
        $blacklistDomains = setting('blacklist_email_domains');

        if ($blacklistDomains) {
            $emailDomain = Str::after(strtolower($request->input('email')), '@');

            $blacklistDomains = collect(json_decode($blacklistDomains, true))->pluck('value')->all();

            if (in_array($emailDomain, $blacklistDomains)) {
                return $response
                    ->setError()
                    ->setMessage(__('Your email is in blacklist. Please use another email address.'));
            }
        }

        $blacklistWords = trim(setting('blacklist_keywords', ''));

        if ($blacklistWords) {
            $content = strtolower($request->input('content'));

            $badWords = collect(json_decode($blacklistWords, true))
                ->filter(function ($item) use ($content) {
                    $matches = [];
                    $pattern = '/\b' . $item['value'] . '\b/iu';

                    return preg_match($pattern, $content, $matches, PREG_UNMATCHED_AS_NULL);
                })
                ->pluck('value')
                ->all();

            if (count($badWords)) {
                return $response
                    ->setError()
                    ->setMessage(__('Your message contains blacklist words: ":words".', ['words' => implode(', ', $badWords)]));
            }
        }

        try {
            $testimonial = $this->testimonialRepository->getModel();
            $testimonial->fill($request->input());
            $this->testimonialRepository->createOrUpdate($testimonial);

            event(new SentTestimonialEvent($testimonial));

            $args = [];

            if ($testimonial->name && $testimonial->email) {
                $args = ['replyTo' => [$testimonial->name => $testimonial->email]];
            }

            EmailHandler::setModule(TESTIMONIAL_MODULESCREEN_NAME)
                ->setVariableValues([
                    'testimonial_name' => $testimonial->name ?? 'N/A',
                    'testimonial_subject' => $testimonial->subject ?? 'N/A',
                    'testimonial_email' => $testimonial->email ?? 'N/A',
                    'testimonial_phone' => $testimonial->phone ?? 'N/A',
                    'testimonial_address' => $testimonial->address ?? 'N/A',
                    'testimonial_content' => $testimonial->content ?? 'N/A',
                ])
                ->sendUsingTemplate('notice', null, $args);

            return $response->setMessage(__('Send message successfully!'));
        } catch (Exception $exception) {
            info($exception->getMessage());

            return $response
                ->setError()
                ->setMessage(__("Can't send message on this time, please try again later!"));
        }
    }
}
