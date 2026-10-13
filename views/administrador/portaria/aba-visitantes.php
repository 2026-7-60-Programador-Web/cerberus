<?php
/* ABA VISITANTES — autorizações pendentes, visitantes esperados e histórico.
   O cadastro e a emissão de convites continuam no módulo Convidados. */

$acaoConvidados = "../../controllers/convidados/convidados-processar.php";

$pendentes = array_reverse(convitesPorStatus($conv, "pendente"));
$aprovados = convitesPorStatus($conv, "aprovado");

// esperados = convite aprovado de quem ainda não está dentro do clube
$esperados = [];
foreach($aprovados as $convite){
    $ref = "convidado-" . $convite["convidado_id"];

    if(acessoAberto($portaria, $ref) === null){
        $esperados[] = $convite;
    }
}

// histórico: só visitantes (as entradas deles)
$historico = [];
foreach(array_reverse($portaria["acessos"]) as $a){
    if(strpos($a["pessoa_ref"], "convidado-") === 0){
        $historico[] = $a;
    }
}
$historico = array_slice($historico, 0, 30);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Visitantes</h1>
    <div class="d-flex flex-wrap gap-2">
        <a href="convidados.php?aba=cadastro" class="btn btn-outline-laranja btn-acao"><i class="bi bi-person-plus me-1"></i>Cadastrar visitante</a>
        <a href="convidados.php?aba=convites" class="btn btn-outline-laranja btn-acao"><i class="bi bi-envelope-plus me-1"></i>Emitir convite</a>
    </div>
</div>

<!-- ============ AGUARDANDO AUTORIZAÇÃO ============ -->
<section class="card-painel mb-3" aria-labelledby="titulo-pendentes">
    <h2 id="titulo-pendentes" class="h5 mb-3">
        Aguardando autorização
        <?php if(!empty($pendentes)): ?><span class="badge text-bg-warning ms-1"><?= count($pendentes) ?></span><?php endif; ?>
    </h2>

    <?php if(empty($pendentes)): ?>
        <p class="mb-0 text-secondary">Nenhum convite aguardando autorização.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0 tabela-cartoes">
                <thead>
                    <tr><th>Visitante</th><th>Convidado por</th><th>Emitido em</th><th><span class="visually-hidden">Ações</span></th></tr>
                </thead>
                <tbody>
                    <?php foreach($pendentes as $cv): ?>
                        <?php
                            $visitante = buscarPorId($conv["convidados"], $cv["convidado_id"]);
                            $anfit     = buscarSocio($cv["socio_id"]);
                        ?>
                        <tr>
                            <td data-label="Visitante"><strong><?= h($visitante["nome"] ?? "—") ?></strong></td>
                            <td data-label="Convidado por"><?= h($anfit["nome"] ?? "—") ?></td>
                            <td data-label="Emitido em"><?= fmtMomento($cv["emitido_em"]) ?></td>
                            <td class="celula-acao">
                                <form method="POST" action="<?= $acaoConvidados ?>" class="d-flex flex-wrap gap-2">
                                    <input type="hidden" name="convite_id" value="<?= (int)$cv["id"] ?>">
                                    <input type="hidden" name="retorno" value="portaria">
                                    <button type="submit" name="acao" value="aprovar" class="btn btn-outline-success btn-sm btn-acao-tabela">Aprovar</button>
                                    <button type="submit" name="acao" value="bloquear" class="btn btn-outline-danger btn-sm btn-acao-tabela">Bloquear</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- ============ ESPERADOS ============ -->
<section class="card-painel mb-3" aria-labelledby="titulo-esperados">
    <h2 id="titulo-esperados" class="h5 mb-3">Visitantes esperados</h2>

    <?php if(empty($esperados)): ?>
        <p class="mb-0 text-secondary">Nenhum visitante com convite aprovado aguardando para entrar.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0 tabela-cartoes">
                <thead>
                    <tr><th>Visitante</th><th>Convidado por</th><th>Situação</th><th><span class="visually-hidden">Ação</span></th></tr>
                </thead>
                <tbody>
                    <?php foreach($esperados as $cv): ?>
                        <?php
                            $visitante = buscarPessoa("convidado-" . $cv["convidado_id"], $pessoas);
                            $anfit     = buscarSocio($cv["socio_id"]);
                            $avV       = $visitante ? avaliarAcesso($visitante, $conv) : null;
                        ?>
                        <tr>
                            <td data-label="Visitante"><strong><?= h($visitante["nome"] ?? "—") ?></strong></td>
                            <td data-label="Convidado por"><?= h($anfit["nome"] ?? "—") ?></td>
                            <td data-label="Situação">
                                <?php if($avV): ?>
                                    <span class="badge <?= classeBadgeNivel($avV["nivel"]) ?>"><?= rotuloNivel($avV["nivel"]) ?></span>
                                    <?php foreach($avV["motivos"] as $m): ?>
                                        <small class="d-block text-secondary"><?= h($m) ?></small>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td class="celula-acao">
                                <?php if($visitante): ?>
                                    <form method="POST" action="<?= $acaoPortaria ?>">
                                        <?= camposPortaria($csrf, "visitantes") ?>
                                        <input type="hidden" name="ref" value="<?= h($visitante["ref"]) ?>">
                                        <button type="submit" name="acao" value="entrada" class="btn btn-success btn-sm btn-acao-tabela"
                                                <?= ($avV && $avV["liberado"]) ? "" : "disabled" ?>>Liberar entrada</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- ============ HISTÓRICO ============ -->
<section class="card-painel" aria-labelledby="titulo-hist-visit">
    <h2 id="titulo-hist-visit" class="h5 mb-3">Histórico de visitantes</h2>

    <?php if(empty($historico)): ?>
        <p class="mb-0 text-secondary">Nenhum visitante passou pela portaria ainda.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0 tabela-cartoes">
                <thead>
                    <tr><th>Visitante</th><th>Convidado por</th><th>Entrada</th><th>Saída</th></tr>
                </thead>
                <tbody>
                    <?php foreach($historico as $a): ?>
                        <?php
                            $conviteHist = buscarPorId($conv["convites"], (int)($a["convite_id"] ?? 0));
                            $anfitHist   = $conviteHist ? buscarSocio($conviteHist["socio_id"]) : null;
                        ?>
                        <tr>
                            <td data-label="Visitante"><strong><?= h($a["nome"]) ?></strong></td>
                            <td data-label="Convidado por"><?= h($anfitHist["nome"] ?? "—") ?></td>
                            <td data-label="Entrada"><?= fmtMomento($a["entrada_em"]) ?></td>
                            <td data-label="Saída"><?= $a["saida_em"] ? fmtMomento($a["saida_em"]) : "No clube" ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
