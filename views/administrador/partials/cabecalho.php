<?php
// Abertura comum das telas novas do administrador (Painel, Sócios e Cadastro dos sócios).
// Antes de incluir, defina: $tituloPagina e $menuAtivo.
session_start();

if(!isset($_SESSION["email"]) || $_SESSION["admin"] !== true){
    header("Location: ../autenticacao/login.php");
    exit;
}

require_once __DIR__ . "/../../../models/admin-dados.php";
require_once __DIR__ . "/../../../core/acessos-site.php";

$tituloPagina = $tituloPagina ?? "Administrador";
$nomeAdmin    = $_SESSION["nome"] ?? "administrador(a)";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ae($tituloPagina) ?> — Cerberus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="painel-layout">

    <?php include __DIR__ . "/menu.php"; ?>

    <div id="sidebarOverlay" class="sidebar-overlay"></div>

    <div class="painel-principal">

        <header class="topbar d-flex align-items-center px-3">
            <button id="btnMenu" type="button" class="btn btn-outline-laranja btn-sm"
                    title="Recolher/expandir menu" aria-controls="sidebar" aria-expanded="true">
                <i class="bi bi-list fs-5"></i>
            </button>
            <span class="navbar-text ms-3 d-none d-sm-inline">Bem-vindo(a), <?= ae($nomeAdmin) ?>!</span>

            <div class="ms-auto">
                <button id="btnTema" type="button" class="btn btn-tema" title="Alternar tema">🌙</button>
            </div>
        </header>

        <main class="painel-conteudo">
