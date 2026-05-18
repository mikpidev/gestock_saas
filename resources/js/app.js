import './bootstrap';
import '../css/app.css';
import '../css/custom.css';
import Alpine from 'alpinejs';
import { Chart, registerables } from 'chart.js';

window.Alpine = Alpine;
Alpine.start();

Chart.register(...registerables);

// Ejecutar solo si existe el canvas del gráfico
document.addEventListener('DOMContentLoaded', () => {
    const chartEl = document.getElementById('salesChart');
    if (!chartEl) return; // 

    const labelsEl = document.getElementById('weeklySalesLabels');
    const dataEl = document.getElementById('weeklySalesData');

    if (!labelsEl || !dataEl) return;

    const weeklySalesLabels = JSON.parse(labelsEl.textContent);
    const weeklySalesData = JSON.parse(dataEl.textContent);

    const ctx = chartEl.getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: weeklySalesLabels,
            datasets: [{
                label: 'Ventas',
                data: weeklySalesData,
                backgroundColor: '#ff0000',
                borderColor: '#af2828',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});
