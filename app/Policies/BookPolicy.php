<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    public function view(User $user, Book $book): bool
    {
        return $book->vendor_id === $user->id || $user->hasRole('admin');
    }

    public function update(User $user, Book $book): bool
    {
        return $book->vendor_id === $user->id || $user->hasRole('admin');
    }

    public function delete(User $user, Book $book): bool
    {
        return $book->vendor_id === $user->id || $user->hasRole('admin');
    }
}
