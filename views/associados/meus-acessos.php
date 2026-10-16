<?php
// Aba "Meus acessos" — é carregada dentro de painel.php (que já faz o login e define e() e $nome).
// Mostra só a consulta: quem registra entradas e saídas é a portaria/administrador.

date_default_timezone_set("America/Fortaleza");

// TEMPORÁRIO: exemplo no formato que a portaria registra.
// BANCO: trocar por uma consulta dos acessos do sócio logado (entrada, saída, situação, motivo e operador).
$acessos = [
    ["entrada" => "2026-10-16 09:12:00", "saida" => null,                  "situacao" => "dentro", "detalhe" => "",                       "operador" => "Portaria"],
    ["entrada" => "2026-10-12 18:40:00", "saida" => "2026-10-12 21:05:00", "situacao" => "saiu",   "detalhe" => "",                       "operador" => "Portaria"],
    ["entrada" => "2026-10-09 14:02:00", "saida" => "2026-10-09 17:30:00", "situacao" => "saiu",   "detalhe" => "",                       "operador" => "Portaria"],
    ["entrada" => "2026-10-03 10:15:00", "saida" => "2026-10-03 13:45:00", "situacao" => "saiu",   "detalhe" => "",                       "operador" => "Portaria"],
    ["entrada" => "2026-09-27 16:20:00", "saida" => null,                  "situacao" => "negado", "detalhe" => "Mensalidade em aberto.", "operador" => "Portaria"],
    ["entrada" => "2026-09-20 11:00:00", "saida" => "2026-09-20 15:10:00", "situacao" => "saiu",   "detalhe" => "",                       "operador" => "Portaria"],
];

$badges = [
    "dentro" => ["text-bg-primary",   "No clube"],
    "saiu"   => ["text-bg-secondary", "Saiu"],
    "negado" => ["text-bg-danger",    "Negado"],
];

// ---------- filtro de período ----------
$periodos = ["todos" => "Todo o período", "7d" => "Últimos 7 dias", "30d" => "Últimos 30 dias"];
$dias     = ["7d" => 7, "30d" => 30];

$periodo = $_GET["periodo"] ?? "todos";
if(!array_key_exists($periodo, $periodos)){
    $periodo = "todos";
}

if(isset($dias[$periodo])){
    $corte   = strtotime("-" . $dias[$periodo] . " days");
    $acessos = array_filter($acessos, fn($a) => strtotime($a["entrada"]) >= $corte);
}

// mais recentes primeiro
usort($acessos, fn($a, $b) => strcmp($b["entrada"], $a["entrada"]));

// ---------- funções de apoio ----------
function horaSaida(string $entrada, string $saida): string {
    // se saiu em outro dia, mostra a data junto
    $formato = substr($entrada, 0, 10) === substr($saida, 0, 10) ? "H:i" : "d/m H:i";
    return date($formato, strtotime($saida));
}

function permanencia(string $entrada, ?string $saida): string {
    if(!$saida){
        return "—";
    }
    $min = intdiv(strtotime($saida) - strtotime($entrada), 60);
    return intdiv($min, 60) . "h " . str_pad($min % 60, 2, "0", STR_PAD_LEFT) . "min";
}
?>

<h1 class="h4 mb-1">Meus acessos</h1>
<p class="text-secondary mb-4">Histórico de entradas e saídas registradas pela portaria do clube.</p>

<!-- FILTRO -->
<form method="GET" action="" class="card-painel mb-3">
    <input type="hidden" name="pagina" value="meus-acessos">

    <div class="row g-2 align-items-end">
        <div class="col-8 col-md-4">
            <label for="periodo" class="form-label">Período</label>
            <select id="periodo" name="periodo" class="form-select form-select-lg">
                <?php foreach($periodos as $chave => $rotulo): ?>
                    <option value="<?= $chave; ?>" <?= $periodo === $chave ? "selected" : ""; ?>><?= e($rotulo); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-4 col-md-2 d-grid">
            <button type="submit" class="btn btn-laranja btn-lg">Filtrar</button>
        </div>
    </div>
</form>

<!-- HISTÓRICO -->
<div class="card-painel">
    <?php if(empty($acessos)): ?>
        <p class="text-secondary mb-0">Nenhum acesso registrado neste período.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0 tabela-cartoes">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Entrada</th>
                        <th>Saída</th>
                        <th>Permanência</th>
                        <th>Registrado por</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($acessos as $a): ?>
                        <?php $b = $badges[$a["situacao"]]; ?>
                        <tr>
                            <td data-label="Data"><strong><?= date("d/m/Y", strtotime($a["entrada"])); ?></strong></td>
                            <td data-label="Entrada"><?= date("H:i", strtotime($a["entrada"])); ?></td>
                            <td data-label="Saída"><?= $a["saida"] ? horaSaida($a["entrada"], $a["saida"]) : "—"; ?></td>
                            <td data-label="Permanência"><?= permanencia($a["entrada"], $a["saida"]); ?></td>
                            <td data-label="Registrado por"><?= e($a["operador"]); ?></td>
                            <td data-label="Situação">
                                <span class="badge <?= $b[0]; ?>"><?= $b[1]; ?></span>
                                <?php if($a["detalhe"] !== ""): ?>
                                    <small class="d-block text-secondary"><?= e($a["detalhe"]); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p class="small text-secondary mt-3 mb-0"><?= count($acessos); ?> registro(s).</p>
    <?php endif; ?>
</div>