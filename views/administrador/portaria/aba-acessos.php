<?php
/* ABA ACESSOS — histórico de entradas, saídas e tentativas negadas */

$situacoes = [
    "todos"  => "Todos",
    "dentro" => "Dentro do clube",
    "saiu"   => "Já saíram",
    "negado" => "Entradas negadas",
];

$filtroSituacao = (string)($_GET["situacao"] ?? "todos");
if(!array_key_exists($filtroSituacao, $situacoes)){
    $filtroSituacao = "todos";
}

$filtroNome = trim((string)($_GET["nome"] ?? ""));
$periodos   = ["hoje" => "Hoje", "7d" => "Últimos 7 dias", "30d" => "Últimos 30 dias"];

$linhas       = historicoAcessos($portaria, inicioPeriodo($periodo), $filtroSituacao, $filtroNome);
$limiteLinhas = 200;
$total        = count($linhas);
$linhas       = array_slice($linhas, 0, $limiteLinhas);

$badgesSituacao = [
    "dentro" => ["text-bg-primary", "Dentro"],
    "saiu"   => ["text-bg-secondary", "Saiu"],
    "negado" => ["text-bg-danger", "Negado"],
];
?>

<section class="card-painel mb-3" aria-labelledby="titulo-filtros">
    <h1 id="titulo-filtros" class="h5 mb-3">Consultar acessos</h1>

    <form method="GET" action="portaria.php" class="row g-3 align-items-end">
        <input type="hidden" name="aba" value="acessos">

        <div class="col-12 col-md-4">
            <label for="filtro-nome" class="form-label">Nome</label>
            <input type="search" id="filtro-nome" name="nome" class="form-control" value="<?= h($filtroNome) ?>" autocomplete="off">
        </div>
        <div class="col-6 col-md-3">
            <label for="filtro-periodo" class="form-label">Período</label>
            <select id="filtro-periodo" name="periodo" class="form-select">
                <?php foreach($periodos as $chave => $rotulo): ?>
                    <option value="<?= $chave ?>" <?= $periodo === $chave ? "selected" : "" ?>><?= $rotulo ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label for="filtro-situacao" class="form-label">Situação</label>
            <select id="filtro-situacao" name="situacao" class="form-select">
                <?php foreach($situacoes as $chave => $rotulo): ?>
                    <option value="<?= $chave ?>" <?= $filtroSituacao === $chave ? "selected" : "" ?>><?= $rotulo ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-2 d-grid">
            <button type="submit" class="btn btn-laranja btn-acao">Filtrar</button>
        </div>
    </form>
</section>

<section class="card-painel" aria-labelledby="titulo-historico">
    <h2 id="titulo-historico" class="h5 mb-3">Histórico</h2>

    <?php if(empty($linhas)): ?>
        <p class="mb-0 text-secondary">Nenhum registro encontrado com esses filtros.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0 tabela-cartoes">
                <thead>
                    <tr><th>Nome</th><th>Tipo</th><th>Entrada</th><th>Saída</th><th>Permanência</th><th>Situação</th></tr>
                </thead>
                <tbody>
                    <?php foreach($linhas as $l): ?>
                        <?php $b = $badgesSituacao[$l["situacao"]]; ?>
                        <tr>
                            <td data-label="Nome"><strong><?= h($l["nome"]) ?></strong></td>
                            <td data-label="Tipo"><?= h($l["rotulo"]) ?></td>
                            <td data-label="Entrada"><?= fmtMomento($l["entrada"]) ?></td>
                            <td data-label="Saída"><?= $l["saida"] ? fmtMomento($l["saida"]) : "—" ?></td>
                            <td data-label="Permanência">
                                <?= $l["situacao"] === "negado" ? "—" : fmtPermanencia($l["entrada"], $l["saida"]) ?>
                            </td>
                            <td data-label="Situação">
                                <span class="badge <?= $b[0] ?>"><?= $b[1] ?></span>
                                <?php if($l["detalhe"] !== ""): ?>
                                    <small class="d-block text-secondary"><?= h($l["detalhe"]) ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p class="small text-secondary mt-3 mb-0">
            <?= $total > $limiteLinhas ? "Mostrando os " . $limiteLinhas . " registros mais recentes de " . $total . "." : $total . " registro(s)." ?>
        </p>
    <?php endif; ?>
</section>
