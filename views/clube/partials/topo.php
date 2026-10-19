<?php
// Início do layout do painel do clube (head + menu lateral + barra superior).
// Antes de incluir, defina: $tituloPagina e $menuAtivo ("painel", "associados" ou "novo").

$tituloPagina = $tituloPagina ?? 'Painel';
$menuAtivo    = $menuAtivo ?? '';

$itensMenu = [
    ['chave' => 'painel',     'href' => 'painel.php',                  'titulo' => 'Painel',          'icone' => 'bi-speedometer2'],
    ['chave' => 'associados', 'href' => 'associados.php',              'titulo' => 'Associados',      'icone' => 'bi-people'],
    ['chave' => 'novo',       'href' => 'associado-form.php',          'titulo' => 'Novo associado',  'icone' => 'bi-person-plus'],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clEsc($tituloPagina); ?> — Cerberus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
    <link href="../../assets/css/clube.css" rel="stylesheet">
</head>
<body>

<div class="painel-layout">

    <aside id="sidebar" class="sidebar" aria-label="Menu principal">
        <div class="sidebar-marca">
            <img src="../../assets/img/logo-cerberus-tres-lobos.svg" alt="Logo Cerberus" class="logo-sidebar">
            <span class="sidebar-texto sidebar-titulo">Cerberus</span>
        </div>

        <nav class="sidebar-nav">
            <ul class="sidebar-menu">
                <?php foreach ($itensMenu as $item): ?>
                    <li>
                        <a href="<?= $item['href']; ?>"
                           class="sidebar-link <?= $menuAtivo === $item['chave'] ? 'ativo' : ''; ?>"
                           title="<?= clEsc($item['titulo']); ?>"
                           <?= $menuAtivo === $item['chave'] ? 'aria-current="page"' : ''; ?>>
                            <i class="bi <?= $item['icone']; ?>"></i>
                            <span class="sidebar-texto"><?= clEsc($item['titulo']); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="sidebar-rodape">
            <a href="../../controllers/logout.php" class="sidebar-link" title="Sair">
                <i class="bi bi-box-arrow-right"></i>
                <span class="sidebar-texto">Sair</span>
            </a>
        </div>
    </aside>

    <div id="sidebarOverlay" class="sidebar-overlay"></div>

    <div class="painel-principal">

        <header class="topbar d-flex align-items-center px-3">
            <button id="btnMenu" type="button" class="btn btn-outline-laranja btn-sm"
                    title="Recolher/expandir menu" aria-controls="sidebar" aria-expanded="true">
                <i class="bi bi-list fs-5"></i>
            </button>
            <span class="navbar-text ms-3 text-truncate">
                <strong><?= clEsc($_SESSION['clube_nome'] ?? ''); ?></strong>
                <span class="d-none d-md-inline"> · <?= clEsc($_SESSION['nome'] ?? ''); ?></span>
            </span>

            <div class="ms-auto ps-2">
                <button id="btnTema" type="button" class="btn btn-tema" title="Alternar tema">🌙</button>
            </div>
        </header>

        <main class="painel-conteudo">
            <?php if (!empty($mensagem)): ?>
                <div class="alert alert-<?= clEsc($mensagem['tipo']); ?> alert-dismissible fade show" role="alert">
                    <?php foreach ($mensagem['textos'] as $texto): ?>
                        <div><?= clEsc($texto); ?></div>
                    <?php endforeach; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endif; ?>
