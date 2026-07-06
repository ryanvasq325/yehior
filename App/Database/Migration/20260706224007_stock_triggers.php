<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class StockTriggers extends AbstractMigration
{
   public function up(): void
    {
        $this->execute("
            CREATE TRIGGER trg_refresh_estoque_on_movement
            AFTER INSERT OR UPDATE OR DELETE ON stock_movement
            FOR EACH STATEMENT
            EXECUTE FUNCTION refresh_mvw_estoque();

            CREATE TRIGGER trg_refresh_estoque_on_products
            AFTER UPDATE OF excluido, nome OR DELETE ON products
            FOR EACH STATEMENT
            EXECUTE FUNCTION refresh_mvw_estoque();

            CREATE TRIGGER trg_init_product_stock
            AFTER INSERT ON products
            FOR EACH ROW
            EXECUTE FUNCTION fn_trigger_inicializar_estoque();
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TRIGGER IF EXISTS trg_refresh_estoque_on_movement ON stock_movement;");
        $this->execute("DROP TRIGGER IF EXISTS trg_refresh_estoque_on_products ON products;");
        $this->execute("DROP TRIGGER IF EXISTS trg_init_product_stock ON products;");
    }
}
