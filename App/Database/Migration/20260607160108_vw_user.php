<?php

declare(strict_types=1);

namespace App\Database\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607160108 extends AbstractMigration
{
    # Descrição curta e legível do que esta migration faz
    public function getDescription(): string
    {
        return 'vw_user';
    }

    # Aplica as alterações no banco (criação ou mudança de estrutura)
    public function up(Schema $schema): void
    {
       {
            $this->addSql(<<<'SQL'
            CREATE OR REPLACE VIEW vw_user AS
            SELECT
                u.id,
                u.nome,
                u.sobrenome,
                u.cpf,
                u.rg,
                u.senha,
                u.ativo,
                u.administrador,
                MAX(c.contato) FILTER (WHERE c.tipo = 'EMAIL')    AS email,
                MAX(c.contato) FILTER (WHERE c.tipo = 'CELULAR')  AS celular,
                MAX(c.contato) FILTER (WHERE c.tipo = 'TELEFONE') AS telefone,
                u.criado_em,
                u.atualizado_em
            FROM public.users u
            LEFT JOIN public.contact c
                   ON c.id_usuario = u.id
            GROUP BY
                u.id,
                u.nome,
                u.sobrenome,
                u.cpf,
                u.rg,
                u.senha,
                u.ativo,
                u.administrador,
                u.criado_em,
                u.atualizado_em
        SQL);
        }
    }
    

    # Desfaz exatamente o que o método up() fez (rollback)
    public function down(Schema $schema): void
    {
        # escreva aqui o rollback do up()
    }
}