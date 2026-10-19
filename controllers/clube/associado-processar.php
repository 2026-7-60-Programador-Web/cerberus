<?php
// Cadastro, edição e exclusão de associados — sempre dentro do clube da sessão.

require_once __DIR__ . '/../../core/clube-acesso.php';
require_once __DIR__ . '/../../core/flash.php';

exigirAdminClube('../../views/autenticacao/login.php');

$lista = '../../views/clube/associados.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $lista);
    exit;
}

$clubeId = clubeIdDaSessao(); // NUNCA vem do formulário

function clVoltar(string $url, string $chave, string $tipo, array $textos, array $antigo = []): void
{
    flashDefinir($chave, $tipo, $textos);
    if (!empty($antigo)) {
        antigoDefinir('clube_associado', $antigo);
    }
    header('Location: ' . $url);
    exit;
}

if (!clubeCsrfValido($_POST['csrf'] ?? null)) {
    clVoltar($lista, 'clube_associados', 'danger', ['A sessão expirou. Atualize a página e tente de novo.']);
}

require_once __DIR__ . '/../../core/conexao.php';
require_once __DIR__ . '/../../models/associados-dados.php';

$acao = (string)($_POST['acao'] ?? '');
$id   = (int)($_POST['id'] ?? 0);

try {
    switch ($acao) {

        /* ================= CRIAR / EDITAR ================= */
        case 'criar':
        case 'editar':
            $editando = ($acao === 'editar');
            $form = $editando ? '../../views/clube/associado-form.php?id=' . $id : '../../views/clube/associado-form.php';

            // só edita quem é do próprio clube
            if ($editando && !associadoBuscar($clubeId, $id)) {
                clVoltar($lista, 'clube_associados', 'danger', ['Associado não encontrado.']);
            }

            $matricula = limparTexto($_POST['matricula'] ?? '', 20);
            $email     = mb_strtolower(limparTexto($_POST['email'] ?? '', 150), 'UTF-8');

            $dados = [
                'nome'       => limparTexto($_POST['nome'] ?? '', 120),
                'cpf'        => apenasDigitos($_POST['cpf'] ?? ''),
                'email'      => $email === '' ? null : $email,
                'telefone'   => apenasDigitos($_POST['telefone'] ?? ''),
                'nascimento' => (string)($_POST['nascimento'] ?? ''),
                'matricula'  => $matricula === '' ? null : $matricula,
                'situacao'   => (string)($_POST['situacao'] ?? 'ativo'),
            ];

            $erros = [];
            if (!validarNomeCompleto($dados['nome']))                   $erros[] = 'Informe o nome completo do associado.';
            if (!validarCpf($dados['cpf']))                             $erros[] = 'CPF inválido.';
            if ($dados['email'] !== null && !validarEmail($dados['email'])) $erros[] = 'E-mail inválido.';
            if (!validarTelefone($dados['telefone']))                   $erros[] = 'Telefone inválido (use DDD + número).';
            if (!validarDataNascimento($dados['nascimento']))           $erros[] = 'Data de nascimento inválida.';
            if ($dados['matricula'] !== null && !preg_match('/^[A-Za-z0-9._\/-]{1,20}$/', $dados['matricula'])) {
                $erros[] = 'A matrícula aceita apenas letras, números e . - / _';
            }
            if (!isset(SITUACOES_ASSOCIADO[$dados['situacao']]))        $erros[] = 'Situação inválida.';

            if (empty($erros)) {
                $erros = associadoDuplicidades($clubeId, $dados['cpf'], $dados['matricula'], $editando ? $id : 0);
            }

            if (!empty($erros)) {
                clVoltar($form, 'clube_form', 'danger', $erros, $_POST);
            }

            try {
                if ($editando) {
                    associadoAtualizar($clubeId, $id, $dados);
                    clVoltar($lista, 'clube_associados', 'success', ['Associado atualizado com sucesso!']);
                }

                associadoCriar($clubeId, $dados);
                clVoltar($lista, 'clube_associados', 'success', ['Associado cadastrado com sucesso!']);
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') { // CPF/matrícula duplicados por corrida
                    clVoltar($form, 'clube_form', 'danger', ['CPF ou matrícula já cadastrado no seu clube.'], $_POST);
                }
                throw $e;
            }
            break;

        /* ================= EXCLUIR ================= */
        case 'excluir':
            if (associadoExcluir($clubeId, $id)) {
                clVoltar($lista, 'clube_associados', 'success', ['Associado excluído.']);
            }
            clVoltar($lista, 'clube_associados', 'danger', ['Associado não encontrado.']);
            break;

        default:
            clVoltar($lista, 'clube_associados', 'danger', ['Ação inválida.']);
    }
} catch (PDOException $e) {
    error_log('Cerberus associados: ' . $e->getMessage());
    clVoltar($lista, 'clube_associados', 'danger', ['Não foi possível concluir a operação agora. Tente novamente.']);
}
