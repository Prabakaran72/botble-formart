<?php

namespace Botble\Ecommerce\Repositories\Caches;

use Botble\Ecommerce\Repositories\Interfaces\ProductRecurringOptionInterface;
use Botble\Support\Repositories\Caches\CacheAbstractDecorator;

class ProductRecurringOptionCacheDecorator extends CacheAbstractDecorator implements ProductRecurringOptionInterface
{
    public function getCategories(array $param)
    {
        return $this->getDataIfExistCache(__FUNCTION__, func_get_args());
    }

    public function getDataSiteMap()
    {
        return $this->getDataIfExistCache(__FUNCTION__, func_get_args());
    }

    public function getFeaturedCategories($limit)
    {
        return $this->getDataIfExistCache(__FUNCTION__, func_get_args());
    }

    public function getAllCategories($active = true)
    {
        return $this->getDataIfExistCache(__FUNCTION__, func_get_args());
    }

    public function getProductCategories(
        array $conditions = [],
        array $with = [],
        array $withCount = [],
        bool $parentOnly = false
    ) {
        return $this->getDataIfExistCache(__FUNCTION__, func_get_args());
    }
}
