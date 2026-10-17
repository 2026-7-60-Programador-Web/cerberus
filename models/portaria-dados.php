<?php
// Dados do módulo PORTARIA (administrador).
// Usado por: views/administrador/portaria.php (+ abas em portaria/) e controllers/portaria/portaria-processar.php
//
// TEMPORÁRIO: enquanto o banco não existe, tudo fica em database/portaria.json.
// Quando as tabelas existirem, troque só o corpo das funções marcadas com "BANCO".
//
// Este arquivo usa os sócios e convidados do models/convidados-dados.php.

require_once __DIR__ . "/convidados-dados.php";

const ARQUIVO_PORTARIA = __DIR__ . "/../database/portaria.json";
const CAPACIDADE_ESTACIONAMENTO = 20;      // vagas do estacionamento
const HORAS_ALERTA_PERMANENCIA = 12;       // alerta se alguém passar disso dentro do clube

/* ==================== ARQUIVO (BANCO) ==================== */

// Devolve sempre as 4 listas, mesmo se o arquivo ainda não existir.
//   acessos   = entradas/saídas de pessoas
//   negados   = tentativas de entrada recusadas
//   veiculos  = veículos cadastrados
//   passagens = entradas/saídas de veículos no estacionamento
function carregarPortaria(): array {
    $padrao = ["acessos" => [], "negados" => [], "veiculos" => [], "passagens" => []];

    // BANCO: trocar por SELECT nas tabelas de acessos, negados, veículos e passagens
    if(!is_file(ARQUIVO_PORTARIA)){
        return $padrao;
    }

    $lido = json_decode((string)file_get_contents(ARQUIVO_PORTARIA), true);

    if(!is_array($lido)){
        return $padrao;
    }

    return array_merge($padrao, $lido);
}

