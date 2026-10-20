<?php

session_start();
require_once __DIR__ . "/../../models/convidados-dados.php";

// só administrador logado
if(!isset($_SESSION["email"]) || $_SESSION["admin"] !== true){
    header("Location: ../../views/autenticacao/login.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"] !== "POST"){
    header("Location: ../../views/administrador/convidados.php");
    exit;
}

function voltar(string $aba, string $tipo, array $textos): void {
    // aprovar/bloquear pedidos pela Portaria voltam para a Portaria
    if(($_POST["retorno"] ?? "") === "portaria"){
        $_SESSION["msg_portaria"] = ["tipo" => $tipo, "textos" => $textos];
        header("Location: ../../views/administrador/portaria.php?aba=visitantes");
        exit;
    }

    $_SESSION["msg_convidados"] = ["tipo" => $tipo, "textos" => $textos];
    header("Location: ../../views/administrador/convidados.php?aba=" . $aba);
    exit;
}

// lê um campo do formulário sempre como texto (evita erro se alguém mandar um array)
function textoPost(string $campo): string {
    $valor = $_POST[$campo] ?? "";

    return is_string($valor) ? $valor : "";
}

$acao  = textoPost("acao");
$pdo   = conectarBanco();   // conexão com o MySQL (core/conexao.php)
$dados = carregarDados();

