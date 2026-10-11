<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** DEMO ONLY: change these passwords before any real deployment. */
    public function run(): void
    {
        foreach (['admin' => 'Admin', 'technician' => 'Tess Technician', 'auditor' => 'Aaron Auditor', 'staff' => 'Sam Staff'] as $role => $name) {
            User::firstOrCreate(['email' => "{$role}@example.com"], ['name' => $name, 'password' => 'password', 'role' => $role, 'status' => 'active']);
        }

        foreach ([['LAP-001', 'Dell Latitude 5420', 'Laptop', 'IT Office'], ['SRV-001', 'File Server', 'Server', 'Server Room'], ['PRN-001', 'HP LaserJet', 'Printer', 'Admin Office']] as [$tag, $n, $c, $l]) {
            Asset::firstOrCreate(['asset_tag' => $tag], ['name' => $n, 'category' => $c, 'location' => $l, 'status' => 'active']);
        }
    }
}
