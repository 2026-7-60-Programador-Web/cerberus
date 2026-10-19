<?php
// Acesso ao banco: associados.
// ISOLAMENTO: toda função recebe $clubeId (vindo da sessão) e o usa em TODA consulta.
// Antes de incluir este arquivo, inclua core/conexao.php (ele cria o $pdo).

const SITUACOES_ASSOCIADO = ['ativo' => 'Ativo', 'inativo' => 'Inativo', 'suspenso' => 'Suspenso'];

function associadosPdo(): PDO
{
    global $pdo;
    return $pdo;
}

function associadosContar(int $clubeId): array
{
    $stmt = associadosPdo()->prepare(
        'SELECT situacao, COUNT(*) AS total FROM associados WHERE clube_id = :clube GROUP BY situacao'
    );
    $stmt->execute(['clube' => $clubeId]);

    $contagem = ['ativo' => 0, 'inativo' => 0, 'suspenso' => 0, 'total' => 0];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        $contagem[$linha['situacao']] = (int)$linha['total'];
        $contagem['total'] += (int)$linha['total'];
    }

    return $contagem;
}

// monta o WHERE da busca (sempre começa pelo clube)
function associadosFiltro(int $clubeId, string $busca, string $situacao): array
{
    $where  = ['clube_id = :clube'];
    $params = ['clube' => $clubeId];

    if ($busca !== '') {
        $like = '%' . addcslashes($busca, '%_\\') . '%';
        $partes = ['nome LIKE :b_nome', 'email LIKE :b_email', 'matricula LIKE :b_mat'];
        $params['b_nome']  = $like;
        $params['b_email'] = $like;
        $params['b_mat']   = $like;

        $digitos = apenasDigitos($busca);
        if ($digitos !== '') {
            $partes[] = 'cpf LIKE :b_cpf';
            $params['b_cpf'] = '%' . $digitos . '%';
        }

        $where[] = '(' . implode(' OR ', $partes) . ')';
    }

    if (isset(SITUACOES_ASSOCIADO[$situacao])) {
        $where[] = 'situacao = :situacao';
        $params['situacao'] = $situacao;
    }

    return [implode(' AND ', $where), $params];
}

function associadosListar(int $clubeId, string $busca, string $situacao, int $pagina, int $porPagina): array
{
    $pdo = associadosPdo();
    [$where, $params] = associadosFiltro($clubeId, $busca, $situacao);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM associados WHERE $where");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $paginas = max(1, (int)ceil($total / $porPagina));
    $pagina  = min(max(1, $pagina), $paginas);
    $offset  = ($pagina - 1) * $porPagina;

    $stmt = $pdo->prepare(
        "SELECT * FROM associados WHERE $where ORDER BY nome ASC, id ASC LIMIT :limite OFFSET :deslocamento"
    );
    foreach ($params as $chave => $valor) {
        $stmt->bindValue(':' . $chave, $valor);
    }
    $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $stmt->bindValue(':deslocamento', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return [
        'itens'   => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total'   => $total,
        'pagina'  => $pagina,
        'paginas' => $paginas,
    ];
}

function associadosRecentes(int $clubeId, int $limite = 5): array
{
    $stmt = associadosPdo()->prepare(
        'SELECT id, nome, situacao, criado_em FROM associados
          WHERE clube_id = :clube ORDER BY id DESC LIMIT :limite'
    );
    $stmt->bindValue(':clube', $clubeId, PDO::PARAM_INT);
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function associadoBuscar(int $clubeId, int $id): ?array
{
    $stmt = associadosPdo()->prepare('SELECT * FROM associados WHERE id = :id AND clube_id = :clube');
    $stmt->execute(['id' => $id, 'clube' => $clubeId]);
    $linha = $stmt->fetch(PDO::FETCH_ASSOC);
    return $linha ?: null;
}

// devolve mensagens de duplicidade DENTRO do mesmo clube (outros clubes não interferem)
function associadoDuplicidades(int $clubeId, string $cpf, ?string $matricula, int $ignorarId = 0): array
{
    $erros = [];
    $pdo = associadosPdo();

    $stmt = $pdo->prepare('SELECT 1 FROM associados WHERE clube_id = :clube AND cpf = :cpf AND id <> :id LIMIT 1');
    $stmt->execute(['clube' => $clubeId, 'cpf' => $cpf, 'id' => $ignorarId]);
    if ($stmt->fetchColumn()) {
        $erros[] = 'Já existe um associado com este CPF no seu clube.';
    }

    if ($matricula !== null) {
        $stmt = $pdo->prepare('SELECT 1 FROM associados WHERE clube_id = :clube AND matricula = :mat AND id <> :id LIMIT 1');
        $stmt->execute(['clube' => $clubeId, 'mat' => $matricula, 'id' => $ignorarId]);
        if ($stmt->fetchColumn()) {
            $erros[] = 'Já existe um associado com esta matrícula no seu clube.';
        }
    }

    return $erros;
}

function associadoCriar(int $clubeId, array $d): int
{
    $stmt = associadosPdo()->prepare(
        'INSERT INTO associados (clube_id, nome, cpf, email, telefone, nascimento, matricula, situacao)
         VALUES (:clube, :nome, :cpf, :email, :telefone, :nascimento, :matricula, :situacao)'
    );
    $stmt->execute([
        'clube'      => $clubeId,
        'nome'       => $d['nome'],
        'cpf'        => $d['cpf'],
        'email'      => $d['email'],
        'telefone'   => $d['telefone'],
        'nascimento' => $d['nascimento'],
        'matricula'  => $d['matricula'],
        'situacao'   => $d['situacao'],
    ]);

    return (int)associadosPdo()->lastInsertId();
}

function associadoAtualizar(int $clubeId, int $id, array $d): bool
{
    $stmt = associadosPdo()->prepare(
        'UPDATE associados
            SET nome = :nome, cpf = :cpf, email = :email, telefone = :telefone,
                nascimento = :nascimento, matricula = :matricula, situacao = :situacao
          WHERE id = :id AND clube_id = :clube'
    );
    $stmt->execute([
        'nome'       => $d['nome'],
        'cpf'        => $d['cpf'],
        'email'      => $d['email'],
        'telefone'   => $d['telefone'],
        'nascimento' => $d['nascimento'],
        'matricula'  => $d['matricula'],
        'situacao'   => $d['situacao'],
        'id'         => $id,
        'clube'      => $clubeId,
    ]);

    return true;
}

function associadoExcluir(int $clubeId, int $id): bool
{
    $stmt = associadosPdo()->prepare('DELETE FROM associados WHERE id = :id AND clube_id = :clube');
    $stmt->execute(['id' => $id, 'clube' => $clubeId]);
    return $stmt->rowCount() > 0;
}
