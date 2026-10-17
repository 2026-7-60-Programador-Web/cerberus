<?php
// Dados do módulo CONVIDADOS (administrador).
// Usado por: views/administrador/convidados.php e controllers/convidados/convidados-processar.php
//
// TEMPORÁRIO: enquanto o banco não existe, tudo fica em database/convidados.json
// (mesmo esquema do database/feedbacks.json da aba Configurações do sócio).
// Quando as tabelas existirem, troque só o corpo das funções marcadas com "BANCO".

require_once __DIR__ . "/../core/validacao.php";

date_default_timezone_set("America/Fortaleza");

const LIMITE_CONVIDADOS_POR_SOCIO = 5;                              // mesmo "3 de 5" do início do sócio
const ARQUIVO_CONVIDADOS = __DIR__ . "/../database/convidados.json";

/* ==================== ARQUIVO (BANCO) ==================== */

// Devolve sempre as 3 listas, mesmo se o arquivo ainda não existir.
function carregarDados(): array {
    $padrao = ["convidados" => [], "convites" => [], "visitas" => []];

    // BANCO: trocar por SELECT nas tabelas convidados, convites e visitas
    if(!is_file(ARQUIVO_CONVIDADOS)){
        return $padrao;
    }

    $lido = json_decode((string)file_get_contents(ARQUIVO_CONVIDADOS), true);

    if(!is_array($lido)){
        return $padrao;
    }

    return array_merge($padrao, $lido);
}

function salvarDados(array $dados): void {
    // BANCO: trocar por INSERT/UPDATE (o controller chama este método depois de cada alteração)
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