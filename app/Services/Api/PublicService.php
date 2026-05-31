<?php

namespace App\Services\Api;

use App\Models\Banner;
use App\Models\BookCategory;

class PublicService {

    public function getBanners() {
        $banners = Banner::where('is_active', true)->orderBy('sort_order', 'asc')->get();
        return $banners->map(function ($banner) {
            return [
                'id' => $banner->id,
                'title' => $banner->title,
                'subtitle' => $banner->subtitle,
                'image' => $this->resolveImageUrl($banner),
                'action_url' => $banner->action_url
            ];
        });
    }

    public function getCategories() {
        $categories = BookCategory::where('is_active', true)->orderBy('sort_order', 'asc')->get();
        return $categories;
    }

    private function resolveImageUrl(Banner $banner) {
        if ($banner->image_is_external) {
            return $banner->image_url;
        }
        return asset('storage/banners/'.$banner->image_url);
    }

}