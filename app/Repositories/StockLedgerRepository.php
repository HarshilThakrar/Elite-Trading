<?php

namespace App\Repositories;

use App\Models\StockLedger;

class StockLedgerRepository extends BaseRepository implements StockLedgerRepositoryInterface
{
    public function __construct(StockLedger $model)
    {
        parent::__construct($model);
    }
}
