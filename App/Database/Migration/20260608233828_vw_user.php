<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class VwUser extends AbstractMigration
{

     /**
     * Aplica as alterações no banco (Cria a View)
     */
    public function up(): void
    {
        $this->execute(<<<'SQL'
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
                u.data_cadastro,
                u.data_atualizacao
            FROM public.users u
            LEFT JOIN public.contact c
                   ON c.id_users = u.id
            GROUP BY
                u.id,
                u.nome,
                u.sobrenome,
                u.cpf,
                u.rg,
                u.senha,
                u.ativo,
                u.administrador,
                u.data_cadastro,
                u.data_atualizacao
        SQL);
    }

    /**
     * Desfaz as alterações (Remove a View no Rollback)
     */
    public function down(): void
    {
        $this->execute('DROP VIEW IF EXISTS vw_user');
    }
}

