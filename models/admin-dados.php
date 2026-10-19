<?php
// Dados e funções de apoio das telas do administrador: Painel, Sócios e Cadastro dos sócios.
// Por enquanto são dados de exemplo em arrays (o banco só tem a tabela "usuarios").
// Quando as tabelas existirem, basta trocar o conteúdo destas funções por consultas SQL.

const LIMITE_CONVIDADOS = 5;

/* ---------- formatação ---------- */
function ae($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}
function admData(string $data): string {
    return date("d/m/Y", strtotime($data));
}
function admMoeda(float $valor): string {
    return "R$ " . number_format($valor, 2, ",", ".");
}
function admCelular(string $n): string {
    return preg_replace("/(\d{2})(\d{5})(\d{4})/", "($1) $2-$3", preg_replace("/\D/", "", $n));
}
function admCpf(string $n): string {
    return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", preg_replace("/\D/", "", $n));
}
function admIdade(string $nascimento): int {
    return (new DateTime($nascimento))->diff(new DateTime("today"))->y;
}
function admDiasRestantes(string $fim): int {
    $dif = (new DateTime("today"))->diff(new DateTime($fim));
    return $dif->invert ? -$dif->days : $dif->days;
}
// "11 meses e 3 dias" a partir do total de dias
function admTempoRestante(int $dias): string {
    if($dias < 0)  return "Vencido há " . abs($dias) . " dia(s)";
    if($dias === 0) return "Vence hoje";
    $meses = intdiv($dias, 30);
    $resto = $dias % 30;
    if($meses === 0) return "$dias dia(s)";
    return $meses . ($meses === 1 ? " mês" : " meses") . ($resto ? " e $resto dia(s)" : "");
}

/* ---------- sócios ---------- */
function admSocios(): array {
    $base = [
        [1, "Emily Vitória",           "000123", "Titular", "emily@email.com",    "85991110001", "123.456.789-01", "1994-03-12", "Rua das Flores, 120 — Aldeota, Fortaleza",        "2026-04-01", "2027-03-31", 350, "aberto", 2, 0.35],
        [2, "Hellison Soares",         "000124", "Família", "enzo@email.com",     "85991110002", "234.567.890-12", "1988-07-25", "Av. Santos Dumont, 800 — Meireles, Fortaleza",    "2025-11-10", "2026-11-09", 520, "em dia", 3, 0.5],
        [3, "Marina Albuquerque",      "000125", "Titular", "marina@email.com",   "85991110003", "345.678.901-23", "1991-11-02", "Rua Barão de Studart, 45 — Aldeota, Fortaleza",  "2026-01-15", "2027-01-14", 350, "em dia", 0, 0.6],
        [4, "Roberto Fontenele",       "000126", "Titular", "roberto@email.com",  "85991110004", "456.789.012-34", "1979-01-30", "Rua Silva Jatahy, 310 — Meireles, Fortaleza",    "2025-10-20", "2026-10-19", 350, "aberto", 0, 0.15],
        [5, "Camila Teixeira",         "000127", "Família", "camila@email.com",   "85991110005", "567.890.123-45", "1996-09-18", "Av. Washington Soares, 1500 — Edson Queiroz",     "2026-06-01", "2027-05-31", 520, "em dia", 1, 0.45],
        [6, "João Pedro Cavalcante",   "000128", "Titular", "joao@email.com",     "85991110006", "678.901.234-56", "1985-05-07", "Rua Joaquim Nabuco, 77 — Joaquim Távora",        "2024-12-05", "2026-12-04", 350, "em dia", 5, 0.7],
        [7, "Larissa Moura",           "000129", "Família", "larissa@email.com",  "85991110007", "789.012.345-67", "1999-12-21", "Rua Ana Bilhar, 260 — Meireles, Fortaleza",       "2026-08-12", "2027-08-11", 520, "em dia", 2, 0.4],
        [8, "Carlos Eduardo Pinheiro", "000130", "Titular", "carlos@email.com",   "85991110008", "890.123.456-78", "1972-04-14", "Rua Torres Câmara, 15 — Aldeota, Fortaleza",     "2025-09-01", "2026-08-31", 350, "aberto", 0, 0.05],
    ];

    $socios = [];
    foreach($base as $b){
        $dias = admDiasRestantes($b[10]);
        $socios[] = [
            "id" => $b[0], "nome" => $b[1], "matricula" => $b[2], "plano" => $b[3], "email" => $b[4],
            "celular" => $b[5], "cpf" => $b[6], "nascimento" => $b[7], "endereco" => $b[8],
            "inicio" => $b[9], "fim" => $b[10], "mensalidade" => (float)$b[11], "financeiro" => $b[12],
            "dependentes" => $b[13], "freq_prob" => $b[14],
            "dias_restantes" => $dias,
            "contrato" => $dias < 0 ? "vencido" : ($dias <= 60 ? "a vencer" : "ativo"),
        ];
    }
    return $socios;
}

