<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as CapsuleManager;

return new class extends Migration
{
    public function up(): void
    {
        CapsuleManager::schema()->create('settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('setting_key', 255);
            $table->text('setting_value');
            $table->string('setting_group', 100)->default('general')->index();
            $table->unique(['setting_group', 'setting_key'], 'settings_group_key_unique');
            $table->string('data_type', 50);
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        CapsuleManager::schema()->dropIfExists('settings');
    }
};
