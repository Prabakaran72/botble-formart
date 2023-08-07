@php
    $layout = theme_option('product_list_layout');

    $requestLayout = (string)request()->input('layout');
    if ($requestLayout && in_array($requestLayout, array_keys(get_product_single_layouts()))) {
        $layout = $requestLayout;
    }

    $layout = ($layout && in_array($layout, array_keys(get_product_single_layouts()))) ? $layout : 'product-full-width';
@endphp

<div class="list-content-loading">
    <div class="half-circle-spinner">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>
</div>

<div class="shop-product-filter">
    <div class="total-product">
        <p>{!! BaseHelper::clean(__('We found :total items for you!', ['total' => '<strong class="text-brand">' . $products->total() . '</strong>'])) !!}</p>
    </div>
    @include(Theme::getThemeNamespace() . '::views/ecommerce/includes/sort')
</div>

<input type="hidden" name="page" data-value="{{ $products->currentPage() }}">
<input type="hidden" name="sort-by" value="{{ json_encode(request()->input('sort-by')) }}">
<input type="hidden" name="num" value="{{ (int)request()->input('num') }}">
<input type="hidden" name="q" value="{{ BaseHelper::stringify(request()->input('q')) }}">

<div class="row product-grid">
    @forelse ($products as $product)
        <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-4 col-12 col-sm-6">
            @include(Theme::getThemeNamespace() . '::views.ecommerce.includes.product-item', compact('product'))
        </div>
    @empty
        <div class="mt__60 mb__60 text-center">
            <p>{{ __('No products found!') }}</p>
        </div>
    @endforelse
</div>

@if ($products->total() > 0)
    <br>
    {!! $products->withQueryString()->links(Theme::getThemeNamespace() . '::partials.custom-pagination') !!}
@endif
