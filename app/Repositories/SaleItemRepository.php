<?php

namespace App\Repositories;

use App\Models\SaleItem;

class SaleItemRepository extends BaseRepository implements SaleItemRepositoryInterface
{
    public function __construct(SaleItem $model)
    {
        parent::__construct($model);
    }
}
