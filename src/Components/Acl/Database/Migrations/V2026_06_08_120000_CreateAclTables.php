<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as CapsuleManager;

return new class extends Migration
{
    public function up(): void
    {
        CapsuleManager::schema()->create('acl_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100)->unique();
            $table->unsignedInteger('parent_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('parent_id')->references('id')->on('acl_roles')->nullOnDelete();
        });

        CapsuleManager::schema()->create('acl_resources', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 150)->unique();
            $table->unsignedInteger('parent_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('parent_id')->references('id')->on('acl_resources')->nullOnDelete();
        });

        CapsuleManager::schema()->create('acl_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type', 10);
            $table->unsignedInteger('role_id')->nullable();
            $table->unsignedInteger('resource_id')->nullable();
            $table->string('privilege', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('role_id')->references('id')->on('acl_roles')->cascadeOnDelete();
            $table->foreign('resource_id')->references('id')->on('acl_resources')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        CapsuleManager::schema()->dropIfExists('acl_rules');
        CapsuleManager::schema()->dropIfExists('acl_resources');
        CapsuleManager::schema()->dropIfExists('acl_roles');
    }
};
