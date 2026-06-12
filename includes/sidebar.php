<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside id="appSidebar" class="app-sidebar bg-light p-3">
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
</aside>
<main class="app-content p-4">
