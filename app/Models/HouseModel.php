<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HouseModel extends Model
{
    protected $fillable = [
        'name', 'house_type', 'glb_path', 'thumbnail_path',
        'description', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * API proxy URL for the GLB file.
     * Routes through Laravel so CORS headers are applied (needed by model-viewer WebView).
     */
    public function getGlbUrlAttribute(): string
    {
        return url("/api/v1/house-models/{$this->id}/file");
    }

    /** Full public URL for thumbnail, null if none */
    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path ? asset('storage/' . $this->thumbnail_path) : null;
    }
}
