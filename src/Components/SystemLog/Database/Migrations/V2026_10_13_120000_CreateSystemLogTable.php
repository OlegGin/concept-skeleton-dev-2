<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as CapsuleManager;

return new class extends Migration
{
    public function up(): void
    {
        CapsuleManager::schema()->create('system_log', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('event', 100)->index();
            $table->unsignedInteger('actor_user_id')->nullable()->index();
            $table->string('subject_type', 50)->nullable();
            $table->unsignedInteger('subject_id')->nullable()->index();
            $table->string('message', 255);
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        CapsuleManager::schema()->dropIfExists('system_log');
    }
};
