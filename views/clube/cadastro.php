<?php
// Cadastro público do clube (não exige login).

require_once __DIR__ . '/../../core/clube-acesso.php';
require_once __DIR__ . '/../../core/flash.php';

clubeIniciarSessao();

if (clubeLogado()) {
    header('Location: painel.php');
    exit;
}

$mensagem = flashObter('clube_cadastro');
$antigo   = antigoObter('clube_cadastro');

function clValor(array $antigo, string $campo): string
{
    return clEsc($antigo[$campo] ?? '');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastre seu clube — Cerberus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
    <link href="../../assets/css/clube.css" rel="stylesheet">
</head>
<body class="login-pagina clube-cadastro-pagina">

    <button id="btnTema" type="button" class="btn btn-tema login-tema" title="Alternar tema">🌙</button>

    <main class="login-container clube-cadastro-container">

        <img src="../../assets/img/logo-cerberus-tres-lobos.svg" alt="Logo Cerberus" class="login-logo">
        <h1 class="login-marca">Cerberus</h1>
        <p class="login-subtitulo">Cadastre seu clube e comece a gerenciar seus associados</p>

        <div class="card login-card">
            <div class="card-body p-3 p-sm-4">

                <?php if ($mensagem): ?>
                    <div class="alert login-alerta py-2" role="alert">
                        <?php foreach ($mensagem['textos'] as $texto): ?>
                            <div><i class="bi bi-exclamation-circle me-1"></i><?= clEsc($texto); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form id="formCadastroClube" action="../../controllers/clube/cadastro-processar.php"
                      method="POST" class="needs-validation" novalidate autocomplete="off">
                    <?= clubeCsrfCampo(); ?>

                    <!-- ============ DADOS DO CLUBE ============ -->
                    <h2 class="h6 clube-secao"><i class="bi bi-building me-2"></i>Dados do clube</h2>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-8">
                            <label for="clube_nome" class="form-label">Nome do clube</label>
                            <input type="text" class="form-control" id="clube_nome" name="clube_nome"
                                   maxlength="120" minlength="3" required value="<?= clValor($antigo, 'clube_nome'); ?>">
                            <div class="invalid-feedback">Informe o nome do clube.</div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="cnpj" class="form-label">CNPJ</label>
                            <input type="text" class="form-control" id="cnpj" name="cnpj" data-mascara="cnpj"
                                   inputmode="numeric" maxlength="18" required value="<?= clValor($antigo, 'cnpj'); ?>">
                            <div class="invalid-feedback">CNPJ inválido.</div>
                        </div>

                        <div class="col-12 col-md-7">
                            <label for="clube_email" class="form-label">E-mail de contato</label>
                            <input type="email" class="form-control" id="clube_email" name="clube_email"
                                   maxlength="150" required value="<?= clValor($antigo, 'clube_email'); ?>">
                            <div class="invalid-feedback">Informe um e-mail válido.</div>
                        </div>

                        <div class="col-12 col-md-5">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="tel" class="form-control" id="telefone" name="telefone" data-mascara="telefone"
                                   inputmode="numeric" maxlength="15" required value="<?= clValor($antigo, 'telefone'); ?>">
                            <div class="invalid-feedback">Informe DDD + número.</div>
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="cep" class="form-label">CEP</label>
                            <input type="text" class="form-control" id="cep" name="cep" data-mascara="cep"
                                   inputmode="numeric" maxlength="9" required value="<?= clValor($antigo, 'cep'); ?>">
                            <div class="invalid-feedback">CEP inválido.</div>
                        </div>

                        <div class="col-12 col-md-9">
                            <label for="endereco" class="form-label">Endereço <span class="text-muted fw-normal">(rua, número e bairro)</span></label>
                            <input type="text" class="form-control" id="endereco" name="endereco"
                                   maxlength="200" minlength="5" required value="<?= clValor($antigo, 'endereco'); ?>">
                            <div class="invalid-feedback">Informe o endereço.</div>
                        </div>

                        <div class="col-12 col-sm-8">
                            <label for="cidade" class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="cidade" name="cidade"
                                   maxlength="80" minlength="2" required value="<?= clValor($antigo, 'cidade'); ?>">
                            <div class="invalid-feedback">Informe a cidade.</div>
                        </div>

                        <div class="col-12 col-sm-4">
                            <label for="uf" class="form-label">Estado</label>
                            <select class="form-select" id="uf" name="uf" required>
                                <option value="">Selecione</option>
                                <?php foreach (UFS_VALIDAS as $uf): ?>
                                    <option value="<?= $uf; ?>" <?= ($antigo['uf'] ?? '') === $uf ? 'selected' : ''; ?>><?= $uf; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Selecione o estado.</div>
                        </div>
                    </div>

                    <!-- ============ ADMINISTRADOR ============ -->
                    <h2 class="h6 clube-secao"><i class="bi bi-person-badge me-2"></i>Administrador do clube</h2>
                    <p class="small opacity-75">Esta será a conta de acesso do clube. Com ela você cadastra e gerencia os associados.</p>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label for="admin_nome" class="form-label">Nome completo</label>
                            <input type="text" class="form-control" id="admin_nome" name="admin_nome"
                                   maxlength="120" required value="<?= clValor($antigo, 'admin_nome'); ?>">
                            <div class="invalid-feedback">Informe nome e sobrenome.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="admin_email" class="form-label">E-mail de acesso</label>
                            <input type="email" class="form-control" id="admin_email" name="admin_email"
                                   maxlength="150" autocomplete="username" required value="<?= clValor($antigo, 'admin_email'); ?>">
                            <div class="invalid-feedback">Informe um e-mail válido.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="senha" class="form-label">Senha</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="senha" name="senha"
                                       minlength="8" maxlength="72" autocomplete="new-password" required>
                                <button type="button" class="btn btn-outline-laranja" data-alternar-senha="senha"
                                        title="Mostrar/ocultar senha" aria-label="Mostrar ou ocultar senha">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <div class="invalid-feedback">Mínimo de 8 caracteres, com letras e números.</div>
                            </div>
                            <div class="form-text">Mínimo de 8 caracteres, com letras e números.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="confirmar_senha" class="form-label">Confirmar senha</label>
                            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha"
                                   minlength="8" maxlength="72" autocomplete="new-password" required>
                            <div class="invalid-feedback">As senhas não coincidem.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login w-100">
                        <i class="bi bi-check2-circle me-1"></i>Cadastrar clube e entrar
                    </button>

                    <p class="text-center small mt-3 mb-0">
                        Já tem uma conta? <a href="../autenticacao/login.php">Entrar</a>
                    </p>
                </form>

            </div>
        </div>
    </main>

    <script src="../../assets/js/script.js"></script>
    <script src="../../assets/js/clube.js"></script>
</body>
</html>
