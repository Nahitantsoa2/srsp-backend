edzé<?php

use App\Models\Message;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

try {
    $msg = Message::create([
        'contenu' => 'test message',
        'expediteur' => 'utilisateur',
        'id_conversation' => 1,
    ]);
    echo 'Message created: '.$msg->id_message;
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage();
}