function admSocioPorId(int $id): ?array {
    foreach(admSocios() as $s){
        if($s["id"] === $id) return $s;
    }
    return null;
}

// Datas em que o sócio frequentou o clube nos últimos $dias dias (mais recentes primeiro).
function admFrequencia(array $socio, int $dias = 90): array {
    $lista = [];
    for($i = 0; $i < $dias; $i++){
        $data = date("Y-m-d", strtotime("-$i days"));
        if($data < $socio["inicio"] || $data > $socio["fim"]) continue;   // só dentro do contrato
        if(crc32($socio["id"] . "|" . $data) % 100 < $socio["freq_prob"] * 100){
            $lista[] = $data;
        }
    }
    return $lista;
}

/* ---------- convidados ---------- */
function admConvidados(): array {
    return [
        [1, 1, "Ana Beatriz Lima",      "2000-02-10", "85988880001", "111.222.333-01", null,                                    "2026-10-10", "Aprovado"],
        [2, 1, "Pedro Henrique Lima",   "2010-06-15", "85988880002", "111.222.333-02", ["Lúcia Lima", "85988880099", "111.222.333-90"], "2026-10-03", "Aprovado"],
        [3, 1, "Rafael Gomes",          "1995-08-30", "85988880003", "111.222.333-03", null,                                    "2026-09-27", "Aprovado"],
        [4, 2, "Tiago Soares",          "1990-01-19", "85988880004", "222.333.444-01", null,                                    "2026-10-12", "Aprovado"],
        [5, 2, "Julia Soares",          "2012-04-04", "85988880005", "222.333.444-02", ["Hellison Soares", "85991110002", "234.567.890-12"], "2026-10-12", "Aprovado"],
        [6, 3, "Beatriz Nogueira",      "1993-10-09", "85988880006", "333.444.555-01", null,                                    "2026-10-14", "Pendente"],
        [7, 5, "Fernando Teixeira",     "1987-12-01", "85988880007", "444.555.666-01", null,                                    "2026-10-08", "Aprovado"],
        [8, 6, "Patrícia Cavalcante",   "1989-03-23", "85988880008", "555.666.777-01", null,                                    "2026-10-15", "Aprovado"],
        [9, 6, "Lucas Cavalcante",      "2008-09-11", "85988880009", "555.666.777-02", ["João Pedro Cavalcante", "85991110006", "678.901.234-56"], "2026-10-15", "Aprovado"],
        [10, 6, "Marcos Vinícius Rocha","1992-05-17", "85988880010", "555.666.777-03", null,                                    "2026-10-01", "Aprovado"],
        [11, 6, "Daniela Freitas",      "1997-07-28", "85988880011", "555.666.777-04", null,                                    "2026-09-20", "Aprovado"],
        [12, 6, "Henrique Alves",       "1994-11-13", "85988880012", "555.666.777-05", null,                                    "2026-09-14", "Aprovado"],
        [13, 7, "Isabela Moura",        "2001-02-26", "85988880013", "666.777.888-01", null,                                    "2026-10-11", "Aprovado"],
        [14, 7, "Gustavo Moura",        "1998-06-06", "85988880014", "666.777.888-02", null,                                    "2026-10-05", "Aprovado"],
    ];
}

function admConvidadosFormatados(): array {
    $lista = [];
    foreach(admConvidados() as $c){
        $lista[] = [
            "id" => $c[0], "socio_id" => $c[1], "nome" => $c[2], "nascimento" => $c[3], "celular" => $c[4], "cpf" => $c[5],
            "responsavel" => $c[6] ? ["nome" => $c[6][0], "celular" => $c[6][1], "cpf" => $c[6][2]] : null,
            "visita" => $c[7], "status" => $c[8],
        ];
    }
    return $lista;
}

function admConvidadosDoSocio(int $socioId): array {
    return array_values(array_filter(admConvidadosFormatados(), fn($c) => $c["socio_id"] === $socioId));
}

