<?php

session_start();

if(!isset($_SESSION["email"]) || $_SESSION["admin"] !== true){
    header("Location: ../autenticacao/login.php");
    exit;
}

require_once __DIR__ . "/../../models/portaria-dados.php";

/* ---------- abas ---------- */
$abas = [
    "painel"     => ["titulo" => "Painel",     "icone" => "bi-grid"],
    "acessos"    => ["titulo" => "Acessos",    "icone" => "bi-clock-history"],
    "visitantes" => ["titulo" => "Visitantes", "icone" => "bi-person-badge"],
    "veiculos"   => ["titulo" => "Veículos",   "icone" => "bi-car-front"],
];

$aba = $_GET["aba"] ?? "painel";
if(!array_key_exists($aba, $abas)){
    $aba = "painel";
}

/* ---------- dados ---------- */
$conv     = carregarDados();
$portaria = carregarPortaria();
$pessoas  = montarPessoas($conv);
$csrf     = tokenCsrf();

$mensagem = $_SESSION["msg_portaria"] ?? null;
$antigo   = $_SESSION["form_veiculo"] ?? [];
unset($_SESSION["msg_portaria"], $_SESSION["form_veiculo"]);

$periodo = periodoValido((string)($_GET["periodo"] ?? "hoje"));

// atalho para os formulários: campos que todo POST precisa levar
function camposPortaria(string $csrf, string $aba): string {
    return '<input type="hidden" name="csrf" value="' . h($csrf) . '">'
         . '<input type="hidden" name="aba" value="' . h($aba) . '">';
}

$acaoPortaria = "../../controllers/portaria/portaria-processar.php";

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portaria — Cerberus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="painel-layout">

    <?php $menuAtivo = "portaria"; include __DIR__ . "/partials/menu.php"; ?>

    <div id="sidebarOverlay" class="sidebar-overlay"></div>

    <div class="painel-principal">

        <header class="topbar d-flex align-items-center px-3">
            <button id="btnMenu" type="button" class="btn btn-outline-laranja btn-sm"
                    title="Recolher/expandir menu" aria-controls="sidebar" aria-expanded="true">
                <i class="bi bi-list fs-5"></i>
            </button>
            <span class="navbar-text ms-3">Portaria</span>

            <div class="ms-auto">
                <button id="btnTema" type="button" class="btn btn-tema" title="Alternar tema">🌙</button>
            </div>
        </header>

        <main class="painel-conteudo">

            <?php if($mensagem): ?>
                <div class="alert alert-<?= h($mensagem["tipo"]) ?>" role="alert">
                    <?php foreach($mensagem["textos"] as $texto): ?>
                        <div><?= h($texto) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- ABAS (cada uma é um link: funciona sem JavaScript) -->
            <nav class="portaria-abas mb-3" aria-label="Seções da portaria">
                <?php foreach($abas as $chave => $info): ?>
                    <a href="portaria.php?aba=<?= $chave ?>"
                       class="portaria-aba <?= $aba === $chave ? "ativa" : "" ?>"
                       <?= $aba === $chave ? 'aria-current="page"' : "" ?>>
                        <i class="bi <?= $info["icone"] ?>"></i>
                        <span><?= h($info["titulo"]) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php include __DIR__ . "/portaria/aba-" . $aba . ".php"; ?>

        </main>

    </div>
</div>

<script src="../../assets/js/script.js"></script>
<script>
    // Em computador, deixa o cursor na busca: o leitor de QR Code "digita" nesse campo.
    // No celular não foca sozinho, para não abrir o teclado sem necessidade.
    (() => {
        const busca = document.getElementById('busca');
        if (busca && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
            busca.focus();
            busca.select();
        }
    })();
</script>
</body>
</html>
