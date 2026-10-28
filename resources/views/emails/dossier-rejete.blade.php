<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dossier rejeté</title>
</head>
<body style="margin:0;padding:0;background:#eef1f5;font-family:Arial,Helvetica,sans-serif;color:#1f2937;-webkit-font-smoothing:antialiased;">
    <div style="max-width:600px;margin:0 auto;padding:28px 16px;">

        <div style="background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 8px 24px rgba(15,23,42,0.06);">

            {{-- En-tête --}}
            <div style="background:linear-gradient(135deg,#dc2626,#b91c1c);padding:26px 28px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td style="width:44px;vertical-align:middle;">
                            <div style="width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,0.18);text-align:center;line-height:40px;font-size:20px;">⚠️</div>
                        </td>
                        <td style="vertical-align:middle;padding-left:14px;">
                            <div style="color:#ffffff;font-size:19px;font-weight:bold;line-height:1.2;">Votre dossier a été rejeté</div>
                            <div style="color:rgba(255,255,255,0.85);font-size:12.5px;margin-top:3px;">Espace Services &middot; Suivi des dossiers</div>
                        </td>
                    </tr>
                </table>
            </div>

            {{-- Corps --}}
            <div style="padding:28px;font-size:14px;line-height:1.6;color:#334155;">

                <p style="margin:0 0 14px;">Bonjour <strong style="color:#0f172a;">{{ $destinataire->prenom ?: '' }} {{ $destinataire->nom ?: '' }}</strong>,</p>

                <p style="margin:0 0 18px;">
                    Nous vous informons que votre dossier a été <strong style="color:#dc2626;">rejeté</strong>.
                    Veuillez prendre connaissance des informations ci-dessous.
                </p>

                {{-- Bandeau référence dossier --}}
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 18px;margin-bottom:20px;">
                    <div style="font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#b91c1c;font-weight:bold;">Dossier concerné</div>
                    <div style="font-size:18px;font-weight:bold;color:#991b1b;margin-top:2px;">
                        N° {{ $dossier->ref_dossier ?: ($dossier->ref_be ?: '—') }}
                        @if ($dossier->nature_dossier || $dossier->type_dossier)
                            <span style="font-size:13px;font-weight:normal;color:#b91c1c;">({{ $dossier->nature_dossier ?: $dossier->type_dossier }})</span>
                        @endif
                    </div>
                </div>

                {{-- Tableau détails --}}
                <table role="presentation" style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:18px;">
                    @if (trim((string) $dossier->motif_rejet) !== '')
                        <tr>
                            <td style="padding:10px 12px;background:#f8fafc;border:1px solid #eef2f7;font-weight:bold;color:#475569;width:170px;">Motif du rejet</td>
                            <td style="padding:10px 12px;border:1px solid #eef2f7;color:#1f2937;">{{ $dossier->motif_rejet }}</td>
                        </tr>
                    @endif
                    @if (trim((string) $dossier->detail_motif_rejet) !== '' && ! in_array(trim($dossier->detail_motif_rejet), ['-----', '-', 'N/A'], true))
                        <tr>
                            <td style="padding:10px 12px;background:#f8fafc;border:1px solid #eef2f7;font-weight:bold;color:#475569;">Détail</td>
                            <td style="padding:10px 12px;border:1px solid #eef2f7;color:#1f2937;">{{ $dossier->detail_motif_rejet }}</td>
                        </tr>
                    @endif
                    @if (trim((string) ($dossier->nature_dossier ?: $dossier->type_dossier)) !== '')
                        <tr>
                            <td style="padding:10px 12px;background:#f8fafc;border:1px solid #eef2f7;font-weight:bold;color:#475569;">Type de dossier</td>
                            <td style="padding:10px 12px;border:1px solid #eef2f7;color:#1f2937;">{{ $dossier->nature_dossier ?: $dossier->type_dossier }}</td>
                        </tr>
                    @endif
                    @if (trim((string) $dossier->date_reception) !== '')
                        <tr>
                            <td style="padding:10px 12px;background:#f8fafc;border:1px solid #eef2f7;font-weight:bold;color:#475569;">Date de réception</td>
                            <td style="padding:10px 12px;border:1px solid #eef2f7;color:#1f2937;">{{ $dossier->date_reception }}</td>
                        </tr>
                    @endif
                </table>

                {{-- Encadré action --}}
                <div style="border-left:4px solid #dc2626;background:#f8fafc;border-radius:8px;padding:12px 16px;">
                    <p style="margin:0;color:#475569;font-size:13.5px;">
                        Merci de prendre les dispositions nécessaires afin de régulariser votre situation.
                    </p>
                </div>
            </div>

            {{-- Pied --}}
            <div style="padding:16px 28px;background:#f8fafc;border-top:1px solid #e5e7eb;">
                <p style="margin:0;color:#94a3b8;font-size:11.5px;line-height:1.5;">
                    SRSP — message automatique, merci de ne pas répondre à cet email.
                </p>
            </div>
        </div>

        <p style="margin:16px 0 0;text-align:center;color:#94a3b8;font-size:11px;">
            &copy; {{ date('Y') }} Espace Services
        </p>
    </div>
</body>
</html>
