<?php
// Cadastro público de clube: cria o clube + a conta de administrador e já abre a sessão.

require_once __DIR__ . '/../../core/clube-acesso.php';
require_once __DIR__ . '/../../core/flash.php';

clubeIniciarSessao();

$formulario = '../../views/clube/cadastro.php';
$painel     = '../../views/clube/painel.php';

function clVoltarCadastro(string $url, array $erros, array $antigo): void
{
    flashDefinir('clube_cadastro', 'danger', $erros);
    antigoDefinir('clube_cadastro', $antigo);
    header('Location: ' . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $formulario);
    exit;
}

/* ---------- dados recebidos (higienizados) ---------- */
$clube = [
    'nome'     => limparTexto($_POST['clube_nome'] ?? '', 120),
    'cnpj'     => apenasDigitos($_POST['cnpj'] ?? ''),
    'email'    => mb_strtolower(limparTexto($_POST['clube_email'] ?? '', 150), 'UTF-8'),
    'telefone' => apenasDigitos($_POST['telefone'] ?? ''),
    'cep'      => apenasDigitos($_POST['cep'] ?? ''),
    'endereco' => limparTexto($_POST['endereco'] ?? '', 200),
    'cidade'   => limparTexto($_POST['cidade'] ?? '', 80),
    'uf'       => strtoupper(limparTexto($_POST['uf'] ?? '', 2)),
];

$adminNome  = limparTexto($_POST['admin_nome'] ?? '', 120);
$adminEmail = mb_strtolower(limparTexto($_POST['admin_email'] ?? '', 150), 'UTF-8');
$senha      = (string)($_POST['senha'] ?? '');
$confirmar  = (string)($_POST['confirmar_senha'] ?? '');

// o que volta preenchido se der erro (nunca as senhas)
$antigo = [
    'clube_nome' => $clube['nome'], 'cnpj' => $clube['cnpj'], 'clube_email' => $clube['email'],
    'telefone' => $clube['telefone'], 'cep' => $clube['cep'], 'endereco' => $clube['endereco'],
    'cidade' => $clube['cidade'], 'uf' => $clube['uf'],
    'admin_nome' => $adminNome, 'admin_email' => $adminEmail,
];

if (!clubeCsrfValido($_POST['csrf'] ?? null)) {
    clVoltarCadastro($formulario, ['A sessão expirou. Atualize a página e tente de novo.'], $antigo);
}

/* ---------- validação no servidor ---------- */
$erros = [];

if (mb_strlen($clube['nome'], 'UTF-8') < 3)  $erros[] = 'Informe o nome do clube (mínimo 3 caracteres).';
if (!validarCnpj($clube['cnpj']))             $erros[] = 'CNPJ inválido.';
if (!validarEmail($clube['email']))           $erros[] = 'Informe um e-mail de contato válido para o clube.';
if (!validarTelefone($clube['telefone']))     $erros[] = 'Telefone inválido (use DDD + número).';
if (!validarCep($clube['cep']))               $erros[] = 'CEP inválido.';
if (mb_strlen($clube['endereco'], 'UTF-8') < 5) $erros[] = 'Informe o endereço (rua, número e bairro).';
if (mb_strlen($clube['cidade'], 'UTF-8') < 2) $erros[] = 'Informe a cidade.';
if (!in_array($clube['uf'], UFS_VALIDAS, true)) $erros[] = 'Selecione o estado (UF).';

if (!validarNomeCompleto($adminNome))         $erros[] = 'Informe o nome completo do administrador.';
if (!validarEmail($adminEmail))               $erros[] = 'Informe um e-mail de acesso válido para o administrador.';

$erros = array_merge($erros, errosDaSenha($senha));
if ($senha !== $confirmar)                    $erros[] = 'As senhas não coincidem.';

if (!empty($erros)) {
    clVoltarCadastro($formulario, $erros, $antigo);
}

/* ---------- banco de dados ---------- */
require_once __DIR__ . '/../../core/conexao.php';
require_once __DIR__ . '/../../models/clubes-dados.php';

try {
    if (clubeCnpjExiste($clube['cnpj'])) {
        $erros[] = 'Já existe um clube cadastrado com este CNPJ.';
    }
    if (adminEmailExiste($adminEmail)) {
        $erros[] = 'Este e-mail de acesso já está em uso. Use outro e-mail ou entre com a conta existente.';
    }
    if (!empty($erros)) {
        clVoltarCadastro($formulario, $erros, $antigo);
    }

    $admin = clubeCadastrar($clube, [
        'nome'       => $adminNome,
        'email'      => $adminEmail,
        'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
    ]);
} catch (PDOException $e) {
    // 23000 = violação de UNIQUE (duas pessoas cadastrando ao mesmo tempo)
    if ($e->getCode() === '23000') {
        clVoltarCadastro($formulario, ['CNPJ ou e-mail de acesso já cadastrado.'], $antigo);
    }
    error_log('Cerberus cadastro de clube: ' . $e->getMessage());
    clVoltarCadastro($formulario, ['Não foi possível concluir o cadastro agora. Tente novamente em instantes.'], $antigo);
}

/* ---------- acesso imediato ---------- */
clubeAbrirSessao($admin, $clube['nome']);
flashDefinir('clube_painel', 'success', ['Clube cadastrado com sucesso! Você já está dentro da plataforma.']);

header('Location: ' . $painel);
exit;
