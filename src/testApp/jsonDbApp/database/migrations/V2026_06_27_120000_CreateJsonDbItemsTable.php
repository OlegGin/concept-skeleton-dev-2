<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as CapsuleManager;

return new class extends Migration
{
    public function up(): void
    {
        CapsuleManager::schema()->create('jsondb_items', function(Blueprint $table): void {
            $table->increments('id');
            $table->string('title', 255);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        CapsuleManager::schema()->dropIfExists('jsondb_items');
    }
};
