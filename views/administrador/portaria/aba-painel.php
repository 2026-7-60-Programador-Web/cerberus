<?php
/* ABA PAINEL — busca rápida, resumo do período, quem está dentro, alertas e acessos recentes */

$q         = trim((string)($_GET["q"] ?? ""));
$refPedida = (string)($_GET["pessoa"] ?? "");

$resultados  = [];
$selecionada = null;
$naoAchou    = false;

if($refPedida !== ""){
    $selecionada = buscarPessoa($refPedida, $pessoas);
    $naoAchou    = $selecionada === null;
} elseif($q !== ""){
    $resultados = buscarPessoas($q, $pessoas);

    if(count($resultados) === 1){
        $selecionada = $resultados[0];
    } elseif(empty($resultados)){
        $naoAchou = true;
    }
}

$codigoQr = stripos($q, "CERBERUS|") === 0;   // leitor de QR com código que não existe

/* resumo */
$dentro          = pessoasDentro($portaria);
$veiculosDentro  = veiculosPresentes($portaria);
$entradasPeriodo = contarEntradasDesde($portaria, inicioPeriodo($periodo));
$pendentes       = convitesPorStatus($conv, "pendente");
$alertas         = gerarAlertas($portaria, $conv, $pessoas);
$recentes        = eventosRecentes($portaria, 8);

$periodos = ["hoje" => "Hoje", "7d" => "7 dias", "30d" => "30 dias"];
?>

<!-- ============ BUSCA RÁPIDA ============ -->
<section class="card-painel mb-3" aria-labelledby="titulo-busca">
    <h1 id="titulo-busca" class="h5 mb-3">Localizar pessoa</h1>

    <form method="GET" action="portaria.php" class="row g-2" role="search">
        <input type="hidden" name="aba" value="painel">

        <div class="col-12 col-sm">
            <label for="busca" class="visually-hidden">Nome, CPF, matrícula, carteirinha ou QR Code</label>
            <input type="search" id="busca" name="q" class="form-control form-control-lg"
                   placeholder="Nome, CPF, matrícula, carteirinha ou QR Code"
                   value="<?= h($q) ?>" autocomplete="off" enterkeyhint="search">
        </div>
        <div class="col-12 col-sm-auto d-grid">
            <button type="submit" class="btn btn-laranja btn-lg btn-acao">
                <i class="bi bi-search me-1"></i>Buscar
            </button>
        </div>
    </form>
</section>

<?php if($naoAchou): ?>
    <div class="alert alert-warning d-flex align-items-start gap-3" role="alert">
        <i class="bi <?= $codigoQr ? "bi-qr-code" : "bi-person-x" ?> fs-3"></i>
        <div>
            <?php if($codigoQr): ?>
                <strong>Carteirinha inválida.</strong> O QR Code lido não corresponde a nenhum cadastro.
            <?php else: ?>
                <strong>Pessoa não encontrada.</strong> Confira o que foi digitado.
            <?php endif; ?>
            Se for um visitante, <a href="convidados.php?aba=cadastro" class="alert-link">cadastre o convidado</a> e emita o convite.
        </div>
    </div>
<?php endif; ?>

