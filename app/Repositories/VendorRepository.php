<?php

namespace App\Repositories;

use App\Models\Vendor;

class VendorRepository extends BaseRepository implements VendorRepositoryInterface
{
    public function __construct(Vendor $model)
    {
        parent::__construct($model);
    }
}
