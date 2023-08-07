<?php

namespace Botble\Testimonial\Repositories\Caches;

use Botble\Support\Repositories\Caches\CacheAbstractDecorator;
use Botble\Testimonial\Repositories\Interfaces\TestimonialInterface;
use Illuminate\Database\Eloquent\Collection;

class TestimonialCacheDecorator extends CacheAbstractDecorator implements TestimonialInterface
{
    public function getUnread(array $select = ['*']): Collection
    {
        return $this->getDataIfExistCache(__FUNCTION__, func_get_args());
    }

    public function countUnread(): int
    {
        return $this->getDataIfExistCache(__FUNCTION__, func_get_args());
    }
}
