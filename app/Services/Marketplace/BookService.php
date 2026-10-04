<?php

namespace App\Services\Marketplace;

use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookService
{
    public function createForVendor(User $vendor, array $attributes): Book
    {
        return DB::transaction(function () use ($vendor, $attributes): Book {
            $baseSlug = Str::slug($attributes['title']);
            $slug = $baseSlug;
            $suffix = 1;

            while (Book::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix++;
            }

            return $vendor->books()->create([
                ...$attributes,
                'slug' => $slug,
                'status' => 'draft',
            ]);
        });
    }
}
