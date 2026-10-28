<?php

namespace App\Mail;

use App\Models\SuiviDossier;
use App\Models\Utilisateur;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DossierRejete extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SuiviDossier $dossier,
        public Utilisateur $destinataire,
    ) {}

    public function build(): self
    {
        $ref = $this->dossier->ref_dossier ?: ($this->dossier->ref_be ?: '—');

        return $this
            ->subject('Rejet de votre dossier n° '.$ref.' — '.now()->format('d/m/Y H:i'))
            ->view('emails.dossier-rejete');
    }
}
