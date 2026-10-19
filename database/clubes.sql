-- Cerberus — módulo de clubes, administradores e associados.
-- Execute DEPOIS do database.sql (usa o mesmo banco "cerberus").
-- Não altera nem remove nada que já existe.

USE cerberus;

-- Cada clube que se cadastra sozinho na plataforma
CREATE TABLE IF NOT EXISTS clubes
(
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome      VARCHAR(120) NOT NULL,
    cnpj      CHAR(14)     NOT NULL,
    email     VARCHAR(150) NOT NULL,
    telefone  VARCHAR(11)  NOT NULL,
    cep       CHAR(8)      NOT NULL,
    endereco  VARCHAR(200) NOT NULL,
    cidade    VARCHAR(80)  NOT NULL,
    uf        CHAR(2)      NOT NULL,
    criado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_clubes_cnpj UNIQUE (cnpj)
) ENGINE = InnoDB;

-- Conta de administrador vinculada a UM clube (criada junto com o clube)
CREATE TABLE IF NOT EXISTS administradores
(
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clube_id         INT UNSIGNED     NOT NULL,
    nome             VARCHAR(120)     NOT NULL,
    email            VARCHAR(150)     NOT NULL,
    senha            VARCHAR(255)     NOT NULL,   -- hash gerado por password_hash()
    tentativas_login TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_ate    DATETIME         NULL,
    criado_em        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_administradores_email UNIQUE (email),
    CONSTRAINT fk_administradores_clube FOREIGN KEY (clube_id) REFERENCES clubes (id) ON DELETE CASCADE
) ENGINE = InnoDB;

-- Associados: sempre pertencem a um clube (isolamento por clube_id)
CREATE TABLE IF NOT EXISTS associados
(
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clube_id        INT UNSIGNED                         NOT NULL,
    nome            VARCHAR(120)                         NOT NULL,
    cpf             CHAR(11)                             NOT NULL,
    email           VARCHAR(150)                         NULL,
    telefone        VARCHAR(11)                          NOT NULL,
    nascimento      DATE                                 NOT NULL,
    matricula       VARCHAR(20)                          NULL,
    situacao        ENUM ('ativo', 'inativo', 'suspenso') NOT NULL DEFAULT 'ativo',
    criado_em       DATETIME                             NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_associados_clube_cpf UNIQUE (clube_id, cpf),
    CONSTRAINT uq_associados_clube_matricula UNIQUE (clube_id, matricula),
    CONSTRAINT fk_associados_clube FOREIGN KEY (clube_id) REFERENCES clubes (id) ON DELETE CASCADE
) ENGINE = InnoDB;
