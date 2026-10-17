<?php

$tituloPagina = "Painel";
$menuAtivo    = "painel";
require __DIR__ . "/partials/cabecalho.php";

$resumo = admResumoGeral();
$site   = resumoAcessosSite(14);
$fluxo  = admFluxoCaixa();
$mes    = $resumo["mes_atual"];

// cada card: [rótulo, valor, página para onde o link leva, ícone]
$cards = [
    ["Sócios ativos",          $resumo["ativos"] . " de " . $resumo["total_socios"],            "socios.php",                 "bi-people"],
    ["Acessos ao site hoje",   $site["hoje_visitas"],                                           "socios.php?aba=site",        "bi-graph-up-arrow"],
    ["Saldo do mês",           admMoeda($mes["saldo"]),                                         "#fluxo-caixa",               "bi-cash-stack"],
    ["Presentes hoje no clube", $resumo["presentes_hoje"],                                      "portaria.php",               "bi-door-open"],
    ["Contratos a vencer (60 dias)", count($resumo["a_vencer"]),                                "socios.php",                 "bi-hourglass-split"],
    ["Mensalidades em aberto", admMoeda($resumo["valor_em_aberto"]),                            "socios.php",                 "bi-exclamation-circle"],
    ["Convidados pendentes",   $resumo["convidados_pendentes"],                                 "convidados.php?aba=convites", "bi-person-plus"],
    ["Nota média dos feedbacks", number_format($resumo["nota_media"], 1, ",", ".") . " / 5",    "socios.php?aba=feedback",    "bi-chat-heart"],
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

// série dos gráficos (vai para o JavaScript)
$dadosSite = [
    "rotulos" => array_map(fn($d) => date("d/m", strtotime($d["data"])), $site["serie"]),
    "visitas" => array_column($site["serie"], "visitas"),
    "unicos"  => array_column($site["serie"], "unicos"),
];
$dadosFluxo = [
    "rotulos"  => array_column($fluxo, "mes"),
    "entradas" => array_column($fluxo, "entradas"),
    "saidas"   => array_column($fluxo, "saidas"),
];
?>

<h1 class="h4 mb-4">Olá, <?= ae($nomeAdmin) ?>!</h1>

<!-- ========== INDICADORES ========== -->
<div class="row g-3 mb-4">
    <?php foreach($cards as $card): ?>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card-painel h-100 position-relative">
                <small class="text-secondary"><i class="bi <?= $card[3] ?> me-1"></i><?= ae($card[0]) ?></small>
                <div class="indicador-numero"><?= ae($card[1]) ?></div>
                <a href="<?= $card[2] ?>" class="stretched-link small">Ver detalhes</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">

    <!-- ========== ACESSOS AO SITE ========== -->
    <div class="col-12 col-xl-6" id="acessos-site">
        <div class="card-painel h-100">
            <h2 class="h6 mb-3"><i class="bi bi-graph-up-arrow me-1"></i> Acessos ao site (14 dias)</h2>
            <div class="row text-center mb-3">
                <div class="col-4"><div class="indicador-numero"><?= $site["hoje_visitas"] ?></div><small class="text-secondary">visitas hoje</small></div>
                <div class="col-4"><div class="indicador-numero"><?= $site["hoje_unicos"] ?></div><small class="text-secondary">visitantes únicos</small></div>
                <div class="col-4"><div class="indicador-numero"><?= $site["visitas_7d"] ?></div><small class="text-secondary">visitas em 7 dias</small></div>
            </div>
            <div style="height: 240px;"><canvas id="graficoSite"></canvas></div>
            <?php if($site["simulado"]): ?>
                <small class="text-secondary d-block mt-2">Dias sem registro mostram valores de exemplo; o dia de hoje é contado de verdade.</small>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========== FLUXO DE CAIXA ========== -->
    <div class="col-12 col-xl-6" id="fluxo-caixa">
        <div class="card-painel h-100">
            <h2 class="h6 mb-3"><i class="bi bi-cash-stack me-1"></i> Fluxo de caixa (6 meses)</h2>
            <div class="row text-center mb-3">
                <div class="col-4"><div class="indicador-numero fs-5"><?= admMoeda($mes["entradas"]) ?></div><small class="text-secondary">entradas</small></div>
                <div class="col-4"><div class="indicador-numero fs-5"><?= admMoeda($mes["saidas"]) ?></div><small class="text-secondary">saídas</small></div>
                <div class="col-4"><div class="indicador-numero fs-5"><?= admMoeda($mes["saldo"]) ?></div><small class="text-secondary">saldo do mês</small></div>
            </div>
            <div style="height: 240px;"><canvas id="graficoFluxo"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3">

    <!-- ========== AVISOS ========== -->
    <div class="col-12 col-xl-6">
        <div class="card-painel h-100">
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
    </div>

    <!-- ========== ÚLTIMOS LANÇAMENTOS ========== -->
    <div class="col-12 col-xl-6">
        <div class="card-painel h-100">
            <h2 class="h6 mb-3"><i class="bi bi-receipt me-1"></i> Últimos lançamentos</h2>
            <ul class="lista-eventos">
                <?php foreach(admLancamentos() as $l): ?>
                    <?php $entrada = $l[2] === "entrada"; ?>
                    <li>
                        <i class="bi <?= $entrada ? 'bi-arrow-down-circle evento-entrada' : 'bi-arrow-up-circle evento-negado' ?>"></i>
                        <span class="evento-texto"><?= ae($l[1]) ?><br><small class="text-secondary"><?= admData(date("Y-m-d", strtotime($l[0]))) ?></small></span>
                        <span class="evento-hora <?= $entrada ? 'evento-entrada' : 'evento-negado' ?>"><?= $entrada ? "+" : "−" ?> <?= admMoeda($l[3]) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<?php
$scriptsExtras = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const dadosSite  = ' . json_encode($dadosSite) . ';
    const dadosFluxo = ' . json_encode($dadosFluxo) . ';
    const LARANJA = "#ff7a00", LARANJA_SUAVE = "rgba(255,122,0,.25)", CINZA = "#6c757d";

    const graficos = [];
    const corTexto = () => getComputedStyle(document.body).color;

    function opcoes(moeda) {
        return {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { labels: { color: corTexto() } } },
            scales: {
                x: { ticks: { color: corTexto() }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { color: corTexto(), callback: v => moeda ? "R$ " + v.toLocaleString("pt-BR") : v }, grid: { color: "rgba(128,128,128,.2)" } }
            }
        };
    }

    graficos.push(new Chart(document.getElementById("graficoSite"), {
        type: "line",
        data: { labels: dadosSite.rotulos, datasets: [
            { label: "Visitas", data: dadosSite.visitas, borderColor: LARANJA, backgroundColor: LARANJA_SUAVE, fill: true, tension: .3 },
            { label: "Visitantes únicos", data: dadosSite.unicos, borderColor: CINZA, backgroundColor: "transparent", tension: .3 }
        ]},
        options: opcoes(false)
    }));

    graficos.push(new Chart(document.getElementById("graficoFluxo"), {
        type: "bar",
        data: { labels: dadosFluxo.rotulos, datasets: [
            { label: "Entradas", data: dadosFluxo.entradas, backgroundColor: LARANJA },
            { label: "Saídas",   data: dadosFluxo.saidas,   backgroundColor: CINZA }
        ]},
        options: opcoes(true)
    }));

    // ao trocar o tema, atualiza a cor dos textos dos gráficos
    document.getElementById("btnTema")?.addEventListener("click", () => setTimeout(() => {
        graficos.forEach(g => {
            g.options.plugins.legend.labels.color = corTexto();
            g.options.scales.x.ticks.color = corTexto();
            g.options.scales.y.ticks.color = corTexto();
            g.update();
        });
    }, 50));
</script>';
require __DIR__ . "/partials/rodape.php";
