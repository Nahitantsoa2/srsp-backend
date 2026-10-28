<?php

/*
|--------------------------------------------------------------------------
| Dossiers VISA des actes administratifs — réponses hors ligne
|--------------------------------------------------------------------------
| Chaque entrée associe une liste de mots-clés (détection) à la réponse
| renvoyée. Utilisé par App\Services\AssistantIaService pour répondre même
| lorsqu'aucune clé API Gemini n'est configurée.
|
| Structure : [ [mots-clés], réponse ].
*/

return [
    'renouvellement de contrat' => [
        ['renouvellement', 'fanavaozana'],
        "Voici les pièces à fournir pour un RENOUVELLEMENT DE CONTRAT (Agent EFA) :\n- Demande (avec avis favorable du chef hiérarchique) - 02 ex\n- Projet - 12 ex\n- Photocopie certifiée des contrats et/ou avenants précédents - 03 ex\n- Fiche n°2 - 02 ex\n- Note de présentation - 02 ex\n- Attestation de non interruption de service - 02 ex\n- Certificat administratif - 02 ex\n- Fiche médicale 57/58 - 01 ex\n- BIN - 02 ex\n- Avis de crédit - 03 ex",
    ],
    'avenant' => [
        ['avenant'],
        "Voici les pièces à fournir pour un AVENANT :\n- Demande (avec avis favorable du chef hiérarchique) - 02 ex\n- Projet - 12 ex\n- Photocopie certifiée des contrats et/ou avenants précédents - 03 ex\n- Attestation de non interruption de service - 02 ex\n- Attestation de non jouissance de congé (1er avenant) - 03 ex\n- BIN - 02 ex\n- Avis de crédit - 03 ex",
    ],
    'intégration' => [
        ['intégration', 'integration'],
        "Voici les pièces à fournir pour une INTEGRATION :\n- Demande (avec avis favorable du chef hiérarchique) - 02 ex\n- Projet d'arrêté - 12 ex\n- Fiche d'intégration - 03 ex\n- Copie d'acte de naissance original ou CIN légalisée - 03 ex\n- Diplôme certifié conforme à la dernière situation - 03 ex\n- Arrêté d'équivalence délivré par la FOP (-1 AN) - 03 ex\n- Photocopie certifiée des contrats et avenants précédents (s'il y a lieu) - 03 ex\n- Compte rendu de prise de service - 02 ex\n- Attestation de non interruption de service - 02 ex\n- Certificat administratif - 02 ex\n- Avis de crédit - 03 ex",
    ],
    'majoration de salaire' => [
        ['majoration de salaire', 'augmentation de salaire', 'fampiakarana karama'],
        "Voici les pièces à fournir pour une MAJORATION DE SALAIRE (Agent ELD) :\n- Demande (avec avis favorable du Chef Hiérarchique) - 02 ex\n- Projet de décision - 12 ex\n- Photocopie certifiée de la dernière décision - 03 ex\n- Attestation de prise de service (pour la 1ère majoration) - 02 ex\n- Certificat administratif - 02 ex\n- Attestation de non interruption - 02 ex\n- Fiche de proposition d'augmentation de salaire - 02 ex\n- Avis de crédit - 03 ex",
    ],
    'avancement de classe' => [
        ['avancement de classe', 'avancement en classe', 'fisondrotana kilasy'],
        "Voici les pièces à fournir pour un AVANCEMENT DE CLASSE :\n- Demande (avec avis favorable du Chef Hiérarchique) - 02 ex\n- Projet de décision - 06 ex\n- Projet d'arrêté - 12 ex\n- P.V - 01 ex\n- Photocopie certifiée de la dernière situation - 03 ex\n- BIN - 02 ex\n- Attestation de non interruption de service - 02 ex\n- Etat de service (s'il y a bonification d'ancienneté) - 03 ex\n- Attestation de non application de la bonification d'ancienneté (s'il existe) - 02 ex\n- Arrêté de bonification (s'il existe) - 03 ex\n- Avis de crédit - 03 ex",
    ],
    "avancement d'échelon" => [
        ['avancement d\'échelon', "avancement d'echelon", 'avancement échelon', 'avancement echelon'],
        "Voici les pièces à fournir pour un AVANCEMENT D'ECHELON :\n- Demande (avec avis favorable du Chef Hiérarchique) - 02 ex\n- Projet d'arrêté - 12 ex\n- Photocopie certifiée de la dernière situation - 03 ex\n- Attestation de non interruption de service - 02 ex\n- Etat de service (s'il y a bonification d'ancienneté) - 03 ex\n- Attestation de non application de la bonification d'ancienneté (s'il existe) - 02 ex\n- Arrêté de bonification (s'il existe) - 03 ex\n- Avis de crédit - 03 ex",
    ],
    'reclassement indiciaire' => [
        ['reclassement'],
        "Voici les pièces à fournir pour un RECLASSEMENT INDICIAIRE :\n- Demande (avec avis favorable du chef hiérarchique) - 02 ex\n- Projet d'arrêté - 12 ex\n- Photocopie certifiée de la dernière situation - 02 ex\n- Attestation de non interruption de service - 02 ex\n- Certificat administratif avec date de naissance ou CIN légalisée - 03 ex\n- Etat de service (s'il y a bonification d'ancienneté) - 03 ex\n- Arrêté de bonification (s'il existe) - 02 ex\n- Avis de crédit - 03 ex",
    ],
    "majoration d'indice" => [
        ['majoration d\'indice', "majoration d'indice", 'majoration indice'],
        "Voici les pièces à fournir pour une MAJORATION D'INDICE (catégorie VIII) :\n- Demande (avec avis favorable du chef hiérarchique) - 02 ex\n- Projet d'arrêté - 12 ex\n- Photocopie certifiée de la dernière situation - 02 ex\n- Attestation de non interruption de service - 02 ex\n- Certificat administratif avec date de naissance - 02 ex\n- CIN légalisée - 03 ex\n- Attestation de non application de la bonification d'ancienneté (s'il existe) - 02 ex\n- Arrêté de bonification (s'il existe) - 03 ex\n- Avis de crédit - 03 ex",
    ],
    'bonification RSM' => [
        ['bonification rsm', 'rsm'],
        "Voici les pièces à fournir pour une BONIFICATION RSM :\n- Demande (avec avis favorable du Chef Hiérarchique) - 02 ex\n- Projet d'arrêté - 12 ex\n- Etat signalétique certifié par l'autorité compétente - 03 ex\n- Photocopie certifiée de la dernière situation - 02 ex\n- Attestation de non interruption de service - 02 ex\n- Attestation de non attribution de la bonification d'ancienneté - 02 ex\n- Déclaration sur l'honneur - 02 ex\n- Etat de service - 03 ex\n- Avis de crédit - 03 ex",
    ],
    'bonification DELF-DALF' => [
        ['bonification delf', 'delf', 'dalf', 'bonification dalf'],
        "Voici les pièces à fournir pour une BONIFICATION DELF / DALF :\n- Demande (avec avis favorable du Chef Hiérarchique) - 02 ex\n- Projet d'arrêté - 12 ex\n- Photocopie certifiée de la dernière situation - 02 ex\n- Diplôme DELF et/ou DALF - 03 ex\n- Arrêté d'équivalence délivré par la FOP - 03 ex\n- Attestation de non interruption de service - 02 ex\n- Attestation de non attribution de la bonification d'ancienneté - 02 ex\n- Avis de crédit - 03 ex",
    ],
    "indemnité d'installation de retraite" => [
        ['installation de retraite', 'installation à la retraite', 'indemnite d\'installation'],
        "Voici les pièces à fournir pour une INDEMNITE D'INSTALLATION DE RETRAITE :\n- Demande (avec avis favorable du Chef Hiérarchique) - 02 ex\n- Projet de décision - 12 ex\n- Arrêté d'admission à la retraite - 03 ex\n- Photocopie certifiée de la dernière situation - 03 ex\n- Relevé de service - 03 ex\n- Attestation de non interruption de service - 02 ex\n- CIN légalisée - 03 ex\n- Pièces de validation (pour les fonctionnaires ayant effectué le Contractuel EFA) - 03 ex\n- Cessation de service pour la retraite anticipée - 02 ex\n- Avis de crédit - 03 ex",
    ],
    'titularisation' => [
        ['titularisation'],
        "Voici les pièces à fournir pour une TITULARISATION :\n- Demande (avec avis favorable du Chef Hiérarchique) - 02 ex\n- Projet d'arrêté - 12 ex\n- P.V - 01 ex\n- Photocopie certifiée de la dernière situation (arrêté d'intégration ou arrêté de nomination) - 03 ex\n- Etat de congé ou Attestation de non jouissance de congé - 02 ex\n- Compte rendu de prise de service ou Attestation de non interruption de service - 03 ex\n- BIN - 02 ex\n- Avis de crédit - 03 ex",
    ],
    'indemnité compensatrice de congé non pris' => [
        ['indemnité compensatrice', 'indemnite compensatrice', 'compensatrice de cong'],
        "Voici les pièces à fournir pour une INDEMNITE COMPENSATRICE DE CONGE NON PRIS :\n- Demande (avec avis favorable du Chef Hiérarchique) - 02 ex\n- Projet de décision - 12 ex\n- Arrêté d'admission à la retraite - 03 ex\n- Photocopie certifiée de la dernière situation - 03 ex\n- Décision de congé des trois dernières années - 03 ex\n- Etat de congé - 02 ex\n- Titre de congé - 02 ex\n- Attestation de non jouissance de congé - 02 ex\n- CIN légalisée - 03 ex\n- Avis de crédit - 03 ex",
    ],
    'en cas de décès' => [
        ['maty ny', 'en cas de décès', 'décès de l\'agent', "deces de l'agent", 'dossier de décès', 'fahafatesana', 'maty'],
        "Voici les pièces à fournir EN CAS DE DECES :\n- Acte de décès de l'agent - 03 ex\n- Acte de mariage du défunt ou acte de naissance de l'enfant - 03 ex\n- CIN légalisée - 03 ex\n- Avis de crédit - 03 ex",
    ],
];
