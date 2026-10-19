<?php

$tituloPagina = "Sócios";
$menuAtivo    = "socios";
require __DIR__ . "/partials/cabecalho.php";

$abasValidas = ["geral", "convidados", "frequencia", "feedback", "site"];
$aba = in_array($_GET["aba"] ?? "", $abasValidas, true) ? $_GET["aba"] : "geral";

$socios     = admSocios();
$feedbacks  = admFeedbacksFormatados();
$site       = resumoAcessosSite(14);

// prepara os dados de cada sócio uma única vez
foreach($socios as &$s){
    $s["convidados"] = admConvidadosDoSocio($s["id"]);
    $s["freq"]       = admFrequencia($s, 90);
    $limite30        = date("Y-m-d", strtotime("-29 days"));
    $s["freq30"]     = array_values(array_filter($s["freq"], fn($d) => $d >= $limite30));
}
unset($s);

$totalAtivos   = count(array_filter($socios, fn($s) => $s["contrato"] !== "vencido"));
$totalAVencer  = count(array_filter($socios, fn($s) => $s["contrato"] === "a vencer"));
$totalVencidos = count(array_filter($socios, fn($s) => $s["contrato"] === "vencido"));
$mediaNota     = $feedbacks ? array_sum(array_column($feedbacks, "nota")) / count($feedbacks) : 0;

$badgeContrato = ["ativo" => "text-bg-success", "a vencer" => "text-bg-warning", "vencido" => "text-bg-danger"];

function estrelas(int $nota): string {
    $html = "";
    for($i = 1; $i <= 5; $i++){
        $html .= '<i class="bi ' . ($i <= $nota ? "bi-star-fill" : "bi-star") . '"></i>';
    }
    return $html;
}

$abas = [
    "geral"      => ["Visão geral",      "bi-people"],
    "convidados" => ["Convidados",       "bi-person-plus"],
    "frequencia" => ["Frequência",       "bi-calendar-check"],
    "feedback"   => ["Feedbacks",        "bi-chat-heart"],
    "site"       => ["Acessos ao site",  "bi-graph-up-arrow"],
];
?>

<h1 class="h4 mb-3">Sócios</h1>

<!-- ========== RESUMO ========== -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="card-painel h-100"><small class="text-secondary">Contratos ativos</small><div class="indicador-numero"><?= $totalAtivos ?></div></div></div>
    <div class="col-6 col-xl-3"><div class="card-painel h-100"><small class="text-secondary">Vencem em até 60 dias</small><div class="indicador-numero"><?= $totalAVencer ?></div></div></div>
    <div class="col-6 col-xl-3"><div class="card-painel h-100"><small class="text-secondary">Contratos vencidos</small><div class="indicador-numero"><?= $totalVencidos ?></div></div></div>
    <div class="col-6 col-xl-3"><div class="card-painel h-100"><small class="text-secondary">Nota média dos feedbacks</small><div class="indicador-numero"><?= number_format($mediaNota, 1, ",", ".") ?> / 5</div></div></div>
</div>

<!-- ========== ABAS ========== -->
<ul class="nav nav-tabs mb-3" role="tablist">
    <?php foreach($abas as $chave => $info): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $aba === $chave ? "active" : "" ?>" data-bs-toggle="tab"
                    data-bs-target="#aba-<?= $chave ?>" type="button" role="tab">
                <i class="bi <?= $info[1] ?> me-1"></i><?= $info[0] ?>
            </button>
        </li>
    <?php endforeach; ?>
</ul>

