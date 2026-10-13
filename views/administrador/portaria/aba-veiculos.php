<?php
/* ABA VEÍCULOS — cadastro, entrada e saída do estacionamento */

$filtroPlaca = normalizarPlaca((string)($_GET["placa"] ?? ""));

$presentes  = veiculosPresentes($portaria);
$ocupadas   = count($presentes);
$percentual = CAPACIDADE_ESTACIONAMENTO > 0 ? min(100, (int)round($ocupadas * 100 / CAPACIDADE_ESTACIONAMENTO)) : 0;

$veiculos = [];
foreach(array_reverse($portaria["veiculos"]) as $v){
    if($filtroPlaca === "" || strpos($v["placa"], $filtroPlaca) !== false){
        $veiculos[] = $v;
    }
}

// últimos movimentos de veículos
$movimentos = array_slice(array_reverse($portaria["passagens"]), 0, 10);

// quem pode ser proprietário: sócios e convidados
$donosSocios     = [];
$donosConvidados = [];
foreach($pessoas as $p){
    if($p["tipo"] === "socio"){
        $donosSocios[] = $p;
    } elseif($p["tipo"] === "convidado"){
        $donosConvidados[] = $p;
    }
}
$donoAntigo = (string)($antigo["proprietario"] ?? "");
?>

<h1 class="h4 mb-3">Veículos</h1>

<!-- ============ OCUPAÇÃO ============ -->
<section class="card-painel mb-3" aria-labelledby="titulo-vagas">
    <div class="d-flex justify-content-between align-items-baseline mb-2">
        <h2 id="titulo-vagas" class="h5 mb-0">Estacionamento</h2>
        <span><strong><?= $ocupadas ?></strong> de <?= CAPACIDADE_ESTACIONAMENTO ?> vagas ocupadas</span>
    </div>
    <div class="progress" role="progressbar" aria-label="Vagas ocupadas" aria-valuenow="<?= $percentual ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar <?= $percentual >= 90 ? "bg-danger" : "bg-warning" ?>" style="width: <?= $percentual ?>%"></div>
    </div>
</section>

<!-- ============ CADASTRO ============ -->
<section class="card-painel mb-3" aria-labelledby="titulo-novo-veiculo">
    <h2 id="titulo-novo-veiculo" class="h5 mb-3">Cadastrar veículo</h2>

    <form method="POST" action="<?= $acaoPortaria ?>" class="row g-3 align-items-end">
        <?= camposPortaria($csrf, "veiculos") ?>
        <input type="hidden" name="acao" value="veiculo_cadastrar">

        <div class="col-6 col-md-3">
            <label for="placa" class="form-label">Placa</label>
            <input type="text" id="placa" name="placa" class="form-control form-control-lg campo-placa"
                   placeholder="ABC1D23" maxlength="8" autocomplete="off" autocapitalize="characters"
                   value="<?= h($antigo["placa"] ?? "") ?>" required>
        </div>
        <div class="col-6 col-md-3">
            <label for="modelo" class="form-label">Modelo</label>
            <input type="text" id="modelo" name="modelo" class="form-control form-control-lg" maxlength="60"
                   value="<?= h($antigo["modelo"] ?? "") ?>" required>
        </div>
        <div class="col-12 col-md-4">
            <label for="proprietario" class="form-label">Proprietário</label>
            <select id="proprietario" name="proprietario" class="form-select form-select-lg" required>
                <option value="">Selecione...</option>
                <optgroup label="Sócios">
                    <?php foreach($donosSocios as $d): ?>
                        <option value="<?= h($d["ref"]) ?>" <?= $donoAntigo === $d["ref"] ? "selected" : "" ?>><?= h($d["nome"]) ?></option>
                    <?php endforeach; ?>
                </optgroup>
                <?php if(!empty($donosConvidados)): ?>
                    <optgroup label="Convidados">
                        <?php foreach($donosConvidados as $d): ?>
                            <option value="<?= h($d["ref"]) ?>" <?= $donoAntigo === $d["ref"] ? "selected" : "" ?>><?= h($d["nome"]) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
            </select>
        </div>
        <div class="col-12 col-md-2 d-grid">
            <button type="submit" class="btn btn-laranja btn-lg btn-acao">Cadastrar</button>
        </div>
    </form>
