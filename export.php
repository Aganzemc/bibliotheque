<?php
// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$type = $_GET['type'] ?? '';

// ==================== CODES EXPORTS CSV (INCHANGÉS) ====================
if ($type == 'livres_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=livres_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Titre', 'Auteur', 'ISBN', 'Étagère', 'Date édition', 'Quantité totale', 'Quantité disponible']);
    
    $livres = $pdo->query("SELECT * FROM livres ORDER BY titre")->fetchAll();
    foreach ($livres as $livre) {
        fputcsv($output, [$livre['titre'], $livre['auteur'], $livre['isbn'], $livre['numero_etagere'], $livre['date_edition'], $livre['quantite_totale'], $livre['quantite_disponible']]);
    }
    fclose($output);
    exit();
}

if ($type == 'membres_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=membres_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Nom', 'Prénom', 'Classe', 'Type', 'Téléphone', 'Email']);
    
    $membres = $pdo->query("SELECT * FROM membres ORDER BY nom")->fetchAll();
    foreach ($membres as $membre) {
        fputcsv($output, [$membre['nom'], $membre['prenom'], $membre['classe'], $membre['type'], $membre['telephone'], $membre['email']]);
    }
    fclose($output);
    exit();
}

if ($type == 'emprunts_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=emprunts_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Membre', 'Livre', 'Date emprunt', 'Date retour prévue', 'Date retour réelle', 'Statut', 'Amende']);
    
    $emprunts = $pdo->query("
        SELECT m.nom, m.prenom, l.titre, e.date_emprunt, e.date_retour_prevue, e.date_retour_reelle, e.statut, e.amende_montant
        FROM emprunts e
        JOIN membres m ON e.membre_id = m.id
        JOIN livres l ON e.livre_id = l.id
        ORDER BY e.date_emprunt DESC
    ")->fetchAll();
    foreach ($emprunts as $e) {
        fputcsv($output, [$e['nom'] . ' ' . $e['prenom'], $e['titre'], $e['date_emprunt'], $e['date_retour_prevue'], $e['date_retour_reelle'], $e['statut'], $e['amende_montant']]);
    }
    fclose($output);
    exit();
}

