<?php
// Mapeia os acessos ao site usando a SESSÃO do PHP.
// - "visitas": toda vez que uma página monitorada é aberta.
// - "unicos":  conta só uma vez por sessão por dia (visitante diferente).
// - "logins":  quantas vezes alguém entrou no sistema no dia.
// Os totais ficam em database/acessos-site.json (sem precisar de tabela nova).

const ARQUIVO_ACESSOS_SITE = __DIR__ . "/../database/acessos-site.json";

function lerAcessosSite(): array {
    if(!is_file(ARQUIVO_ACESSOS_SITE)){
        return [];
    }
    $dados = json_decode((string)file_get_contents(ARQUIVO_ACESSOS_SITE), true);
    return is_array($dados) ? $dados : [];
}

function gravarAcessosSite(array $dados): void {
    @file_put_contents(ARQUIVO_ACESSOS_SITE, json_encode($dados), LOCK_EX);
}

function registrarAcessoSite(): void {
    if(session_status() !== PHP_SESSION_ACTIVE){
        session_start();
    }
    $hoje  = date("Y-m-d");
    $dados = lerAcessosSite();
    $dados[$hoje] = $dados[$hoje] ?? ["visitas" => 0, "unicos" => 0, "logins" => 0];

    $dados[$hoje]["visitas"]++;

    // primeira vez que esta sessão aparece hoje = visitante único
    if(($_SESSION["acesso_contado_em"] ?? "") !== $hoje){
        $dados[$hoje]["unicos"]++;
        $_SESSION["acesso_contado_em"] = $hoje;
    }
    gravarAcessosSite($dados);
}

function registrarLoginSite(): void {
    $hoje  = date("Y-m-d");
    $dados = lerAcessosSite();
    $dados[$hoje] = $dados[$hoje] ?? ["visitas" => 0, "unicos" => 0, "logins" => 0];
    $dados[$hoje]["logins"]++;
    gravarAcessosSite($dados);
}

// Série dos últimos N dias. Dias antigos que ainda não têm registro recebem
// números de EXEMPLO (marcados como simulados) só para o gráfico não ficar vazio na apresentação.
function resumoAcessosSite(int $dias = 14): array {
    $real  = lerAcessosSite();
    $serie = [];
    $simulado = false;

    for($i = $dias - 1; $i >= 0; $i--){
        $data = date("Y-m-d", strtotime("-$i days"));

        if(isset($real[$data])){
            $serie[] = ["data" => $data] + $real[$data] + ["simulado" => false];
        } elseif($i === 0){
            $serie[] = ["data" => $data, "visitas" => 0, "unicos" => 0, "logins" => 0, "simulado" => false];
        } else {
            $visitas  = 40 + crc32("v" . $data) % 80;
            $serie[]  = ["data" => $data, "visitas" => $visitas, "unicos" => (int)round($visitas * 0.55),
                         "logins" => 8 + crc32("l" . $data) % 20, "simulado" => true];
            $simulado = true;
        }
    }

    $ultimos7 = array_slice($serie, -7);
    $hoje     = end($serie);

    return [
        "serie"         => $serie,
        "hoje_visitas"  => $hoje["visitas"],
        "hoje_unicos"   => $hoje["unicos"],
        "hoje_logins"   => $hoje["logins"],
        "visitas_7d"    => array_sum(array_column($ultimos7, "visitas")),
        "unicos_7d"     => array_sum(array_column($ultimos7, "unicos")),
        "visitas_total" => array_sum(array_column($serie, "visitas")),
        "simulado"      => $simulado,
    ];
}
