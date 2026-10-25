<?php

namespace App\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;

class AssetsController extends \Lava\Routing\Controller
{
    public function index()
    {
        try {
            $page = request()->get('page', 1);
            $limit = request()->get('limit', 12);
            $category = request()->get('category');
            $search = request()->get('search');
            $status = request()->get('status');

            $query = Asset::where('is_active', 1)->where('is_borrowable', 1);

            if ($status) {
                $query->where('status', $status);
            }

            if ($category) {
                $query->where('category_id', $category);
            }

            if ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('asset_tag', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
            }

            $total = $query->count();
            $assets = $query->limit($limit)
                           ->offset(($page - 1) * $limit)
                           ->get();

            return response()->json([
                'status' => 'success',
                'data' => $assets,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch assets',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function show($id)
    {
        try {
            $asset = Asset::with(['category', 'borrowings'])
                          ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $asset,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Asset not found',
            ], 404);
        }
    }

    public function store()
    {
        $this->authorize('admin');

        $this->validate([
            'asset_tag' => 'required|unique:assets',
            'name' => 'required|string',
            'category_id' => 'required|exists:asset_categories,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $asset = Asset::create([
                'asset_tag' => request()->get('asset_tag'),
                'name' => request()->get('name'),
                'category_id' => request()->get('category_id'),
                'description' => request()->get('description'),
                'quantity' => request()->get('quantity', 1),
                'quantity_available' => request()->get('quantity', 1),
                'condition' => request()->get('condition', 'good'),
                'status' => 'available',
                'current_location' => request()->get('current_location'),
                'purchase_date' => request()->get('purchase_date'),
                'purchase_price' => request()->get('purchase_price'),
                'is_borrowable' => request()->get('is_borrowable', 1),
                'is_active' => 1,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Asset created successfully',
                'data' => $asset,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create asset',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function update($id)
    {
        $this->authorize('admin');

        try {
            $asset = Asset::findOrFail($id);

            $asset->update([
                'name' => request()->get('name', $asset->name),
                'category_id' => request()->get('category_id', $asset->category_id),
                'description' => request()->get('description', $asset->description),
                'quantity' => request()->get('quantity', $asset->quantity),
                'condition' => request()->get('condition', $asset->condition),
                'status' => request()->get('status', $asset->status),
                'current_location' => request()->get('current_location', $asset->current_location),
                'is_borrowable' => request()->get('is_borrowable', $asset->is_borrowable),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Asset updated successfully',
                'data' => $asset,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update asset',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function delete($id)
    {
        $this->authorize('admin');

        try {
            $asset = Asset::findOrFail($id);
            $asset->update(['is_active' => 0]);

            return response()->json([
                'status' => 'success',
                'message' => 'Asset deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete asset',
            ], 400);
        }
    }

    public function categories()
    {
        try {
            $categories = AssetCategory::where('is_active', 1)->get();

            return response()->json([
                'status' => 'success',
                'data' => $categories,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch categories',
            ], 400);
        }
    }

    public function inventory()
    {
        $this->authorize('admin');

        try {
            $assets = Asset::where('is_active', 1)
                          ->with('category')
                          ->get();

            return response()->json([
                'status' => 'success',
                'data' => $assets,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch inventory',
            ], 400);
        }
    }
}
