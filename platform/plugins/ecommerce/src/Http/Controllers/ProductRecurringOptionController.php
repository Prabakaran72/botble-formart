<?php

namespace Botble\Ecommerce\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Events\DeletedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Base\Facades\PageTitle;
use Botble\Base\Forms\FormAbstract;
use Botble\Base\Forms\FormBuilder;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Forms\ProductCategoryForm;
use Botble\Ecommerce\Http\Requests\ProductCategoryRequest;
use Botble\Ecommerce\Http\Resources\ProductCategoryResource;
use Botble\Ecommerce\Models\ProductCategory;
use Botble\Ecommerce\Repositories\Interfaces\ProductRecurringOptionInterface;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductRecurringOptionController extends BaseController
{
    public function __construct(protected ProductRecurringOptionInterface $productRecurringOptionRepository)
    {
        
    }
    
    


    public function index(FormBuilder $formBuilder, Request $request, BaseHttpResponse $response)
    {
        PageTitle::setTitle(trans('plugins/ecommerce::product-recurring-option.name'));

        $categories = $this->productRecurringOptionRepository->getProductCategories([], ['slugable'], ['products']);

        // if ($request->ajax()) {
        //     $data = view('core/base::forms.partials.tree-categories', $this->getOptions(compact('categories')))
        //         ->render();

        //     return $response->setData($data);
        // }

        // Assets::addStylesDirectly(['vendor/core/core/base/css/tree-category.css'])
        //     ->addScriptsDirectly(['vendor/core/core/base/js/tree-category.js']);

        $form = $formBuilder->create(ProductCategoryForm::class, ['template' => 'core/base::forms.form-product-recurring-option']);
        $form = $this->setFormOptions($form, null, compact('categories'));

        return $form->renderForm();
    }

    public function create(FormBuilder $formBuilder, Request $request, BaseHttpResponse $response)
    {
        PageTitle::setTitle(trans('plugins/ecommerce::product-recurring-option.create'));

        if ($request->ajax()) {
            return $response->setData($this->getForm());
        }

        return $formBuilder->create(ProductCategoryForm::class)->renderForm();
    }

    public function store(ProductCategoryRequest $request, BaseHttpResponse $response)
    {
        $productCategory = $this->productRecurringOptionRepository->createOrUpdate($request->input());

        event(new CreatedContentEvent(PRODUCT_CATEGORY_MODULE_SCREEN_NAME, $request, $productCategory));

        if ($request->ajax()) {
            $productCategory = $this->productRecurringOptionRepository->findOrFail($productCategory->id);

            if ($request->input('submit') == 'save') {
                $form = $this->getForm();
            } else {
                $form = $this->getForm($productCategory);
            }

            $response->setData([
                'model' => $productCategory,
                'form' => $form,
            ]);
        }

        return $response
                ->setPreviousUrl(route('product-recurring-option.index'))
                ->setNextUrl(route('product-recurring-option.edit', $productCategory->id))
                ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function edit(int|string $id, FormBuilder $formBuilder, Request $request, BaseHttpResponse $response)
    {
        $productCategory = $this->productRecurringOptionRepository->findOrFail($id);

        if ($request->ajax()) {
            return $response->setData($this->getForm($productCategory));
        }

        PageTitle::setTitle(trans('core/base::forms.edit_item', ['name' => $productCategory->name]));

        return $formBuilder->create(ProductCategoryForm::class, ['model' => $productCategory])->renderForm();
    }

    public function update(int|string $id, ProductCategoryRequest $request, BaseHttpResponse $response)
    {
        $productCategory = $this->productRecurringOptionRepository->findOrFail($id);
        $productCategory->fill($request->input());

        $this->productRecurringOptionRepository->createOrUpdate($productCategory);
        event(new UpdatedContentEvent(PRODUCT_CATEGORY_MODULE_SCREEN_NAME, $request, $productCategory));

        if ($request->ajax()) {
            $productCategory = $this->productRecurringOptionRepository->findOrFail($id);

            if ($request->input('submit') == 'save') {
                $form = $this->getForm();
            } else {
                $form = $this->getForm($productCategory);
            }
            $response->setData([
                'model' => $productCategory,
                'form' => $form,
            ]);
        }

        return $response
                ->setPreviousUrl(route('product-recurring-option.index'))
                ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(int|string $id, Request $request, BaseHttpResponse $response)
    {
        try {
            $productCategory = $this->productRecurringOptionRepository->findOrFail($id);

            $this->productRecurringOptionRepository->delete($productCategory);
            event(new DeletedContentEvent(PRODUCT_CATEGORY_MODULE_SCREEN_NAME, $request, $productCategory));

            return $response->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function deletes(Request $request, BaseHttpResponse $response)
    {
        $ids = $request->input('ids');
        if (empty($ids)) {
            return $response
                ->setError()
                ->setMessage(trans('core/base::notices.no_select'));
        }

        foreach ($ids as $id) {
            $productCategory = $this->productRecurringOptionRepository->findOrFail($id);
            $this->productRecurringOptionRepository->delete($productCategory);
            event(new DeletedContentEvent(PRODUCT_CATEGORY_MODULE_SCREEN_NAME, $request, $productCategory));
        }

        return $response->setMessage(trans('core/base::notices.delete_success_message'));
    }

    protected function getForm(?ProductCategory $model = null): string
    {
        $options = ['template' => 'core/base::forms.forms.form-no-wrap'];
        if ($model) {
            $options['model'] = $model;
        }

        $form = app(FormBuilder::class)->create(ProductCategoryForm::class, $options);

        $form = $this->setFormOptions($form, $model);

        return $form->renderForm();
    }

    protected function setFormOptions(FormAbstract $form, ?ProductCategory $model = null, array $options = [])
    {
        if (! $model) {
            $form->setUrl(route('product-recurring-option.create'));
        }

        if (! Auth::user()->hasPermission('product-recurring-option.create') && ! $model) {
            $class = $form->getFormOption('class');
            $form->setFormOption('class', $class . ' d-none');
        }

        $form->setFormOptions($this->getOptions($options));

        return $form;
    }

    protected function getOptions(array $options = []): array
    {
        return array_merge([
            'canCreate' => Auth::user()->hasPermission('product-recurring-option.create'),
            'canEdit' => Auth::user()->hasPermission('product-recurring-option.edit'),
            'canDelete' => Auth::user()->hasPermission('product-recurring-option.destroy'),
            'createRoute' => 'product-recurring-option.create',
            'editRoute' => 'product-recurring-option.edit',
            'deleteRoute' => 'product-recurring-option.destroy',
        ], $options);
    }

    public function getSearch(Request $request, BaseHttpResponse $response)
    {
        $term = $request->input('search');

        $categories = $this->productRecurringOptionRepository
                ->select(['id', 'name'])
                ->where('name', 'LIKE', '%' . $term . '%')
                ->paginate(10);

        $data = ProductCategoryResource::collection($categories);

        return $response->setData($data)->toApiResponse();
    }
}
