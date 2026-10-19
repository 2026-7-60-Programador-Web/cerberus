<?php
// Mensagens rápidas (sucesso/erro) e "valores antigos" de formulário.
// Tudo fica na sessão e some depois de ser lido uma vez.

function flashDefinir(string $chave, string $tipo, array $textos): void
{
    $_SESSION['flash'][$chave] = ['tipo' => $tipo, 'textos' => array_values($textos)];
}

function flashObter(string $chave): ?array
{
    $flash = $_SESSION['flash'][$chave] ?? null;
    unset($_SESSION['flash'][$chave]);
    return $flash;
}

function antigoDefinir(string $chave, array $dados): void
{
    $_SESSION['antigo'][$chave] = $dados;
}

function antigoObter(string $chave): array
{
    $dados = $_SESSION['antigo'][$chave] ?? [];
    unset($_SESSION['antigo'][$chave]);
    return is_array($dados) ? $dados : [];
}
