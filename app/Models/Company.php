<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Company extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'country_code',
        'name',
        'logo',
        'description',
        'is_vip',
        'status',
        'website',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'description' => 'json', // For translatable descriptions
            'is_vip' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the company.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get translated description for current locale.
     */
    public function getTranslatedDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();

        if (is_array($this->description) && isset($this->description[$locale])) {
            return $this->description[$locale];
        }

        // Fallback to English if current locale not found
        if (is_array($this->description) && isset($this->description['en'])) {
            return $this->description['en'];
        }

        // Return as string if not an array (backwards compatibility)
        return is_string($this->description) ? $this->description : null;
    }

    /**
     * Set description for multiple locales.
     */
    public function setDescriptionAttribute($value): void
    {
        // If it's already an array (translations), keep it
        if (is_array($value)) {
            $this->attributes['description'] = json_encode($value);

            return;
        }

        // If it's a string, set it for current locale
        $locale = app()->getLocale();
        $descriptions = is_array($this->description) ? $this->description : [];
        $descriptions[$locale] = $value;

        $this->attributes['description'] = json_encode($descriptions);
    }

    /**
     * Scope: Get only active companies.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Get VIP companies.
     */
    public function scopeVip($query)
    {
        return $query->where('is_vip', true);
    }

    /**
     * Scope: Filter by country.
     */
    public function scopeByCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Get the full logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo;
        }

        // Return public URL for local logo files
        return url('logos/'.$this->logo);
    }

    /**
     * Delete logo file from public directory.
     */
    public function deleteLogo(): bool
    {
        if (! $this->logo) {
            return true;
        }

        // Don't delete external URLs
        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return true;
        }

        $logoPath = public_path('logos/'.$this->logo);

        if (file_exists($logoPath)) {
            return unlink($logoPath);
        }

        return true;
    }
}
