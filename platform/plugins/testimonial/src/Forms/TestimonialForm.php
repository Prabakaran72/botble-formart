<?php

namespace Botble\Testimonial\Forms;

use Botble\Base\Facades\Assets;
use Botble\Base\Forms\FormAbstract;
use Botble\Testimonial\Enums\TestimonialStatusEnum;
use Botble\Testimonial\Http\Requests\EditTestimonialRequest;
use Botble\Testimonial\Models\Testimonial;

class TestimonialForm extends FormAbstract
{
    public function buildForm(): void  {
        Assets::addScriptsDirectly('vendor/core/plugins/testimonial/js/testimonial.js')
            ->addStylesDirectly('vendor/core/plugins/testimonial/css/testimonial.css');

        $this
            ->setupModel(new Testimonial())
            ->setValidatorClass(EditTestimonialRequest::class)
            ->withCustomFields()
            ->add('status', 'customSelect', [
                'label' => trans('core/base::tables.status'),
                'label_attr' => ['class' => 'control-label required'],
                'choices' => TestimonialStatusEnum::labels(),
            ])
            ->setBreakFieldPoint('status')
            ->addMetaBoxes([
                'information' => [
                    'title' => trans('plugins/testimonial::testimonial.testimonial_information'),
                    'content' => view('plugins/testimonial::testimonial-info', ['testimonial' => $this->getModel()])->render(),
                    'attributes' => [
                        'style' => 'margin-top: 0',
                    ],
                ],
                'replies' => [
                    'title' => trans('plugins/testimonial::testimonial.replies'),
                    'content' => view('plugins/testimonial::reply-box', ['testimonial' => $this->getModel()])->render(),
                ],
            ]);
    }
}
