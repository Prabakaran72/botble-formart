<?php

namespace Botble\Testimonial\Http\Controllers;

use Botble\Base\Facades\PageTitle;
use Botble\Base\Forms\FormBuilder;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Traits\HasDeleteManyItemsTrait;
use Botble\Testimonial\Enums\TestimonialStatusEnum;
use Botble\Testimonial\Forms\TestimonialForm;
use Botble\Testimonial\Http\Requests\TestimonialReplyRequest;
use Botble\Testimonial\Http\Requests\EditTestimonialRequest;
use Botble\Testimonial\Models\Testimonial;
use Botble\Testimonial\Repositories\Interfaces\TestimonialReplyInterface;
use Botble\Testimonial\Tables\TestimonialTable;
use Botble\Testimonial\Repositories\Interfaces\TestimonialInterface;
use Botble\Base\Facades\EmailHandler;
use Exception;
use Illuminate\Http\Request;
use Botble\Base\Events\DeletedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;

class TestimonialController extends BaseController
{
    use HasDeleteManyItemsTrait;

    public function __construct(protected TestimonialInterface $testimonialRepository)
    {
    }

    public function index(TestimonialTable $dataTable)
    {
        PageTitle::setTitle(trans('plugins/testimonial::testimonial.menu'));

        return $dataTable->renderTable();
    }

    public function edit(Testimonial $testimonial, FormBuilder $formBuilder)
    {
        PageTitle::setTitle(trans('plugins/testimonial::testimonial.edit'));

        return $formBuilder->create(TestimonialForm::class, ['model' => $testimonial])->renderForm();
    }

    public function update(Testimonial $testimonial, EditTestimonialRequest $request, BaseHttpResponse $response)
    {
        $testimonial->fill($request->input());

        $this->testimonialRepository->createOrUpdate($testimonial);

        event(new UpdatedContentEvent(TESTIMONIAL_MODULE_SCREEN_NAME, $request, $testimonial));

        return $response
            ->setPreviousUrl(route('testimonials.index'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(Testimonial $testimonial, Request $request, BaseHttpResponse $response)
    {
        try {
            $this->testimonialRepository->delete($testimonial);
            event(new DeletedContentEvent(TESTIMONIAL_MODULE_SCREEN_NAME, $request, $testimonial));

            return $response->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function deletes(Request $request, BaseHttpResponse $response)
    {
        return $this->executeDeleteItems($request, $response, $this->testimonialRepository, TESTIMONIAL_MODULE_SCREEN_NAME);
    }

    public function postReply(
        int|string $id,
        TestimonialReplyRequest $request,
        BaseHttpResponse $response,
        TestimonialReplyInterface $testimonialReplyRepository
    ) {
        $testimonial = $this->testimonialRepository->findOrFail($id);

        EmailHandler::send($request->input('message'), 'Re: ' . $testimonial->subject, $testimonial->email);

        $testimonialReplyRepository->create([
            'message' => $request->input('message'),
            'testimonial_id' => $testimonial->id,
        ]);

        $testimonial->status = TestimonialStatusEnum::READ();
        $this->testimonialRepository->createOrUpdate($testimonial);

        return $response
            ->setMessage(trans('plugins/testimonial::testimonial.message_sent_success'));
    }
}
