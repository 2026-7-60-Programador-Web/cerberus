<?php
// Acesso ao banco: clubes e contas de administrador.
// Antes de incluir este arquivo, inclua core/conexao.php (ele cria o $pdo).

const LOGIN_MAX_TENTATIVAS = 5;
const LOGIN_BLOQUEIO_MINUTOS = 15;

function clubePdo(): PDO
{
    global $pdo;
    return $pdo;
}

function clubeCnpjExiste(string $cnpj): bool
{
    $stmt = clubePdo()->prepare('SELECT 1 FROM clubes WHERE cnpj = :cnpj LIMIT 1');
    $stmt->execute(['cnpj' => $cnpj]);
    return (bool)$stmt->fetchColumn();
}

function adminEmailExiste(string $email): bool
{
    $stmt = clubePdo()->prepare('SELECT 1 FROM administradores WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Cria o clube e a conta de administrador numa única transação:
 * ou grava os dois, ou não grava nada.
 * Devolve o administrador já com clube_id (pronto para abrir a sessão).
 */
function clubeCadastrar(array $clube, array $admin): array
{
    $pdo = clubePdo();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO clubes (nome, cnpj, email, telefone, cep, endereco, cidade, uf)
             VALUES (:nome, :cnpj, :email, :telefone, :cep, :endereco, :cidade, :uf)'
        );
        $stmt->execute($clube);
        $clubeId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'INSERT INTO administradores (clube_id, nome, email, senha)
             VALUES (:clube_id, :nome, :email, :senha)'
        );
        $stmt->execute([
            'clube_id' => $clubeId,
            'nome'     => $admin['nome'],
            'email'    => $admin['email'],
            'senha'    => $admin['senha_hash'],
        ]);
        $adminId = (int)$pdo->lastInsertId();

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return [
        'id'       => $adminId,
        'clube_id' => $clubeId,
        'nome'     => $admin['nome'],
        'email'    => $admin['email'],
    ];
}

function clubeBuscar(int $clubeId): ?array
{
    $stmt = clubePdo()->prepare('SELECT * FROM clubes WHERE id = :id');
    $stmt->execute(['id' => $clubeId]);
    $linha = $stmt->fetch(PDO::FETCH_ASSOC);
    return $linha ?: null;
}

/**
 * Confere e-mail e senha de um administrador de clube.
 * Devolve ['ok' => bool, 'admin' => array|null, 'clube_nome' => string, 'erro' => string]
 * - 'erro' vazio  => credenciais simplesmente não batem (ou e-mail não é de clube)
 * - 'erro' cheio  => mensagem para mostrar (ex.: conta temporariamente bloqueada)
 */
function adminClubeAutenticar(string $email, string $senha): array
{
    $pdo = clubePdo();

    $stmt = $pdo->prepare(
        'SELECT a.*, c.nome AS clube_nome,
                (a.bloqueado_ate IS NOT NULL AND a.bloqueado_ate > NOW()) AS bloqueado
           FROM administradores a
           JOIN clubes c ON c.id = a.clube_id
          WHERE a.email = :email'
    );
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        // gasta o mesmo tempo de uma conferência real (dificulta descobrir quais e-mails existem)
        password_verify($senha, '$2y$10$usesomesillystringforsalt.abcdefghijklmnopqrstuvwxyz012');
        return ['ok' => false, 'admin' => null, 'clube_nome' => '', 'erro' => ''];
    }

    if ((int)$admin['bloqueado'] === 1) {
        return [
            'ok' => false, 'admin' => null, 'clube_nome' => '',
            'erro' => 'Muitas tentativas de acesso. Tente novamente em alguns minutos.',
        ];
    }

    if (!password_verify($senha, $admin['senha'])) {
        $pdo->prepare(
            'UPDATE administradores
                SET bloqueado_ate = IF(tentativas_login + 1 >= :max,
                                       DATE_ADD(NOW(), INTERVAL :min MINUTE), bloqueado_ate),
                    tentativas_login = tentativas_login + 1
              WHERE id = :id'
        )->execute(['max' => LOGIN_MAX_TENTATIVAS, 'min' => LOGIN_BLOQUEIO_MINUTOS, 'id' => $admin['id']]);

        // (a ordem dos campos acima importa no MySQL) ao bloquear, zera o contador para o próximo ciclo
        $pdo->prepare(
            'UPDATE administradores SET tentativas_login = 0
              WHERE id = :id AND bloqueado_ate IS NOT NULL AND bloqueado_ate > NOW()'
        )->execute(['id' => $admin['id']]);

        return ['ok' => false, 'admin' => null, 'clube_nome' => '', 'erro' => ''];
    }

    // senha correta: zera tentativas e atualiza o hash se o algoritmo padrão mudou
    $pdo->prepare('UPDATE administradores SET tentativas_login = 0, bloqueado_ate = NULL WHERE id = :id')
        ->execute(['id' => $admin['id']]);

    if (password_needs_rehash($admin['senha'], PASSWORD_DEFAULT)) {
        $pdo->prepare('UPDATE administradores SET senha = :senha WHERE id = :id')
            ->execute(['senha' => password_hash($senha, PASSWORD_DEFAULT), 'id' => $admin['id']]);
    }

    return ['ok' => true, 'admin' => $admin, 'clube_nome' => $admin['clube_nome'], 'erro' => ''];
}
