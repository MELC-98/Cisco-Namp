<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FMPTangerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Drop the category check constraint if it exists
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE devices DROP CONSTRAINT IF EXISTS devices_category_check');

        // 1. Create Sites
        $sitesData = [
            'Internet' => 'Liaisons Fibre Optique',
            'Salle Serveur' => 'Salle Serveur Principale',
            'Bibliothèque' => 'Bibliothèque Universitaire',
            'Administration' => 'Bâtiment Administratif',
            'Centre de Formation' => 'C.F (Centre de Formation)',
            'Salle de Conférence' => 'S.C (Salle de Conférence)',
            'Départements' => 'Départements (Dpt)',
            'Amphis' => 'Amphithéâtres (Amph2, Amhp3)',
            'Centre Pédagogique' => 'C.P (Centre Pédagogique)',
            'Laboratoires de Recherche (Droite)' => 'C.R.D (Centre de Recherche Droite)',
            'Laboratoires de Recherche (Gauche)' => 'C.R.G (Centre de Recherche Gauche)',
            'L.S / L.G' => 'Autres Laboratoires (L.S, L.G)',
            'Inconnu' => 'Localisation Non Spécifiée',
        ];

        $sites = [];
        foreach ($sitesData as $name => $description) {
            $sites[$name] = Site::firstOrCreate(
                ['name' => $name],
                [
                    'code' => strtoupper(Str::random(5)),
                    'location' => 'FMP Tanger',
                    'description' => $description,
                ]
            );
        }

        // Wipe old devices
        Device::truncate();

        // Helper function
        $createDevice = function($hostname, $ip, $siteName, $category, $model) use ($sites) {
            return Device::create([
                'hostname' => $hostname,
                'management_ip' => $ip,
                'ssh_port' => 22,
                'vendor' => Str::contains(strtolower($model), 'sophos') ? 'sophos' : (Str::contains(strtolower($model), 'fortigate') ? 'fortinet' : 'cisco'),
                'category' => $category,
                'device_type' => 'ios',
                'model' => $model,
                'serial_number' => strtoupper(Str::random(10)),
                'os_name' => 'IOS', // simplified
                'os_version' => '15.2(4)E6',
                'site_id' => $sites[$siteName]->id,
                'description' => "Equipement $hostname",
                'status' => 'online',
                'last_seen_at' => now(),
            ]);
        };

        // Firewalls
        $createDevice('FW-Sophos-XGS4300', '192.168.99.1', 'Salle Serveur', 'firewall', 'Sophos XGS 4300');
        $createDevice('FW-FortiGate-201F', '192.168.99.254', 'Salle Serveur', 'firewall', 'FortiGate 201F');

        // Core
        $createDevice('Core-Catalyst-4507', '192.168.99.2', 'Salle Serveur', 'router', 'Cisco Catalyst 4507R+E');

        // WLCs
        $createDevice('WLC-2504-Primary', '192.168.99.252', 'Salle Serveur', 'wireless', 'Cisco WLC 2504');
        $createDevice('WLC-2504-Backup', '192.168.99.253', 'Salle Serveur', 'wireless', 'Cisco WLC 2504');

        // APs
        $createDevice('AP-Biblio-1', '192.168.99.100', 'Bibliothèque', 'wireless', 'Cisco Aironet 702i');
        $createDevice('AP-Admin-1', '192.168.99.101', 'Administration', 'wireless', 'Cisco Aironet 702i');
        $createDevice('AP-Amphi-1', '192.168.99.102', 'Amphis', 'wireless', 'Cisco Aironet 702i');

        // Access Switches (from spreadsheet)
        $devices = [
            // Salle Serveur
            ['SW-Serveur-2', '192.168.99.3', 'Salle Serveur', 'switch', 'Cisco SG500-52'],
            
            // Biblio
            ['SW-Biblio-1', '192.168.99.4', 'Bibliothèque', 'switch', 'Cisco SG300-28MP'],
            ['SW-Biblio-2', '192.168.99.5', 'Bibliothèque', 'switch', 'Cisco SG300-28MP'],

            // Administration
            ['SW-Admin-1', '192.168.99.6', 'Administration', 'switch', 'Cisco SG500-52'],
            ['SW-Admin-2', '192.168.99.7', 'Administration', 'switch', 'Cisco SG500-52'],
            ['SW-Admin-3', '192.168.99.8', 'Administration', 'switch', 'Cisco SG500-52'],
            ['SW-Admin-4', '192.168.99.9', 'Administration', 'switch', 'Cisco SG500-52'],

            // C.F
            ['SW-CF-1', '192.168.99.10', 'Centre de Formation', 'switch', 'Cisco SG500-52'],
            ['SW-CF-2', '192.168.99.11', 'Centre de Formation', 'switch', 'Cisco SG300-28MP'],
            ['SW-CF-3', '192.168.99.16', 'Centre de Formation', 'switch', 'Cisco SG500-52'],

            // S.C
            ['SW-SC-1', '192.168.99.12', 'Salle de Conférence', 'switch', 'Cisco SG500-52'],
            ['SW-SC-2', '192.168.99.13', 'Salle de Conférence', 'switch', 'Cisco SG500-52'],

            // Dpt
            ['SW-Dpt-1', '192.168.99.14', 'Départements', 'switch', 'Cisco SG300-28MP'],
            ['SW-Dpt-2', '192.168.99.15', 'Départements', 'switch', 'Cisco SG500-52'],

            // Unnamed
            ['SW-Unknown-18', '192.168.99.18', 'Inconnu', 'switch', 'Cisco SG500-52'],

            // Amph2
            ['SW-Amph2-1', '192.168.99.19', 'Amphis', 'switch', 'Cisco SG500-52'],
            ['SW-Amph2-2', '192.168.99.20', 'Amphis', 'switch', 'Cisco SG500-52'],

            // Amhp3
            ['SW-Amhp3-1', '192.168.99.21', 'Amphis', 'switch', 'Cisco SG500-52'],
            ['SW-Amhp3-2', '192.168.99.22', 'Amphis', 'switch', 'Cisco SG500-52'],

            // C.P(RDC)
            ['SW-CP-RDC-1', '192.168.99.23', 'Centre Pédagogique', 'switch', 'Cisco SG300-28MP'],
            ['SW-CP-RDC-2', '192.168.99.24', 'Centre Pédagogique', 'switch', 'Cisco SG500-52'],

            // C.P(1er)
            ['SW-CP-1er-1', '192.168.99.25', 'Centre Pédagogique', 'switch', 'Cisco SG500-52'],
            ['SW-CP-1er-2', '192.168.99.26', 'Centre Pédagogique', 'switch', 'Cisco SG500-52'],

            // C.R.D(2)
            ['SW-CRD-2-1', '192.168.99.27', 'Laboratoires de Recherche (Droite)', 'switch', 'Cisco SG500-52'],
            ['SW-CRD-2-2', '192.168.99.28', 'Laboratoires de Recherche (Droite)', 'switch', 'Cisco SG500-52'],
            ['SW-CRD-2-3', '192.168.99.29', 'Laboratoires de Recherche (Droite)', 'switch', 'Cisco SG500-52'],
            ['SW-CRD-2-4', '192.168.99.30', 'Laboratoires de Recherche (Droite)', 'switch', 'Cisco SG500-52'],

            // C.R.D(3)
            ['SW-CRD-3-1', '192.168.99.31', 'Laboratoires de Recherche (Droite)', 'switch', 'Cisco SG500-52'],
            ['SW-CRD-3-2', '192.168.99.32', 'Laboratoires de Recherche (Droite)', 'switch', 'Cisco SG500-52'],
            ['SW-CRD-3-3', '192.168.99.33', 'Laboratoires de Recherche (Droite)', 'switch', 'Cisco SG500-52'],
            ['SW-CRD-3-4', '192.168.99.34', 'Laboratoires de Recherche (Droite)', 'switch', 'Cisco SG500-52'],

            // C.R.D(4)
            ['SW-CRD-4-1', '192.168.99.35', 'Laboratoires de Recherche (Droite)', 'switch', 'Cisco SG500-52'],
            ['SW-CRD-4-2', '192.168.99.36', 'Laboratoires de Recherche (Droite)', 'switch', 'Cisco SG500-52'],
            ['SW-CRD-4-3', '192.168.99.37', 'Laboratoires de Recherche (Droite)', 'switch', 'Cisco SG500-52'],
            ['SW-CRD-4-4', '192.168.99.38', 'Laboratoires de Recherche (Droite)', 'switch', 'Cisco SG500-52'],

            // C.R.G(2)
            ['SW-CRG-2-1', '192.168.99.39', 'Laboratoires de Recherche (Gauche)', 'switch', 'Cisco SG500-52'],
            ['SW-CRG-2-2', '192.168.99.40', 'Laboratoires de Recherche (Gauche)', 'switch', 'Cisco SG500-52'],

            // C.R.G(3)
            ['SW-CRG-3-1', '192.168.99.41', 'Laboratoires de Recherche (Gauche)', 'switch', 'Cisco SG500-52'],
            ['SW-CRG-3-2', '192.168.99.42', 'Laboratoires de Recherche (Gauche)', 'switch', 'Cisco SG500-52'],

            // C.R.G(4)
            ['SW-CRG-4-1', '192.168.99.43', 'Laboratoires de Recherche (Gauche)', 'switch', 'Cisco SG500-52'],
            ['SW-CRG-4-2', '192.168.99.44', 'Laboratoires de Recherche (Gauche)', 'switch', 'Cisco SG500-52'],

            // L.S
            ['SW-LS-1', '192.168.99.45', 'L.S / L.G', 'switch', 'Cisco SG500-52'],
            ['SW-LS-2', '192.168.99.46', 'L.S / L.G', 'switch', 'Cisco SG300-28MP'],

            // C.R.D(RDC)
            ['SW-CRD-RDC-1', '192.168.99.47', 'Laboratoires de Recherche (Droite)', 'switch', 'Cisco SG500-52'],
            ['SW-CRD-RDC-2', '192.168.99.48', 'Laboratoires de Recherche (Droite)', 'switch', 'Cisco SG500-52'],

            // C.R.D(1)
            ['SW-CRD-1-1', '192.168.99.49', 'Laboratoires de Recherche (Droite)', 'switch', 'Cisco SG500-52'],
            ['SW-CRD-1-2', '192.168.99.50', 'Laboratoires de Recherche (Droite)', 'switch', 'Cisco SG500-52'],
            ['SW-CRD-1-3', '192.168.99.51', 'Laboratoires de Recherche (Droite)', 'switch', 'Cisco SG500-52'],
            ['SW-CRD-1-4', '192.168.99.52', 'Laboratoires de Recherche (Droite)', 'switch', 'Cisco SG500-52'],

            // C.R.G(1)
            ['SW-CRG-1-1', '192.168.99.53', 'Laboratoires de Recherche (Gauche)', 'switch', 'Cisco SG500-52'],
            ['SW-CRG-1-2', '192.168.99.54', 'Laboratoires de Recherche (Gauche)', 'switch', 'Cisco SG500-52'],

            // C.R.G(RDC)
            ['SW-CRG-RDC-1', '192.168.99.55', 'Laboratoires de Recherche (Gauche)', 'switch', 'Cisco SG500-52'],
            ['SW-CRG-RDC-2', '192.168.99.56', 'Laboratoires de Recherche (Gauche)', 'switch', 'Cisco SG500-52'],

            // L.G
            ['SW-LG-1', '192.168.99.57', 'L.S / L.G', 'switch', 'Cisco SG300-28MP'],
        ];

        foreach ($devices as $d) {
            $createDevice($d[0], $d[1], $d[2], $d[3], $d[4]);
        }
    }
}