</section>

<!-- ============ LISTA ============ -->
<section class="card-painel mb-3" aria-labelledby="titulo-lista-veiculos">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 id="titulo-lista-veiculos" class="h5 mb-0">Veículos cadastrados</h2>

        <form method="GET" action="portaria.php" class="d-flex gap-2" role="search">
            <input type="hidden" name="aba" value="veiculos">
            <label for="filtro-placa" class="visually-hidden">Buscar por placa</label>
            <input type="search" id="filtro-placa" name="placa" class="form-control campo-placa"
                   placeholder="Buscar placa" value="<?= h($filtroPlaca) ?>" autocomplete="off">
            <button type="submit" class="btn btn-outline-laranja btn-acao" aria-label="Buscar"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <?php if(empty($veiculos)): ?>
        <p class="mb-0 text-secondary">
            <?= $filtroPlaca !== "" ? "Nenhum veículo encontrado com essa placa." : "Nenhum veículo cadastrado ainda." ?>
        </p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0 tabela-cartoes">
                <thead>
                    <tr><th>Placa</th><th>Modelo</th><th>Proprietário</th><th>Situação</th><th><span class="visually-hidden">Ação</span></th></tr>
                </thead>
                <tbody>
                    <?php foreach($veiculos as $v): ?>
                        <?php
                            $dono   = buscarPessoa($v["proprietario_ref"], $pessoas);
                            $aberta = passagemAberta($portaria, $v["id"]);
                        ?>
                        <tr>
                            <td data-label="Placa"><strong class="placa"><?= h(fmtPlaca($v["placa"])) ?></strong></td>
                            <td data-label="Modelo"><?= h($v["modelo"]) ?></td>
                            <td data-label="Proprietário"><?= h($dono["nome"] ?? "—") ?></td>
                            <td data-label="Situação">
                                <?php if($aberta): ?>
                                    <span class="badge text-bg-primary">No clube desde <?= fmtMomento($aberta["entrada_em"]) ?></span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Fora</span>
                                <?php endif; ?>
                            </td>
                            <td class="celula-acao">
                                <form method="POST" action="<?= $acaoPortaria ?>">
                                    <?= camposPortaria($csrf, "veiculos") ?>
                                    <input type="hidden" name="veiculo_id" value="<?= (int)$v["id"] ?>">
                                    <?php if($aberta): ?>
                                        <button type="submit" name="acao" value="veiculo_saida" class="btn btn-outline-laranja btn-sm btn-acao-tabela">Registrar saída</button>
                                    <?php else: ?>
                                        <button type="submit" name="acao" value="veiculo_entrada" class="btn btn-success btn-sm btn-acao-tabela">Registrar entrada</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- ============ MOVIMENTOS ============ -->
<section class="card-painel" aria-labelledby="titulo-mov-veic">
    <h2 id="titulo-mov-veic" class="h5 mb-3">Últimos movimentos</h2>

    <?php if(empty($movimentos)): ?>
        <p class="mb-0 text-secondary">Nenhuma entrada ou saída de veículo registrada ainda.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0 tabela-cartoes">
                <thead>
                    <tr><th>Placa</th><th>Proprietário</th><th>Entrada</th><th>Saída</th></tr>
                </thead>
                <tbody>
                    <?php foreach($movimentos as $m): ?>
                        <?php
                            $veic = buscarVeiculo($portaria, (int)$m["veiculo_id"]);
                            $dono = $veic ? buscarPessoa($veic["proprietario_ref"], $pessoas) : null;
                        ?>
                        <tr>
                            <td data-label="Placa"><strong class="placa"><?= h($veic ? fmtPlaca($veic["placa"]) : "—") ?></strong></td>
                            <td data-label="Proprietário"><?= h($dono["nome"] ?? "—") ?></td>
                            <td data-label="Entrada"><?= fmtMomento($m["entrada_em"]) ?></td>
                            <td data-label="Saída"><?= $m["saida_em"] ? fmtMomento($m["saida_em"]) : "No clube" ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
