<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

// Vérification de sécurité
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Récupération de l'ID de l'emprunt transmis dans l'URL
$emprunt_id = $_GET['id'] ?? null;

if (!$emprunt_id) {
    die("ID d'emprunt manquant.");
}

// Requête pour récupérer toutes les infos nécessaires (Élève, Livre, Dates)
$stmt = $pdo->prepare("
    SELECT e.*, l.titre, m.nom, m.prenom, m.classe
    FROM emprunts e
    JOIN livres l ON e.livre_id = l.id
    JOIN membres m ON e.membre_id = m.id
    WHERE e.id = ?
");
$stmt->execute([$emprunt_id]);
$e = $stmt->fetch();

if (!$e) {
    die("Emprunt introuvable.");
}

// Récupération du paramètre d'amende pour affichage sur le ticket
$params = $pdo->query("SELECT amende_par_jour FROM parametres ORDER BY id DESC LIMIT 1")->fetch();
$amendeParJour = $params['amende_par_jour'] ?? 5000;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu d'Emprunt #<?= $e['id'] ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background-color: #fff;
            color: #000;
            margin: 0;
            padding: 20px;
        }
        .ticket-container {
            max-width: 600px;
            margin: 0 auto;
            border: 1px dashed #000;
            padding: 20px;
        }
        
        /* Modification de l'en-tête pour aligner le logo à gauche et le texte à côté */
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 2px dashed #000;
            padding-bottom: 15px;
        }
        .header-text {
            flex-grow: 1;
            text-align: center;
            padding-right: 70px; /* Compense la largeur du logo pour centrer parfaitement le texte */
        }
        
        /* Ajustement du style du logo */
        .school-logo {
            max-width: 70px;
            height: auto;
            margin-right: 15px;
        }
        
        .school-name {
            font-size: 1.5rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .ticket-title {
            font-size: 1.1rem;
            margin-top: 5px;
            font-style: italic;
        }
        .info-row {
            margin-bottom: 15px;
            font-size: 1.1rem;
            line-height: 1.4;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 220px;
        }
        .date-box {
            border: 1px solid #000;
            padding: 10px;
            margin-top: 20px;
            background-color: #f9f9f9;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
            margin-bottom: 30px;
        }
        .signature-box {
            text-align: center;
            width: 45%;
            border-top: 1px solid #000;
            padding-top: 8px;
            font-size: 0.9rem;
        }
        .footer {
            text-align: center;
            font-size: 0.85rem;
            border-top: 1px dashed #000;
            padding-top: 15px;
            margin-top: 30px;
        }
        /* Style pour le bouton d'action à l'écran */
        .no-print-btn {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 10px;
            background-color: #007bff;
            color: #fff;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            font-family: Arial, sans-serif;
        }
        /* Règles d'impression : on cache le bouton */
        @media print {
            .no-print-btn {
                display: none;
            }
            body {
                padding: 0;
            }
            .ticket-container {
                border: none;
            }
        }
    </style>
</head>
<body>

    <a href="#" class="no-print-btn" onclick="window.print(); return false;">🖨️ Lancer l'impression</a>

    <div class="ticket-container">
        <div class="header">
            <img src="assets/images/image.png" alt="Logo Congo Peace School" class="school-logo">
            
            <div class="header-text">
                <div class="school-name">BIBLIOTHEQUE</div>
                <div class="school-name">CONGO PEACE SCHOOL</div>
                <div class="ticket-title">Reçu Officiel de Prêt de Livre</div>
            </div>
        </div>

        <div class="info-row">
            <span class="label">Date d'édition :</span> 
            <?= date('d/m/Y H:i') ?>
        </div>
        
        <div class="info-row">
            <span class="label">Nom du Bénéficiaire :</span> 
            <strong><?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?></strong>
        </div>

        <div class="info-row">
            <span class="label">Classe / Section :</span> 
            <?= htmlspecialchars($e['classe']) ?>
        </div>

        <div class="info-row">
            <span class="label">Livre Emprunté :</span> 
            "<?= htmlspecialchars($e['titre']) ?>"
        </div>

        <div class="info-row">
            <span class="label">Date d'Emprunt :</span> 
            <?= date('d/m/Y', strtotime($e['date_emprunt'])) ?>
        </div>

        <div class="info-row date-box">
            <span class="label">Date de Retour Prévue :</span> 
            <strong><?= date('d/m/Y', strtotime($e['date_retour_prevue'])) ?></strong>
        </div>

        <div class="signatures">
            <div class="signature-box">Signature de l'Élève<br><small>(Précédée de "Lu et approuvé")</small></div>
            <div class="signature-box">Signature Bibliothèque</div>
        </div>

        <div class="footer">
            Prenez soin de ce patrimoine. CPS vous remercie.<br>
            Tout retard engendre une amende réglementaire.
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>