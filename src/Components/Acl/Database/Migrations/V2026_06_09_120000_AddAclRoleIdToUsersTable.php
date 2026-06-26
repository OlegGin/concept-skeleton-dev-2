<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as CapsuleManager;

return new class extends Migration
{
    public function up(): void
    {
        CapsuleManager::schema()->table('users', function (Blueprint $table) {
            $table->unsignedInteger('acl_role_id')->nullable()->after('is_admin');
            $table->foreign('acl_role_id')->references('id')->on('acl_roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        CapsuleManager::schema()->table('users', function (Blueprint $table) {
            $table->dropForeign(['acl_role_id']);
            $table->dropColumn('acl_role_id');
        });
    }
};
