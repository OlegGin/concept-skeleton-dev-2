<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as CapsuleManager;

return new class extends Migration
{
    public function up(): void
    {
        CapsuleManager::schema()->create('sessions', function(Blueprint $table) {
            $table->binary('sess_id', 128)->primary();
            $table->binary('sess_data');
            $table->unsignedInteger('sess_lifetime');
            $table->unsignedInteger('sess_time');

            $table->index('sess_lifetime', 'sess_lifetime_idx');
        });
    }

    public function down(): void
    {
        CapsuleManager::schema()->dropIfExists('sessions');
    }
};
