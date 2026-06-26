<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as CapsuleManager;

return new class extends Migration
{
    public function up(): void
    {
        CapsuleManager::schema()->create('pages', function(Blueprint $table) {
            $table->increments('id');
            $table->string('title', 50);
            $table->string('slug', 255)->unique();
            $table->string('description', 255);
            $table->text('content')->nullable();
            $table->boolean('published')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        CapsuleManager::schema()->dropIfExists('pages');
    }
};