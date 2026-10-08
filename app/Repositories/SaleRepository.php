<?php

namespace App\Repositories;

use App\Models\Sale;

class SaleRepository extends BaseRepository implements SaleRepositoryInterface
{
    public function __construct(Sale $model)
    {
        parent::__construct($model);
    }
}
