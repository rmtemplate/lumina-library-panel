<?php

namespace App\Services\Api;

use App\Models\Book;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Support\Collection;

class RecommendationPreparationService
{
    /**
     * Lightweight recommendation placeholder for future mobile/API usage.
     * It prioritizes categories mapped to a user's major/program and explicit preferences.
     */
    public function recommendedBooksFor(User $user, int $limit = 12): Collection
    {
        $categoryWeights = collect();

        $user->loadMissing(['bookPreferences.category']);
        foreach ($user->bookPreferences as $preference) {
            $categoryWeights[$preference->book_category_id] = max((float) ($categoryWeights[$preference->book_category_id] ?? 0), (float) $preference->weight);
        }

        if ($user->major_name) {
            $program = StudyProgram::query()
                ->with('categories')
                ->where('is_active', true)
                ->where(function ($query) use ($user) {
                    $query->where('name', 'like', '%'.$user->major_name.'%')
                        ->orWhere('code', 'like', '%'.str_replace(' ', '_', strtolower($user->major_name)).'%');
                })
                ->first();

            if ($program) {
                foreach ($program->categories as $category) {
                    $categoryWeights[$category->id] = max((float) ($categoryWeights[$category->id] ?? 0), (float) ($category->pivot->weight ?? 1));
                }
            }
        }

        $preferredCategoryIds = $categoryWeights->sortDesc()->keys()->values();

        return Book::query()
            ->with(['category'])
            ->withCount(['items as available_items_count' => fn ($q) => $q->where('status', 'available')])
            ->where('is_active', true)
            ->when($preferredCategoryIds->isNotEmpty(), fn ($q) => $q->orderByRaw('FIELD(category_id, '.str_repeat('?,', $preferredCategoryIds->count() - 1).'?) DESC', $preferredCategoryIds->all()))
            ->orderByDesc('rating')
            ->limit($limit)
            ->get();
    }
}
