<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppInfo extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'name',
        'ccphone1',
        'phone1',
        'ccphone2',
        'phone2',
        'email1',
        'email2',
        'logo_color',
        'logo_white',
        'addresses',
        'maintenance',
        'show_only_with_images',
    ];

    protected $searchableFields = ['*'];

    protected $table = 'app_infos';

    protected $casts = [
        'maintenance' => 'boolean',
        'show_only_with_images' => 'boolean',
        'addresses' => 'array',
    ];

    /**
     * Get the default address from the addresses array
     */
    public function getDefaultAddressAttribute(): ?array
    {
        $addresses = $this->addresses ?? [];
        
        // Find the default address
        foreach ($addresses as $address) {
            if (!empty($address['is_default'])) {
                return $address;
            }
        }
        
        // Return first address if no default is set
        return $addresses[0] ?? null;
    }

    /**
     * Get address by ID
     */
    public function getAddressById(string $id): ?array
    {
        $addresses = $this->addresses ?? [];
        
        foreach ($addresses as $address) {
            if (($address['id'] ?? '') === $id) {
                return $address;
            }
        }
        
        return null;
    }
}
