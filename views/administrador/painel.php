<?php

$tituloPagina = "Painel";
$menuAtivo    = "painel";
require __DIR__ . "/partials/cabecalho.php";

$resumo = admResumoGeral();

// cada card: [rótulo, valor, página para onde o link leva, ícone]
$cards = [
    ["Sócios ativos",                $resumo["ativos"] . " de " . $resumo["total_socios"],          "socios.php",                  "bi-people"],
    ["Presentes hoje no clube",      $resumo["presentes_hoje"],                                     "portaria.php",                "bi-door-open"],
    ["Contratos a vencer (60 dias)", count($resumo["a_vencer"]),                                    "socios.php",                  "bi-hourglass-split"],
    ["Mensalidades em aberto",       admMoeda($resumo["valor_em_aberto"]),                          "socios.php",                  "bi-exclamation-circle"],
    ["Convidados pendentes",         $resumo["convidados_pendentes"],                               "convidados.php?aba=convites", "bi-person-plus"],
    ["Nota média dos feedbacks",     number_format($resumo["nota_media"], 1, ",", ".") . " / 5",    "socios.php?aba=feedback",     "bi-chat-heart"],
];

// avisos que merecem atenção do administrador
$avisos = [];
foreach($resumo["vencidos"] as $s){
    $avisos[] = ["danger", "bi-x-octagon", "Contrato de {$s['nome']} vencido há " . abs($s["dias_restantes"]) . " dia(s).", "socios.php"];
}
foreach($resumo["a_vencer"] as $s){
    $avisos[] = ["warning", "bi-hourglass-split", "Contrato de {$s['nome']} vence em " . admTempoRestante($s["dias_restantes"]) . " (" . admData($s["fim"]) . ").", "socios.php"];
}
foreach($resumo["devedores"] as $s){
    $avisos[] = ["info", "bi-cash-coin", "{$s['nome']} está com mensalidade em aberto (" . admMoeda($s["mensalidade"]) . ").", "socios.php"];
}
if($resumo["convidados_pendentes"] > 0){
    $avisos[] = ["info", "bi-person-check", $resumo["convidados_pendentes"] . " convite(s) de convidados aguardando aprovação.", "convidados.php?aba=convites"];
}
?>

<h1 class="h4 mb-4">Olá, <?= ae($nomeAdmin) ?>!</h1>

<!-- ========== INDICADORES ========== -->
<div class="row g-3 mb-4">
    <?php foreach($cards as $card): ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card-painel h-100 position-relative">
                <small class="text-secondary"><i class="bi <?= $card[3] ?> me-1"></i><?= ae($card[0]) ?></small>
                <div class="indicador-numero"><?= ae($card[1]) ?></div>
                <a href="<?= $card[2] ?>" class="stretched-link small">Ver detalhes</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ========== AVISOS ========== -->
<div class="card-painel">
    <h2 class="h6 mb-3"><i class="bi bi-bell me-1"></i> Avisos para o administrador</h2>
    <?php if(empty($avisos)): ?>
        <p class="mb-0 text-secondary">Nenhum aviso no momento.</p>
    <?php else: ?>
        <ul class="lista-alertas">
            <?php foreach($avisos as $a): ?>
                <li class="alerta-item alerta-<?= $a[0] ?>">
                    <i class="bi <?= $a[1] ?>"></i>
                    <span><?= ae($a[2]) ?> <a href="<?= $a[3] ?>">Abrir</a></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php
require __DIR__ . "/partials/rodape.php";
