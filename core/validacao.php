<?php
// Validação, higienização e formatação de dados de formulário.

const UFS_VALIDAS = [
    'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
    'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
];

/* ---------------------- higienização ---------------------- */

function apenasDigitos($valor): string
{
    return preg_replace('/\D+/', '', (string)$valor) ?? '';
}

function limparTexto($valor, int $max = 255): string
{
    $texto = trim(preg_replace('/\s+/u', ' ', (string)$valor) ?? '');
    return mb_substr($texto, 0, $max, 'UTF-8');
}

/* ---------------------- validação ---------------------- */

function validarEmail(string $email): bool
{
    return strlen($email) <= 150 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validarNomeCompleto(string $nome): bool
{
    return count(preg_split('/\s+/u', trim($nome))) >= 2 && mb_strlen($nome, 'UTF-8') >= 5;
}

function validarCpf(string $cpf): bool
{
    $cpf = apenasDigitos($cpf);

    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) {
            $soma += (int)$cpf[$i] * (($t + 1) - $i);
        }
        if ((int)$cpf[$t] !== ((10 * $soma) % 11) % 10) {
            return false;
        }
    }

    return true;
}

function validarCnpj(string $cnpj): bool
{
    $c = apenasDigitos($cnpj);

    if (strlen($c) !== 14 || preg_match('/^(\d)\1{13}$/', $c)) {
        return false;
    }

    $pesos = [
        12 => [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        13 => [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
    ];

    foreach ($pesos as $posicao => $lista) {
        $soma = 0;
        foreach ($lista as $i => $peso) {
            $soma += (int)$c[$i] * $peso;
        }
        $resto = $soma % 11;
        $digito = $resto < 2 ? 0 : 11 - $resto;

        if ((int)$c[$posicao] !== $digito) {
            return false;
        }
    }

    return true;
}

function validarTelefone(string $telefone): bool
{
    $t = apenasDigitos($telefone);

    if (!in_array(strlen($t), [10, 11], true)) {
        return false;
    }
    if ((int)substr($t, 0, 2) < 11) {
        return false;
    }

    return strlen($t) === 10 || $t[2] === '9';
}

function validarCep(string $cep): bool
{
    return strlen(apenasDigitos($cep)) === 8;
}

// data no formato AAAA-MM-DD, real, não futura e depois de 1900
function validarDataNascimento(string $data): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $data);

    if (!$d || $d->format('Y-m-d') !== $data) {
        return false;
    }

    return $d <= new DateTime('today') && (int)$d->format('Y') >= 1900;
}

// devolve a lista de problemas da senha (vazia = senha aceita)
function errosDaSenha(string $senha): array
{
    $erros = [];

    if (strlen($senha) < 8) {
        $erros[] = 'A senha deve ter pelo menos 8 caracteres.';
    }
    if (strlen($senha) > 72) {
        $erros[] = 'A senha pode ter no máximo 72 caracteres.';
    }
    if (!preg_match('/[A-Za-z]/', $senha) || !preg_match('/\d/', $senha)) {
        $erros[] = 'A senha deve conter letras e números.';
    }

    return $erros;
}

/* ---------------------- formatação para exibir ---------------------- */

function clEsc($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function clFmtCpf(string $cpf): string
{
    return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $cpf) ?? $cpf;
}

function clFmtCnpj(string $cnpj): string
{
    return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $cnpj) ?? $cnpj;
}

function clFmtTelefone(string $tel): string
{
    if (strlen($tel) === 11) {
        return preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $tel) ?? $tel;
    }
    return preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $tel) ?? $tel;
}

function clFmtCep(string $cep): string
{
    return preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $cep) ?? $cep;
}

function clFmtData(?string $data): string
{
    return $data ? date('d/m/Y', strtotime($data)) : '';
}