<?php if(count($resultados) > 1): ?>
    <div class="list-group mb-3" aria-label="Resultados da busca">
        <?php foreach($resultados as $r): ?>
            <?php $avR = avaliarAcesso($r, $conv); ?>
            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3 py-3"
               href="portaria.php?aba=painel&amp;q=<?= urlencode($q) ?>&amp;pessoa=<?= urlencode($r["ref"]) ?>">
                <span>
                    <strong><?= h($r["nome"]) ?></strong><br>
                    <small class="text-secondary">
                        <?= h($r["rotulo"]) ?><?= $r["matricula"] !== "" ? " · Matrícula " . h($r["matricula"]) : "" ?>
                    </small>
                </span>
                <span class="badge <?= classeBadgeNivel($avR["nivel"]) ?>"><?= rotuloNivel($avR["nivel"]) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if($selecionada): ?>
    <?php
        $ref     = $selecionada["ref"];
        $av      = avaliarAcesso($selecionada, $conv);
        $aberto  = acessoAberto($portaria, $ref);
        $ultimo  = ultimoAcesso($portaria, $ref);
        $faixas  = [
            "ok"       => ["ok",       "bi-check-circle-fill",        "Entrada liberada"],
            "atencao"  => ["atencao",  "bi-exclamation-triangle-fill", "Atenção: entrada não liberada"],
            "bloqueio" => ["bloqueio", "bi-x-octagon-fill",           "Entrada bloqueada"],
        ];
        $faixa   = $faixas[$av["nivel"]];
        $ehConv  = $selecionada["tipo"] === "convidado";
    ?>
    <article class="pessoa-card mb-4" aria-label="Dados de <?= h($selecionada["nome"]) ?>">

        <div class="status-faixa status-<?= $faixa[0] ?>" role="status">
            <i class="bi <?= $faixa[1] ?>"></i>
            <span><?= $faixa[2] ?></span>
        </div>

        <div class="p-3 p-md-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h2 class="h3 mb-2"><?= h($selecionada["nome"]) ?></h2>
                    <span class="badge text-bg-secondary"><?= h($selecionada["rotulo"]) ?></span>
                    <?php if(!$ehConv): ?>
                        <span class="badge <?= classeBadgeSituacao($selecionada["situacao"]) ?>">
                            <?= rotuloSituacao($selecionada["situacao"]) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="text-md-end">
                    <?php if($aberto): ?>
                        <span class="badge text-bg-primary fs-6"><i class="bi bi-door-open me-1"></i>Dentro do clube desde <?= fmtMomento($aberto["entrada_em"]) ?></span>
                    <?php else: ?>
                        <span class="badge text-bg-light border fs-6">Fora do clube</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if(!empty($av["motivos"])): ?>
                <ul class="motivos-acesso motivos-<?= $faixa[0] ?> mb-3">
                    <?php foreach($av["motivos"] as $motivo): ?>
                        <li><?= h($motivo) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <dl class="dados-pessoa mb-4">
                <?php if(!$ehConv): ?>
                    <div><dt>Matrícula</dt><dd><?= h($selecionada["matricula"]) ?></dd></div>
                    <div><dt>Carteirinha</dt><dd><?= h($selecionada["carteirinha"]) ?></dd></div>
                    <div><dt>Validade</dt><dd><?= fmtData($selecionada["validade"]) ?></dd></div>
                <?php else: ?>
                    <div><dt>Convidado de</dt><dd><?= h($av["anfitriao"]["nome"] ?? "—") ?></dd></div>
                    <div><dt>Celular</dt><dd><?= fmtCelular($selecionada["celular"]) ?></dd></div>
                    <div><dt>Nascimento</dt><dd><?= fmtData($selecionada["nascimento"]) ?></dd></div>
                <?php endif; ?>

                <div><dt>CPF</dt><dd><?= fmtCpfMascarado($selecionada["cpf"]) ?></dd></div>

                <div>
                    <dt>Último acesso</dt>
                    <dd><?= $ultimo ? fmtMomento($ultimo["entrada_em"]) : "Nenhum registro" ?></dd>
                </div>

                <?php if($ehConv && !empty($selecionada["menor"]) && !empty($selecionada["responsavel"])): ?>
                    <div class="dados-largo">
                        <dt>Menor de idade — responsável</dt>
                        <dd>
                            <?= h($selecionada["responsavel"]["nome"]) ?>
                            · <?= fmtCelular($selecionada["responsavel"]["celular"]) ?>
                        </dd>
                    </div>
                <?php endif; ?>
            </dl>

            <!-- AÇÕES -->
            <div class="acoes-pessoa">
                <?php if($aberto): ?>
                    <form method="POST" action="<?= $acaoPortaria ?>">
                        <?= camposPortaria($csrf, "painel") ?>
                        <input type="hidden" name="ref" value="<?= h($ref) ?>">
                        <button type="submit" name="acao" value="saida" class="btn btn-laranja btn-lg btn-acao w-100">
                            <i class="bi bi-box-arrow-left me-1"></i>Registrar saída
                        </button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="<?= $acaoPortaria ?>">
                        <?= camposPortaria($csrf, "painel") ?>
                        <input type="hidden" name="ref" value="<?= h($ref) ?>">
                        <button type="submit" name="acao" value="entrada" class="btn btn-success btn-lg btn-acao w-100"
                                <?= $av["liberado"] ? "" : "disabled" ?>>
                            <i class="bi bi-box-arrow-in-right me-1"></i>Liberar entrada
                        </button>
                    </form>

                    <?php if(!$av["liberado"]): ?>
                        <form method="POST" action="<?= $acaoPortaria ?>">
                            <?= camposPortaria($csrf, "painel") ?>
                            <input type="hidden" name="ref" value="<?= h($ref) ?>">
                            <button type="submit" name="acao" value="negar" class="btn btn-outline-danger btn-lg btn-acao w-100">
                                <i class="bi bi-slash-circle me-1"></i>Registrar entrada negada
                            </button>
                        </form>
                    <?php else: ?>
                        <details class="negar-manual">
                            <summary class="btn btn-outline-danger btn-lg btn-acao w-100">
                                <i class="bi bi-slash-circle me-1"></i>Negar entrada
                            </summary>
                            <form method="POST" action="<?= $acaoPortaria ?>" class="mt-2">
                                <?= camposPortaria($csrf, "painel") ?>
                                <input type="hidden" name="ref" value="<?= h($ref) ?>">
                                <label for="motivo-negar" class="form-label">Motivo</label>
                                <input type="text" id="motivo-negar" name="motivo" class="form-control form-control-lg mb-2"
                                       maxlength="120" required>
                                <button type="submit" name="acao" value="negar" class="btn btn-danger btn-acao w-100">Confirmar negativa</button>
                            </form>
                        </details>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </article>
