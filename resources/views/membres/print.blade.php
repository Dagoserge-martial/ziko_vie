<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport des Membres</title>
    <style>
        @media print {
            .no-print {
                display: none;
            }
            .section {
                page-break-after: auto;
                margin-bottom: 40px;
            }
            /* Forcer l'impression des couleurs de fond */
            * {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header-title {
            background-color: #6366f1;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 18px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .header-title .heart {
            color: #ff0000;
            font-size: 20px;
        }
        .header-subtitle {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-top: 10px;
        }
        .section {
            margin-bottom: 20px;
        }
        .print-button {
            margin-bottom: 20px;
            text-align: center;
        }
        .print-button button {
            padding: 10px 20px;
            background-color: #4a5568;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .print-button button:hover {
            background-color: #2d3748;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .section-header {
            background-color: #ffe4b5;
            font-weight: bold;
            color: #0066cc;
            padding: 8px;
            text-align: center;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .section-total {
            background-color: #b3d9ff;
            font-weight: bold;
            padding: 8px;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .grand-total {
            background-color: #a78bfa;
            font-weight: bold;
            padding: 10px;
            font-size: 14px;
            color: white;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .row-number {
            width: 50px;
            text-align: center;
        }
        .amount-column {
            text-align: right;
        }
        .contact-column {
            max-width: 150px;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="print-button no-print">
        <button onclick="window.print()">Imprimer</button>
    </div>

    <div class="header">
        <div class="header-title">
            <span class="heart">❤</span>
            {{ $titre }}
            <span class="heart">❤</span>
        </div>
        @if(isset($filters['localite']))
            <div class="header-subtitle" style="text-align: center; font-size: 14px; color: #666; margin-top: 10px;">
                Localité : {{ strtoupper($filters['localite']->libelle) }}
            </div>
        @endif
    </div>

    @if($membresParLocalite->isEmpty())
        <p style="text-align: center; color: #666; padding: 20px;">
            Aucun membre trouvé
        </p>
    @else
        @php
            $numeroGlobal = 1;
        @endphp

        @if($localiteSelectionnee)
            {{-- Si une localité est sélectionnée, afficher directement les membres sans sections --}}
            <table>
                <thead>
                    <tr>
                        <th class="row-number">N°</th>
                        <th>NOM & PRENOMS</th>
                        <th class="contact-column">CONTACT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($membresParLocalite->first()['membres'] as $membre)
                        <tr>
                            <td class="row-number">{{ $numeroGlobal++ }}</td>
                            <td>{{ strtoupper($membre->nom) }} {{ ucfirst($membre->prenom) }}</td>
                            <td class="contact-column">{{ $membre->telephone ?? '-' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                {{-- Si aucune localité n'est sélectionnée, afficher par sections de localité --}}
                @foreach($membresParLocalite as $localiteLibelle => $dataLocalite)
                    <div class="section">
                        <table>
                            <thead>
                                <tr>
                                    <th class="row-number">N°</th>
                                    <th>NOM & PRENOMS</th>
                                    <th class="contact-column">CONTACT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="3" class="section-header">
                                        {{ strtoupper($localiteLibelle) }}
                                    </td>
                                </tr>
                                
                                @foreach($dataLocalite['membres'] as $membre)
                                    <tr>
                                        <td class="row-number">{{ $numeroGlobal++ }}</td>
                                        <td>{{ strtoupper($membre->nom) }} {{ ucfirst($membre->prenom) }}</td>
                                        <td class="contact-column">{{ $membre->telephone ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            @endif
    @endif

    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
        <p>Généré le {{ date('d/m/Y à H:i') }}</p>
    </div>
</body>
</html>
