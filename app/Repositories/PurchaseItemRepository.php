<?php

namespace App\Repositories;

use App\Models\PurchaseItem;

class PurchaseItemRepository extends BaseRepository implements PurchaseItemRepositoryInterface
{
    public function __construct(PurchaseItem $model)
    {
        parent::__construct($model);
    }
}
