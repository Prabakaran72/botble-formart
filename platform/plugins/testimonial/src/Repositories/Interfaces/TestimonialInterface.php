<?php

namespace Botble\Testimonial\Repositories\Interfaces;

use Botble\Support\Repositories\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface TestimonialInterface extends RepositoryInterface
{
    public function getUnread(array $select = ['*']): Collection;

    public function countUnread(): int;
}
