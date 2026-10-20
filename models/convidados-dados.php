<?php
// Dados do módulo CONVIDADOS (administrador).
// Usado por: views/administrador/convidados.php e controllers/convidados/convidados-processar.php
//
// Convidados ficam na tabela "convidados" do MySQL (script em database/convidados.sql).
// Convites e visitas ainda ficam em database/convidados.json, até ganharem suas tabelas.

require_once __DIR__ . "/../core/validacao.php";

date_default_timezone_set("America/Fortaleza");

const LIMITE_CONVIDADOS_POR_SOCIO = 5;                              // mesmo "3 de 5" do início do sócio
const ARQUIVO_CONVIDADOS = __DIR__ . "/../database/convidados.json";

/* ==================== BANCO DE DADOS (MySQL) ==================== */

// Abre a conexão uma única vez por requisição e devolve o mesmo $pdo nas próximas chamadas.
// O core/conexao.php já existe no projeto e cria a variável $pdo.
function conectarBanco(): PDO {
    static $pdo = null;

    if($pdo === null){
        // "require" (e não "require_once") de propósito: o $pdo criado lá dentro vira o $pdo desta função
        require __DIR__ . "/../core/conexao.php";
    }

    return $pdo;
}

// Primeira função de CPF: "este CPF já existe na tabela convidados?"
// (a outra, cpfValido(), confere se o CPF é um CPF de verdade e fica em core/validacao.php)
function cpfJaCadastrado(PDO $pdo, string $cpf): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM convidados WHERE cpf = :cpf LIMIT 1");
    $stmt->execute(["cpf" => $cpf]);

    return (bool)$stmt->fetchColumn();
}

// Grava um convidado e devolve o id gerado pelo banco (AUTO_INCREMENT).
// Campo vazio vira NULL no banco (nascimento e celular são opcionais).
function inserirConvidado(PDO $pdo, array $convidado): int {
    $vazioViraNulo = fn($valor) => ($valor === null || $valor === "") ? null : $valor;
    $resp = $convidado["responsavel"] ?? null;

    $stmt = $pdo->prepare(
        "INSERT INTO convidados (nome, cpf, data_nascimento, celular, resp_nome, resp_nascimento, resp_celular, resp_cpf)
         VALUES (:nome, :cpf, :nascimento, :celular, :resp_nome, :resp_nascimento, :resp_celular, :resp_cpf)"
    );

    $stmt->execute([
        "nome"            => $convidado["nome"],
        "cpf"             => $convidado["cpf"],
        "nascimento"      => $vazioViraNulo($convidado["nascimento"] ?? null),
        "celular"         => $vazioViraNulo($convidado["celular"] ?? null),
        "resp_nome"       => $vazioViraNulo($resp["nome"] ?? null),
        "resp_nascimento" => $vazioViraNulo($resp["nascimento"] ?? null),
        "resp_celular"    => $vazioViraNulo($resp["celular"] ?? null),
        "resp_cpf"        => $vazioViraNulo($resp["cpf"] ?? null),
    ]);

    return (int)$pdo->lastInsertId();
}

// Lê os convidados do banco no MESMO formato que as telas já usavam
// (nascimento/celular vazios viram "" e "menor" é calculado pela data de nascimento).
function listarConvidados(PDO $pdo): array {
    $linhas = $pdo->query(
        "SELECT id, nome, cpf, data_nascimento, celular, resp_nome, resp_nascimento, resp_celular, resp_cpf, criado_em
         FROM convidados ORDER BY id"
    )->fetchAll(PDO::FETCH_ASSOC);

    $convidados = [];

    foreach($linhas as $l){
        $nascimento  = $l["data_nascimento"] ?? "";
        $responsavel = null;

        if($l["resp_nome"] !== null){
            $responsavel = [
                "nome"       => $l["resp_nome"],
                "nascimento" => $l["resp_nascimento"] ?? "",
                "celular"    => $l["resp_celular"] ?? "",
                "cpf"        => $l["resp_cpf"] ?? "",
            ];
        }

        $convidados[] = [
            "id"          => (int)$l["id"],
            "nome"        => $l["nome"],
            "nascimento"  => $nascimento,
            "celular"     => $l["celular"] ?? "",
            "cpf"         => $l["cpf"],
            "menor"       => $nascimento !== "" && calcularIdade($nascimento) < 18,
            "responsavel" => $responsavel,
            "criado_em"   => $l["criado_em"],
        ];
    }

    return $convidados;
}

