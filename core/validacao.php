<?php
// Funções de validação e higienização de dados de formulário.
// Usadas pelos controllers (ex.: controllers/convidados/convidados-processar.php).

// "(85) 99999-8888" -> "85999998888"
function somenteNumeros(string $texto): string {
    return preg_replace('/\D/', '', $texto);
}

// Aceita só "AAAA-MM-DD" que exista no calendário, de 1900 até hoje (nascimento não pode ser no futuro).
function dataValida(string $data): bool {
    // o "!" zera a hora; sem ele, a data de hoje seria considerada "maior" que hoje 00:00
    $d = DateTime::createFromFormat('!Y-m-d', $data);

    // o format() confere se a data não foi "corrigida" (ex.: 2026-02-31 viraria 03-03)
    if($d === false || $d->format('Y-m-d') !== $data){
        return false;
    }

    return $d >= new DateTime('1900-01-01') && $d <= new DateTime('today');
}

// idade em anos completos
function calcularIdade(string $nascimento): int {
    return (new DateTime($nascimento))->diff(new DateTime('today'))->y;
}

// DDD (2 dígitos, sem zero) + 9 + 8 dígitos. Ex.: 85999998888
function celularValido(string $celular): bool {
    return (bool)preg_match('/^[1-9][1-9]9\d{8}$/', $celular);
}

// CPF com os 11 dígitos (já sem pontos/traço) e dígitos verificadores corretos
function cpfValido(string $cpf): bool {
    // 11 dígitos e não pode ser sequência repetida (111.111.111-11 passa na conta, mas é inválido)
    if(!preg_match('/^\d{11}$/', $cpf) || preg_match('/^(\d)\1{10}$/', $cpf)){
        return false;
    }

    // $t = posição do dígito verificador: 9 (primeiro) e 10 (segundo)
    for($t = 9; $t < 11; $t++){
        $soma = 0;

        for($i = 0; $i < $t; $i++){
            $soma += (int)$cpf[$i] * (($t + 1) - $i);
        }

        $digito = ((10 * $soma) % 11) % 10;

        if((int)$cpf[$t] !== $digito){
            return false;
        }
    }

    return true;
}