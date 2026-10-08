<?php

namespace App\Repositories;

use App\Models\Stock;

class StockRepository extends BaseRepository implements StockRepositoryInterface
{
    public function __construct(Stock $model)
    {
        parent::__construct($model);
    }
}
