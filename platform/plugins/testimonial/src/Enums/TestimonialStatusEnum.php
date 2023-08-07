<?php

namespace Botble\Testimonial\Enums;

use Botble\Base\Supports\Enum;
use Collective\Html\HtmlFacade as Html;
use Illuminate\Support\HtmlString;

/**
 * @method static TestimonialStatusEnum UNREAD()
 * @method static TestimonialStatusEnum READ()
 */
class TestimonialStatusEnum extends Enum
{
    public const READ = 'read';
    public const UNREAD = 'unread';

    public static $langPath = 'plugins/testimonial::testimonial.statuses';

    public function toHtml(): HtmlString|string
    {
        return match ($this->value) {
            self::UNREAD => Html::tag('span', self::UNREAD()->label(), ['class' => 'label-warning status-label'])
                ->toHtml(),
            self::READ => Html::tag('span', self::READ()->label(), ['class' => 'label-success status-label'])
                ->toHtml(),
            default => parent::toHtml(),
        };
    }
}
