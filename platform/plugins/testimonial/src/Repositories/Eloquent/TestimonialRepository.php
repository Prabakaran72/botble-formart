<?php

namespace Botble\Testimonial\Repositories\Eloquent;

use Botble\Testimonial\Enums\TestimonialStatusEnum;
use Botble\Testimonial\Repositories\Interfaces\TestimonialInterface;
use Botble\Support\Repositories\Eloquent\RepositoriesAbstract;
use Illuminate\Database\Eloquent\Collection;

class TestimonialRepository extends RepositoriesAbstract implements TestimonialInterface
{
    public function getUnread(array $select = ['*']): Collection
    {
        $data = $this->model
            ->where('status', TestimonialStatusEnum::UNREAD)
            ->select($select)
            ->orderBy('created_at', 'DESC')
            ->get();

        $this->resetModel();

        return $data;
    }

    public function countUnread(): int
    {
        $data = $this->model->where('status', TestimonialStatusEnum::UNREAD)->count();
        $this->resetModel();

        return $data;
    }
}