<?php endif; ?>

<!-- ============ RESUMO DO PERÍODO ============ -->
<section aria-labelledby="titulo-resumo" class="mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h2 id="titulo-resumo" class="h5 mb-0">Resumo</h2>

        <div class="btn-group btn-group-sm" role="group" aria-label="Período do resumo">
            <?php foreach($periodos as $chave => $rotulo): ?>
                <a href="portaria.php?aba=painel&amp;periodo=<?= $chave ?>"
                   class="btn btn-outline-laranja <?= $periodo === $chave ? "active" : "" ?>"
                   <?= $periodo === $chave ? 'aria-current="true"' : "" ?>><?= $rotulo ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-6 col-xl-3">
            <div class="card-painel h-100">
                <small class="text-secondary">Entradas (<?= h(strtolower($periodos[$periodo])) ?>)</small>
                <div class="indicador-numero"><?= $entradasPeriodo ?></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card-painel h-100">
                <small class="text-secondary">Dentro do clube agora</small>
                <div class="indicador-numero"><?= count($dentro) ?></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card-painel h-100 position-relative">
                <small class="text-secondary">Veículos presentes</small>
                <div class="indicador-numero"><?= count($veiculosDentro) ?><small class="fs-6 text-secondary"> / <?= CAPACIDADE_ESTACIONAMENTO ?></small></div>
                <a href="portaria.php?aba=veiculos" class="stretched-link small">Ver veículos</a>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card-painel h-100 position-relative">
                <small class="text-secondary">Aguardando autorização</small>
                <div class="indicador-numero"><?= count($pendentes) ?></div>
                <a href="portaria.php?aba=visitantes" class="stretched-link small">Ver visitantes</a>
            </div>
        </div>
    </div>
</section>

