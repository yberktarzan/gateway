<?php

namespace App\Models;

use App\Enums\ListingType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Listing extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'cover_image',
        'images',
        'slug',
        'location',
        'price',
        'listing_type',
        'user_id',
        'country_code',
        'category_id',
        'is_active',
        'is_featured',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'title' => 'array',
        'description' => 'array',
        'slug' => 'array',
        'images' => 'array',
        'price' => 'decimal:2',
        'listing_type' => ListingType::class,
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Translatable attributes for multi-language support
     *
     * @var array<int, string>
     */
    public $translatable = ['title', 'description', 'slug'];

    /**
     * Get the user that owns the listing.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Future category relationship (when category system is implemented)
    // public function category(): BelongsTo
    // {
    //     return $this->belongsTo(Category::class);
    // }

    /**
     * Get cover image URL attribute.
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        return url('listings/'.$this->cover_image);
    }

    /**
     * Get gallery image URLs attribute.
     *
     * @return array<string>
     */
    public function getImageUrlsAttribute(): array
    {
        if (! $this->images || ! is_array($this->images)) {
            return [];
        }

        return array_map(function ($image) {
            return url('listings/'.$image);
        }, $this->images);
    }

    /**
     * Get the formatted price with currency.
     */
    public function getFormattedPriceAttribute(): ?string
    {
        if ($this->price === null) {
            return null;
        }

        // You can customize currency formatting based on country_code here
        return number_format($this->price, 2).' '.$this->getCurrency();
    }

    /**
     * Get currency based on country code.
     */
    private function getCurrency(): string
    {
        return match ($this->country_code) {
            'TR' => 'TL',
            'US' => '$',
            'EU' => '€',
            'GB' => '£',
            default => '$',
        };
    }

    /**
     * Get translation for a field in current locale or fallback.
     */
    public function getTranslation(string $field, ?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');

        $value = $this->{$field};

        if (! is_array($value)) {
            return $value;
        }

        return $value[$locale] ?? $value[$fallback] ?? null;
    }

    /**
     * Delete cover image file from filesystem.
     */
    public function deleteCoverImage(): bool
    {
        if (! $this->cover_image) {
            return true;
        }

        $filePath = public_path('listings/'.$this->cover_image);

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return true;
    }

    /**
     * Delete all gallery images from filesystem.
     */
    public function deleteGalleryImages(): bool
    {
        if (! $this->images || ! is_array($this->images)) {
            return true;
        }

        foreach ($this->images as $image) {
            $filePath = public_path('listings/'.$image);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        return true;
    }

    /**
     * Delete all listing images (cover + gallery).
     */
    public function deleteAllImages(): bool
    {
        $coverResult = $this->deleteCoverImage();
        $galleryResult = $this->deleteGalleryImages();

        return $coverResult && $galleryResult;
    }

    /**
     * Scope a query to only include active listings.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive listings.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query to only include featured listings.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include non-featured listings.
     */
    public function scopeNotFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', false);
    }

    /**
     * Scope a query to filter by listing type.
     */
    public function scopeOfType(Builder $query, ListingType $type): Builder
    {
        return $query->where('listing_type', $type);
    }

    /**
     * Scope a query to filter by country.
     */
    public function scopeInCountry(Builder $query, string $countryCode): Builder
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to search in translatable fields.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('location', 'like', '%'.$search.'%')
                ->orWhereRaw('JSON_EXTRACT(title, "$.en") LIKE ?', ['%'.$search.'%'])
                ->orWhereRaw('JSON_EXTRACT(title, "$.tr") LIKE ?', ['%'.$search.'%'])
                ->orWhereRaw('JSON_EXTRACT(description, "$.en") LIKE ?', ['%'.$search.'%'])
                ->orWhereRaw('JSON_EXTRACT(description, "$.tr") LIKE ?', ['%'.$search.'%']);
        });
    }

    /**
     * Scope a query to filter by price range.
     */
    public function scopePriceBetween(Builder $query, ?float $min = null, ?float $max = null): Builder
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }

        if ($max !== null) {
            $query->where('price', '<=', $max);
        }

        return $query;
    }

    /**
     * Scope a query to get recent listings.
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
