<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Homeowner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'first_name',
        'initial',
        'last_name',
        'full_name', // Computed field for easier searching/display
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model and set up event listeners
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically generate full_name when creating/updating
        static::saving(function ($homeowner) {
            $homeowner->full_name = $homeowner->generateFullName();
        });
    }

    /**
     * Generate full name from individual components
     */
    public function generateFullName(): string
    {
        $nameParts = array_filter([
            $this->title,
            $this->first_name ?? $this->initial,
            $this->last_name,
        ]);

        return implode(' ', $nameParts);
    }

    /**
     * Get the homeowner's display name (formatted for UI)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->generateFullName();
    }

    /**
     * Scope to search homeowners by name
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('full_name', 'like', "%{$searchTerm}%")
                ->orWhere('first_name', 'like', "%{$searchTerm}%")
                ->orWhere('last_name', 'like', "%{$searchTerm}%");
        });
    }

    /**
     * Scope to filter by title
     */
    public function scopeByTitle($query, $title)
    {
        return $query->where('title', $title);
    }

    /**
     * Create homeowner from parsed data array
     */
    public static function createFromParsedData(array $parsedData): self
    {
        return self::create([
            'title' => $parsedData['title'],
            'first_name' => $parsedData['first_name'],
            'initial' => $parsedData['initial'],
            'last_name' => $parsedData['last_name'],
        ]);
    }

    /**
     * Bulk create homeowners from array of parsed data
     */
    public static function createManyFromParsedData(array $parsedDataArray): Collection
    {
        $homeowners = collect();

        foreach ($parsedDataArray as $parsedData) {
            $homeowners->push(self::createFromParsedData($parsedData));
        }

        return $homeowners;
    }
}
