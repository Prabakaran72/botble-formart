<?php

namespace Botble\Testimonial\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\Testimonial\Exports\TestimonialExport;
use Botble\Testimonial\Models\Testimonial;
use Collective\Html\HtmlFacade as Html;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Botble\Testimonial\Enums\TestimonialStatusEnum;
use Botble\Testimonial\Repositories\Interfaces\TestimonialInterface;
use Botble\Table\Abstracts\TableAbstract;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class TestimonialTable extends TableAbstract
{
    protected $hasActions = true;

    protected $hasFilter = true;

    protected string $exportClass = TestimonialExport::class;

    public function __construct(DataTables $table, UrlGenerator $urlGenerator, TestimonialInterface $testimonialRepository)
    {
        parent::__construct($table, $urlGenerator);

        $this->repository = $testimonialRepository;

        if (! Auth::user()->hasAnyPermission(['testimonials.edit', 'testimonials.destroy'])) {
            $this->hasOperations = false;
            $this->hasActions = false;
        }
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('name', function (Testimonial $item) {
                if (! Auth::user()->hasPermission('testimonials.edit')) {
                    return BaseHelper::clean($item->name);
                }

                return Html::link(route('testimonials.edit', $item->id), BaseHelper::clean($item->name));
            })
            ->editColumn('checkbox', function (Testimonial $item) {
                return $this->getCheckbox($item->id);
            })
            ->editColumn('created_at', function (Testimonial $item) {
                return BaseHelper::formatDate($item->created_at);
            })
            ->editColumn('status', function (Testimonial $item) {
                return $item->status->toHtml();
            })
            ->addColumn('operations', function (Testimonial $item) {
                return $this->getOperations('testimonials.edit', 'testimonials.destroy', $item);
            });

        return $this->toJson($data);
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        $query = $this->repository->getModel()->select([
            'id',
            'name',
            'phone',
            'email',
            'created_at',
            'status',
        ]);

        return $this->applyScopes($query);
    }

    public function columns(): array
    {
        return [
            'id' => [
                'title' => trans('core/base::tables.id'),
                'width' => '20px',
            ],
            'name' => [
                'title' => trans('core/base::tables.name'),
                'class' => 'text-start',
            ],
            'email' => [
                'title' => trans('plugins/testimonial::testimonial.tables.email'),
                'class' => 'text-start',
            ],
            'phone' => [
                'title' => trans('plugins/testimonial::testimonial.tables.phone'),
            ],
            'created_at' => [
                'title' => trans('core/base::tables.created_at'),
                'width' => '100px',
            ],
            'status' => [
                'title' => trans('core/base::tables.status'),
                'width' => '100px',
            ],
        ];
    }

    public function bulkActions(): array
    {
        return $this->addDeleteAction(route('testimonials.deletes'), 'testimonials.destroy', parent::bulkActions());
    }

    public function getBulkChanges(): array
    {
        return [
            'name' => [
                'title' => trans('core/base::tables.name'),
                'type' => 'text',
                'validate' => 'required|max:120',
            ],
            'email' => [
                'title' => trans('core/base::tables.email'),
                'type' => 'text',
                'validate' => 'required|max:120',
            ],
            'phone' => [
                'title' => trans('plugins/testimonial::testimonial.sender_phone'),
                'type' => 'text',
                'validate' => 'required|max:120',
            ],
            'status' => [
                'title' => trans('core/base::tables.status'),
                'type' => 'customSelect',
                'choices' => TestimonialStatusEnum::labels(),
                'validate' => 'required|' . Rule::in(TestimonialStatusEnum::values()),
            ],
            'created_at' => [
                'title' => trans('core/base::tables.created_at'),
                'type' => 'datePicker',
            ],
        ];
    }

    public function getDefaultButtons(): array
    {
        return [
            'export',
            'reload',
        ];
    }
}