// ==================== VUES DES TABLEAUX PDF SÉPARÉS ====================
if (in_array($type, ['livres_pdf', 'membres_pdf', 'emprunts_pdf'])) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Rapport PDF - Congo Peace School</title>
        <style>
            body {
                 font-family: 'Times New Roman', Times, serif;
                 font-size: 12px; margin: 20px; 
                 color: #333; 
                }
            .header-container {
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 30px;
                border-bottom: 3px double #000; 
                padding-bottom: 15px;
            }
            .header-logo {
                max-height: 80px; /* Ajuste la hauteur maximale du logo */
                width: auto;
                margin-right: 20px; /* Espace entre le logo et le texte */
            }
            .header-report { 
                text-align: left;
            }
            .header-report h1 { 
                margin: 0; 
                font-size: 20px; 
                text-transform: uppercase; 
                letter-spacing: 1px; 
                line-height: 1.2;
            }
            .header-report p { 
                margin: 5px 0 0 0;
                font-size: 14px; 
                font-style: italic; 
                color: #555; }
            .meta-info {
                 display: flex; 
                 justify-content: space-between; 
                 margin-bottom: 15px; 
                 font-weight: bold; 
                }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-top: 10px; 
            }
            th, td { 
                border: 1px solid #999; 
                padding: 8px; 
                text-align: left; 
            }
            th { 
                background-color: #f2f2f2; 
                font-weight: bold; 
                text-transform: uppercase; 
                font-size: 11px; 
            }
            tr:nth-child(even) { 
                background-color: #fafafa; 
            }
            .btn-print-floating { 
                display: block; 
                width: 180px; 
                margin: 10px auto; 
                padding: 10px; 
                background: #dc3545; 
                color: #fff; 
                text-align: center; 
                font-weight: bold; 
                text-decoration: none; 
                border-radius: 4px; 
            }
            @media print {
                .btn-print-floating { 
                    display: none; 
                }
                body { 
                    margin: 0; 
                }
            }
        </style>
    </head>
    <body>
        <a href="#" class="btn-print-floating" onclick="window.print(); return false;">💾 Sauvegarder en PDF</a>
        
        <div class="header-container">
            <img src="assets/images/image.png" alt="Logo CPS" class="header-logo">
            <div class="header-report">
                <center><h1>Bibliothèque</h1></center>
                <h1>Congo Peace School</h1>
                <?php if($type == 'livres_pdf'): ?><p>Rapport Général de l'Inventaire des Livres</p><?php endif; ?>
                <?php if($type == 'membres_pdf'): ?><p>Liste Officielle des Membres / Élèves</p><?php endif; ?>
                <?php if($type == 'emprunts_pdf'): ?><p>Historique Général des Mouvements d'Emprunts</p><?php endif; ?>
            </div>
        </div>

        <div class="meta-info">
            <span>Date d'édition : <?= date('d/m/Y H:i') ?></span>
            <span>Bibliothèque CPS</span>
        </div>

        <table>
            <?php if ($type == 'livres_pdf'): ?>
                <thead>
                    <tr>
                        <th>Titre</th><th>Auteur</th><th>ISBN</th><th>Étagère</th><th>Édition</th><th>Qté Totale</th><th>Qté Dispo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $livres = $pdo->query("SELECT * FROM livres ORDER BY titre")->fetchAll();
                    foreach ($livres as $l): ?>
                        <tr>
                            <td><?= htmlspecialchars($l['titre']) ?></td>
                            <td><?= htmlspecialchars($l['auteur']) ?></td>
                            <td><?= htmlspecialchars($l['isbn']) ?></td>
                            <td><?= htmlspecialchars($l['numero_etagere']) ?></td>
                            <td><?= htmlspecialchars($l['date_edition']) ?></td>
                            <td><?= $l['quantite_totale'] ?></td>
                            <td><strong><?= $l['quantite_disponible'] ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            <?php elseif ($type == 'membres_pdf'): ?>
                <thead>
                    <tr>
                        <th>Nom & Prénom</th><th>Classe</th><th>Catégorie</th><th>Téléphone</th><th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $membres = $pdo->query("SELECT * FROM membres ORDER BY nom")->fetchAll();
                    foreach ($membres as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars(($m['nom'] ?? '') . ' ' . ($m['prenom'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($m['classe'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['type'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['telephone'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['email'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            <?php elseif ($type == 'emprunts_pdf'): ?>
                <thead>
                    <tr>
                        <th>Membre / Élève</th><th>Ouvrage Emprunté</th><th>Date Emprunt</th><th>Retour Prévu</th><th>Statut</th><th>Amende</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $emprunts = $pdo->query("
                        SELECT m.nom, m.prenom, m.classe, l.titre, e.date_emprunt, e.date_retour_prevue, e.statut, e.amende_montant
                        FROM emprunts e
                        JOIN membres m ON e.membre_id = m.id
                        JOIN livres l ON e.livre_id = l.id
                        ORDER BY e.date_emprunt DESC
                    ")->fetchAll();
                    foreach ($emprunts as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?> <small>(<?= htmlspecialchars($e['classe']) ?>)</small></td>
                            <td><?= htmlspecialchars($e['titre']) ?></td>
                            <td><?= date('d/m/Y', strtotime($e['date_emprunt'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($e['date_retour_prevue'])) ?></td>
                            <td><?= $e['statut'] == 'en_cours' ? 'En cours' : 'Retourné' ?></td>
                            <td><?= $e['amende_montant'] > 0 ? number_format($e['amende_montant'], 0, ',', ' ') . ' FC' : '0 FC' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            <?php endif; ?>
        </table>

        <script>
            window.onload = function() { window.print(); }
        </script>
    </body>
    </html>
    <?php
    exit();
}

// ==================== INTERFACE DE LA PAGE D'EXPORT ====================
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<h2>Export et Rapports des données</h2>

<div class="row mt-4">
    <div class="col-md-4 mb-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <i class="fas fa-book fa-3x text-primary mb-3"></i>
                <h5>Livres</h5>
                <p class="text-muted">Générer la liste complète des livres</p>
                <div class="d-grid gap-2">
                    <a href="export.php?type=livres_csv" class="btn btn-outline-primary">
                        <i class="fas fa-file-csv"></i> Exporter en CSV
                    </a>
                    <a href="export.php?type=livres_pdf" target="_blank" class="btn btn-primary">
                        <i class="fas fa-file-pdf"></i> Imprimer / Tableau PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <i class="fas fa-users fa-3x text-success mb-3"></i>
                <h5>Membres</h5>
                <p class="text-muted">Générer le répertoire des élèves et membres</p>
                <div class="d-grid gap-2">
                    <a href="export.php?type=membres_csv" class="btn btn-outline-success">
                        <i class="fas fa-file-csv"></i> Exporter en CSV
                    </a>
                    <a href="export.php?type=membres_pdf" target="_blank" class="btn btn-success">
                        <i class="fas fa-file-pdf"></i> Imprimer / Tableau PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <i class="fas fa-exchange-alt fa-3x text-warning mb-3"></i>
                <h5>Emprunts</h5>
                <p class="text-muted">Historique complet des transactions</p>
                <div class="d-grid gap-2">
                    <a href="export.php?type=emprunts_csv" class="btn btn-outline-warning">
                        <i class="fas fa-file-csv"></i> Exporter en CSV
                    </a>
                    <a href="export.php?type=emprunts_pdf" target="_blank" class="btn btn-warning">
                        <i class="fas fa-file-pdf"></i> Imprimer / Tableau PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info mt-3">
    <i class="fas fa-info-circle"></i> <strong>Astuce PDF :</strong> Lorsque la fenêtre d'impression apparaît, choisissez <strong>"Enregistrer au format PDF"</strong> dans la liste des imprimantes de votre ordinateur pour sauvegarder le tableau directement sur votre disque dur.
</div>