<div class="tab-content">

    <!-- ==================== VISÃO GERAL ==================== -->
    <div class="tab-pane fade <?= $aba === "geral" ? "show active" : "" ?>" id="aba-geral" role="tabpanel">
        <div class="card-painel">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <h2 class="h6 mb-0">Todos os sócios</h2>
                <input type="search" id="buscaSocio" class="form-control campo-busca" placeholder="Buscar por nome ou matrícula">
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0 tabela-cartoes" id="tabelaSocios">
                    <thead>
                        <tr>
                            <th>Sócio</th><th>Contrato</th><th>Tempo restante</th>
                            <th>Convidados</th><th>Dias no clube (30d)</th><th>Mensalidade</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($socios as $s): ?>
                            <?php
                                $usados = count($s["convidados"]);
                                $pct    = min(100, $usados / LIMITE_CONVIDADOS * 100);
                            ?>
                            <tr data-busca="<?= ae(mb_strtolower($s["nome"] . " " . $s["matricula"])) ?>">
                                <td data-label="Sócio"><strong><?= ae($s["nome"]) ?></strong><br><small class="text-secondary">Matrícula <?= ae($s["matricula"]) ?> · <?= ae($s["plano"]) ?></small></td>
                                <td data-label="Contrato"><?= admData($s["inicio"]) ?> a <?= admData($s["fim"]) ?></td>
                                <td data-label="Tempo restante"><span class="badge <?= $badgeContrato[$s["contrato"]] ?>"><?= ae(admTempoRestante($s["dias_restantes"])) ?></span></td>
                                <td data-label="Convidados" style="min-width: 130px;">
                                    <?= $usados ?> de <?= LIMITE_CONVIDADOS ?>
                                    <div class="progress barra-convidados mt-1"><div class="progress-bar <?= $usados >= LIMITE_CONVIDADOS ? "cheio" : "" ?>" style="width: <?= $pct ?>%"></div></div>
                                </td>
                                <td data-label="Dias no clube (30d)"><?= count($s["freq30"]) ?> dia(s)</td>
                                <td data-label="Mensalidade"><span class="badge <?= $s["financeiro"] === "em dia" ? "text-bg-success" : "text-bg-danger" ?>"><?= $s["financeiro"] === "em dia" ? "Em dia" : "Em aberto" ?></span></td>
                                <td class="text-end celula-acao">
                                    <button type="button" class="btn btn-sm btn-outline-laranja btn-acao-tabela" data-bs-toggle="modal"
                                            data-bs-target="#modalSocio" data-id="<?= $s["id"] ?>">Detalhes</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ==================== CONVIDADOS POR SÓCIO ==================== -->
    <div class="tab-pane fade <?= $aba === "convidados" ? "show active" : "" ?>" id="aba-convidados" role="tabpanel">
        <div class="card-painel">
            <h2 class="h6 mb-1">Controle de convidados por sócio</h2>
            <p class="small text-secondary mb-3">Cada sócio pode ter até <?= LIMITE_CONVIDADOS ?> convidados cadastrados. Para cadastrar ou aprovar, use a área <a href="convidados.php">Convidados</a>.</p>

            <div class="accordion" id="acordeaoConvidados">
                <?php foreach($socios as $s): ?>
                    <?php $usados = count($s["convidados"]); ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#conv-<?= $s["id"] ?>">
                                <span class="me-auto"><?= ae($s["nome"]) ?></span>
                                <span class="badge <?= $usados >= LIMITE_CONVIDADOS ? "text-bg-danger" : "text-bg-secondary" ?> me-2"><?= $usados ?>/<?= LIMITE_CONVIDADOS ?></span>
                            </button>
                        </h2>
                        <div id="conv-<?= $s["id"] ?>" class="accordion-collapse collapse" data-bs-parent="#acordeaoConvidados">
                            <div class="accordion-body">
                                <?php if(empty($s["convidados"])): ?>
                                    <p class="mb-0 text-secondary">Nenhum convidado cadastrado. Restam <?= LIMITE_CONVIDADOS ?> vaga(s).</p>
                                <?php else: ?>
                                    <p class="small text-secondary">Restam <?= max(0, LIMITE_CONVIDADOS - $usados) ?> vaga(s).</p>
                                    <ul class="lista-eventos">
                                        <?php foreach($s["convidados"] as $c): ?>
                                            <li>
                                                <i class="bi bi-person"></i>
                                                <span class="evento-texto"><?= ae($c["nome"]) ?><br><small class="text-secondary">Última visita: <?= admData($c["visita"]) ?></small></span>
                                                <span class="badge <?= $c["status"] === "Aprovado" ? "text-bg-success" : "text-bg-warning" ?>"><?= ae($c["status"]) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ==================== FREQUÊNCIA ==================== -->
    <div class="tab-pane fade <?= $aba === "frequencia" ? "show active" : "" ?>" id="aba-frequencia" role="tabpanel">
        <div class="card-painel">
            <h2 class="h6 mb-1">Dias em que os sócios frequentaram o clube</h2>
            <p class="small text-secondary mb-3">Cada quadradinho é um dia dos últimos 30 (o mais antigo à esquerda). Laranja = esteve no clube.</p>

            <div class="table-responsive">
                <table class="table align-middle mb-0 tabela-cartoes">
                    <thead><tr><th>Sócio</th><th>Últimos 30 dias</th><th>30 dias</th><th>90 dias</th><th>Última visita</th></tr></thead>
                    <tbody>
                        <?php foreach($socios as $s): ?>
                            <tr>
                                <td data-label="Sócio"><strong><?= ae($s["nome"]) ?></strong></td>
                                <td data-label="Últimos 30 dias">
                                    <div class="freq-grade">
                                        <?php for($i = 29; $i >= 0; $i--): ?>
                                            <?php $d = date("Y-m-d", strtotime("-$i days")); ?>
                                            <span class="freq-dia <?= in_array($d, $s["freq30"], true) ? "presente" : "" ?>" title="<?= admData($d) ?>"></span>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td data-label="30 dias"><?= count($s["freq30"]) ?> dia(s)</td>
                                <td data-label="90 dias"><?= count($s["freq"]) ?> dia(s)</td>
                                <td data-label="Última visita"><?= $s["freq"] ? admData($s["freq"][0]) : "—" ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ==================== FEEDBACKS ==================== -->
    <div class="tab-pane fade <?= $aba === "feedback" ? "show active" : "" ?>" id="aba-feedback" role="tabpanel">
        <div class="card-painel">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h6 mb-1">Feedbacks recebidos</h2>
                    <span class="estrelas"><?= estrelas((int)round($mediaNota)) ?></span>
                    <small class="text-secondary ms-1"><?= number_format($mediaNota, 1, ",", ".") ?> de média em <?= count($feedbacks) ?> avaliações</small>
                </div>
                <select id="filtroFeedback" class="form-select campo-busca">
                    <option value="">Todas as categorias</option>
                    <?php foreach(array_unique(array_column($feedbacks, "categoria")) as $cat): ?>
                        <option value="<?= ae($cat) ?>"><?= ae($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row g-3" id="listaFeedback">
                <?php foreach($feedbacks as $f): ?>
                    <div class="col-12 col-lg-6" data-categoria="<?= ae($f["categoria"]) ?>">
                        <div class="card-painel h-100 py-3">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <strong><?= ae($f["socio"]) ?></strong>
                                <span class="estrelas"><?= estrelas($f["nota"]) ?></span>
                            </div>
                            <p class="my-2"><?= ae($f["mensagem"]) ?></p>
                            <small class="text-secondary"><span class="badge text-bg-secondary"><?= ae($f["categoria"]) ?></span> · <?= admData($f["data"]) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ==================== ACESSOS AO SITE ==================== -->
    <div class="tab-pane fade <?= $aba === "site" ? "show active" : "" ?>" id="aba-site" role="tabpanel">
        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3"><div class="card-painel h-100"><small class="text-secondary">Visitas hoje</small><div class="indicador-numero"><?= $site["hoje_visitas"] ?></div></div></div>
            <div class="col-6 col-xl-3"><div class="card-painel h-100"><small class="text-secondary">Visitantes únicos hoje</small><div class="indicador-numero"><?= $site["hoje_unicos"] ?></div></div></div>
            <div class="col-6 col-xl-3"><div class="card-painel h-100"><small class="text-secondary">Logins hoje</small><div class="indicador-numero"><?= $site["hoje_logins"] ?></div></div></div>
            <div class="col-6 col-xl-3"><div class="card-painel h-100"><small class="text-secondary">Visitas em 7 dias</small><div class="indicador-numero"><?= $site["visitas_7d"] ?></div></div></div>
        </div>

        <div class="card-painel">
            <h2 class="h6 mb-3">Visitas por dia</h2>
            <div style="height: 280px;"><canvas id="graficoSiteSocios"></canvas></div>
            <?php if($site["simulado"]): ?>
                <small class="text-secondary d-block mt-2">Dias sem registro mostram valores de exemplo; o dia de hoje é contado de verdade.</small>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ==================== MODAL: DETALHES DO SÓCIO ==================== -->
<div class="modal fade" id="modalSocio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="modalSocioNome"></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="ficha-secao mb-1">Contrato</p>
                <p id="modalSocioContrato" class="mb-3"></p>
                <p class="ficha-secao mb-1">Convidados</p>
                <ul id="modalSocioConvidados" class="mb-3 ps-3"></ul>
                <p class="ficha-secao mb-1">Dias de frequência (90 dias)</p>
                <p id="modalSocioFreq" class="mb-0 small"></p>
            </div>
        </div>
    </div>
</div>

<?php
// dados que o JavaScript usa no modal e no gráfico
$jsSocios = [];
foreach($socios as $s){
    $jsSocios[$s["id"]] = [
        "nome"      => $s["nome"],
        "contrato"  => admData($s["inicio"]) . " a " . admData($s["fim"]) . " — " . admTempoRestante($s["dias_restantes"]),
        "convidados" => array_map(fn($c) => $c["nome"] . " (" . admData($c["visita"]) . ")", $s["convidados"]),
        "freq"      => array_map("admData", $s["freq"]),
    ];
}
$jsSite = [
    "rotulos" => array_map(fn($d) => date("d/m", strtotime($d["data"])), $site["serie"]),
    "visitas" => array_column($site["serie"], "visitas"),
    "unicos"  => array_column($site["serie"], "unicos"),
];

$scriptsExtras = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const socios = ' . json_encode($jsSocios, JSON_UNESCAPED_UNICODE) . ';
    const dadosSite = ' . json_encode($jsSite) . ';

    // busca na tabela de sócios
    document.getElementById("buscaSocio").addEventListener("input", (e) => {
        const termo = e.target.value.toLowerCase().trim();
        document.querySelectorAll("#tabelaSocios tbody tr").forEach(tr => {
            tr.style.display = tr.dataset.busca.includes(termo) ? "" : "none";
        });
    });

    // filtro dos feedbacks por categoria
    document.getElementById("filtroFeedback").addEventListener("change", (e) => {
        document.querySelectorAll("#listaFeedback > div").forEach(div => {
            div.style.display = (!e.target.value || div.dataset.categoria === e.target.value) ? "" : "none";
        });
    });

    // modal com os detalhes do sócio clicado
    document.getElementById("modalSocio").addEventListener("show.bs.modal", (ev) => {
        const s = socios[ev.relatedTarget.dataset.id];
        document.getElementById("modalSocioNome").textContent = s.nome;
        document.getElementById("modalSocioContrato").textContent = s.contrato;
        const lista = document.getElementById("modalSocioConvidados");
        lista.replaceChildren();
        (s.convidados.length ? s.convidados : ["Nenhum convidado."]).forEach(texto => {
            const li = document.createElement("li");
            li.textContent = texto;
            lista.appendChild(li);
        });
        document.getElementById("modalSocioFreq").textContent = s.freq.length ? s.freq.join(" · ") : "Sem registros de frequência.";
    });

    // gráfico de acessos: criado quando a aba aparece (canvas escondido não tem tamanho)
    let graficoSite = null;
    function criarGrafico() {
        if (graficoSite) return;
        const cor = getComputedStyle(document.body).color;
        graficoSite = new Chart(document.getElementById("graficoSiteSocios"), {
            type: "line",
            data: { labels: dadosSite.rotulos, datasets: [
                { label: "Visitas", data: dadosSite.visitas, borderColor: "#ff7a00", backgroundColor: "rgba(255,122,0,.25)", fill: true, tension: .3 },
                { label: "Visitantes únicos", data: dadosSite.unicos, borderColor: "#6c757d", tension: .3 }
            ]},
            options: { responsive: true, maintainAspectRatio: false,
                plugins: { legend: { labels: { color: cor } } },
                scales: { x: { ticks: { color: cor }, grid: { display: false } }, y: { beginAtZero: true, ticks: { color: cor } } } }
        });
    }
    document.querySelector("[data-bs-target=\"#aba-site\"]").addEventListener("shown.bs.tab", criarGrafico);
    if (document.getElementById("aba-site").classList.contains("active")) criarGrafico();
</script>';
require __DIR__ . "/partials/rodape.php";
