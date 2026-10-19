<?php
// Lista de associados do clube logado: busca, filtro, paginação e exclusão.

require_once __DIR__ . '/../../core/clube-acesso.php';
require_once __DIR__ . '/../../core/flash.php';

exigirAdminClube('../autenticacao/login.php');

require_once __DIR__ . '/../../core/conexao.php';
require_once __DIR__ . '/../../models/associados-dados.php';

const ASSOCIADOS_POR_PAGINA = 10;

$clubeId  = clubeIdDaSessao();
$mensagem = flashObter('clube_associados');

$busca    = limparTexto($_GET['q'] ?? '', 100);
$situacao = (string)($_GET['situacao'] ?? '');
$pagina   = max(1, (int)($_GET['pagina'] ?? 1));

$resultado = associadosListar($clubeId, $busca, $situacao, $pagina, ASSOCIADOS_POR_PAGINA);

// mantém busca/filtro nos links da paginação
$consulta = function (int $p) use ($busca, $situacao): string {
    return '?' . http_build_query(array_filter(['q' => $busca, 'situacao' => $situacao, 'pagina' => $p]));
};

$classeSituacao = ['ativo' => 'text-bg-success', 'inativo' => 'text-bg-secondary', 'suspenso' => 'text-bg-danger'];

$tituloPagina = 'Associados';
$menuAtivo    = 'associados';
include __DIR__ . '/partials/topo.php';
?>

<div class="card-painel">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h4 mb-0">Associados <span class="badge text-bg-light border"><?= (int)$resultado['total']; ?></span></h1>
        <a href="associado-form.php" class="btn btn-laranja"><i class="bi bi-person-plus me-1"></i>Novo associado</a>
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-12 col-md">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" name="q" class="form-control" maxlength="100"
                       placeholder="Buscar por nome, CPF, e-mail ou matrícula" value="<?= clEsc($busca); ?>">
            </div>
        </div>
        <div class="col-7 col-md-3">
            <select name="situacao" class="form-select" aria-label="Filtrar por situação">
                <option value="">Todas as situações</option>
                <?php foreach (SITUACOES_ASSOCIADO as $chave => $rotulo): ?>
                    <option value="<?= $chave; ?>" <?= $situacao === $chave ? 'selected' : ''; ?>><?= $rotulo; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-5 col-md-auto d-flex gap-2">
            <button type="submit" class="btn btn-outline-laranja flex-grow-1">Filtrar</button>
            <?php if ($busca !== '' || $situacao !== ''): ?>
                <a href="associados.php" class="btn btn-outline-secondary" title="Limpar filtros"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($resultado['itens'])): ?>
        <p class="mb-0 opacity-75 py-4 text-center">
            <?= ($busca !== '' || $situacao !== '') ? 'Nenhum associado encontrado com esses filtros.' : 'Nenhum associado cadastrado ainda.'; ?>
        </p>
    <?php else: ?>
        <table class="table align-middle tabela-cartoes mb-0">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Telefone</th>
                    <th>Matrícula</th>
                    <th>Situação</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultado['itens'] as $a): ?>
                    <tr>
                        <td data-label="Nome">
                            <strong><?= clEsc($a['nome']); ?></strong>
                            <?php if ($a['email']): ?><div class="small opacity-75"><?= clEsc($a['email']); ?></div><?php endif; ?>
                        </td>
                        <td data-label="CPF"><?= clEsc(clFmtCpf($a['cpf'])); ?></td>
                        <td data-label="Telefone"><?= clEsc(clFmtTelefone($a['telefone'])); ?></td>
                        <td data-label="Matrícula"><?= $a['matricula'] !== null ? clEsc($a['matricula']) : '—'; ?></td>
                        <td data-label="Situação">
                            <span class="badge <?= $classeSituacao[$a['situacao']] ?? 'text-bg-secondary'; ?>"><?= clEsc(SITUACOES_ASSOCIADO[$a['situacao']] ?? $a['situacao']); ?></span>
                        </td>
                        <td class="celula-acao text-md-end">
                            <div class="d-flex gap-2 justify-content-md-end">
                                <a href="associado-form.php?id=<?= (int)$a['id']; ?>" class="btn btn-outline-laranja btn-sm btn-acao-tabela flex-fill flex-md-grow-0">
                                    <i class="bi bi-pencil-square me-1"></i>Editar
                                </a>
                                <button type="button" class="btn btn-outline-danger btn-sm btn-acao-tabela flex-fill flex-md-grow-0"
                                        data-bs-toggle="modal" data-bs-target="#modalExcluir"
                                        data-id="<?= (int)$a['id']; ?>" data-nome="<?= clEsc($a['nome']); ?>">
                                    <i class="bi bi-trash me-1"></i>Excluir
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($resultado['paginas'] > 1): ?>
            <nav class="mt-3" aria-label="Paginação">
                <ul class="pagination pagination-sm justify-content-center flex-wrap mb-0">
                    <li class="page-item <?= $resultado['pagina'] <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?= clEsc($consulta($resultado['pagina'] - 1)); ?>">Anterior</a>
                    </li>
                    <li class="page-item disabled">
                        <span class="page-link">Página <?= (int)$resultado['pagina']; ?> de <?= (int)$resultado['paginas']; ?></span>
                    </li>
                    <li class="page-item <?= $resultado['pagina'] >= $resultado['paginas'] ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?= clEsc($consulta($resultado['pagina'] + 1)); ?>">Próxima</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- confirmação de exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1" aria-labelledby="modalExcluirTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="../../controllers/clube/associado-processar.php">
            <?= clubeCsrfCampo(); ?>
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" name="id" id="excluirId" value="">

            <div class="modal-header">
                <h2 class="modal-title h5" id="modalExcluirTitulo">Excluir associado</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                Tem certeza que deseja excluir <strong id="excluirNome"></strong>? Essa ação não pode ser desfeita.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Excluir</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/partials/rodape.php'; ?>
