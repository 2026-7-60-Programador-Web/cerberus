<?php
require_once __DIR__ . '/validacao.php';

// Sessão, controle de acesso e proteção CSRF do módulo de clubes.
// Regra de ouro: o clube_id vem SEMPRE da sessão, nunca de formulário ou URL.

function clubeIniciarSessao(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
    }
}

function clubeLogado(): bool
{
    return !empty($_SESSION['clube_id']) && !empty($_SESSION['clube_admin_id']);
}

// $destinoLogin: caminho do login relativo ao arquivo que está chamando
function exigirAdminClube(string $destinoLogin): void
{
    clubeIniciarSessao();

    if (!clubeLogado()) {
        header('Location: ' . $destinoLogin);
        exit;
    }
}

function clubeIdDaSessao(): int
{
    return (int)($_SESSION['clube_id'] ?? 0);
}

// Abre a sessão do administrador do clube (troca o ID da sessão contra session fixation)
function clubeAbrirSessao(array $admin, string $nomeClube): void
{
    clubeIniciarSessao();
    session_regenerate_id(true);

    $_SESSION['clube_id']       = (int)$admin['clube_id'];
    $_SESSION['clube_admin_id'] = (int)$admin['id'];
    $_SESSION['clube_nome']     = $nomeClube;
    $_SESSION['nome']           = $admin['nome'];
    $_SESSION['email']          = $admin['email'];
    // não é administrador do protótipo antigo: as telas antigas continuam fechadas para ele
    $_SESSION['admin']          = null;
    $_SESSION['csrf']           = bin2hex(random_bytes(32));
}

/* ---------------------- CSRF ---------------------- */

function clubeCsrfToken(): string
{
    clubeIniciarSessao();

    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function clubeCsrfCampo(): string
{
    return '<input type="hidden" name="csrf" value="' . clEsc(clubeCsrfToken()) . '">';
}

function clubeCsrfValido($token): bool
{
    clubeIniciarSessao();

    return is_string($token)
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}
