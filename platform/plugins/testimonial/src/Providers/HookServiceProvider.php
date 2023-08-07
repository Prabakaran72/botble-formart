<?php

namespace Botble\Testimonial\Providers;

use Botble\Base\Facades\Assets;
use Botble\Testimonial\Enums\TestimonialStatusEnum;
use Botble\Testimonial\Repositories\Interfaces\TestimonialInterface;
use Botble\Shortcode\Compilers\Shortcode;
use Collective\Html\HtmlFacade as Html;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Botble\Theme\Facades\Theme;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_filter(BASE_FILTER_TOP_HEADER_LAYOUT, [$this, 'registerTopHeaderNotification'], 120);
        add_filter(BASE_FILTER_APPEND_MENU_NAME, [$this, 'getUnreadCount'], 120, 2);
        add_filter(BASE_FILTER_MENU_ITEMS_COUNT, [$this, 'getMenuItemCount'], 120);

        if (function_exists('add_shortcode')) {
            add_shortcode(
                'testimonial-form',
                trans('plugins/testimonial::testimonial.shortcode_name'),
                trans('plugins/testimonial::testimonial.shortcode_description'),
                [$this, 'form']
            );

            shortcode()
                ->setAdminConfig('testimonial-form', view('plugins/testimonial::partials.short-code-admin-config')->render());
        }

        add_filter(BASE_FILTER_AFTER_SETTING_CONTENT, [$this, 'addSettings'], 93);
    }

    public function registerTopHeaderNotification(?string $options): ?string
    {
        if (Auth::user()->hasPermission('testimonials.edit')) {
            $testimonials = $this->app[TestimonialInterface::class]
                ->advancedGet([
                    'condition' => [
                        'status' => TestimonialStatusEnum::UNREAD,
                    ],
                    'paginate' => [
                        'per_page' => 10,
                        'current_paged' => 1,
                    ],
                    'select' => ['id', 'name', 'email', 'phone', 'created_at'],
                    'order_by' => ['created_at' => 'DESC'],
                ]);

            if ($testimonials->count() == 0) {
                return $options;
            }

            return $options . view('plugins/testimonial::partials.notification', compact('testimonials'))->render();
        }

        return $options;
    }

    public function getUnreadCount(string|null|int $number, string $menuId): int|string|null
    {
        if ($menuId !== 'cms-plugins-testimonial') {
            return $number;
        }

        $attributes = [
            'class' => 'badge badge-success menu-item-count unread-testimonials',
            'style' => 'display: none;',
        ];

        return Html::tag('span', '', $attributes)->toHtml();
    }

    public function getMenuItemCount(array $data = []): array
    {
        if (Auth::user()->hasPermission('testimonials.index')) {
            $data[] = [
                'key' => 'unread-testimonials',
                'value' => app(TestimonialInterface::class)->countUnread(),
            ];
        }

        return $data;
    }

    public function form(Shortcode $shortcode): string
    {
        $view = apply_filters(TESTIMONIAL_FORM_TEMPLATE_VIEW, 'plugins/testimonial::forms.testimonial');

        if (defined('THEME_OPTIONS_MODULE_SCREEN_NAME')) {
            $this->app->booted(function () {
                Theme::asset()
                    ->usePath(false)
                    ->add('testimonial-css', asset('vendor/core/plugins/testimonial/css/testimonial-public.css'), [], [], '1.0.0');

                Theme::asset()
                    ->container('footer')
                    ->usePath(false)
                    ->add(
                        'testimonial-public-js',
                        asset('vendor/core/plugins/testimonial/js/testimonial-public.js'),
                        ['jquery'],
                        [],
                        '1.0.0'
                    );
            });
        }

        if ($shortcode->view && view()->exists($shortcode->view)) {
            $view = $shortcode->view;
        }

        return view($view, compact('shortcode'))->render();
    }

    public function addSettings(?string $data = null): string
    {
        Assets::addStylesDirectly('vendor/core/core/base/libraries/tagify/tagify.css')
            ->addScriptsDirectly([
                'vendor/core/core/base/libraries/tagify/tagify.js',
                'vendor/core/core/base/js/tags.js',
            ]);

        return $data . view('plugins/testimonial::settings')->render();
    }
}
