<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GeneralSetting extends Model
{
    protected $fillable = [
        'company_name',
        'currency_symbol',
        'logo_path',
    ];

    /**
     * There is exactly one row in this table. Fetch it (creating it with
     * sane defaults on first access) rather than modelling a full CRUD
     * resource for a singleton.
     */
    public static function current(): self
    {
        return static::firstOrCreate([], [
            'company_name' => config('app.name'),
            'currency_symbol' => '$',
        ]);
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null);
    }

    /**
     * Absolute filesystem path to the logo, for embedding in DomPDF/print
     * views — those don't have `enable_remote` on, so an http(s) URL
     * (logo_url) won't load, but a local file path will.
     */
    protected function logoFilePath(): Attribute
    {
        return Attribute::get(fn () => $this->logo_path ? Storage::disk('public')->path($this->logo_path) : null);
    }
}
