<div class="h-64"><canvas id="{{ $id }}"></canvas></div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const datos = @js($serie);
    new Chart(document.getElementById(@js($id)), {
        type: 'bar',
        data: {
            labels: Object.keys(datos),
            datasets: [
                { label: 'Comprobantes', data: Object.values(datos).map(d => d.cpe), backgroundColor: '#2563eb' },
                { label: 'Recibos', data: Object.values(datos).map(d => d.recibos), backgroundColor: '#f43f5e' },
            ],
        },
        options: {
            maintainAspectRatio: false,
            scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { callback: v => 'S/ ' + v } } },
            plugins: { tooltip: { callbacks: { label: c => `${c.dataset.label}: S/ ${c.parsed.y.toFixed(2)}` } } },
        },
    });
});
</script>
