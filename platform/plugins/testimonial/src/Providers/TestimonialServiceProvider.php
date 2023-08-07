<?php

namespace Botble\Testimonial\Providers;

use Botble\Base\Facades\EmailHandler;
use Illuminate\Routing\Events\RouteMatched;
use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\Testimonial\Models\TestimonialReply;
use Botble\Testimonial\Repositories\Caches\TestimonialReplyCacheDecorator;
use Botble\Testimonial\Repositories\Eloquent\TestimonialReplyRepository;
use Botble\Testimonial\Repositories\Interfaces\TestimonialInterface;
use Botble\Testimonial\Models\Testimonial;
use Botble\Testimonial\Repositories\Caches\TestimonialCacheDecorator;
use Botble\Testimonial\Repositories\Eloquent\TestimonialRepository;
use Botble\Testimonial\Repositories\Interfaces\TestimonialReplyInterface;
use Illuminate\Support\ServiceProvider;

class TestimonialServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        $this->app->bind(TestimonialInterface::class, function () {
            return new TestimonialCacheDecorator(new TestimonialRepository(new Testimonial()));
        });

        $this->app->bind(TestimonialReplyInterface::class, function () {
            return new TestimonialReplyCacheDecorator(new TestimonialReplyRepository(new TestimonialReply()));
        });
    }

    public function boot(): void
    {
        $this
            ->setNamespace('plugins/testimonial')
            ->loadHelpers()
            ->loadAndPublishConfigurations(['permissions', 'email'])
            ->loadRoutes()
            ->loadAndPublishViews()
            ->loadAndPublishTranslations()
            ->loadMigrations()
            ->publishAssets();

        $this->app['events']->listen(RouteMatched::class, function () {
            dashboard_menu()->registerItem([
                'id' => 'cms-plugins-testimonial',
                'priority' => 120,
                'parent_id' => null,
                'name' => 'plugins/testimonial::testimonial.menu',
                'icon' => 'far fa-envelope',
                'url' => route('testimonials.index'),
                'permissions' => ['testimonials.index'],
            ]);

            EmailHandler::addTemplateSettings(TESTIMONIAL_MODULE_SCREEN_NAME, config('plugins.testimonial.email', []));
        });

        $this->app->booted(function () {
            $this->app->register(HookServiceProvider::class);
        });
    }
}
