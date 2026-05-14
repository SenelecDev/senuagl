<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11pt;
            color: #1e293b;
            line-height: 1.6;
            margin: 40px 48px;
        }
        h1 {
            text-align: center;
            font-size: 14pt;
            margin-bottom: 28px;
            color: #0f172a;
        }
        .muted {
            color: #64748b;
            font-size: 10pt;
        }
        p {
            margin: 0 0 12px 0;
        }
        .signature {
            margin-top: 36px;
            text-align: right;
        }
        .signature-box {
            margin-top: 16px;
            padding: 20px;
            border: 1px dashed #94a3b8;
            text-align: center;
            color: #64748b;
            font-size: 9pt;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>

    <p>Je soussigné(e), Directeur des Ressources Humaines de SENELEC,</p>
    <p>Atteste que M./Mme <strong>{{ $employeName }}</strong>,</p>
    <p>En service au département <strong>{{ $serviceName }}</strong>,</p>
    <p>
        Bénéficie @if ($article === 'une') d'une @else d'un @endif <strong>{{ mb_strtolower($typeNature, 'UTF-8') }}</strong>
        pour la période du <strong>{{ $dateDebutFr }}</strong> au <strong>{{ $dateFinFr }}</strong>.
    </p>

    <div class="signature">
        <p>Fait à Dakar, le {{ $faitLe }}</p>
        <p><strong>Le Directeur des Ressources Humaines</strong></p>
        <div class="signature-box">
            (Espace réservé à la signature et au cachet)
        </div>
    </div>
</body>
</html>
