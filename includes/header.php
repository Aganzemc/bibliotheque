<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliotheque Congo Peace School</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <button class="navbar-toggler app-menu-toggle d-md-none me-2" type="button" aria-label="Ouvrir le menu" aria-expanded="false">
            <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-book"></i> Bibliotheque CPS
        </a>
        <div class="d-flex align-items-center ms-auto navbar-user-area">
            <span class="text-white me-3 app-user-label">
                <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?>
                (<?= htmlspecialchars($_SESSION['user_role'] ?? '') ?>)
            </span>
            <a href="logout.php" class="btn btn-danger btn-sm">
                <i class="fas fa-sign-out-alt"></i> Deconnexion
            </a>
        </div>
    </div>
</nav>

<div class="app-sidebar-backdrop d-md-none"></div>

<div class="container-fluid">
    <div class="row">
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.querySelector('.app-menu-toggle');
    const backdrop = document.querySelector('.app-sidebar-backdrop');
    const links = document.querySelectorAll('.app-sidebar .nav-link');

    function closeSidebar() {
        document.body.classList.remove('sidebar-open');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            const isOpen = document.body.classList.toggle('sidebar-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }

    links.forEach(function (link) {
        link.addEventListener('click', closeSidebar);
    });
});
</script>
