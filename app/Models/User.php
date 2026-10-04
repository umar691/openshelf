<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Chatify\Traits\InteractsWithChatify;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, InteractsWithChatify, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'headline',
        'bio',
        'avatar_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function bookmarks(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'bookmarks')->withTimestamps();
    }

    public function reactions(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_reactions')->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'vendor_id');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    public function gigs(): HasMany
    {
        return $this->hasMany(Gig::class, 'freelancer_id');
    }

    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'applicant_id');
    }

    public function jobPosts(): HasMany
    {
        return $this->hasMany(JobPost::class, 'employer_id');
    }

    public function courseEnrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function digitalEntitlements(): HasMany
    {
        return $this->hasMany(DigitalEntitlement::class);
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_follows', 'follower_id', 'followed_id')->withTimestamps();
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_follows', 'followed_id', 'follower_id')->withTimestamps();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('admin');
    }
}