/* ==================== CARREGAR / SALVAR ==================== */
// Convidados: MySQL (tabela convidados).
// Convites e visitas: continuam no JSON (database/convidados.json) até ganharem tabelas.

// Devolve sempre as 3 listas, mesmo se o arquivo ainda não existir.
function carregarDados(): array {
    $padrao = ["convidados" => [], "convites" => [], "visitas" => []];
    $dados  = $padrao;

    // BANCO (próximo passo): convites e visitas ainda vêm do arquivo
    if(is_file(ARQUIVO_CONVIDADOS)){
        $lido = json_decode((string)file_get_contents(ARQUIVO_CONVIDADOS), true);

        if(is_array($lido)){
            $dados = array_merge($padrao, $lido);
        }
    }

    // convidados sempre vêm do banco (ignora qualquer "convidados" antigo que estivesse no arquivo)
    $dados["convidados"] = listarConvidados(conectarBanco());

    return $dados;
}

function salvarDados(array $dados): void {
    // convidados já são gravados no banco (inserirConvidado), então não vão para o arquivo
    unset($dados["convidados"]);

    // BANCO (próximo passo): trocar por INSERT/UPDATE de convites e visitas
    $pasta = dirname(ARQUIVO_CONVIDADOS);

    if(!is_dir($pasta)){
        mkdir($pasta, 0775, true);
    }

    file_put_contents(
        ARQUIVO_CONVIDADOS,
        json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

/* ==================== AUXILIARES DE LISTA ==================== */

// Próximo id livre (o AUTO_INCREMENT do banco faz isso sozinho)
function proximoId(array $lista): int {
    $ids = array_column($lista, "id");

    return $ids ? max($ids) + 1 : 1;
}

function buscarPorId(array $lista, int $id): ?array {
    foreach($lista as $item){
        if((int)$item["id"] === $id){
            return $item;
        }
    }

    return null;
}

/* ==================== SÓCIOS ==================== */

// TEMPORÁRIO: sócios de exemplo. Os dois primeiros são os usuários não-administradores
// do login-processar.php; os outros dois existem para testar bloqueios na portaria.
// BANCO: trocar por SELECT na tabela de sócios.
function listarSocios(): array {
    return [
        [
            "id"          => 1,
            "nome"        => "Emily Vitória",
            "email"       => "emily@email.com",
            "cpf"         => "39053344705",
            "matricula"   => "000123",
            "carteirinha" => "CT-000123",
            "plano"       => "Sócio Titular",
            "validade"    => "2027-03-31",
            "situacao"    => "ativo",
            "dependentes" => [
                ["nome" => "Ana Souza",    "parentesco" => "Filha"],
                ["nome" => "Carlos Souza", "parentesco" => "Cônjuge"],
            ],
        ],
        [
            "id"          => 2,
            "nome"        => "Hellison Soares",
            "email"       => "enzo@email.com",
            "cpf"         => "16899535009",
            "matricula"   => "000124",
            "carteirinha" => "CT-000124",
            "plano"       => "Sócio Titular",
            "validade"    => "2027-03-31",
            "situacao"    => "ativo",
            "dependentes" => [],
        ],
        [
            "id"          => 3,
            "nome"        => "Maria Oliveira",
            "email"       => "maria@email.com",
            "cpf"         => "74697131401",
            "matricula"   => "000125",
            "carteirinha" => "CT-000125",
            "plano"       => "Sócio Titular",
            "validade"    => "2027-03-31",
            "situacao"    => "inadimplente",   // mensalidade em aberto: portaria deve barrar
            "dependentes" => [],
        ],
        [
            "id"          => 4,
            "nome"        => "João Pereira",
            "email"       => "joao@email.com",
            "cpf"         => "12345678909",
            "matricula"   => "000126",
            "carteirinha" => "CT-000126",
            "plano"       => "Sócio Titular",
            "validade"    => "2026-08-31",     // carteirinha vencida: portaria deve barrar
            "situacao"    => "ativo",
            "dependentes" => [],
        ],
    ];
}

function buscarSocio(int $id): ?array {
    foreach(listarSocios() as $socio){
        if($socio["id"] === $id){
            return $socio;
        }
    }

    return null;
}

// Convites que ainda "ocupam vaga" do sócio (pendente ou aprovado; bloqueado libera a vaga)
function contarConvidadosDoSocio(array $dados, int $socioId): int {
    $total = 0;

    foreach($dados["convites"] as $convite){
        if((int)$convite["socio_id"] === $socioId && $convite["status"] !== "bloqueado"){
            $total++;
        }
    }

    return $total;
}