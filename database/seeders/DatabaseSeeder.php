<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::create([
            'name' => 'Admin Gratias',
            'email' => 'admin@gratiastechnology.com',
            'password' => Hash::make('Admin@2026!'),
            'role' => 'admin',
        ]);

        // Client de test
        $client = User::create([
            'name' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'password' => Hash::make('password'),
            'role' => 'client',
            'company' => 'Dupont SAS',
        ]);

        // Services
        $services = [
            ['fr' => 'Développement web', 'en' => 'Web Development'],
            ['fr' => 'Solutions logicielles', 'en' => 'Software Solutions'],
            ['fr' => 'Produits SaaS', 'en' => 'SaaS Products'],
            ['fr' => 'Maintenance & Assistance', 'en' => 'Maintenance & Support'],
        ];

        foreach ($services as $i => $names) {
            Service::create([
                'title' => ['fr' => $names['fr'], 'en' => $names['en']],
                'description' => [
                    'fr' => "Description du service {$names['fr']}.",
                    'en' => "Description of the {$names['en']} service.",
                ],
                'order' => $i + 1,
                'is_active' => true,
            ]);
        }

        // Projet de test avec étapes
        $project = Project::create([
            'client_id' => $client->id,
            'title' => 'Site vitrine e-commerce',
            'description' => 'Refonte complète du site avec boutique en ligne.',
            'tracking_code' => 'GT-2024-001',
            'status' => 'in_progress',
            'type' => 'web_development',
            'progress' => 40,
            'started_at' => now()->subDays(20),
            'estimated_end_at' => now()->addDays(40),
            'tech_stack' => ['Next.js', 'Laravel', 'MySQL', 'Tailwind CSS'],
        ]);

        $steps = [
            ['title' => 'Analyse et cahier des charges', 'status' => 'completed', 'order' => 1],
            ['title' => 'Maquettes et design UI/UX', 'status' => 'completed', 'order' => 2],
            ['title' => 'Développement backend', 'status' => 'in_progress', 'order' => 3],
            ['title' => 'Développement frontend', 'status' => 'pending', 'order' => 4],
            ['title' => 'Tests & recette', 'status' => 'pending', 'order' => 5],
            ['title' => 'Mise en production', 'status' => 'pending', 'order' => 6],
        ];

        foreach ($steps as $step) {
            $project->steps()->create($step);
        }
    }
}