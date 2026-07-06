<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class StockFunctions extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("
            CREATE OR REPLACE FUNCTION refresh_mvw_estoque()
            RETURNS TRIGGER AS $$
            BEGIN
                REFRESH MATERIALIZED VIEW mvw_estoque;
                RETURN NULL;
            END;
            $$ LANGUAGE plpgsql;

            CREATE OR REPLACE FUNCTION fn_trigger_inicializar_estoque()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO stock_movement (
                    id_produto,
                    quantidade_entrada,
                    quantidade_saida,
                    observacao
                )
                VALUES (
                    NEW.id,
                    0,
                    0,
                    'INICIALIZAÇÃO DE CADASTRO'
                );
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");
    }

    public function down(): void
    {
        $this->execute("DROP FUNCTION IF EXISTS refresh_mvw_estoque() CASCADE;");
        $this->execute("DROP FUNCTION IF EXISTS fn_trigger_inicializar_estoque() CASCADE;");
    }
}
