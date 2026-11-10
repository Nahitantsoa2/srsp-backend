<?php

namespace Tests\Unit;

use App\Services\AssistantIaService;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Tests de la qualité des réponses de l'assistant RAG (sans appel réseau).
 *
 * Le service est forcé en mode « hors ligne » pour tester la détection de
 * langue, l'intention, la fiabilité et la mise en forme des réponses.
 */
class AssistantIaServiceTest extends TestCase
{
    private AssistantIaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.key' => null]);

        $this->service = new AssistantIaService();
    }

    private function appelerPrive(string $methode, mixed ...$arguments): mixed
    {
        $reflexion = new ReflectionClass(AssistantIaService::class);
        $methode = $reflexion->getMethod($methode);

        return $methode->invoke($this->service, ...$arguments);
    }

    public function test_detection_de_la_langue(): void
    {
        $this->assertSame(
            'fr',
            $this->appelerPrive('detecterLangue', 'Quelles sont les pièces à fournir pour une pension ?')
        );

        $this->assertSame(
            'mg',
            $this->appelerPrive('detecterLangue', "Inona avy ny taratasy ilaina amin'ny fanovana toerana ?")
        );

        $this->assertSame(
            'mg',
            $this->appelerPrive('detecterLangue', 'Aiza ny sata ny rakitrako ?')
        );
    }

    public function test_detection_de_l_intention(): void
    {
        $this->assertSame('pieces', $this->appelerPrive('detecterIntention', 'Quelles sont les pièces pour un avenant ?'));
        $this->assertSame('procedure', $this->appelerPrive('detecterIntention', 'Comment obtenir un bon de caisse ?'));
        $this->assertSame('code', $this->appelerPrive('detecterIntention', 'Que signifie le code CM 13 ?'));
        $this->assertSame('suivi', $this->appelerPrive('detecterIntention', 'Où en est mon dossier ?'));
        $this->assertSame('salutation', $this->appelerPrive('detecterIntention', 'Bonjour'));
    }

    public function test_le_prompt_contient_les_regles_de_fiabilite_et_de_langue(): void
    {
        $prompt = $this->appelerPrive('construirePromptBase', 'mg', 'pieces');

        $this->assertStringContainsString("N'invente JAMAIS", $prompt);
        $this->assertStringContainsString('intégralement en malagasy', $prompt);
        $this->assertStringContainsString('SRSP AMM', $prompt);
        $this->assertStringContainsString('hors domaine', $prompt);
        $this->assertStringContainsString('Consigne prioritaire pour cette question', $prompt);
        $this->assertStringContainsString('BASE 1 : PIÈCES À FOURNIR', $prompt);
        $this->assertStringContainsString('- [ ]', $prompt);
    }

    public function test_reponse_francaise_pieces_en_checklist_sans_invention(): void
    {
        $reponse = $this->service->repondre('Quelles sont les pièces pour une réversion orphelin ?');

        $this->assertStringContainsString('réversion orphelin', $reponse);
        $this->assertStringContainsString('- [ ]', $reponse);
        $this->assertStringContainsString('base documentaire locale', $reponse);
        $this->assertStringNotContainsString('[DEBUG]', $reponse);
    }

    public function test_reponse_malagasy_pour_une_question_de_suivi(): void
    {
        $reponse = $this->service->repondre('Aiza ny sata ny rakitrako ?');

        $this->assertStringContainsString('Tsy afaka', $reponse);
        $this->assertStringContainsString('SRSP AMM', $reponse);
        $this->assertStringNotContainsString('[DEBUG]', $reponse);
    }

    public function test_question_hors_domaine_ne_produit_pas_de_reponse_inventee(): void
    {
        $reponse = $this->service->repondre('Quelle est la météo à Antananarivo ?');

        $this->assertStringContainsString("Cette information n'est pas disponible", $reponse);
        $this->assertStringContainsString('SRSP AMM', $reponse);
    }

    public function test_reponse_malagasy_pour_une_information_absente(): void
    {
        $reponse = $this->service->repondre('Inona ny mponina eto Madagasikara ?');

        $this->assertStringContainsString('Tsy hita', $reponse);
        $this->assertStringContainsString('SRSP AMM', $reponse);
    }

    public function test_reponse_code_de_la_solde_en_checklist(): void
    {
        $reponse = $this->service->repondre('Que signifie le code CM 13 ?');

        $this->assertStringContainsString('Codes de la Solde', $reponse);
        $this->assertStringContainsString('Avancement', $reponse);
        $this->assertStringContainsString('- [ ]', $reponse);
    }
}
