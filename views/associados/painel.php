<?php

session_start();

// só entra quem está logado E não é administrador
if(!isset($_SESSION["email"]) || $_SESSION["admin"] !== false){
    header("Location: ../autenticacao/login.php");
    exit;
}

// lista das páginas: chave (vai na URL) => título e ícone do menu
$paginas = [
    "inicio"      => ["titulo" => "Início",              "icone" => "bi-house"],
    "perfil"      => ["titulo" => "Meu perfil",          "icone" => "bi-person-circle"],
    "carteirinha" => ["titulo" => "Carteirinha digital", "icone" => "bi-qr-code"],
    "convidados"  => ["titulo" => "Meus convidados",     "icone" => "bi-person-plus"],
    "financeiro"  => ["titulo" => "Financeiro",          "icone" => "bi-cash-coin"],
    "reservas"    => ["titulo" => "Reservas e eventos",  "icone" => "bi-calendar-event"],
    "avisos"      => ["titulo" => "Avisos",              "icone" => "bi-megaphone"],
    "acessos"     => ["titulo" => "Meus acessos",        "icone" => "bi-door-open"],
    "suporte"     => ["titulo" => "Suporte",             "icone" => "bi-headset"],
];

// qual página o usuário pediu? (padrão: início)
$pagina = $_GET["pagina"] ?? "inicio";

// SEGURANÇA: só aceita chaves que existem na lista acima
if(!array_key_exists($pagina, $paginas)){
    $pagina = "inicio";
}

$nome = $_SESSION["nome"] ?? "Sócio(a)";

// função curta para escapar textos (evita ataque XSS)
function e($texto){
    return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8");
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($paginas[$pagina]["titulo"]); ?> — Cerberus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
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
                <?php foreach($paginas as $chave => $info): ?>
                    <li>
                        <a href="?pagina=<?= $chave; ?>"
                           class="sidebar-link <?= $pagina === $chave ? 'ativo' : ''; ?>"
                           title="<?= e($info['titulo']); ?>">
                            <i class="bi <?= $info['icone']; ?>"></i>
                            <span class="sidebar-texto"><?= e($info['titulo']); ?></span>
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
            <span class="navbar-text ms-3 d-none d-sm-inline">Bem-vindo(a), <?= e($nome); ?>!</span>

            <div class="ms-auto">
                <button id="btnTema" type="button" class="btn btn-tema" title="Alternar tema">🌙</button>
            </div>
        </header>

        <main class="painel-conteudo">
            <?php include __DIR__ . "/" . $pagina . ".php"; ?>
        </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/script.js"></script>
</body>
</html>