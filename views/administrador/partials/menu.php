<?php
// Menu lateral do administrador. Antes de incluir, defina $menuAtivo:
// "painel", "convidados", "portaria" ou "configuracoes".
$menuAtivo = $menuAtivo ?? "";

$itensMenu = [
    ["chave" => "painel",        "href" => "painel.php",                "titulo" => "Painel",        "icone" => "bi-speedometer2"],
    ["chave" => "socios",        "href" => "../associados/painel.php",  "titulo" => "Sócios",        "icone" => "bi-people"],
    ["chave" => "convidados",    "href" => "convidados.php",            "titulo" => "Convidados",    "icone" => "bi-person-plus"],
    ["chave" => "portaria",      "href" => "portaria.php",              "titulo" => "Portaria",      "icone" => "bi-door-open"],
    ["chave" => "configuracoes", "href" => "configuracoes.php",        "titulo" => "Configurações", "icone" => "bi-gear"],
];
?>
    <!-- MENU LATERAL -->
    <aside id="sidebar" class="sidebar" aria-label="Menu principal">
        <div class="sidebar-marca">
            <img src="../../assets/img/logo-cerberus-tres-lobos.svg" alt="Logo Cerberus" class="logo-sidebar">
            <span class="sidebar-texto sidebar-titulo">Cerberus</span>
        </div>

        <nav class="sidebar-nav">
            <ul class="sidebar-menu">
                <?php foreach($itensMenu as $item): ?>
                    <li>
                        <a href="<?= $item["href"] ?>"
                           class="sidebar-link <?= $menuAtivo === $item["chave"] ? "ativo" : "" ?>"
                           title="<?= $item["titulo"] ?>"
                           <?= $menuAtivo === $item["chave"] ? 'aria-current="page"' : "" ?>>
                            <i class="bi <?= $item["icone"] ?>"></i>
                            <span class="sidebar-texto"><?= $item["titulo"] ?></span>
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