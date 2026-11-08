<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
create or replace function app_verificar_documento(p_token text)
returns table(tipo text, numero text, cliente text, fecha date, vence date, estado text)
language sql stable security definer set search_path = public as $fn$
    select 'garantia'::text, g.numero::text, c.razon_social::text, g.desde, g.hasta, g.estado::text
      from garantias g
      join ordenes_trabajo o on o.id = g.orden_trabajo_id
      join clientes c on c.id = o.cliente_id
     where g.token_verificacion = p_token
    union all
    select 'recibo'::text, p.numero::text, c.razon_social::text, p.fecha, null::date, 'vigente'::text
      from pagos p
      join clientes c on c.id = p.cliente_id
     where p.token_verificacion = p_token
    union all
    select 'factura'::text, f.numero::text, c.razon_social::text, f.fecha, f.vence, f.estado::text
      from facturas f
      join clientes c on c.id = f.cliente_id
     where f.token_verificacion = p_token
$fn$;
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('drop function if exists app_verificar_documento(text)');
        }
    }
};
