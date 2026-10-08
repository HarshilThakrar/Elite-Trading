<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountGroup extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
        'nature',
        'normal_balance',
        'description',
        'is_system',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(AccountGroup::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(AccountGroup::class, 'parent_id');
    }

    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }

    /**
     * Derive normal balance based on accounting nature.
     */
    public static function deriveNormalBalance($nature)
    {
        switch ($nature) {
            case 'Assets':
            case 'Expenses':
                return 'Dr';
            case 'Liabilities':
            case 'Income':
            case 'Capital':
                return 'Cr';
            default:
                return 'Dr'; // Fallback
        }
    }

    /**
     * Check if assigning a parent would create a circular reference.
     */
    public function createsCircularReference($newParentId)
    {
        if (!$newParentId) {
            return false;
        }

        if ($this->id == $newParentId) {
            return true;
        }

        $parent = AccountGroup::find($newParentId);
        while ($parent) {
            if ($parent->parent_id == $this->id) {
                return true;
            }
            $parent = $parent->parent;
        }

        return false;
    }

    protected static $groupCache = null;

    /**
     * Build the full accounting path recursively.
     * Uses static cache to prevent N+1 queries.
     */
    public function getPath()
    {
        if (self::$groupCache === null) {
            self::$groupCache = AccountGroup::all()->keyBy('id');
        }

        $path = [];
        $current = $this;

        while ($current) {
            array_unshift($path, $current->name);
            if ($current->parent_id && isset(self::$groupCache[$current->parent_id])) {
                $current = self::$groupCache[$current->parent_id];
            } else {
                $current = null;
            }
        }

        return implode(' &rarr; ', $path);
    }
}