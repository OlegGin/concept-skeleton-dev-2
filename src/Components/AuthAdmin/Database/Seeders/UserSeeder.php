<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Database\Seeders;

use Concept\Core\Services\Database\Contracts\SeederInterface;
use Illuminate\Database\Capsule\Manager as CapsuleManager;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder implements SeederInterface
{
    public function run(): void
    {
        CapsuleManager::schema()->getConnection()->table('users')->truncate();

        CapsuleManager::schema()->getConnection()->table('users')->insert(
            [
                [
                    'name' => 'admin',
                    'email' => 'admin@example.com',
                    'is_admin' => 1,
                    'status' => 'active',
                    'password' => password_hash('admin_password', PASSWORD_DEFAULT),
                ],
                [
                    'name' => 'manager',
                    'email' => 'manager@example.com',
                    'is_admin' => 1,
                    'status' => 'active',
                    'password' => password_hash('manager_password', PASSWORD_DEFAULT),
                ],
                [
                    'name' => 'editor',
                    'email' => 'editor@example.com',
                    'is_admin' => 1,
                    'status' => 'active',
                    'password' => password_hash('editor_password', PASSWORD_DEFAULT),
                ],
                [
                    'name' => 'user',
                    'email' => 'user@example.com',
                    'is_admin' => 0,
                    'status' => 'active',
                    'password' => password_hash('user_password', PASSWORD_DEFAULT),
                ],
            ]
        );
    }
}
