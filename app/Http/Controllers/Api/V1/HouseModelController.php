<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\HouseModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HouseModelController extends Controller
{
    /**
     * GET /api/v1/house-models
     * List all active house models.
     */
    public function index(): JsonResponse
    {
        $models = HouseModel::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (HouseModel $m) => $this->serialize($m));

        return response()->json(['data' => $models]);
    }

    /**
     * POST /api/v1/house-models
     * Upload a new house model (multipart/form-data).
     *
     * Fields: name, house_type, description?, sort_order?, glb (file), thumbnail? (file)
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'house_type'  => ['required', 'string', 'in:thai_roof,japanese_roof,villa,townhouse,level4,other'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'glb'         => ['required', 'file', 'mimes:glb,gltf', 'max:102400'], // 100 MB
            'thumbnail'   => ['nullable', 'image', 'max:5120'],                    // 5 MB
        ]);

        $slug     = Str::slug($data['name']) . '_' . time();
        $glbPath  = $request->file('glb')->storeAs('models', "{$slug}.glb", 'public');

        $thumbPath = null;
        if ($request->hasFile('thumbnail')) {
            $ext       = $request->file('thumbnail')->getClientOriginalExtension();
            $thumbPath = $request->file('thumbnail')->storeAs('thumbnails', "{$slug}.{$ext}", 'public');
        }

        $model = HouseModel::create([
            'name'           => $data['name'],
            'house_type'     => $data['house_type'],
            'description'    => $data['description'] ?? null,
            'sort_order'     => $data['sort_order'] ?? 0,
            'glb_path'       => $glbPath,
            'thumbnail_path' => $thumbPath,
        ]);

        Log::channel('daily')->info('[HouseModel] uploaded', [
            'id'   => $model->id,
            'name' => $model->name,
            'path' => $glbPath,
        ]);

        return response()->json(['data' => $this->serialize($model)], 201);
    }

    /**
     * GET /api/v1/house-models/{id}
     */
    public function show(HouseModel $houseModel): JsonResponse
    {
        return response()->json(['data' => $this->serialize($houseModel)]);
    }

    /**
     * DELETE /api/v1/house-models/{id}
     * Remove model + files from storage.
     */
    public function destroy(HouseModel $houseModel): JsonResponse
    {
        Storage::disk('public')->delete($houseModel->glb_path);
        if ($houseModel->thumbnail_path) {
            Storage::disk('public')->delete($houseModel->thumbnail_path);
        }
        $houseModel->delete();

        return response()->json(['message' => 'Đã xóa mô hình.']);
    }

    /**
     * GET /api/v1/house-models/{id}/file
     * Serve the GLB file through the API (adds CORS headers via middleware).
     * Required because php artisan serve bypasses Laravel middleware for static files.
     */
    public function serveGlb(HouseModel $houseModel): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($houseModel->glb_path)) {
            abort(404, 'Model file not found.');
        }

        $size = $disk->size($houseModel->glb_path);

        return response()->stream(
            function () use ($disk, $houseModel): void {
                $stream = $disk->readStream($houseModel->glb_path);
                while (! feof($stream)) {
                    echo fread($stream, 8192);
                    flush();
                }
                fclose($stream);
            },
            200,
            [
                'Content-Type'   => 'model/gltf-binary',
                'Content-Length' => $size,
                'Cache-Control'  => 'public, max-age=3600',
            ]
        );
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function serialize(HouseModel $m): array
    {
        return [
            'id'            => $m->id,
            'name'          => $m->name,
            'house_type'    => $m->house_type,
            'description'   => $m->description,
            'glb_url'       => $m->glb_url,
            'thumbnail_url' => $m->thumbnail_url,
            'sort_order'    => $m->sort_order,
        ];
    }
}
