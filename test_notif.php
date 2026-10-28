<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

use App\Models\Notification;
use App\Models\Utilisateur;
use Illuminate\Contracts\Console\Kernel;

$users = Utilisateur::all();
foreach ($users as $u) {
    Notification::create([
        'id_utilisateur' => $u->id_utilisateur,
        'message' => 'Ceci est une notification de test!',
        'lu' => false,
    ]);
}

echo "Notifications created!\n";