function salvarPortaria(array $portaria): void {
    // BANCO: trocar por INSERT/UPDATE
    $pasta = dirname(ARQUIVO_PORTARIA);

    if(!is_dir($pasta)){
        mkdir($pasta, 0775, true);
    }

    file_put_contents(
        ARQUIVO_PORTARIA,
        json_encode($portaria, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

/* ==================== SEGURANÇA ==================== */

// Escapa texto antes de mostrar na tela (evita XSS). Mesmo papel do e() das outras telas.
function h($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

// CSRF: o formulário leva um código secreto da sessão; o controller confere.
// Assim, outro site não consegue mandar um POST "em nome" do funcionário logado.
function tokenCsrf(): string {
    if(empty($_SESSION["csrf_portaria"])){
        $_SESSION["csrf_portaria"] = bin2hex(random_bytes(16));
    }

    return $_SESSION["csrf_portaria"];
}

function csrfValido(string $token): bool {
    return $token !== "" && hash_equals($_SESSION["csrf_portaria"] ?? "", $token);
}

/* ==================== TEXTO E FORMATAÇÃO ==================== */

function cortarTexto(string $texto, int $limite): string {
    return mb_substr($texto, 0, $limite);
}

// minúsculo e sem acento, para a busca achar "joao" ao digitar "João"
function normalizarTexto(string $texto): string {
    $semAcento = strtr(mb_strtolower($texto), [
        "á" => "a", "à" => "a", "â" => "a", "ã" => "a", "ä" => "a",
        "é" => "e", "è" => "e", "ê" => "e", "ë" => "e",
        "í" => "i", "ì" => "i", "î" => "i", "ï" => "i",
        "ó" => "o", "ò" => "o", "ô" => "o", "õ" => "o", "ö" => "o",
        "ú" => "u", "ù" => "u", "û" => "u", "ü" => "u",
        "ç" => "c",
    ]);

    return trim($semAcento);
}

function fmtData(?string $data): string {
    return $data ? date("d/m/Y", strtotime($data)) : "—";
}

// "Hoje, 14:32" / "Ontem, 09:10" / "12/10/2026 18:40"
function fmtMomento(?string $momento): string {
    if(!$momento){
        return "—";
    }

    $dia = date("Y-m-d", strtotime($momento));
    $hora = date("H:i", strtotime($momento));

    if($dia === date("Y-m-d")){
        return "Hoje, " . $hora;
    }

    if($dia === date("Y-m-d", strtotime("yesterday"))){
        return "Ontem, " . $hora;
    }

    return date("d/m/Y", strtotime($momento)) . " " . $hora;
}

// "2h 05min". Sem saída, conta até agora.
function fmtPermanencia(string $entrada, ?string $saida): string {
    $fim = $saida ? strtotime($saida) : time();
    $min = max(0, intdiv($fim - strtotime($entrada), 60));

    return intdiv($min, 60) . "h " . str_pad((string)($min % 60), 2, "0", STR_PAD_LEFT) . "min";
}

function fmtCelular(?string $celular): string {
    $n = somenteNumeros((string)$celular);

    return strlen($n) === 11 ? preg_replace("/(\d{2})(\d{5})(\d{4})/", "($1) $2-$3", $n) : "—";
}

// Na portaria o CPF aparece parcialmente (LGPD): ***.456.789-**
function fmtCpfMascarado(?string $cpf): string {
    $n = somenteNumeros((string)$cpf);

    return strlen($n) === 11 ? "***." . substr($n, 3, 3) . "." . substr($n, 6, 3) . "-**" : "—";
}

// ABC1234 -> ABC-1234 (modelo antigo); ABC1D23 (Mercosul) fica como está
function fmtPlaca(string $placa): string {
    return preg_match("/^[A-Z]{3}\d{4}$/", $placa) ? substr($placa, 0, 3) . "-" . substr($placa, 3) : $placa;
}

/* ==================== PLACAS ==================== */

function normalizarPlaca(string $placa): string {
    return strtoupper(preg_replace("/[^A-Za-z0-9]/", "", $placa));
}

function placaValida(string $placa): bool {
    return (bool)preg_match("/^[A-Z]{3}\d{4}$/", $placa) || (bool)preg_match("/^[A-Z]{3}\d[A-Z]\d{2}$/", $placa);
}

/* ==================== PERÍODOS ==================== */

function periodoValido(string $periodo): string {
    return in_array($periodo, ["hoje", "7d", "30d"], true) ? $periodo : "hoje";
}

// Devolve o "a partir de quando" como timestamp
function inicioPeriodo(string $periodo): int {
    switch($periodo){
        case "7d":  return strtotime("-7 days");
        case "30d": return strtotime("-30 days");
        default:    return strtotime("today");
    }
}

/* ==================== PESSOAS ==================== */

// Junta sócios, dependentes e convidados numa lista só, cada um com uma "ref" única:
//   socio-1 | dependente-1-2 | convidado-3
// É essa ref que viaja nos formulários e fica gravada nos acessos.
function montarPessoas(array $conv): array {
    $pessoas = [];

    foreach(listarSocios() as $s){
        $pessoas[] = [
            "ref"         => "socio-" . $s["id"],
            "tipo"        => "socio",
            "nome"        => $s["nome"],
            "rotulo"      => $s["plano"],
            "matricula"   => $s["matricula"],
            "carteirinha" => $s["carteirinha"],
            "validade"    => $s["validade"],
            "situacao"    => $s["situacao"],
            "cpf"         => $s["cpf"],
            "socio_id"    => $s["id"],
        ];

        // dependente usa a matrícula, a validade e a situação do titular
        foreach($s["dependentes"] as $i => $d){
            $pessoas[] = [
                "ref"         => "dependente-" . $s["id"] . "-" . ($i + 1),
                "tipo"        => "dependente",
                "nome"        => $d["nome"],
                "rotulo"      => "Dependente · " . $d["parentesco"],
                "matricula"   => $s["matricula"],
                "carteirinha" => $s["carteirinha"],
                "validade"    => $s["validade"],
                "situacao"    => $s["situacao"],
                "cpf"         => "",
                "socio_id"    => $s["id"],
            ];
        }
    }

    foreach($conv["convidados"] as $c){
        $pessoas[] = [
            "ref"          => "convidado-" . $c["id"],
            "tipo"         => "convidado",
            "nome"         => $c["nome"],
            "rotulo"       => "Convidado",
            "matricula"    => "",
            "carteirinha"  => "",
            "validade"     => "",
            "situacao"     => "",
            "cpf"          => $c["cpf"],
            "celular"      => $c["celular"],
            "nascimento"   => $c["nascimento"],
            "menor"        => !empty($c["menor"]),
            "responsavel"  => $c["responsavel"] ?? null,
            "convidado_id" => $c["id"],
        ];
    }

    return $pessoas;
}

function buscarPessoa(string $ref, array $pessoas): ?array {
    foreach($pessoas as $p){
        if($p["ref"] === $ref){
            return $p;
        }
    }

    return null;
}

// Busca por nome, CPF, matrícula, carteirinha ou QR Code (o leitor "digita" o texto do QR).
function buscarPessoas(string $busca, array $pessoas): array {
    $busca = trim($busca);

    if($busca === ""){
        return [];
    }

    // QR da carteirinha do sócio: CERBERUS|000123|2027-03-31  -> usa a matrícula (2º pedaço)
    if(stripos($busca, "CERBERUS|") === 0){
        $partes = explode("|", $busca);
        $matricula = $partes[1] ?? "";
        $achadas = [];

        foreach($pessoas as $p){
            if($p["tipo"] === "socio" && $matricula !== "" && $p["matricula"] === $matricula){
                $achadas[] = $p;
            }
        }

        return $achadas;   // vazio = "Carteirinha inválida" na tela
    }

    $numeros = somenteNumeros($busca);
    $texto   = normalizarTexto($busca);
    $achadas = [];

    foreach($pessoas as $p){
        $acertou = false;

        if(mb_strlen($texto) >= 2 && strpos(normalizarTexto($p["nome"]), $texto) !== false){
            $acertou = true;
        } elseif($p["carteirinha"] !== "" && normalizarTexto($p["carteirinha"]) === $texto){
            $acertou = true;
        } elseif(strlen($numeros) >= 3){
            $cpfBate  = $p["cpf"] !== "" && strpos($p["cpf"], $numeros) !== false;
            $matBate  = $p["matricula"] !== "" && strpos($p["matricula"], $numeros) !== false;
            $acertou  = $cpfBate || $matBate;
        }

        if($acertou){
            $achadas[] = $p;
        }
    }

    return $achadas;
}

/* ==================== REGRAS DE ACESSO ==================== */

function piorNivel(string $a, string $b): string {
    $peso = ["ok" => 0, "atencao" => 1, "bloqueio" => 2];

    return $peso[$b] > $peso[$a] ? $b : $a;
}

// Decide se a pessoa pode entrar. Devolve:
//   liberado  (bool)    nivel (ok | atencao | bloqueio)    motivos (lista de textos)
//   anfitriao (sócio responsável, ou null)    convite (convite usado, só para convidado)
function avaliarAcesso(array $pessoa, array $conv): array {
    $nivel     = "ok";
    $motivos   = [];
    $anfitriao = null;
    $convite   = null;

    if($pessoa["tipo"] === "convidado"){

        // procura o convite: aprovado > pendente > bloqueado
        foreach(["aprovado", "pendente", "bloqueado"] as $status){
            foreach($conv["convites"] as $c){
                if((int)$c["convidado_id"] === (int)$pessoa["convidado_id"] && $c["status"] === $status){
                    $convite = $c;
                    break 2;
                }
            }
        }

        if($convite === null){
            $nivel = "atencao";
            $motivos[] = "Este convidado não tem convite emitido.";
        } else {
            $anfitriao = buscarSocio((int)$convite["socio_id"]);

            if($convite["status"] === "pendente"){
                $nivel = "atencao";
                $motivos[] = "Convite aguardando autorização.";
            } elseif($convite["status"] === "bloqueado"){
                $nivel = "bloqueio";
                $motivos[] = "Convite bloqueado pelo administrador.";
            }
        }

    } else {

        // sócio e dependente seguem a situação do sócio titular
        $socio = buscarSocio((int)$pessoa["socio_id"]);
        $anfitriao = $socio;

        if($socio === null){
            $nivel = "bloqueio";
            $motivos[] = "Cadastro do sócio não encontrado.";
        } else {
            if($socio["validade"] < date("Y-m-d")){
                $nivel = piorNivel($nivel, "atencao");
                $motivos[] = "Carteirinha vencida em " . fmtData($socio["validade"]) . ".";
            }

            if($socio["situacao"] === "inadimplente"){
                $nivel = piorNivel($nivel, "atencao");
                $motivos[] = "Mensalidade em aberto.";
            }

            if($socio["situacao"] === "suspenso"){
                $nivel = piorNivel($nivel, "bloqueio");
                $motivos[] = "Sócio suspenso.";
            }
        }
    }

    return [
        "liberado"  => $nivel === "ok",
        "nivel"     => $nivel,
        "motivos"   => $motivos,
        "anfitriao" => $anfitriao,
        "convite"   => $convite,
    ];
}

function classeBadgeNivel(string $nivel): string {
    $classes = ["ok" => "text-bg-success", "atencao" => "text-bg-warning", "bloqueio" => "text-bg-danger"];

    return $classes[$nivel] ?? "text-bg-secondary";
}

function rotuloNivel(string $nivel): string {
    $rotulos = ["ok" => "Liberado", "atencao" => "Atenção", "bloqueio" => "Bloqueado"];

    return $rotulos[$nivel] ?? "—";
}

function classeBadgeSituacao(string $situacao): string {
    $classes = ["ativo" => "text-bg-success", "inadimplente" => "text-bg-warning", "suspenso" => "text-bg-danger"];

    return $classes[$situacao] ?? "text-bg-secondary";
}

function rotuloSituacao(string $situacao): string {
    $rotulos = ["ativo" => "Ativo", "inadimplente" => "Inadimplente", "suspenso" => "Suspenso"];

    return $rotulos[$situacao] ?? "—";
}

/* ==================== CONSULTAS ==================== */

// acesso "aberto" = entrou e ainda não registrou saída
function acessoAberto(array $portaria, string $ref): ?array {
    foreach($portaria["acessos"] as $a){
        if($a["pessoa_ref"] === $ref && $a["saida_em"] === null){
            return $a;
        }
    }

    return null;
}

function ultimoAcesso(array $portaria, string $ref): ?array {
    $ultimo = null;

    foreach($portaria["acessos"] as $a){
        if($a["pessoa_ref"] === $ref && ($ultimo === null || $a["entrada_em"] > $ultimo["entrada_em"])){
            $ultimo = $a;
        }
    }

    return $ultimo;
}

// quem está dentro agora (entrada mais recente primeiro)
function pessoasDentro(array $portaria): array {
    $dentro = [];

    foreach(array_reverse($portaria["acessos"]) as $a){
        if($a["saida_em"] === null){
            $dentro[] = $a;
        }
    }

    return $dentro;
}

function contarEntradasDesde(array $portaria, int $desde): int {
    $total = 0;

    foreach($portaria["acessos"] as $a){
        if(strtotime($a["entrada_em"]) >= $desde){
            $total++;
        }
    }

    return $total;
}

function convitesPorStatus(array $conv, string $status): array {
    $lista = [];

    foreach($conv["convites"] as $c){
        if($c["status"] === $status){
            $lista[] = $c;
        }
    }

    return $lista;
}

// Histórico da aba Acessos: junta entradas/saídas e negados, filtra e ordena (mais recente primeiro)
function historicoAcessos(array $portaria, int $desde, string $situacao, string $nome): array {
    $linhas = [];

    foreach($portaria["acessos"] as $a){
        $linhas[] = [
            "nome"     => $a["nome"],
            "rotulo"   => $a["rotulo"],
            "entrada"  => $a["entrada_em"],
            "saida"    => $a["saida_em"],
            "situacao" => $a["saida_em"] === null ? "dentro" : "saiu",
            "detalhe"  => "",
        ];
    }

    foreach($portaria["negados"] as $n){
        $linhas[] = [
            "nome"     => $n["nome"],
            "rotulo"   => $n["rotulo"],
            "entrada"  => $n["em"],
            "saida"    => null,
            "situacao" => "negado",
            "detalhe"  => $n["motivo"],
        ];
    }

    $filtroNome = normalizarTexto($nome);
    $resultado  = [];

    foreach($linhas as $l){
        if(strtotime($l["entrada"]) < $desde){
            continue;
        }

        if($situacao !== "todos" && $l["situacao"] !== $situacao){
            continue;
        }

        if($filtroNome !== "" && strpos(normalizarTexto($l["nome"]), $filtroNome) === false){
            continue;
        }

        $resultado[] = $l;
    }

    usort($resultado, fn($x, $y) => strcmp($y["entrada"], $x["entrada"]));

    return $resultado;
}

// Últimos acontecimentos (entrada, saída, negado), do mais novo para o mais antigo
function eventosRecentes(array $portaria, int $limite): array {
    $eventos = [];

    foreach($portaria["acessos"] as $a){
        $eventos[] = ["tipo" => "entrada", "nome" => $a["nome"], "rotulo" => $a["rotulo"], "detalhe" => "", "ts" => $a["entrada_em"]];

        if($a["saida_em"] !== null){
            $eventos[] = ["tipo" => "saida", "nome" => $a["nome"], "rotulo" => $a["rotulo"], "detalhe" => "", "ts" => $a["saida_em"]];
        }
    }

    foreach($portaria["negados"] as $n){
        $eventos[] = ["tipo" => "negado", "nome" => $n["nome"], "rotulo" => $n["rotulo"], "detalhe" => $n["motivo"], "ts" => $n["em"]];
    }

    usort($eventos, fn($x, $y) => strcmp($y["ts"], $x["ts"]));

    return array_slice($eventos, 0, $limite);
}

// Alertas do painel. nivel: danger | warning | info (classes já existem no CSS)
function gerarAlertas(array $portaria, array $conv, array $pessoas): array {
    $alertas = [];

    // visitante dentro do clube cujo convite foi bloqueado depois da entrada
    foreach(pessoasDentro($portaria) as $a){
        $pessoa = buscarPessoa($a["pessoa_ref"], $pessoas);

        if($pessoa && $pessoa["tipo"] === "convidado" && avaliarAcesso($pessoa, $conv)["nivel"] === "bloqueio"){
            $alertas[] = [
                "nivel" => "danger",
                "icone" => "bi-x-octagon",
                "texto" => $a["nome"] . " está dentro do clube, mas o convite foi bloqueado.",
            ];
        }
    }

    // convites esperando o administrador
    $pendentes = count(convitesPorStatus($conv, "pendente"));
    if($pendentes > 0){
        $alertas[] = [
            "nivel" => "warning",
            "icone" => "bi-hourglass-split",
            "texto" => $pendentes . " convite(s) aguardando autorização.",
            "link"  => ["portaria.php?aba=visitantes", "Ver visitantes"],
        ];
    }

    // estacionamento quase cheio
    $ocupadas = count(veiculosPresentes($portaria));
    if(CAPACIDADE_ESTACIONAMENTO > 0 && $ocupadas * 100 / CAPACIDADE_ESTACIONAMENTO >= 90){
        $alertas[] = [
            "nivel" => "warning",
            "icone" => "bi-car-front",
            "texto" => "Estacionamento quase lotado: " . $ocupadas . " de " . CAPACIDADE_ESTACIONAMENTO . " vagas.",
            "link"  => ["portaria.php?aba=veiculos", "Ver veículos"],
        ];
    }

    // alguém há muito tempo dentro: provavelmente esqueceram de registrar a saída
    foreach(pessoasDentro($portaria) as $a){
        if(time() - strtotime($a["entrada_em"]) > HORAS_ALERTA_PERMANENCIA * 3600){
            $alertas[] = [
                "nivel" => "info",
                "icone" => "bi-clock-history",
                "texto" => $a["nome"] . " está há mais de " . HORAS_ALERTA_PERMANENCIA . "h no clube. Falta registrar a saída?",
            ];
        }
    }

    // entradas negadas hoje
    $negadosHoje = 0;
    foreach($portaria["negados"] as $n){
        if(strtotime($n["em"]) >= strtotime("today")){
            $negadosHoje++;
        }
    }
    if($negadosHoje > 0){
        $alertas[] = [
            "nivel" => "info",
            "icone" => "bi-slash-circle",
            "texto" => $negadosHoje . " entrada(s) negada(s) hoje.",
            "link"  => ["portaria.php?aba=acessos&situacao=negado", "Ver negadas"],
        ];
    }

    return $alertas;
}

/* ==================== REGISTROS: PESSOAS ==================== */
// Todas devolvem "" quando deu certo, ou o texto do erro (o controller mostra na tela).

function registrarEntradaPessoa(array $pessoa, array $avaliacao, string $operador): string {
    $portaria = carregarPortaria();

    if(acessoAberto($portaria, $pessoa["ref"]) !== null){
        return $pessoa["nome"] . " já está dentro do clube.";
    }

    $portaria["acessos"][] = [
        "id"         => proximoId($portaria["acessos"]),
        "pessoa_ref" => $pessoa["ref"],
        "nome"       => $pessoa["nome"],
        "rotulo"     => $pessoa["rotulo"],
        "convite_id" => $avaliacao["convite"]["id"] ?? null,   // só convidado tem convite
        "entrada_em" => date("Y-m-d H:i:s"),
        "saida_em"   => null,
        "operador"   => $operador,
    ];

    salvarPortaria($portaria);

    return "";
}

// $nome volta preenchido (por referência) para o controller montar a mensagem
function registrarSaidaPessoa(string $ref, string $operador, string &$nome): string {
    $portaria  = carregarPortaria();
    $encontrou = false;

    foreach($portaria["acessos"] as &$a){
        if($a["pessoa_ref"] === $ref && $a["saida_em"] === null){
            $a["saida_em"]       = date("Y-m-d H:i:s");
            $a["saida_operador"] = $operador;
            $nome      = $a["nome"];
            $encontrou = true;
            break;
        }
    }
    unset($a);

    if(!$encontrou){
        return "Esta pessoa não está registrada dentro do clube.";
    }

    salvarPortaria($portaria);

    return "";
}

function registrarNegado(array $pessoa, string $motivo, string $operador): string {
    $portaria = carregarPortaria();

    $portaria["negados"][] = [
        "id"         => proximoId($portaria["negados"]),
        "pessoa_ref" => $pessoa["ref"],
        "nome"       => $pessoa["nome"],
        "rotulo"     => $pessoa["rotulo"],
        "motivo"     => $motivo,
        "em"         => date("Y-m-d H:i:s"),
        "operador"   => $operador,
    ];

    salvarPortaria($portaria);

    return "";
}

/* ==================== VEÍCULOS ==================== */

function buscarVeiculo(array $portaria, int $id): ?array {
    foreach($portaria["veiculos"] as $v){
        if((int)$v["id"] === $id){
            return $v;
        }
    }

    return null;
}

// passagem "aberta" = veículo entrou e ainda não saiu
function passagemAberta(array $portaria, int $veiculoId): ?array {
    foreach($portaria["passagens"] as $p){
        if((int)$p["veiculo_id"] === $veiculoId && $p["saida_em"] === null){
            return $p;
        }
    }

    return null;
}

function veiculosPresentes(array $portaria): array {
    $presentes = [];

    foreach($portaria["passagens"] as $p){
        if($p["saida_em"] === null){
            $presentes[] = $p;
        }
    }

    return $presentes;
}

function cadastrarVeiculo(string $placa, string $modelo, string $donoRef): string {
    $portaria = carregarPortaria();

    foreach($portaria["veiculos"] as $v){
        if($v["placa"] === $placa){
            return "Já existe um veículo cadastrado com a placa " . fmtPlaca($placa) . ".";
        }
    }

    $portaria["veiculos"][] = [
        "id"               => proximoId($portaria["veiculos"]),
        "placa"            => $placa,
        "modelo"           => $modelo,
        "proprietario_ref" => $donoRef,
        "criado_em"        => date("Y-m-d H:i:s"),
    ];

    salvarPortaria($portaria);

    return "";
}

function registrarEntradaVeiculo(int $veiculoId, string $operador): string {
    $portaria = carregarPortaria();

    if(passagemAberta($portaria, $veiculoId) !== null){
        return "Este veículo já está no estacionamento.";
    }

    if(count(veiculosPresentes($portaria)) >= CAPACIDADE_ESTACIONAMENTO){
        return "Estacionamento lotado (" . CAPACIDADE_ESTACIONAMENTO . " vagas).";
    }

    $portaria["passagens"][] = [
        "id"         => proximoId($portaria["passagens"]),
        "veiculo_id" => $veiculoId,
        "entrada_em" => date("Y-m-d H:i:s"),
        "saida_em"   => null,
        "operador"   => $operador,
    ];

    salvarPortaria($portaria);

    return "";
}

function registrarSaidaVeiculo(int $veiculoId): string {
    $portaria  = carregarPortaria();
    $encontrou = false;

    foreach($portaria["passagens"] as &$p){
        if((int)$p["veiculo_id"] === $veiculoId && $p["saida_em"] === null){
            $p["saida_em"] = date("Y-m-d H:i:s");
            $encontrou = true;
            break;
        }
    }
    unset($p);

    if(!$encontrou){
        return "Este veículo não está no estacionamento.";
    }

    salvarPortaria($portaria);

    return "";
}