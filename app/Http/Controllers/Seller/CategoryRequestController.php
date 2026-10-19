<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Communication\Notification;
use App\Models\Profiles\SellerCategoryRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CategoryRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'categories' => ['required', 'array', 'min:1', 'max:20'],
            'categories.*' => ['required', 'integer', 'distinct', 'exists:categories,id'],
            'permit' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $ids = array_map('intval', $data['categories']);
        $path = null;

        try {
            DB::transaction(function () use ($request, $data, $ids, &$path) {
                $seller = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $valid = Category::query()->whereNull('parent_id')->whereIn('id', $ids)->count();
                if ($valid !== count($ids) || $seller->categories()->whereIn('categories.id', $ids)->exists()
                    || SellerCategoryRequest::query()->where('seller_id', $seller->id)->where('status', 'pending')
                        ->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $ids))->exists()) {
                    throw ValidationException::withMessages(['categories' => 'Choose top-level categories that are not approved or pending already.']);
                }

                $path = $request->file('permit')->store('private/seller-category-permits/'.$seller->id, 'local');
                if (! $path) {
                    throw ValidationException::withMessages(['permit' => 'The permit could not be saved. Try again.']);
                }
                $categoryRequest = SellerCategoryRequest::create([
                    'seller_id' => $seller->id,
                    'status' => 'pending',
                    'permit_path' => $path,
                    'note' => $data['note'] ?? null,
                ]);
                $categoryRequest->categories()->attach($ids);
                Notification::create([
                    'user_id' => null,
                    'type' => 'seller_category_request',
                    'title' => 'Seller category request',
                    'message' => $seller->name.' requested additional selling categories.',
                    'link' => route('admin.category-requests.index'),
                ]);
            }, 3);
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }

        return response()->json(['message' => 'Category request submitted for Admin review.'], 201);
    }
}
