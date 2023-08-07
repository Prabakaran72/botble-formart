<?php

namespace Botble\Ecommerce\Models;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Models\BaseModel;
use Botble\Ecommerce\Tables\ProductTable;
use Collective\Html\HtmlFacade as Html;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ProductRecurringOption extends BaseModel
{
    protected $table = 'ec_product_recurring_option';

    protected $fillable = [
        'user_id','product_id','entry_date','is_recurring','recurring_daily','start_date','end_date','recurring_weekly','start_date','end_date','days','recurring_monthly','start_date','end_date','dates','recurring_yearly','start_date','end_date','created_by','created_at','update_by','update_at','status',
    ];

    protected $casts = [
        'status' => BaseStatusEnum::class,
    ];

    public function products(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Product::class,
                'ec_product_recurring_option',
                'product_id'
            )
            ->where('is_variation', 0);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductRecurringOption::class, 'parent_id')->withDefault();
    }

    protected function parents(): Attribute
    {
        return Attribute::make(
            get: function (): Collection {
                $parents = collect();

                $parent = $this->parent;

                while ($parent->id) {
                    $parents->push($parent);
                    $parent = $parent->parent;
                }

                return $parents;
            },
        );
    }

    protected function badgeWithCount(): Attribute
    {
        return Attribute::make(
            get: function (): HtmlString {
                $badge = match ($this->status->getValue()) {
                    BaseStatusEnum::DRAFT => 'bg-secondary',
                    BaseStatusEnum::PENDING => 'bg-warning',
                    default => 'bg-success',
                };

                $link = route('products.index', [
                    'filter_table_id' => strtolower(Str::slug(Str::snake(ProductTable::class))),
                    'class' => Product::class,
                    'filter_columns' => ['category'],
                    'filter_operators' => ['='],
                    'filter_values' => [$this->id],
                ]);

                return Html::link($link, (string)$this->products_count, [
                    'class' => 'badge font-weight-bold ' . $badge,
                    'data-bs-toggle' => 'tooltip',
                    'data-bs-original-title' => trans('plugins/ecommerce::product-categories.total_products', ['total' => $this->products_count]),
                ]);
            },
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProductRecurringOption::class, 'parent_id');
    }

    public function activeChildren(): HasMany
    {
        return $this
            ->children()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->with(['slugable', 'activeChildren']);
    }

    protected static function boot(): void
    {
        parent::boot();

        self::deleting(function (ProductRecurringOption $category) {
            $category->products()->detach();

            foreach ($category->children()->get() as $child) {
                $child->delete();
            }
        });
    }
}
