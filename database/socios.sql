-- Tabela de sócios (cadastro feito pelo administrador).
-- Rode este arquivo uma vez no seu banco local (phpMyAdmin > aba SQL, ou pelo terminal:
--   mysql -u root cerberus < database/socios.sql
-- Pode rodar de novo sem problema: só cria a tabela se ela ainda não existir.

USE cerberus;

CREATE TABLE IF NOT EXISTS socios
(
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    matricula              VARCHAR(10)  NULL UNIQUE,

    -- dados pessoais
    nome                   VARCHAR(150) NOT NULL,
    data_nascimento        DATE         NOT NULL,
    genero                 VARCHAR(30)  NULL,
    estado_civil           VARCHAR(30)  NOT NULL,
    profissao              VARCHAR(100) NOT NULL,

    -- documentos
    cpf                    CHAR(11)     NOT NULL UNIQUE,
    rg                     VARCHAR(20)  NOT NULL,
    orgao_expedidor        VARCHAR(20)  NOT NULL,

    -- contato
    telefone               VARCHAR(15)  NOT NULL,
    email                  VARCHAR(150) NOT NULL UNIQUE,

    -- endereço
    cep                    CHAR(8)      NOT NULL,
    logradouro             VARCHAR(150) NOT NULL,
    numero                 VARCHAR(15)  NOT NULL,
    complemento            VARCHAR(80)  NULL,
    bairro                 VARCHAR(80)  NOT NULL,
    cidade                 VARCHAR(80)  NOT NULL,
    uf                     CHAR(2)      NOT NULL,

    -- associação e mensalidade
    plano                  VARCHAR(20)  NOT NULL,
    data_inicio            DATE         NOT NULL,
    dia_vencimento         TINYINT      NOT NULL,

    -- emergência e observações
    contato_emergencia_nome     VARCHAR(150) NOT NULL,
    contato_emergencia_telefone VARCHAR(15)  NOT NULL,
    observacoes            TEXT         NULL,

    criado_em              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
);
