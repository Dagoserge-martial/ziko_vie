<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport des Dépenses Médicales</title>
    <style>
        @media print {
            .no-print {
                display: none;
            }
            .month-section {
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
            background-color: #dc2626;
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
        .month-section {
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
            background-color: #fca5a5;
            font-weight: bold;
            padding: 10px;
            font-size: 14px;
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
        .description-column {
            max-width: 300px;
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
    </div>

    @php
        $totalGeneral = 0;
    @endphp

    @foreach($tableauxParMois as $tableau)
        @php
            $totalMois = $tableau['montantTotal'];
            $totalGeneral += $totalMois;
        @endphp
        <div class="month-section">
            <div class="header-subtitle" style="text-align: center; font-size: 16px; font-weight: bold; color: #333; margin: 20px 0 10px 0;">
                {{ $tableau['sousTitre'] }} - {{ number_format($totalMois, 0, ',', ' ') }} Fcfa
            </div>

            @if($tableau['depenses']->isEmpty())
                <p style="text-align: center; color: #666; padding: 20px;">
                    Aucune dépense médicale pour ce mois
                </p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th class="row-number">N°</th>
                            <th>NOM & PRENOMS</th>
                            <th class="description-column">DESCRIPTION</th>
                            <th class="amount-column">MONTANT TOTAL (Fcfa)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $numeroGlobal = 1;
                            $localiteSelectionnee = isset($filters['localite']);
                        @endphp
                        
                        @if($localiteSelectionnee)
                            {{-- Si une localité est sélectionnée, afficher directement les dépenses sans sections --}}
                            @foreach($tableau['depenses'] as $depense)
                                <tr>
                                    <td class="row-number">{{ $numeroGlobal++ }}</td>
                                    <td>{{ strtoupper($depense->membre->nom) }} {{ ucfirst($depense->membre->prenom) }}</td>
                                    <td class="description-column">{{ $depense->description }}</td>
                                    <td class="amount-column">{{ number_format($depense->montant_total ?? $depense->montant, 0, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                        @else
                            {{-- Si aucune localité n'est sélectionnée, afficher par sections de localité --}}
                            @foreach($tableau['depensesParLocalite'] as $localiteLibelle => $depensesLocalite)
                                @php
                                    $totalLocalite = $depensesLocalite->sum(function ($depense) {
                                        return $depense->montant_total ?? $depense->montant;
                                    });
                                @endphp
                                
                                <tr>
                                    <td colspan="4" class="section-header">
                                        {{ strtoupper($localiteLibelle) }}
                                    </td>
                                </tr>
                                
                                @foreach($depensesLocalite as $depense)
                                    <tr>
                                        <td class="row-number">{{ $numeroGlobal++ }}</td>
                                        <td>{{ strtoupper($depense->membre->nom) }} {{ ucfirst($depense->membre->prenom) }}</td>
                                        <td class="description-column">{{ $depense->description }}</td>
                                        <td class="amount-column">{{ number_format($depense->montant_total ?? $depense->montant, 0, ',', ' ') }}</td>
                                    </tr>
                                @endforeach
                                
                                <tr>
                                    <td colspan="3" class="section-total">
                                        TOTAL {{ strtoupper($localiteLibelle) }}
                                    </td>
                                    <td class="section-total amount-column">
                                        {{ number_format($totalLocalite, 0, ',', ' ') }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach

    <!-- Total général de tous les tableaux -->
    @if(count($tableauxParMois) > 1)
        <div style="margin-top: 40px; border-top: 2px solid #333; padding-top: 20px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tbody>
                    <tr>
                        <td colspan="3" class="grand-total" style="background-color: #fca5a5; font-weight: bold; padding: 15px; font-size: 16px; border: 1px solid #ddd; print-color-adjust: exact; -webkit-print-color-adjust: exact; color-adjust: exact;">
                            TOTAL GÉNÉRAL
                        </td>
                        <td class="grand-total amount-column" style="background-color: #fca5a5; font-weight: bold; padding: 15px; font-size: 16px; text-align: right; border: 1px solid #ddd; print-color-adjust: exact; -webkit-print-color-adjust: exact; color-adjust: exact;">
                            {{ number_format($totalGeneral, 0, ',', ' ') }} XOF
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
        <p>Généré le {{ date('d/m/Y à H:i') }}</p>
    </div>
</body>
</html>
