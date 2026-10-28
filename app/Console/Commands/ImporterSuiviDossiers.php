<?php

namespace App\Console\Commands;

use App\Services\SuiviImportService;
use Illuminate\Console\Command;

class ImporterSuiviDossiers extends Command
{
    protected $signature = 'suivi:import {chemin : Chemin du fichier Excel/CSV/TSV}';

    protected $description = 'Importe les dossiers de suivi depuis un fichier Excel/CSV (ajoute uniquement les nouvelles lignes)';

    public function handle(SuiviImportService $service): int
    {
        $chemin = $this->argument('chemin');

        if (! file_exists($chemin)) {
            $this->error("Fichier introuvable : {$chemin}");

            return self::FAILURE;
        }

        try {
            $resultat = $service->importer($chemin);
        } catch (\Throwable $e) {
            $this->error('Fichier illisible : '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Import terminé : {$resultat['ajoutes']} ajouté(s), {$resultat['ignores']} déjà présent(s), {$resultat['invalides']} ligne(s) sans IM.");
        $this->info('Total dans la base : '.$resultat['total']);

        return self::SUCCESS;
    }
}
