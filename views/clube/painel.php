<?php
// Painel inicial do administrador do clube.

require_once __DIR__ . '/../../core/clube-acesso.php';
require_once __DIR__ . '/../../core/flash.php';

exigirAdminClube('../autenticacao/login.php');

require_once __DIR__ . '/../../core/conexao.php';
require_once __DIR__ . '/../../models/clubes-dados.php';
require_once __DIR__ . '/../../models/associados-dados.php';

$clubeId   = clubeIdDaSessao();
$clube     = clubeBuscar($clubeId);
$contagem  = associadosContar($clubeId);
$recentes  = associadosRecentes($clubeId, 5);
$mensagem  = flashObter('clube_painel');

if (!$clube) { // clube removido: encerra a sessão
    header('Location: ../../controllers/logout.php');
    exit;
}

$tituloPagina = 'Painel';
$menuAtivo    = 'painel';
include __DIR__ . '/partials/topo.php';
?>

<div class="row g-3 mb-3">
    <?php
    $indicadores = [
        ['Total de associados', $contagem['total'],    'bi-people'],
        ['Ativos',              $contagem['ativo'],    'bi-check-circle'],
        ['Inativos',            $contagem['inativo'],  'bi-dash-circle'],
        ['Suspensos',           $contagem['suspenso'], 'bi-slash-circle'],
    ];
    foreach ($indicadores as [$rotulo, $valor, $icone]): ?>
        <div class="col-6 col-xl-3">
            <div class="card-painel h-100">
                <div class="small opacity-75"><i class="bi <?= $icone; ?> me-1"></i><?= $rotulo; ?></div>
                <div class="indicador-numero"><?= (int)$valor; ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card-painel h-100">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h1 class="h5 mb-0">Últimos associados</h1>
                <a href="associado-form.php" class="btn btn-laranja btn-sm"><i class="bi bi-person-plus me-1"></i>Novo associado</a>
            </div>

            <?php if (empty($recentes)): ?>
                <p class="mb-0 opacity-75">Nenhum associado cadastrado ainda. Comece cadastrando o primeiro!</p>
            <?php else: ?>
                <ul class="lista-eventos">
                    <?php foreach ($recentes as $a): ?>
                        <li>
                            <i class="bi bi-person-circle"></i>
                            <span class="evento-texto text-truncate"><?= clEsc($a['nome']); ?></span>
                            <span class="badge <?= $a['situacao'] === 'ativo' ? 'text-bg-success' : ($a['situacao'] === 'suspenso' ? 'text-bg-danger' : 'text-bg-secondary'); ?>">
                                <?= clEsc(SITUACOES_ASSOCIADO[$a['situacao']]); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="associados.php" class="d-inline-block mt-3">Ver todos os associados</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card-painel h-100">
            <h2 class="h5 mb-3">Dados do clube</h2>
            <dl class="dados-pessoa mb-0">
                <div class="dados-largo"><dt>Nome</dt><dd><?= clEsc($clube['nome']); ?></dd></div>
                <div><dt>CNPJ</dt><dd><?= clEsc(clFmtCnpj($clube['cnpj'])); ?></dd></div>
                <div><dt>Telefone</dt><dd><?= clEsc(clFmtTelefone($clube['telefone'])); ?></dd></div>
                <div class="dados-largo"><dt>E-mail</dt><dd><?= clEsc($clube['email']); ?></dd></div>
                <div class="dados-largo">
                    <dt>Endereço</dt>
                    <dd><?= clEsc($clube['endereco']); ?> — <?= clEsc($clube['cidade']); ?>/<?= clEsc($clube['uf']); ?>, CEP <?= clEsc(clFmtCep($clube['cep'])); ?></dd>
                </div>
            </dl>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/rodape.php'; ?>
