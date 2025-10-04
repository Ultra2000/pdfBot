<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer les permissions de base
        $permissions = [
            'manage users',
            'manage documents',
            'manage task jobs',
            'view admin dashboard',
            'access horizon',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Créer les rôles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $operatorRole = Role::firstOrCreate(['name' => 'operator']);

        // Assigner toutes les permissions à l'admin
        $adminRole->syncPermissions($permissions);

        // Assigner certaines permissions à l'operator
        $operatorRole->syncPermissions([
            'manage documents',
            'manage task jobs',
            'view admin dashboard',
        ]);

        // Créer l'utilisateur admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@pdf-bot.local'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('admin');

        $this->command->info('Admin créé avec succès:');
        $this->command->info('Email: admin@pdf-bot.local');
        $this->command->info('Password: password');
    }
}
