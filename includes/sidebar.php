<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="col-md-2 bg-light vh-100 p-3" style="position: fixed; top: 56px; left: 0; overflow-y: auto;">
    <ul class="nav flex-column">
        <li class="nav-item mb-2">
            <a href="dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="fas fa-tachometer-alt"></i> Tableau de bord
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="livres.php" class="nav-link <?= $current_page == 'livres.php' ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="fas fa-book"></i> Livres
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="categories.php" class="nav-link <?= $current_page == 'categories.php' ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="fas fa-tags"></i> Catégories
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="membres.php" class="nav-link <?= $current_page == 'membres.php' ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="fas fa-users"></i> Membres
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="emprunts.php" class="nav-link <?= $current_page == 'emprunts.php' ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="fas fa-exchange-alt"></i> Emprunts/Retours
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="statistiques.php" class="nav-link <?= $current_page == 'statistiques.php' ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="fas fa-chart-line"></i> Statistiques
            </a>
        </li>
        <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
        <li class="nav-item mb-2">
            <a href="utilisateurs.php" class="nav-link <?= $current_page == 'utilisateurs.php' ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="fas fa-user-shield"></i> Utilisateurs
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item mb-2">
            <a href="export.php" class="nav-link <?= $current_page == 'export.php' ? 'active bg-primary text-white' : 'text-dark' ?>">
                <i class="fas fa-file-export"></i> Export
            </a>
        </li>
    </ul>
</div>
<div class="col-md-10 offset-md-2 p-4" style="margin-top: 56px;">