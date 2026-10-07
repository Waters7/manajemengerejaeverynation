<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * STORE → Categories: e.g. Discipleship books, Apparel, Accessories.
 */
class ProductCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.store.categories', [
            'categories' => ProductCategory::withCount('products')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ProductCategory::create($this->validated($request));

        return back()->with('status', 'Category created.');
    }

    public function update(Request $request, ProductCategory $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return back()->with('status', 'Category updated.');
    }

    public function destroy(ProductCategory $category): RedirectResponse
    {
        $category->delete();

        return back()->with('status', 'Category deleted. Its products are now uncategorised.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?ProductCategory $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('product_categories', 'name')->ignore($category)],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ]);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
