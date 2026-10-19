<?php
// Formulário de associado: cadastro (sem ?id) ou edição (?id=). Só abre associados do próprio clube.

require_once __DIR__ . '/../../core/clube-acesso.php';
require_once __DIR__ . '/../../core/flash.php';

exigirAdminClube('../autenticacao/login.php');

require_once __DIR__ . '/../../core/conexao.php';
require_once __DIR__ . '/../../models/associados-dados.php';

$clubeId  = clubeIdDaSessao();
$id       = (int)($_GET['id'] ?? 0);
$editando = $id > 0;
$mensagem = flashObter('clube_form');
$antigo   = antigoObter('clube_associado');

$associado = [];
if ($editando) {
    $associado = associadoBuscar($clubeId, $id); // filtra pelo clube da sessão

    if (!$associado) {
        flashDefinir('clube_associados', 'danger', ['Associado não encontrado.']);
        header('Location: associados.php');
        exit;
    }
}

// valores do campo: o que foi digitado (se houve erro) > o que está no banco > vazio
function clCampo(array $antigo, array $associado, string $campo, string $padrao = ''): string
{
    if (array_key_exists($campo, $antigo)) {
        return clEsc($antigo[$campo]);
    }
    return clEsc($associado[$campo] ?? $padrao);
}

$situacaoAtual = $antigo['situacao'] ?? ($associado['situacao'] ?? 'ativo');

$tituloPagina = $editando ? 'Editar associado' : 'Novo associado';
$menuAtivo    = $editando ? 'associados' : 'novo';
include __DIR__ . '/partials/topo.php';
?>

<div class="card-painel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h4 mb-0"><?= $editando ? 'Editar associado' : 'Novo associado'; ?></h1>
        <a href="associados.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Voltar à lista</a>
    </div>

    <form id="formAssociado" action="../../controllers/clube/associado-processar.php" method="POST"
          class="needs-validation" novalidate autocomplete="off">
        <?= clubeCsrfCampo(); ?>
        <input type="hidden" name="acao" value="<?= $editando ? 'editar' : 'criar'; ?>">
        <?php if ($editando): ?><input type="hidden" name="id" value="<?= $id; ?>"><?php endif; ?>

        <div class="row g-3">
            <div class="col-12 col-md-8">
                <label for="nome" class="form-label">Nome completo</label>
                <input type="text" class="form-control" id="nome" name="nome" maxlength="120" required
                       value="<?= clCampo($antigo, $associado, 'nome'); ?>">
                <div class="invalid-feedback">Informe nome e sobrenome.</div>
            </div>

            <div class="col-12 col-md-4">
                <label for="cpf" class="form-label">CPF</label>
                <input type="text" class="form-control" id="cpf" name="cpf" data-mascara="cpf" inputmode="numeric"
                       maxlength="14" required value="<?= clEsc(clFmtCpf(apenasDigitos($antigo['cpf'] ?? ($associado['cpf'] ?? '')))); ?>">
                <div class="invalid-feedback">CPF inválido.</div>
            </div>

            <div class="col-12 col-md-6">
                <label for="email" class="form-label">E-mail <span class="text-muted fw-normal">(opcional)</span></label>
                <input type="email" class="form-control" id="email" name="email" maxlength="150"
                       value="<?= clCampo($antigo, $associado, 'email'); ?>">
                <div class="invalid-feedback">Informe um e-mail válido.</div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <label for="telefone" class="form-label">Telefone</label>
                <input type="tel" class="form-control" id="telefone" name="telefone" data-mascara="telefone"
                       inputmode="numeric" maxlength="15" required
                       value="<?= clEsc(clFmtTelefone(apenasDigitos($antigo['telefone'] ?? ($associado['telefone'] ?? '')))); ?>">
                <div class="invalid-feedback">Informe DDD + número.</div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <label for="nascimento" class="form-label">Data de nascimento</label>
                <input type="date" class="form-control" id="nascimento" name="nascimento" min="1900-01-01"
                       max="<?= date('Y-m-d'); ?>" required value="<?= clCampo($antigo, $associado, 'nascimento'); ?>">
                <div class="invalid-feedback">Informe uma data válida.</div>
            </div>

            <div class="col-12 col-sm-6 col-md-4">
                <label for="matricula" class="form-label">Matrícula <span class="text-muted fw-normal">(opcional)</span></label>
                <input type="text" class="form-control" id="matricula" name="matricula" maxlength="20"
                       pattern="[A-Za-z0-9._\/\-]{1,20}" value="<?= clCampo($antigo, $associado, 'matricula'); ?>">
                <div class="invalid-feedback">Use apenas letras, números e . - / _</div>
            </div>

            <div class="col-12 col-sm-6 col-md-4">
                <label for="situacao" class="form-label">Situação</label>
                <select class="form-select" id="situacao" name="situacao" required>
                    <?php foreach (SITUACOES_ASSOCIADO as $chave => $rotulo): ?>
                        <option value="<?= $chave; ?>" <?= $situacaoAtual === $chave ? 'selected' : ''; ?>><?= $rotulo; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="d-flex flex-column-reverse flex-sm-row justify-content-end gap-2 mt-4">
            <a href="associados.php" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-laranja">
                <i class="bi bi-check2 me-1"></i><?= $editando ? 'Salvar alterações' : 'Cadastrar associado'; ?>
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/partials/rodape.php'; ?>
