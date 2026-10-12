<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\ChecklistTemplate;
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

        $tpl = ChecklistTemplate::firstOrCreate(['name' => 'Basic IT Security Baseline'], ['description' => 'Starter controls for workstations and servers']);
        if ($tpl->items()->count() === 0) {
            foreach ([
                'Operating systems and software are patched and supported',
                'Endpoint protection / antivirus is installed and up to date',
                'Host firewall is enabled',
                'Default and shared passwords are removed',
                'Multi-factor authentication is enabled for admin accounts',
                'Disk encryption is enabled on laptops',
                'Regular backups exist and a restore has been tested',
                'Unneeded services and open ports are disabled',
                'User accounts are reviewed and leavers are removed',
                'Logs are kept and reviewed',
            ] as $i => $q) {
                $tpl->items()->create(['question' => $q, 'position' => $i]);
            }
        }
    }
}
