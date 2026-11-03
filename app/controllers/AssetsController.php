<?php


class AssetsController extends \Controller
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

        try {
            $this->validate([
                'asset_tag' => 'required|unique:assets',
                'name' => 'required|string',
                'category_id' => 'required|exists:asset_categories,id',
                'quantity' => 'required|integer|min:1',
            ]);

            $assetTag = trim((string) request()->get('asset_tag'));
            $categoryId = request()->get('category_id');
            $quantity = (int) request()->get('quantity', 1);

            if (Asset::where('asset_tag', $assetTag)->first()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'An asset with this asset tag already exists.',
                    'errors' => ['asset_tag' => 'Asset tag must be unique.'],
                ], 409);
            }

            if (!AssetCategory::find($categoryId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The selected asset category is invalid.',
                    'errors' => ['category_id' => 'Select an existing asset category.'],
                ], 422);
            }

            $asset = Asset::create([
                'asset_tag' => $assetTag,
                'name' => request()->get('name'),
                'category_id' => $categoryId,
                'description' => request()->get('description'),
                'quantity' => $quantity,
                'quantity_available' => $quantity,
                'condition' => request()->get('condition', 'good'),
                'status' => 'available',
                'current_location' => request()->get('current_location'),
                'purchase_date' => request()->get('purchase_date'),
                'purchase_price' => request()->get('purchase_price'),
                'is_borrowable' => request()->get('is_borrowable', 1),
                'is_active' => 1,
            ]);

            if (!$asset) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Asset could not be saved',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Asset created successfully',
                'data' => $asset,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create asset',
            ], 400);
        }
    }

    public function update($id)
    {
        $this->authorize('admin');

        try {
            $asset = Asset::findOrFail($id);
            
            // HIGH PRIORITY FIX #17: Concurrent Update Data Loss (Optimistic Locking)
            $clientVersion = request()->get('_version');
            if ($clientVersion !== null) {
                if ((int) $clientVersion !== (int) $asset->version) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Conflict: Asset has been modified by another user. Please refresh and try again.',
                        'data' => ['current_version' => $asset->version],
                    ], 409);
                }
            }
            
            // CRITICAL FIX: Validate quantity against active borrowings
            if (request()->has('quantity')) {
                $newQuantity = (int) request()->get('quantity');
                $activeBorrowings = AssetBorrowing::where('asset_id', $id)
                    ->whereIn('status', ['pending', 'approved', 'checked_out'])
                    ->sum('quantity');
                
                if ($newQuantity < $activeBorrowings) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Cannot reduce quantity below active borrowings. Active: $activeBorrowings, Requested: $newQuantity",
                    ], 422);
                }
            }

            $updatedData = [
                'name' => request()->get('name', $asset->name),
                'category_id' => request()->get('category_id', $asset->category_id),
                'description' => request()->get('description', $asset->description),
                'quantity' => request()->get('quantity', $asset->quantity),
                'condition' => request()->get('condition', $asset->condition),
                'status' => request()->get('status', $asset->status),
                'current_location' => request()->get('current_location', $asset->current_location),
                'is_borrowable' => request()->get('is_borrowable', $asset->is_borrowable),
                'version' => $asset->version + 1,
            ];

            $asset->update($updatedData);

            return response()->json([
                'status' => 'success',
                'message' => 'Asset updated successfully',
                'data' => $asset,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update asset',
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
