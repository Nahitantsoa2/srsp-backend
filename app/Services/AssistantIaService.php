<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Agent d'assistance conversationnel (LLM Gemini).
 *
 * Centralise l'appel à l'API Gemini ainsi que les réponses « hors ligne »
 * (Codes de la Solde, dossiers VISA) utilisées lorsque la clé API n'est pas
 * configurée. Le contrôleur se contente de déléguer.
 */
class AssistantIaService
{
    /**
     * Retourne la réponse de l'agent à la question de l'utilisateur.
     */
    public function repondre(string $question): string
    {
        $promptBase = $this->construirePromptBase();

        try {
            $apiKey = config('services.gemini.key');
            if (empty($apiKey) || $apiKey === 'votre_cle_gemini_ici') {
                return $this->reponseHorsLigne($question);
            }

            $model = config('services.gemini.model', 'gemini-3-flash');

            $response = Http::withoutVerifying()
                ->timeout(20)
                ->retry(2, 2000, function ($exception, $request) {
                    // Réessaie automatiquement en cas de 503 (surcharge Gemini)
                    return $exception instanceof RequestException
                        && $exception->response?->status() === 503;
                })
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'content-type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'system_instruction' => [
                        'parts' => [['text' => $promptBase]],
                    ],
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [['text' => $question]],
                        ],
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Erreur API Gemini', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                if ($response->status() === 503) {
                    return 'Le service Gemini est actuellement surchargé (forte demande). Veuillez réessayer dans quelques instants.';
                }

                return "Je suis désolé, je n'ai pas pu vous répondre car mon accès à l'IA a rencontré une erreur (peut-être que la clé API est invalide).\n\n[DEBUG] Statut : ".$response->status()."\n[DEBUG] Détail : ".$response->body();
            }

            return $response->json('candidates.0.content.parts.0.text') ?? "Je n'ai pas pu générer de réponse.";
        } catch (\Exception $e) {
            return "Désolé, une erreur est survenue lors de la communication avec l'IA. Veuillez réessayer ou contacter votre service.";
        }
    }

    /**
     * Construit le prompt système (règles + bases de connaissances).
     */
    private function construirePromptBase(): string
    {
        $promptBase = "Tu es un agent d'assistance pour les démarches administratives des fonctionnaires (Direction de la Solde et des Pensions — Madagascar). Réponds de façon claire et concise, dans la langue de la question (français ou malagasy).\n\n"
            ."Tu disposes de deux bases de connaissances :\n"
            ."1) les pièces à fournir pour les démarches administratives ;\n"
            ."2) les CODES DE LA SOLDE : table des corps (ex. A06A), hauts emplois de l'État (ex. E01G), majoration des matricules et liste des codes mouvements (CM 01 à 85).\n"
            ."Lorsque l'utilisateur saisit un code mouvement (« 13 », « CM 13 »…), un code corps (ex. « A06A ») ou un code HEE (ex. « E01G »), donne son libellé ainsi que sa catégorie/indice d'après les informations ci-dessous. Appuie-toi UNIQUEMENT sur ces informations ; si un code ou une donnée n'y figure pas, dis-le clairement sans inventer.\n\n"
            .'===== BASE 1 : PIÈCES À FOURNIR =====\n\n';

        $piecesFile = resource_path('prompts/pieces_a_fournir.txt');
        if (file_exists($piecesFile)) {
            $promptBase .= file_get_contents($piecesFile);
        }

        $codesFile = resource_path('prompts/codes_solde.txt');
        if (file_exists($codesFile)) {
            $promptBase .= "\n\n===== BASE 2 : CODES DE LA SOLDE (DSP MAI 2023) =====\n\n".file_get_contents($codesFile);
        }

        return $promptBase;
    }

    /**
     * Séquence de réponses hors ligne lorsque la clé API n'est pas configurée.
     * Priorité : dossier VISA, puis Codes de la Solde, puis mots-clés connus.
     */
    private function reponseHorsLigne(string $question): string
    {
        $reponseVisa = $this->reponseVisaSimulee($question);
        if ($reponseVisa !== null) {
            return $reponseVisa;
        }

        $reponseCode = $this->reponseCodeSoldeSimulee($question);
        if ($reponseCode !== null) {
            return $reponseCode;
        }

        $prefixe = "Ceci est une réponse simulée car la clé API Gemini n'est pas configurée. Pour obtenir de vraies réponses, ajoutez votre clé dans le fichier .env (GEMINI_API_KEY).";

        $piecesFile = resource_path('prompts/pieces_a_fournir.txt');
        if (file_exists($piecesFile)) {
            $reponse = $this->reponseParMotCle($question, $prefixe);
            if ($reponse !== null) {
                return $reponse;
            }
        }

        return $prefixe.' \n\n'.$question;
    }

    /**
     * Réponses simulées par mot-clé (pièces à fournir connues).
     */
    private function reponseParMotCle(string $question, string $prefixe): ?string
    {
        if (stripos($question, 'réversion orphelin') !== false || stripos($question, 'reversion orphelin') !== false) {
            return $prefixe."\n\nVoici les pièces à fournir pour une pension de réversion orphelin (CDMVT 07) :\n- Acte de décès de l'auteur de droit (Original ou copie certifiée - 2 ex)\n- Certificat de non séparation de corps (Original ou copie certifiée - 2 ex)\n- Certificat de non divorce (Original ou copie certifiée - 2 ex)\n- Certificat de non remariage du (ou des) conjoint(s) survivant(s) (Original ou copie certifiée - 2 ex)\n- Fiche familiale d'état civil (Original - 2 ex)\n- Certificat de scolarité ou de vie des orphelins mineurs (Original - 2 ex)\n- Relevé de compte bancaire (1 ex)";
        }

        if (stripos($question, 'assignation') !== false || stripos($question, 'fanovana toerana') !== false) {
            return $prefixe."\n\nVoici les documents nécessaires pour un changement d'assignation (Trésor ou Perception) (CDMVT 18) :\n- Acte de changement de domicile ou d'assignation (2 ex)\n- Arrêté de concession de pension (1 ex)\n- Certificat de vie (1 ex)\n- Attestation de résidence (1 ex)";
        }

        if (stripos($question, 'rectification de nom') !== false || stripos($question, 'CIN') !== false || stripos($question, 'fanitsiana anarana') !== false) {
            return $prefixe."\n\nVoici les documents nécessaires pour une demande de rectification de nom, prénom ou CIN sur une pension (CDMVT 18) :\n- Acte ou décision de rectification (Original - 2 ex)\n- Fiche d'état civil (Original - 1 ex)\n- Copie de la CIN (1 ex)\n- Arrêté de concession de pension (1 ex)";
        }

        if (stripos($question, 'bonification') !== false) {
            return $prefixe."\n\nVoici les pièces requises pour une révision de bonification (CDMVT 14) :\n- Demande manuscrite (1 ex)\n- Arrêté de concession de pension (2 ex)\n- Pièces justificatives de la bonification (Original ou copie certifiée - 2 ex)";
        }

        if (stripos($question, 'validation') !== false || stripos($question, 'précaire') !== false || stripos($question, 'precaire') !== false || stripos($question, 'fanamarinana') !== false) {
            return $prefixe."\n\nVoici les pièces à fournir pour une DEMANDE DE VALIDATION DES SERVICES PRÉCAIRES (Madame le Chef de Service de la Validation des Services Précaires — Antananarivo) :\n\nPIÈCES COMMUNES :\n- Formulaire de demande manuscrite\n- Arrêté d'Intégration ou de nomination (*)\n- Arrêté de Titularisation (*)\n- Relevé de Service bien détaillé et signé par le Responsable du Personnel\n- Photocopie de l'Avis de Crédit ou du Bon de Caisse\n- Photocopie de la CIN\n\nSI ECD :\n- Première décision d'engagement (*)\n- Dernière décision d'engagement (*)\n- Certificat de non interruption de service\n- État de transfert des cotisations antérieurement versées à la CNaPS\n\nSI ELD :\n- Première décision d'engagement (*)\n- Dernière décision de majoration de salaire (*)\n\nSI EFA :\n- Premier contrat de travail (*)\n- Dernier contrat de travail (*)\n- Avenant s'il y a reclassement\n- Attestation de prise de service\n\nSI SERVICES PRIVÉS :\n- Certificat de travail\n- Transfert de cotisations CNaPS\n- Engagement de payer la part individuelle de 4% et la part patronale de 16% (4% + 16%)\n\nSI BUDGET AUTONOME (EFA/ELD) :\n- Relevé de cotisations CPR 3% avec référence des mandats de versement au Trésor Public (visé par le Responsable du Trésor ou de la Perception)\n\nSI VEUF/VEUVES/ORPHELINS :\n- Acte de décès\n- Acte de mariage (veuf/veuve)\n- Acte de tutelle (orphelins mineurs)\n\n(*) Photocopie certifiée";
        }

        if (stripos($question, 'duplicata') !== false || stripos($question, 'duplika') !== false) {
            return $prefixe."\n\nVoici les pièces à fournir pour une DEMANDE DE DUPLICATA (Madame le Chef de Service de la Validation des Services Précaires — Antananarivo) :\n- Avis d'Émission d'Ordre de Recette\n- Ordre de Recette\n- État de décompte de la part individuelle (4%) (5%)\n- État de décompte de la part patronale (16%) (19%)";
        }

        if ((stripos($question, 'nomination') !== false && stripos($question, 'CDMVT 02') !== false) || stripos($question, 'fanendrena') !== false || stripos($question, 'nomination') !== false) {
            return $prefixe."\n\nVoici les pièces à fournir pour une nomination de fonctionnaire (CDMVT 02) :\n- Acte de nomination (3 ex)\n- Certificat de prise de service (2 ex)\n- Attestation de première prise de service (2 ex)\n- Certificat administratif de non-paiement (1 ex)";
        }

        if ((stripos($question, 'titularisation') !== false && stripos($question, 'CDMVT 11') !== false) || stripos($question, 'titularisation') !== false) {
            return $prefixe."\n\nVoici les documents nécessaires pour le mandatement d'une titularisation (CDMVT 11) :\n- Acte de titularisation (3 ex)\n- Certificat de prise de service (2 ex)\n- Bulletin de solde récent (1 ex)\n- Certificat administratif de non-paiement (1 ex)";
        }

        if (stripos($question, 'avancement') !== false || stripos($question, 'CDMVT 13') !== false || stripos($question, 'fisondrotana kilasy') !== false || stripos($question, 'échelon') !== false || stripos($question, 'echelon') !== false) {
            return $prefixe."\n\nQue faut-il préparer pour un avancement de classe ou d'échelon (CDMVT 13) :\n- Acte d'avancement (3 ex)\n- Certificat de prise de service (2 ex)\n- Bulletin de solde récent (1 ex)\n- Certificat administratif de non-paiement (1 ex)";
        }

        if ((stripos($question, 'majoration') !== false && stripos($question, 'ELD') !== false) || stripos($question, 'ELD') !== false || stripos($question, 'fampiakarana karama') !== false) {
            return $prefixe."\n\nVoici les pièces fournir pour la majoration de salaire d'un ELD (CDMVT 16) :\n- Acte de majoration (3 ex)\n- Certificat de prise de service (2 ex)\n- Bulletin de solde récent (1 ex)\n- Contrat d'engagement (1 ex)\n- Certificat administratif de non-paiement (1 ex)";
        }

        if (stripos($question, 'congé') !== false || stripos($question, 'conge') !== false) {
            return $prefixe."\n\nVoici les pièces à fournir pour une indemnité compensatrice de congé non pris (CDMVT 19) :\n- Acte formaté (2 ex)\n- Décision (3 ex)\n- Arrêté d'admission à la retraite (3 ex)\n- Certificat administratif pour mandatement (1 ex)\n- Bulletin de solde ou avis de crédit (1 ex)";
        }

        return null;
    }

    /**
     * Réponses hors ligne : dossier VISA des actes administratifs (listes SRSP).
     * Retourne null si la question ne correspond à aucun dossier connu.
     */
    private function reponseVisaSimulee(string $question): ?string
    {
        $liste = require resource_path('prompts/dossiers_visa.php');

        $q = mb_strtolower($question);

        foreach ($liste as [$mots, $reponse]) {
            foreach ($mots as $mot) {
                if (strpos($q, mb_strtolower($mot)) !== false) {
                    return $reponse;
                }
            }
        }

        return null;
    }

    /**
     * Réponse hors ligne pour les Codes de la Solde (corps, HEE, codes mouvements CM).
     * Retourne null si aucun code n'est reconnu.
     */
    private function reponseCodeSoldeSimulee(string $question): ?string
    {
        $file = resource_path('prompts/codes_solde.txt');
        if (! file_exists($file)) {
            return null;
        }

        $q = mb_strtolower($question);
        $lignes = preg_split('/\r\n|\r|\n/', file_get_contents($file));
        $trouves = [];

        // Codes corps (ex. a06a) et hauts emplois (ex. e01g, gapm, mc01)
        if (preg_match_all('/\b([a-d]\d{2}[a-z]|e\d{2}[a-z0-9]|gapm|gapr|mc\d{2})\b/u', $q, $m)) {
            foreach (array_unique($m[1]) as $code) {
                foreach ($lignes as $ligne) {
                    $ligne = trim($ligne);
                    if (stripos($ligne, $code.' —') === 0 || stripos($ligne, $code.' –') === 0) {
                        $trouves[] = $ligne;
                        break;
                    }
                }
            }
        }

        // Codes mouvements : « cm 13 », « code mouvement 13 », « code 13 »
        if (preg_match('/\b(?:cm|code(?:\s+mouvement)?)\s*(\d{1,2})\b/u', $q, $m)) {
            $num = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            foreach ($lignes as $ligne) {
                $ligne = trim($ligne);
                if (preg_match('/^'.$num.'\s*[–-]/u', $ligne)) {
                    $trouves[] = $ligne;
                    break;
                }
            }
        }

        if (empty($trouves)) {
            return null;
        }

        return "Voici les informations trouvées dans les Codes de la Solde (DSP MAI 2023) :\n- ".implode("\n- ", array_unique($trouves));
    }
}
