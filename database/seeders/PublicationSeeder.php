<?php

namespace Database\Seeders;

use App\Models\Publication;
use Illuminate\Database\Seeder;

class PublicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Publication::create([
            'title' => 'Investigación sobre IA en Educación',
            'author' => 'Dr. Juan Pérez García',
            'description' => 'Estudio de cómo la inteligencia artificial impacta en los procesos educativos modernos.',
            'date' => now()->subMonths(3),
            'identifier' => 'unmsm-2026-001',
            'subject' => 'Educación, Inteligencia Artificial',
            'type' => 'Article',
            'language' => 'es',
            'publisher' => 'UNMSM',
        ]);

        Publication::create([
            'title' => 'Sostenibilidad en Latinoamérica',
            'author' => 'Mg. María López Rodríguez',
            'description' => 'Análisis de políticas de sostenibilidad ambiental aplicadas en universidades latinoamericanas.',
            'date' => now()->subMonths(2),
            'identifier' => 'unmsm-2026-002',
            'subject' => 'Sostenibilidad, Ambiente',
            'type' => 'Article',
            'language' => 'es',
            'publisher' => 'UNMSM',
        ]);

        Publication::create([
            'title' => 'Metodologías Ágiles en Desarrollo de Software',
            'author' => 'Ing. Carlos Mendoza',
            'description' => 'Revisión sistemática de metodologías ágiles en proyectos de software en instituciones peruanas.',
            'date' => now()->subMonths(1),
            'identifier' => 'unmsm-2026-003',
            'subject' => 'Ingeniería de Software, Metodologías',
            'type' => 'Article',
            'language' => 'es',
            'publisher' => 'UNMSM',
        ]);
    }
}
