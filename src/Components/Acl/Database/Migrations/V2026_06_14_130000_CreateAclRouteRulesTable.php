<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as CapsuleManager;

return new class extends Migration
{
    public function up(): void
    {
        CapsuleManager::schema()->create('acl_route_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('route_name', 150)->unique();
            $table->unsignedInteger('resource_id');
            $table->string('privilege', 100)->nullable();
            $table->string('redirect_route_name', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('resource_id')->references('id')->on('acl_resources')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        CapsuleManager::schema()->dropIfExists('acl_route_rules');
    }
};
