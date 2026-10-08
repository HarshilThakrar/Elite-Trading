<?php

$migrationsDir = __DIR__ . '/database/migrations/';
$files = scandir($migrationsDir);
$migrationFile = '';

foreach ($files as $file) {
    if (strpos($file, 'create_bank_accounts_table') !== false) {
        $migrationFile = $migrationsDir . $file;
        break;
    }
}

if ($migrationFile) {
    $migrationContent = <<<EOT
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint \$table) {
            \$table->id();
            \$table->string('bank_name');
            \$table->string('account_name');
            \$table->string('account_number')->unique();
            \$table->string('ifsc_code')->nullable();
            \$table->string('branch')->nullable();
            \$table->decimal('opening_balance', 15, 2)->default(0);
            \$table->boolean('is_active')->default(true);
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
EOT;
    file_put_contents($migrationFile, $migrationContent);
    echo "Updated migration: \$migrationFile\\n";
} else {
    echo "Migration not found\\n";
}

$modelFile = __DIR__ . '/app/Models/BankAccount.php';
$modelContent = <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected \$fillable = [
        'bank_name',
        'account_name',
        'account_number',
        'ifsc_code',
        'branch',
        'opening_balance',
        'is_active'
    ];

    public function ledger()
    {
        return \$this->hasOne(Ledger::class, 'reference_id')->where('type', 'bank_account');
    }
}
EOT;
file_put_contents($modelFile, $modelContent);
echo "Updated model: BankAccount.php\\n";

$controllerFile = __DIR__ . '/app/Http/Controllers/BankAccountController.php';
$controllerContent = <<<EOT
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        \$accounts = BankAccount::all();
        return view('admin.bank-accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('admin.bank-accounts.create');
    }

    public function store(Request \$request)
    {
        \$data = \$request->validate([
            'bank_name' => 'required',
            'account_name' => 'required',
            'account_number' => 'required|unique:bank_accounts',
            'ifsc_code' => 'nullable',
            'branch' => 'nullable',
            'opening_balance' => 'numeric'
        ]);

        \$account = BankAccount::create(\$data);

        // Auto-create Ledger
        \$group = \App\Models\AccountGroup::where('name', 'Bank Accounts')->first();
        if (\$group) {
            \App\Models\Ledger::create([
                'name' => \$account->bank_name . ' - ' . substr(\$account->account_number, -4),
                'account_group_id' => \$group->id,
                'opening_balance' => \$data['opening_balance'] ?? 0,
                'opening_balance_type' => 'Dr',
                'is_system' => false,
                'type' => 'bank_account',
                'reference_id' => \$account->id,
            ]);
        }

        return redirect()->route('bank-accounts.index')->with('success', 'Bank Account added.');
    }

    public function edit(BankAccount \$bankAccount)
    {
        return view('admin.bank-accounts.edit', compact('bankAccount'));
    }

    public function update(Request \$request, BankAccount \$bankAccount)
    {
        \$data = \$request->validate([
            'bank_name' => 'required',
            'account_name' => 'required',
            'account_number' => 'required|unique:bank_accounts,account_number,' . \$bankAccount->id,
            'ifsc_code' => 'nullable',
            'branch' => 'nullable'
        ]);

        \$bankAccount->update(\$data);
        
        \$ledger = \$bankAccount->ledger;
        if (\$ledger) {
            \$ledger->update(['name' => \$bankAccount->bank_name . ' - ' . substr(\$bankAccount->account_number, -4)]);
        }

        return redirect()->route('bank-accounts.index')->with('success', 'Bank Account updated.');
    }
}
EOT;
file_put_contents(__DIR__ . '/app/Http/Controllers/Admin/BankAccountController.php', $controllerContent);
echo "Updated controller: BankAccountController.php\\n";
