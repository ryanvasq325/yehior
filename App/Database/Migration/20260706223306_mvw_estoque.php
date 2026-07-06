<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MvwEstoque extends AbstractMigration
{
      public function up(): void
    {
        $this->execute("
            CREATE MATERIALIZED VIEW mvw_estoque AS
                SELECT
                    p.id AS id_produto,
                    p.nome,
                    SUM(COALESCE(sm.quantidade_entrada, 0)) AS total_entradas,
                    SUM(COALESCE(sm.quantidade_saida, 0)) AS total_saidas,
                    (SUM(COALESCE(sm.quantidade_entrada, 0)) - SUM(COALESCE(sm.quantidade_saida, 0))) AS estoque_atual,
                    MAX(sm.data_cadastro) AS ultima_movimentacao
                FROM
                    stock_movement sm
                LEFT JOIN products p ON p.id = sm.id_produto
                WHERE p.excluido != true
                GROUP BY p.id, p.nome;
        ");

        $this->execute("
            CREATE INDEX products_id_hash ON products USING HASH (id);
            CREATE INDEX products_nome_hash ON products USING HASH (nome);
            CREATE INDEX stock_movement_idprd_hash ON stock_movement USING HASH (id_produto);
        ");
    }

    public function down(): void
    {
        $this->execute("DROP MATERIALIZED VIEW IF EXISTS mvw_estoque CASCADE;");
        $this->execute("
            DROP INDEX IF EXISTS products_id_hash;
            DROP INDEX IF EXISTS products_nome_hash;
            DROP INDEX IF EXISTS stock_movement_idprd_hash;
        ");
    }
}
