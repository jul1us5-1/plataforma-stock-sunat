{{-- Autocompleta razón social y dirección consultando el RUC/DNI. Uso: x-data="consultaDocumento()" en el formulario --}}
<script>
function consultaDocumento() {
    return {
        buscando: false, aviso: '',
        async buscar(numero) {
            this.aviso = '';
            if (! /^(\d{8}|\d{11})$/.test(numero)) { this.aviso = 'Escribe un DNI de 8 dígitos o un RUC de 11.'; return; }
            this.buscando = true;
            try {
                const r = await fetch(@js(url('consulta-documento')) + '/' + numero, { headers: { Accept: 'application/json' } });
                const datos = await r.json();
                if (! r.ok) { this.aviso = datos.mensaje ?? 'No se pudo consultar.'; return; }
                this.$el.querySelector('[name=razon_social]').value = datos.razon_social;
                if (datos.direccion) this.$el.querySelector('[name=direccion]').value = datos.direccion;
                if (datos.estado && (datos.estado !== 'ACTIVO' || (datos.condicion && datos.condicion !== 'HABIDO'))) {
                    this.aviso = `Atención: RUC ${datos.estado} / ${datos.condicion ?? ''}.`;
                }
            } catch (e) {
                this.aviso = 'No se pudo consultar.';
            } finally {
                this.buscando = false;
            }
        },
    };
}
</script>
