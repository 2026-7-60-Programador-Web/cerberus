<?php

session_start();

if(!isset($_SESSION["email"]) || $_SESSION["admin"] !== true){
    header("Location: ../autenticacao/login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel — Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="painel-layout">

    <?php $menuAtivo = "painel"; include __DIR__ . "/partials/menu.php"; ?>

    <!-- fundo escuro que aparece atrás do menu no celular -->
    <div id="sidebarOverlay" class="sidebar-overlay"></div>

    <!-- ÁREA PRINCIPAL -->
    <div class="painel-principal">

        <header class="topbar d-flex align-items-center px-3">
            <button id="btnMenu" type="button" class="btn btn-outline-laranja btn-sm"
                    title="Recolher/expandir menu" aria-controls="sidebar" aria-expanded="true">
                <i class="bi bi-list fs-5"></i>
            </button>
            <span class="navbar-text ms-3 d-none d-sm-inline">Bem-vindo(a), administrador(a)!</span>

            <div class="ms-auto">
                <button id="btnTema" type="button" class="btn btn-tema" title="Alternar tema">🌙</button>
            </div>
        </header>

        <main class="painel-conteudo">
            <div class="card-painel">
                <h1 class="h4 mb-3">Painel</h1>
                <p class="mb-0">Esta página só deve aparecer para administradores logados.</p>
            </div>
        </main>

    </div>
</div>

<script src="../../assets/js/script.js"></script>
</body>
</html>