/* ---------- feedbacks ---------- */
function admFeedbacks(): array {
    return [
        [1, 3, "Atendimento", 5, "Equipe da portaria muito atenciosa, entrada rápida pela carteirinha digital.", "-1 days"],
        [2, 2, "Estrutura",   4, "Piscina sempre limpa. Só faltam mais espreguiçadeiras nos fins de semana.",    "-2 days"],
        [3, 6, "Eventos",     5, "A festa junina foi ótima! Gostaria de mais eventos para as crianças.",         "-4 days"],
        [4, 1, "Sugestão",    4, "Seria bom poder reservar a quadra de tênis pelo aplicativo.",                  "-5 days"],
        [5, 4, "Financeiro",  2, "Tive dificuldade para emitir a 2ª via do boleto no mês passado.",              "-7 days"],
        [6, 5, "Atendimento", 5, "Resolveram meu pedido de troca de plano no mesmo dia.",                        "-9 days"],
        [7, 7, "Estrutura",   3, "Estacionamento fica lotado aos sábados, vale pensar em mais vagas.",           "-12 days"],
        [8, 8, "Financeiro",  1, "Fui cobrado mesmo após cancelar. Preciso de retorno.",                         "-15 days"],
    ];
}

function admFeedbacksFormatados(): array {
    $lista = [];
    foreach(admFeedbacks() as $f){
        $socio = admSocioPorId($f[1]);
        $lista[] = [
            "id" => $f[0], "socio" => $socio["nome"] ?? "—", "categoria" => $f[2], "nota" => $f[3],
            "mensagem" => $f[4], "data" => date("Y-m-d", strtotime($f[5])),
        ];
    }
    return $lista;
}

/* ---------- financeiro ---------- */
// Fluxo de caixa dos últimos 6 meses (o último é o mês atual).
function admFluxoCaixa(): array {
    $entradasMens = [11200, 11800, 12100, 12650, 13000, 12400];
    $entradasOut  = [1800, 2400, 1500, 3100, 2200, 1900];   // eventos, aluguel de espaços, cantina
    $saidas       = [9800, 10100, 10750, 11200, 11900, 11300];
    $meses = ["", "jan", "fev", "mar", "abr", "mai", "jun", "jul", "ago", "set", "out", "nov", "dez"];

    $fluxo = [];
    for($i = 5; $i >= 0; $i--){
        $k = 5 - $i;
        $t = strtotime("first day of -$i months");
        $fluxo[] = [
            "mes"      => $meses[(int)date("n", $t)] . "/" . date("y", $t),
            "mensal"   => $entradasMens[$k],
            "outras"   => $entradasOut[$k],
            "entradas" => $entradasMens[$k] + $entradasOut[$k],
            "saidas"   => $saidas[$k],
            "saldo"    => $entradasMens[$k] + $entradasOut[$k] - $saidas[$k],
        ];
    }
    return $fluxo;
}

function admLancamentos(): array {
    return [
        ["-0 days", "Mensalidades recebidas (PIX)",  "entrada", 1750],
        ["-1 days", "Aluguel do salão de festas",    "entrada", 900],
        ["-2 days", "Manutenção da piscina",         "saida",   640],
        ["-3 days", "Mensalidade — Hellison Soares", "entrada", 520],
        ["-5 days", "Folha de pagamento (parcial)",  "saida",   3200],
        ["-6 days", "Venda de ingressos do evento",  "entrada", 1260],
        ["-8 days", "Conta de energia",              "saida",   1180],
    ];
}

/* ---------- visão geral usada pelo Painel ---------- */
function admResumoGeral(): array {
    $socios = admSocios();
    $conv   = admConvidadosFormatados();

    $ativos   = array_filter($socios, fn($s) => $s["contrato"] !== "vencido");
    $aVencer  = array_filter($socios, fn($s) => $s["contrato"] === "a vencer");
    $vencidos = array_filter($socios, fn($s) => $s["contrato"] === "vencido");
    $devedores = array_filter($socios, fn($s) => $s["financeiro"] === "aberto");

    $feedbacks = admFeedbacksFormatados();
    $mediaNota = $feedbacks ? array_sum(array_column($feedbacks, "nota")) / count($feedbacks) : 0;

    $presentesHoje = 0;
    foreach($socios as $s){
        if(in_array(date("Y-m-d"), admFrequencia($s, 1), true)) $presentesHoje++;
    }

    $fluxo = admFluxoCaixa();
    $mesAtual = end($fluxo);

    return [
        "total_socios"   => count($socios),
        "ativos"         => count($ativos),
        "a_vencer"       => $aVencer,
        "vencidos"       => $vencidos,
        "devedores"      => $devedores,
        "valor_em_aberto" => array_sum(array_column($devedores, "mensalidade")),
        "convidados"     => count($conv),
        "convidados_pendentes" => count(array_filter($conv, fn($c) => $c["status"] === "Pendente")),
        "convidados_hoje" => count(array_filter($conv, fn($c) => $c["visita"] === date("Y-m-d"))),
        "nota_media"     => $mediaNota,
        "feedbacks"      => count($feedbacks),
        "presentes_hoje" => $presentesHoje,
        "mes_atual"      => $mesAtual,
    ];
}
