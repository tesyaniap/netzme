<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // User Management (Admin)
            'users.view', 'users.create', 'users.update', 'users.delete',
            
            // Role & Permission Management (Admin)
            'roles.view', 'roles.create', 'roles.update', 'roles.delete',
            'permissions.view', 'permissions.assign',
            
            // Mitra (Admin)
            'mitra.view', 'mitra.create', 'mitra.update', 'mitra.delete',
            'mitra.approve', 'mitra.reject', 'mitra.fee',
            
            // Topup 
            'topups.view', 'topups.create', 'topups.approve', 'topups.reject',
            
            // Transaction
            'transactions.view', 'transactions.create', 'transactions.search',
            'transactions.book', 'transactions.pay', 'transactions.issue', 'transactions.cancel',
            'transactions.seat-map',
            
            // Balance & Ledger
            'balance.view', 'balance.histories', 'fee-ledgers.view',
            
            // Reports (Admin)
            'reports.transactions', 'reports.topups', 'reports.fees', 'reports.balances',
            
            // Routes (Admin)
            'routes.view', 'routes.create', 'routes.update', 'routes.delete',
            
            // Cities (Admin)
            'cities.view', 'cities.create', 'cities.update', 'cities.delete',
            
            // Terminals (Admin)
            'terminals.view', 'terminals.create', 'terminals.update', 'terminals.delete',
            
            // Schedules (Admin)
            'schedules.view', 'schedules.create', 'schedules.update', 'schedules.delete',
            
            // Vehicles (Admin)
            'vehicles.view', 'vehicles.create', 'vehicles.update', 'vehicles.delete', 'vehicles.bulk-create',
            
            // Seats (Admin)
            'seats.view', 'seats.create', 'seats.update', 'seats.delete', 'seats.generate',
            
            // Tickets
            'tickets.view', 'tickets.reschedule', 'tickets.cancel',
            
            // Dashboard
            'dashboard.admin', 'dashboard.partner',
        ];

        // buat permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api'
            ]);
        }

        // masukkin permissions to roles
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();
        $mitraRole = Role::where('name', 'mitra')->where('guard_name', 'api')->first();

        if ($adminRole) {
            $adminRole->syncPermissions([
                'users.view', 'users.create', 'users.update', 'users.delete',
                'roles.view', 'roles.create', 'roles.update', 'roles.delete',
                'permissions.view', 'permissions.assign',
                'mitra.view', 'mitra.create', 'mitra.update', 'mitra.delete',
                'mitra.approve', 'mitra.reject', 'mitra.fee',
                'topups.view', 'topups.approve', 'topups.reject',
                'transactions.view', 'transactions.cancel',
                'balance.view', 'balance.histories', 'fee-ledgers.view',
                'reports.transactions', 'reports.topups', 'reports.fees', 'reports.balances',
                'routes.view', 'routes.create', 'routes.update', 'routes.delete',
                'cities.view', 'cities.create', 'cities.update', 'cities.delete',
                'terminals.view', 'terminals.create', 'terminals.update', 'terminals.delete',
                'schedules.view', 'schedules.create', 'schedules.update', 'schedules.delete',
                'vehicles.view', 'vehicles.create', 'vehicles.update', 'vehicles.delete', 'vehicles.bulk-create',
                'seats.view', 'seats.create', 'seats.update', 'seats.delete', 'seats.generate',
                'tickets.view', 'tickets.reschedule', 'tickets.cancel',
                'dashboard.admin',
            ]);
        }

        if ($mitraRole) {
            $mitraRole->syncPermissions([
                'topups.view', 'topups.create',
                'transactions.view', 'transactions.create', 'transactions.search',
                'transactions.book', 'transactions.pay', 'transactions.issue', 'transactions.cancel',
                'transactions.seat-map',
                'balance.view', 'balance.histories', 'fee-ledgers.view',
                'routes.view',
                'cities.view',
                'terminals.view',
                'schedules.view',
                'vehicles.view',
                'seats.view',
                'tickets.view', 'tickets.reschedule', 'tickets.cancel',
                'dashboard.partner',
            ]);
        }

        $this->command->info('Permissions created and assigned successfully');
    }
}
