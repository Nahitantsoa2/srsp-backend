<?php

namespace App\Services;

use App\Models\SuiviDossier;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SuiviImportService
{
    public const COLONNES = [
        'date_reception',
        'ref_be',
        'ref_dossier',
        'im',
        'nom_prenom',
        'ministere',
        'motif_instance',
        'detail_motif_instance',
        'date_leve_motif',
        'info_leve_motif',
        'sort_traitement',
        'motif_rejet',
        'detail_motif_rejet',
        'ref_operation',
        'traitement_augure',
        'date_traitement',
        'type_dossier',
        'nature_dossier',
        'detail_nature_dossier',
        'info_levee_motif',
        'date_leve_motif_2',
        'etat_dossier',
        'motif_rejet_2',
        'user',
    ];

    /**
     * Importe un fichier Excel/CSV/TSV dans la table suivi_dossier.
     * Ajoute uniquement les lignes nouvelles (jamais de doublon).
     *
     * @return array{ajoutes:int, ignores:int, invalides:int, total:int, nouveaux:array<int, SuiviDossier>}
     */
    public function importer(string $chemin): array
    {
        $lecteur = IOFactory::createReaderForFile($chemin);
        $lecteur->setReadDataOnly(true);
        $classeur = $lecteur->load($chemin);

        $feuille = $classeur->getActiveSheet();
        $lignes = $feuille->toArray(null, true, true, true);

        $ajoutes = 0;
        $ignores = 0;
        $invalides = 0;
        $nouveaux = [];
        $premiere = true;

        foreach ($lignes as $key => $ligne) {
            if ($premiere) {
                $premiere = false;

                continue;
            }

            $valeurs = [];
            foreach (array_keys(self::COLONNES) as $i) {
                $col = chr(65 + $i);
                $valeurs[] = $this->formaterCellule($ligne[$col] ?? null);
            }

            if (empty(implode('', $valeurs))) {
                continue;
            }

            $donnees = array_combine(self::COLONNES, $valeurs);

            if (! isset($donnees['im']) || trim($donnees['im']) === '') {
                $invalides++;

                continue;
            }

            $donnees['empreinte'] = SuiviDossier::empreinte($valeurs);

            if (SuiviDossier::where('empreinte', $donnees['empreinte'])->exists()) {
                $ignores++;

                continue;
            }

            $nouveaux[] = SuiviDossier::create($donnees);
            $ajoutes++;
        }

        return [
            'ajoutes' => $ajoutes,
            'ignores' => $ignores,
            'invalides' => $invalides,
            'total' => SuiviDossier::count(),
            'nouveaux' => $nouveaux,
        ];
    }

    private function formaterCellule(mixed $valeur): string
    {
        if ($valeur === null) {
            return '';
        }

        if ($valeur instanceof \DateTimeInterface) {
            return $valeur->format('d/m/Y');
        }

        return trim((string) $valeur);
    }
}
