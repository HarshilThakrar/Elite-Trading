<?php

$dir = __DIR__ . '/database/migrations';
$files = scandir($dir);

$migrations = [
    'sales_returns' => '',
    'sales_return_items' => '',
    'purchase_returns' => '',
    'purchase_return_items' => ''
];

foreach ($files as $file) {
    foreach (array_keys($migrations) as $key) {
        if (strpos($file, 'create_' . $key . '_table.php') !== false) {
            $migrations[$key] = $dir . '/' . $file;
        }
    }
}

// Sales Returns
file_put_contents($migrations['sales_returns'], <<<EOT
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('sales_returns', function (Blueprint \$table) {
            \$table->id();
            \$table->string('return_number')->unique();
            \$table->foreignId('sale_id')->constrained()->onDelete('cascade');
            \$table->foreignId('invoice_id')->nullable()->constrained()->onDelete('cascade');
            \$table->date('return_date');
            \$table->decimal('total_amount', 15, 2)->default(0);
            \$table->string('status')->default('Pending');
            \$table->text('notes')->nullable();
            \$table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('sales_returns');
    }
};
EOT
);

// Sales Return Items
file_put_contents($migrations['sales_return_items'], <<<EOT
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('sales_return_items', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('sales_return_id')->constrained()->onDelete('cascade');
            \$table->foreignId('product_id')->constrained()->onDelete('cascade');
            \$table->integer('quantity');
            \$table->decimal('unit_price', 15, 2);
            \$table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('sales_return_items');
    }
};
EOT
);

// Purchase Returns
file_put_contents($migrations['purchase_returns'], <<<EOT
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('purchase_returns', function (Blueprint \$table) {
            \$table->id();
            \$table->string('return_number')->unique();
            \$table->foreignId('purchase_id')->constrained()->onDelete('cascade');
            \$table->date('return_date');
            \$table->decimal('total_amount', 15, 2)->default(0);
            \$table->string('status')->default('Pending');
            \$table->text('notes')->nullable();
            \$table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('purchase_returns');
    }
};
EOT
);

// Purchase Return Items
file_put_contents($migrations['purchase_return_items'], <<<EOT
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('purchase_return_items', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('purchase_return_id')->constrained()->onDelete('cascade');
            \$table->foreignId('product_id')->constrained()->onDelete('cascade');
            \$table->integer('quantity');
            \$table->decimal('unit_price', 15, 2);
            \$table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('purchase_return_items');
    }
};
EOT
);

// MODELS
file_put_contents(__DIR__ . '/app/Models/SalesReturn.php', <<<EOT
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class SalesReturn extends Model {
    use Auditable;
    protected \$guarded = [];
    public function items() { return \$this->hasMany(SalesReturnItem::class); }
    public function sale() { return \$this->belongsTo(Sale::class); }
    public function invoice() { return \$this->belongsTo(Invoice::class); }
}
EOT
);

file_put_contents(__DIR__ . '/app/Models/SalesReturnItem.php', <<<EOT
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SalesReturnItem extends Model {
    protected \$guarded = [];
    public function salesReturn() { return \$this->belongsTo(SalesReturn::class); }
    public function product() { return \$this->belongsTo(Product::class); }
}
EOT
);

file_put_contents(__DIR__ . '/app/Models/PurchaseReturn.php', <<<EOT
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
class PurchaseReturn extends Model {
    use Auditable;
    protected \$guarded = [];
    public function items() { return \$this->hasMany(PurchaseReturnItem::class); }
    public function purchase() { return \$this->belongsTo(Purchase::class); }
}
EOT
);

file_put_contents(__DIR__ . '/app/Models/PurchaseReturnItem.php', <<<EOT
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseReturnItem extends Model {
    protected \$guarded = [];
    public function purchaseReturn() { return \$this->belongsTo(PurchaseReturn::class); }
    public function product() { return \$this->belongsTo(Product::class); }
}
EOT
);

echo "Migrations and Models created successfully.\\n";