switch($acao){

    /* ================= CADASTRAR CONVIDADO ================= */
    case "cadastrar":
        $nome       = limparNome(textoPost("nome"));
        $nascimento = trim(textoPost("nascimento"));            // opcional
        $celular    = somenteNumeros(textoPost("celular"));     // opcional
        $cpf        = somenteNumeros(textoPost("cpf"));
        $erros      = [];
        $menor      = false;

        // NOME (obrigatório)
        if(!nomeValido($nome)){
            $erros[] = "Informe o nome completo do convidado (nome e sobrenome, somente letras).";
        }

        // NASCIMENTO (opcional, mas se vier preenchido precisa ser uma data real)
        if($nascimento !== ""){
            if(!dataValida($nascimento)){
                $erros[] = "Data de nascimento do convidado inválida.";
            } else {
                $menor = calcularIdade($nascimento) < 18;
            }
        }

        // CELULAR (opcional, mas se vier preenchido precisa estar completo)
        if($celular !== "" && !celularValido($celular)){
            $erros[] = "Celular do convidado inválido (use DDD + 9 dígitos).";
        }

        // CPF (obrigatório): 1) é um CPF válido?  2) já existe no banco?
        if(!cpfValido($cpf)){
            $erros[] = "CPF do convidado inválido.";
        } elseif(cpfJaCadastrado($pdo, $cpf)){
            $erros[] = "Já existe um convidado cadastrado com este CPF.";
        }

        // responsável: obrigatório se o convidado for menor de idade
        $responsavel = null;
        if($menor){
            $rNome       = limparNome(textoPost("resp_nome"));
            $rNascimento = trim(textoPost("resp_nascimento"));
            $rCelular    = somenteNumeros(textoPost("resp_celular"));
            $rCpf        = somenteNumeros(textoPost("resp_cpf"));

            if(!nomeValido($rNome)){
                $erros[] = "Informe o nome completo do responsável (nome e sobrenome, somente letras).";
            }

            if(!dataValida($rNascimento)){
                $erros[] = "Data de nascimento do responsável inválida.";
            } elseif(calcularIdade($rNascimento) < 18){
                $erros[] = "O responsável precisa ser maior de idade.";
            }

            if(!celularValido($rCelular)){
                $erros[] = "Celular do responsável inválido (use DDD + 9 dígitos).";
            }

            if(!cpfValido($rCpf)){
                $erros[] = "CPF do responsável inválido.";
            } elseif($rCpf === $cpf){
                $erros[] = "O CPF do responsável deve ser diferente do CPF do convidado.";
            }

            $responsavel = [
                "nome"       => $rNome,
                "nascimento" => $rNascimento,
                "celular"    => $rCelular,
                "cpf"        => $rCpf
            ];
        }

        if(!empty($erros)){
            $_SESSION["form_convidado"] = $_POST; // mantém o que foi digitado
            voltar("cadastro", "danger", $erros);
        }

        // grava no MySQL
        try {
            inserirConvidado($pdo, [
                "nome"        => $nome,
                "cpf"         => $cpf,
                "nascimento"  => $nascimento,
                "celular"     => $celular,
                "responsavel" => $responsavel
            ]);
        } catch(PDOException $e){
            error_log("Erro ao cadastrar convidado: " . $e->getMessage());
            $_SESSION["form_convidado"] = $_POST;

            // 1062 = chave duplicada: o índice UNIQUE do banco barrou um CPF repetido
            // (acontece se duas pessoas cadastrarem o mesmo CPF no mesmo instante)
            if((int)($e->errorInfo[1] ?? 0) === 1062){
                voltar("cadastro", "danger", ["Já existe um convidado cadastrado com este CPF."]);
            }

            voltar("cadastro", "danger", ["Não foi possível salvar o convidado agora. Tente novamente."]);
        }

        voltar("cadastro", "success", ["Convidado cadastrado com sucesso!"]);
        break;

    /* ================= EMITIR CONVITE ================= */
    case "emitir":
        $convidadoId = (int)($_POST["convidado_id"] ?? 0);
        $socioId     = (int)($_POST["socio_id"] ?? 0);

        $convidado = buscarPorId($dados["convidados"], $convidadoId);
        $socio     = buscarSocio($socioId);

        if(!$convidado || !$socio){
            voltar("convites", "danger", ["Selecione um convidado e um sócio válidos."]);
        }

        if(contarConvidadosDoSocio($dados, $socioId) >= LIMITE_CONVIDADOS_POR_SOCIO){
            voltar("convites", "danger", [
                "O sócio " . $socio["nome"] . " já atingiu o limite de " . LIMITE_CONVIDADOS_POR_SOCIO . " convidados."
            ]);
        }

        foreach($dados["convites"] as $c){
            if($c["convidado_id"] === $convidadoId && $c["socio_id"] === $socioId && $c["status"] !== "bloqueado"){
                voltar("convites", "danger", ["Este convidado já possui um convite ativo com este sócio."]);
            }
        }

        $dados["convites"][] = [
            "id"           => proximoId($dados["convites"]),
            "convidado_id" => $convidadoId,
            "socio_id"     => $socioId,
            "status"       => "pendente",
            "emitido_em"   => date("Y-m-d H:i:s")
        ];
        salvarDados($dados);

        voltar("convites", "success", ["Convite emitido com sucesso!"]);
        break;

    /* ================= APROVAR / BLOQUEAR ================= */
    case "aprovar":
    case "bloquear":
        $conviteId = (int)($_POST["convite_id"] ?? 0);
        $novoStatus = ($acao === "aprovar") ? "aprovado" : "bloqueado";
        $encontrado = false;

        foreach($dados["convites"] as &$convite){
            if($convite["id"] === $conviteId){
                $encontrado = true;

                // reativar um convite bloqueado ocupa uma vaga de novo: confere o limite
                if($convite["status"] === "bloqueado" && $novoStatus === "aprovado"
                   && contarConvidadosDoSocio($dados, $convite["socio_id"]) >= LIMITE_CONVIDADOS_POR_SOCIO){
                    unset($convite);
                    voltar("convites", "danger", ["O sócio já atingiu o limite de convidados."]);
                }

                $convite["status"] = $novoStatus;
                break;
            }
        }
        unset($convite);

        if(!$encontrado){
            voltar("convites", "danger", ["Convite não encontrado."]);
        }

        salvarDados($dados);
        voltar("convites", "success", ["Convite " . $novoStatus . " com sucesso."]);
        break;

    /* ================= REGISTRAR VISITA ================= */
    case "visita":
        $conviteId = (int)($_POST["convite_id"] ?? 0);
        $convite   = buscarPorId($dados["convites"], $conviteId);

        if(!$convite || $convite["status"] !== "aprovado"){
            voltar("convites", "danger", ["Só é possível registrar visita de convite aprovado."]);
        }

        $dados["visitas"][] = [
            "id"           => proximoId($dados["visitas"]),
            "convite_id"   => $convite["id"],
            "convidado_id" => $convite["convidado_id"],
            "socio_id"     => $convite["socio_id"],
            "data_hora"    => date("Y-m-d H:i:s")
        ];
        salvarDados($dados);

        voltar("convites", "success", ["Visita registrada no histórico."]);
        break;

    default:
        voltar("cadastro", "danger", ["Ação inválida."]);
}