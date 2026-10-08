<?php

$dir = __DIR__ . '/app/Models/';

$voucher = <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected \$fillable = [
        'voucher_number',
        'date',
        'type',
        'narration',
        'financial_year_id',
        'reference_id',
        'reference_type',
        'created_by',
        'status'
    ];

    protected \$casts = [
        'date' => 'date',
    ];

    public function entries()
    {
        return \$this->hasMany(JournalEntry::class);
    }

    public function financialYear()
    {
        return \$this->belongsTo(FinancialYear::class);
    }

    public function reference()
    {
        return \$this->morphTo();
    }
}
EOT;
file_put_contents($dir . 'Voucher.php', $voucher);

$journalEntry = <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected \$fillable = [
        'voucher_id',
        'ledger_id',
        'type',
        'amount',
        'narration',
        'cost_centre_id'
    ];

    public function voucher()
    {
        return \$this->belongsTo(Voucher::class);
    }

    public function ledger()
    {
        return \$this->belongsTo(Ledger::class);
    }

    public function costCentre()
    {
        return \$this->belongsTo(CostCentre::class);
    }
}
EOT;
file_put_contents($dir . 'JournalEntry.php', $journalEntry);

$costCentre = <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostCentre extends Model
{
    protected \$fillable = [
        'name',
        'category',
        'is_active'
    ];
}
EOT;
file_put_contents($dir . 'CostCentre.php', $costCentre);

$taxRate = <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected \$fillable = [
        'name',
        'rate',
        'cgst',
        'sgst',
        'igst',
        'is_active'
    ];
}
EOT;
file_put_contents($dir . 'TaxRate.php', $taxRate);

echo "Models updated successfully.\n";
