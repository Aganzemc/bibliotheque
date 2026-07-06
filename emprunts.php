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

// --- FONCTIONNALITÉ D'EXPORT PDF ---
if (isset($_GET['export_archive'])) {
    // Récupération des données pour le PDF
    $stmt = $pdo->query("
        SELECT m.nom, m.prenom, m.classe, l.isbn, l.titre, e.date_emprunt, e.date_retour_reelle, e.amende_montant 
        FROM emprunts e
        JOIN livres l ON e.livre_id = l.id
        JOIN membres m ON e.membre_id = m.id
        WHERE e.statut = 'retourne'
        ORDER BY e.date_retour_reelle DESC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Génération d'une page HTML épurée optimisée pour l'impression PDF
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Archive des Emprunts - <?= date('d/m/Y') ?></title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 20px; }
            .header-container { display: flex; align-items: center; justify-content: center; margin-bottom: 5px; gap: 15px; }
            .logo-pdf { height: 50px; width: auto; } /* Ajustez la taille du logo ici */
            h1 { text-align: center; color: #2c3e50; margin: 0; }
            .date-export { text-align: center; font-style: italic; margin-bottom: 20px; color: #555; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            th { background-color: #2c3e50; color: white; padding: 8px; font-weight: bold; font-size: 11px; }
            td { border: 1px solid #ddd; padding: 6px; text-align: left; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            .text-right { text-align: right; }
            .badge-amende { color: #c0392b; font-weight: bold; }
            @media print {
                #btn-print { display: none; }
                @page { size: A4 landscape; margin: 10mm; }
            }
        </style>
    </head>
    <body>
        <div style="text-align: right; margin-bottom: 10px;">
            <button id="btn-print" onclick="window.print();" style="padding: 8px 15px; background-color: #27ae60; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">🖨️ Enregistrer en PDF / Imprimer</button>
        </div>
        
        <div class="header-container">
            <img src="assets/images/image.png" alt="Logo" class="logo-pdf">
            <h1>Archive Générale des Emprunts de Livres</h1>
        </div>
        <div class="date-export">Export effectué le : <?= date('d/m/Y à H:i') ?></div>
        
        <table>
            <thead>
                <tr>
                    <th>Nom & Prénom</th>
                    <th>Classe</th>
                    <th>ISBN</th>
                    <th>Titre du Livre</th>
                    <th>Date Emprunt</th>
                    <th>Date Retour</th>
                    <th>Amende</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7" style="text-align:center;">Aucune archive trouvée.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nom'] . ' ' . $row['prenom']) ?></td>
                            <td><?= htmlspecialchars($row['classe']) ?></td>
                            <td><?= htmlspecialchars($row['isbn']) ?></td>
                            <td><?= htmlspecialchars($row['titre']) ?></td>
                            <td><?= date('d/m/Y', strtotime($row['date_emprunt'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($row['date_retour_reelle'])) ?></td>
                            <td class="text-right">
                                <?= $row['amende_montant'] > 0 ? '<span class="badge-amende">' . number_format($row['amende_montant'], 0, ',', ' ') . ' FC</span>' : 'Aucune' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <script>
            // Déclenche automatiquement la boîte de dialogue d'impression/sauvegarde PDF
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => { window.print(); }, 500);
            });
        </script>
    </body>
    </html>
    <?php
    exit();
}

$params = $pdo->query("SELECT * FROM parametres ORDER BY id DESC LIMIT 1")->fetch();
$dureeEmprunt = $params['duree_emprunt_jours'] ?? 15;
$maxLivresMembre = $params['max_livres_par_membre'] ?? 3;
$amendeParJour = $params['amende_par_jour'] ?? 5000;

// --- SUPPRESSION D'UN ENREGISTREMENT DE L'ARCHIVE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_archive'])) {
    $emprunt_id = $_POST['emprunt_id'];
    try {
        $pdo->prepare("DELETE FROM emprunts WHERE id = ? AND statut = 'retourne'")->execute([$emprunt_id]);
        $success = "Archive supprimée avec succès !";
    } catch (Exception $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Traitement du formulaire d'emprunt (Saisie via ISBN/Identifiant)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['emprunter'])) {
    $membre_id = $_POST['membre_id'];
    $isbn = trim($_POST['isbn']); 
    $date_emprunt = $_POST['date_emprunt'];
    $date_retour_prevue = $_POST['date_retour_prevue'];
    $statut = $_POST['statut'] ?? 'en_cours';
    
    try {
        $pdo->beginTransaction();
        
        $stmtLivre = $pdo->prepare("SELECT id, quantite_disponible FROM livres WHERE isbn = ?");
        $stmtLivre->execute([$isbn]);
        $livreData = $stmtLivre->fetch();
        
        if (!$livreData) {
            throw new Exception("Aucun livre trouvé avec l'ISBN / Identifiant : " . htmlspecialchars($isbn));
        }
        
        $livre_id = $livreData['id'];
        
        $nbEmprunts = $pdo->prepare("SELECT COUNT(*) FROM emprunts WHERE membre_id = ? AND statut = 'en_cours'");
        $nbEmprunts->execute([$membre_id]);
        if ($nbEmprunts->fetchColumn() >= $maxLivresMembre) {
            throw new Exception("Ce membre a déjà atteint la limite de $maxLivresMembre livres");
        }
        
        if ($livreData['quantite_disponible'] <= 0) {
            throw new Exception("Ce livre n'est plus disponible en stock");
        }
        
        $pdo->prepare("INSERT INTO emprunts (membre_id, livre_id, date_emprunt, date_retour_prevue, statut) VALUES (?, ?, ?, ?, ?)")
            ->execute([$membre_id, $livre_id, $date_emprunt, $date_retour_prevue, $statut]);
            
        if ($statut === 'en_cours') {
            $pdo->prepare("UPDATE livres SET quantite_disponible = quantite_disponible - 1 WHERE id = ?")->execute([$livre_id]);
        }
        
        $pdo->commit();
        $success = "Emprunt enregistré avec succès !";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Traitement du Retour
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['retourner'])) {
    $emprunt_id = $_POST['emprunt_id'];
    $date_retour = date('Y-m-d');
    
    try {
        $pdo->beginTransaction();
        
        $emprunt = $pdo->prepare("SELECT e.*, l.id as livre_id FROM emprunts e JOIN livres l ON e.livre_id = l.id WHERE e.id = ?");
        $emprunt->execute([$emprunt_id]);
        $emprunt = $emprunt->fetch();
        
        $date_prevue = new DateTime($emprunt['date_retour_prevue']);
        $date_reelle = new DateTime($date_retour);
        $joursRetard = $date_reelle > $date_prevue ? $date_reelle->diff($date_prevue)->days : 0;
        $montantAmende = $joursRetard * $amendeParJour;
        
        $pdo->prepare("UPDATE emprunts SET date_retour_reelle = ?, statut = 'retourne', amende_montant = ? WHERE id = ?")
            ->execute([$date_retour, $montantAmende, $emprunt_id]);
        $pdo->prepare("UPDATE livres SET quantite_disponible = quantite_disponible + 1 WHERE id = ?")->execute([$emprunt['livre_id']]);
        
        if ($montantAmende > 0) {
            $pdo->prepare("INSERT INTO notifications (message, type) VALUES (?, 'amende')")
                ->execute(["Amende de $montantAmende FC pour emprunt #$emprunt_id"]);
        }
        
        $pdo->commit();
        $success = $montantAmende > 0 ? "Retour effectué ! Amende : " . number_format($montantAmende, 0, ',', ' ') . " FC" : "Retour effectué sans amende";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Gestion des Recherches
$membresRecherches = [];
if (isset($_GET['search_membre']) && !empty($_GET['search_membre'])) {
    $search = '%' . $_GET['search_membre'] . '%';
    $stmt = $pdo->prepare("SELECT * FROM membres WHERE deleted_at IS NULL AND (nom ILIKE ? OR prenom ILIKE ? OR classe ILIKE ?) LIMIT 10");
    $stmt->execute([$search, $search, $search]);
    $membresRecherches = $stmt->fetchAll();
}

$livresRecherches = [];
if (isset($_GET['search_livre']) && !empty($_GET['search_livre'])) {
    $search = '%' . $_GET['search_livre'] . '%';
    $stmt = $pdo->prepare("SELECT * FROM livres WHERE (titre ILIKE ? OR auteur ILIKE ? OR isbn ILIKE ?) AND quantite_disponible > 0 LIMIT 10");
    $stmt->execute([$search, $search, $search]);
    $livresRecherches = $stmt->fetchAll();
}

$empruntsEncours = $pdo->query("
    SELECT e.*, l.titre, l.isbn, m.nom, m.prenom, m.classe,
           (CURRENT_DATE - e.date_retour_prevue) as jours_retard
    FROM emprunts e
    JOIN livres l ON e.livre_id = l.id
    JOIN membres m ON e.membre_id = m.id
    WHERE e.statut = 'en_cours'
    ORDER BY e.date_retour_prevue ASC
")->fetchAll();

$empruntsArchives = $pdo->query("
    SELECT e.*, l.titre, l.isbn, m.nom, m.prenom, m.classe
    FROM emprunts e
    JOIN livres l ON e.livre_id = l.id
    JOIN membres m ON e.membre_id = m.id
    WHERE e.statut = 'retourne'
    ORDER BY e.date_retour_reelle DESC
")->fetchAll();

$retards = $pdo->query("
    SELECT e.id, m.nom, m.prenom, l.titre, e.date_retour_prevue
    FROM emprunts e
    JOIN membres m ON e.membre_id = m.id
    JOIN livres l ON e.livre_id = l.id
    WHERE e.statut = 'en_cours' AND e.date_retour_prevue < CURRENT_DATE
")->fetchAll();

foreach ($retards as $retard) {
    $message = "RETARD: {$retard['nom']} doit rendre '{$retard['titre']}'";
    $check = $pdo->prepare("SELECT id FROM notifications WHERE message = ? AND vue = FALSE");
    $check->execute([$message]);
    if (!$check->fetch()) {
        $pdo->prepare("INSERT INTO notifications (message, type) VALUES (?, 'retard')")->execute([$message]);
    }
}

$notifications = $pdo->query("SELECT * FROM notifications WHERE vue = FALSE ORDER BY created_at DESC")->fetchAll();
if (isset($_GET['marquer_vues'])) {
    $pdo->query("UPDATE notifications SET vue = TRUE");
    header('Location: emprunts.php');
    exit();
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content-area">
    <h2>🔄 Gestion des emprunts et retours</h2>

    <?php if(isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">📖 Nouvel emprunt</h6>
                </div>
                <div class="card-body">
                    <h6>Étape 1 : Rechercher le membre par nom</h6>
                    <form method="GET" class="mb-3">
                        <div class="input-group">
                            <input type="text" name="search_membre" class="form-control" placeholder="Entrez le nom de la personne..." value="<?= htmlspecialchars($_GET['search_membre'] ?? '') ?>">
                            <button class="btn btn-outline-primary" type="submit">🔍</button>
                        </div>
                    </form>
                    
                    <?php if(!empty($membresRecherches)): ?>
                        <div class="list-group mb-3 border border-primary p-1 rounded" style="max-height: 200px; overflow-y: auto;">
                            <small class="text-muted px-2">Cliquez sur la personne pour la sélectionner :</small>
                            <?php foreach($membresRecherches as $membre): ?>
                                <a href="emprunts.php?membre_id=<?= $membre['id'] ?>&search_membre=<?= urlencode($_GET['search_membre']) ?>" class="list-group-item list-group-item-action py-1 px-2">
                                    <strong><?= htmlspecialchars($membre['nom'] . ' ' . $membre['prenom']) ?></strong> <small class="text-secondary">(<?= $membre['classe'] ?>)</small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($_GET['membre_id'])): 
                        $stmt = $pdo->prepare("SELECT * FROM membres WHERE id = ? AND deleted_at IS NULL");
                        $stmt->execute([$_GET['membre_id']]);
                        $membreSel = $stmt->fetch();
                        if($membreSel):
                    ?>
                        <div class="alert alert-info py-2 mb-3">
                            <i class="fas fa-user"></i> Personne sélectionnée : <strong><?= htmlspecialchars($membreSel['nom'] . ' ' . $membreSel['prenom']) ?></strong>
                        </div>
                        
                        <h6>Étape 2 : Rechercher le livre (Titre, Auteur ou ISBN)</h6>
                        <form method="GET" class="mb-3">
                            <input type="hidden" name="membre_id" value="<?= $_GET['membre_id'] ?>">
                            <input type="hidden" name="search_membre" value="<?= htmlspecialchars($_GET['search_membre'] ?? '') ?>">
                            <div class="input-group">
                                <input type="text" name="search_livre" class="form-control" placeholder="Titre, auteur ou ISBN..." value="<?= htmlspecialchars($_GET['search_livre'] ?? '') ?>">
                                <button class="btn btn-outline-success" type="submit">🔍</button>
                            </div>
                        </form>
                        
                        <?php if(!empty($livresRecherches)): ?>
                            <div class="list-group mb-3 border border-success p-1 rounded" style="max-height: 150px; overflow-y: auto;">
                                <?php foreach($livresRecherches as $livre): ?>
                                    <a href="emprunts.php?membre_id=<?= $_GET['membre_id'] ?>&search_membre=<?= urlencode($_GET['search_membre'] ?? '') ?>&isbn_sel=<?= urlencode($livre['isbn']) ?>&search_livre=<?= urlencode($_GET['search_livre']) ?>" class="list-group-item list-group-item-action py-1 px-2 text-success">
                                        + <?= htmlspecialchars($livre['titre']) ?> <small>(ISBN: <?= htmlspecialchars($livre['isbn']) ?> | Dispo: <?= $livre['quantite_disponible'] ?>)</small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php 
                        $isbnSel = $_GET['isbn_sel'] ?? '';
                        $date_emprunt_defaut = date('Y-m-d');
                        $date_retour_defaut = date('Y-m-d', strtotime("+$dureeEmprunt days"));
                        ?>
                        <hr>
                        <h6 class="text-primary"><i class="fas fa-edit"></i> Saisie des informations d'emprunt</h6>
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="membre_id" value="<?= $membreSel['id'] ?>">
                            
                            <div class="mb-2">
                                <label class="form-label small fw-bold">ISBN / Identifiant Unique du Livre *</label>
                                <input type="text" name="isbn" class="form-control form-control-sm" required value="<?= htmlspecialchars($isbnSel) ?>" placeholder="Saisissez ou chargez l'ISBN du livre">
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-bold">Date d'emprunt *</label>
                                <input type="date" name="date_emprunt" class="form-control form-control-sm" required value="<?= $date_emprunt_defaut ?>">
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-bold">Date de retour prévue *</label>
                                <input type="date" name="date_retour_prevue" class="form-control form-control-sm" required value="<?= $date_retour_defaut ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Statut *</label>
                                <select name="statut" class="form-select form-select-sm">
                                    <option value="en_cours" selected>En cours</option>
                                    <option value="retourne">Retourné</option>
                                </select>
                            </div>

                            <button type="submit" name="emprunter" class="btn btn-sm btn-primary w-100">
                                <i class="fas fa-check"></i> Enregistrer l'Emprunt
                            </button>
                        </form>
                    <?php endif; endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-7">
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">⏳ Emprunts en cours (<?= count($empruntsEncours) ?>)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 align-middle">
                            <thead class="table-dark">
                                <tr><th>Membre</th><th>Livre (ISBN)</th><th>Retour prévu</th><th>Statut</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($empruntsEncours as $e): ?>
                                    <tr class="<?= $e['jours_retard'] > 0 ? 'table-danger' : '' ?>">
                                        <td><?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?><br><small class="text-muted"><?= htmlspecialchars($e['classe']) ?></small></td>
                                        <td><?= htmlspecialchars($e['titre']) ?><br><small class="text-secondary">ISBN: <?= htmlspecialchars($e['isbn']) ?></small></td>
                                        <td><?= date('d/m/Y', strtotime($e['date_retour_prevue'])) ?></td>
                                        <td><?= $e['jours_retard'] > 0 ? "<span class='badge bg-danger'>RETARD ({$e['jours_retard']}j)</span>" : "<span class='badge bg-success'>En cours</span>" ?></td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <form method="POST" onsubmit="return confirm('Confirmer le retour ?')" class="m-0">
                                                    <input type="hidden" name="emprunt_id" value="<?= $e['id'] ?>">
                                                    <button type="submit" name="retourner" class="btn btn-sm btn-success">Retour</button>
                                                </form>
                                                <a href="impression.php?id=<?= $e['id'] ?>" target="_blank" class="btn btn-sm btn-secondary" title="Imprimer le reçu">🖨️</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(count($empruntsEncours) === 0): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-3">Aucun emprunt en cours.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">📦 Historique des Emprunts Archivés (<?= count($empruntsArchives) ?>)</h6>
                    <div class="d-flex align-items-center gap-2">
                        <img src="assets/images/image.png" alt="Logo" style="height: 24px; width: auto; object-fit: contain;">
                        <a href="emprunts.php?export_archive=1" target="_blank" class="btn btn-sm btn-danger fw-bold">📄 Exporter en PDF</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-striped table-bordered mb-0 align-middle small">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Membre</th>
                                    <th>Livre (ISBN)</th>
                                    <th>Dates (Emprunt / Rendu)</th>
                                    <th>Amende</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($empruntsArchives as $archive): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($archive['nom'] . ' ' . $archive['prenom']) ?></strong>
                                            <br><small class="text-muted"><?= htmlspecialchars($archive['classe']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($archive['titre']) ?><br><small class="text-secondary">ISBN: <?= htmlspecialchars($archive['isbn']) ?></small></td>
                                        <td>
                                            <span class="text-success">🗓️ Du: <?= date('d/m/Y', strtotime($archive['date_emprunt'])) ?></span><br>
                                            <span class="text-primary">📥 Rendu: <?= date('d/m/Y', strtotime($archive['date_retour_reelle'])) ?></span>
                                        </td>
                                        <td>
                                            <?php if($archive['amende_montant'] > 0): ?>
                                                <span class="badge bg-danger"><?= number_format($archive['amende_montant'], 0, ',', ' ') ?> FC</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark">Aucune</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Attention ! Voulez-vous vraiment supprimer cet enregistrement des archives ? Cette action est irréversible.')" class="m-0">
                                                <input type="hidden" name="emprunt_id" value="<?= $archive['id'] ?>">
                                                <button type="submit" name="supprimer_archive" class="btn btn-xs btn-outline-danger py-0 px-1" title="Supprimer définitivement">🗑️ Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(count($empruntsArchives) === 0): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-3">Aucune archive disponible.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