<div class="row g-3 mb-4">

    <!-- ============ DENTRO AGORA ============ -->
    <section class="col-12 col-xl-7" aria-labelledby="titulo-dentro">
        <div class="card-painel h-100">
            <h2 id="titulo-dentro" class="h5 mb-3">Dentro do clube agora</h2>

            <?php if(empty($dentro)): ?>
                <p class="mb-0 text-secondary">Ninguém registrado dentro do clube. Use a busca acima para registrar uma entrada.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 tabela-cartoes">
                        <thead>
                            <tr><th>Nome</th><th>Tipo</th><th>Entrada</th><th>Permanência</th><th><span class="visually-hidden">Ação</span></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($dentro as $a): ?>
                                <tr>
                                    <td data-label="Nome"><strong><?= h($a["nome"]) ?></strong></td>
                                    <td data-label="Tipo"><?= h($a["rotulo"]) ?></td>
                                    <td data-label="Entrada"><?= fmtMomento($a["entrada_em"]) ?></td>
                                    <td data-label="Permanência"><?= fmtPermanencia($a["entrada_em"], null) ?></td>
                                    <td class="celula-acao">
                                        <form method="POST" action="<?= $acaoPortaria ?>">
                                            <?= camposPortaria($csrf, "painel") ?>
                                            <input type="hidden" name="ref" value="<?= h($a["pessoa_ref"]) ?>">
                                            <button type="submit" name="acao" value="saida" class="btn btn-outline-laranja btn-sm btn-acao-tabela">Registrar saída</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ============ ALERTAS ============ -->
    <section class="col-12 col-xl-5" aria-labelledby="titulo-alertas">
        <div class="card-painel h-100">
            <h2 id="titulo-alertas" class="h5 mb-3">
                Alertas
                <?php if(!empty($alertas)): ?><span class="badge text-bg-danger ms-1"><?= count($alertas) ?></span><?php endif; ?>
            </h2>

            <?php if(empty($alertas)): ?>
                <p class="mb-0 text-secondary"><i class="bi bi-check-circle me-1"></i>Nenhum alerta no momento.</p>
            <?php else: ?>
                <ul class="lista-alertas">
                    <?php foreach($alertas as $alerta): ?>
                        <li class="alerta-item alerta-<?= h($alerta["nivel"]) ?>">
                            <i class="bi <?= h($alerta["icone"]) ?>"></i>
                            <span>
                                <?= h($alerta["texto"]) ?>
                                <?php if(!empty($alerta["link"])): ?>
                                    <a href="<?= h($alerta["link"][0]) ?>"><?= h($alerta["link"][1]) ?></a>
                                <?php endif; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- ============ ACESSOS RECENTES ============ -->
<section class="card-painel" aria-labelledby="titulo-recentes">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 id="titulo-recentes" class="h5 mb-0">Acessos recentes</h2>
        <a href="portaria.php?aba=acessos" class="small">Ver histórico</a>
    </div>

    <?php if(empty($recentes)): ?>
        <p class="mb-0 text-secondary">Nenhum acesso registrado ainda.</p>
    <?php else: ?>
        <?php
            $tiposEvento = [
                "entrada" => ["bi-box-arrow-in-right", "evento-entrada", "Entrada"],
                "saida"   => ["bi-box-arrow-left",     "evento-saida",   "Saída"],
                "negado"  => ["bi-slash-circle",       "evento-negado",  "Negado"],
            ];
        ?>
        <ul class="lista-eventos">
            <?php foreach($recentes as $ev): ?>
                <?php $t = $tiposEvento[$ev["tipo"]]; ?>
                <li>
                    <i class="bi <?= $t[0] ?> <?= $t[1] ?>" title="<?= $t[2] ?>"></i>
                    <span class="evento-texto">
                        <strong><?= h($ev["nome"]) ?></strong>
                        <small class="text-secondary d-block">
                            <?= $t[2] ?> · <?= h($ev["rotulo"]) ?><?= $ev["detalhe"] !== "" ? " · " . h($ev["detalhe"]) : "" ?>
                        </small>
                    </span>
                    <time class="evento-hora"><?= fmtMomento($ev["ts"]) ?></time>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
