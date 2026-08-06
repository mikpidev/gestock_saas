import "./bootstrap";
import "../css/app.css";
import "../css/custom.css";
import Alpine from "alpinejs";
import { Chart, registerables } from "chart.js";
import { callback } from "chart.js/helpers";

window.Alpine = Alpine;
Alpine.start();

Chart.register(...registerables);

let salesChart;
let paymentChart;
let dteChart;
let pagination;
let topProducts;
let peakHoursChart = null;

//getData Function All Data for Charts

function getData() {
    console.log("dashboard.js cargado");

    $.ajax({
        url: `/stores/${window.storeId}/dashboard-data`,
        method: "GET",
        dataType: "json",
        data: {
            //agregamos filtros
            from: $("#from").val(),
            to: $("#to").val(),
        },

        success: function (data) {
            console.log("Dashboard data ejecutado", data);

            //Get Tables Data
            console.log(data.topProducts);

            let html = "";

            data.topProducts.forEach((product, index) => {
                html += `
            <tr>
                <td>${index + 1}</td>
                <td>${product.product_type.name}</td>
                <td>${product.total_sold}</td>
                <td>${"$" + product.total}</td>

            </tr>
        `;
            });

            $("#topProductsTable tbody").html(html);

            //Get Cards Data
            document.getElementById("salesTodayTotalCard").textContent =
                "$" + Number(data.salesTodayTotal).toFixed(2);
            document.getElementById("salesTodayCountCard").textContent =
                data.salesTodayCount + " Ventas";

            document.getElementById("salesWeekTotalCard").textContent =
                "$" + Number(data.salesWeekTotal).toFixed(2);
            document.getElementById("salesWeekCountCard").textContent =
                data.salesWeekCount + " Ventas";

            document.getElementById("salesMonthTotalCard").textContent =
                "$" + Number(data.salesMonthTotal).toFixed(2);
            document.getElementById("salesMonthCountCard").textContent =
                data.salesMonthCount + " Ventas";

            document.getElementById("dteAprovedCard").textContent = Number(
                data.dteAproved,
            );
            document.getElementById("dteDenyCard").textContent = Number(
                data.dteDeny,
            );

            //Labels Val
            const salesLabels = data.chartData.map((item) => item.date);
            const peakHoursLabels = Array.from(
                { length: 24 },
                (_, hour) => `${hour.toString().padStart(2, "0")}:00`,
            );

            const paymentLabels = ["Efectivo", "tarjeta", "Transferencia"];
            const dteLabels = ["Factura", "CCF", "SE"];

            //Data Vals
            const salesData = data.chartData.map((item) => Number(item.total));
            const peakHoursData = Array.from({ length: 24 }, (_, hour) => {
                const item = data.peakHours.find((sale) => sale.hour === hour);

                return item ? item.total_sales : 0;
            });

            const methodPaymentData = [
                Number(data.methodPaymentData.efectivo),
                Number(data.methodPaymentData.tarjeta),
                Number(data.methodPaymentData.transferencia),
            ];

            const dteSummaryData = [
                Number(data.dteSummary.factura),
                Number(data.dteSummary.CCF),
                Number(data.dteSummary.SE),
            ];

            //Context object (ctx)

            const salesCtx = document
                .getElementById("salesChart")
                .getContext("2d");

            if (salesChart) {
                salesChart.destroy();
            }

            const paymentCtx = document
                .getElementById("paymentChart")
                .getContext("2d");

            if (paymentChart) {
                paymentChart.destroy();
            }

            const dteCtx = document.getElementById("dteChart").getContext("2d");

            if (dteChart) {
                dteChart.destroy();
            }

            const peakHoursctx = document
                .getElementById("peakHoursChart")
                .getContext("2d");

            if (peakHoursChart) {
                peakHoursChart.destroy();
            }

            //Sales Bar Chart

            salesChart = new Chart(salesCtx, {
                type: "bar",

                data: {
                    labels: salesLabels,

                    datasets: [
                        {
                            data: salesData,

                            backgroundColor: function (context) {
                                const chart = context.chart;
                                const { ctx, chartArea } = chart;

                                if (!chartArea) return "#ff0000";

                                const gradient = ctx.createLinearGradient(
                                    0,
                                    chartArea.bottom,
                                    0,
                                    chartArea.top,
                                );

                                gradient.addColorStop(0, "#ff0000");
                                gradient.addColorStop(1, "#FF6666");

                                return gradient;
                            },

                            borderWidth: 0,

                            borderRadius: 25,

                            borderSkipped: false,

                            barThickness: 12,

                            maxBarThickness: 14,
                        },
                    ],
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false,

                    plugins: {
                        legend: {
                            display: false,
                        },

                        tooltip: {
                            backgroundColor: "#1F2937",

                            titleColor: "#fff",

                            bodyColor: "#fff",

                            padding: 12,

                            callbacks: {
                                label(context) {
                                    return "$" + Number(context.raw).toFixed(2);
                                },
                            },
                        },
                    },

                    scales: {
                        x: {
                            grid: {
                                display: false,
                            },

                            border: {
                                display: false,
                            },

                            ticks: {
                                color: "#6B7280",

                                font: {
                                    size: 12,
                                    weight: "500",
                                },
                            },
                        },

                        y: {
                            beginAtZero: true,

                            border: {
                                display: false,
                            },

                            grid: {
                                color: "#EEF2F7",

                                drawBorder: false,
                            },

                            ticks: {
                                color: "#9CA3AF",

                                padding: 10,

                                callback(value) {
                                    return "$" + value;
                                },
                            },
                        },
                    },
                },
            });

            //Peak Hour
            peakHoursChart = new Chart(peakHoursctx, {
                type: "line",
                data: {
                    labels: peakHoursLabels,
                    datasets: [
                        {
                            label: "Ventas",
                            data: peakHoursData,
                            tension: 0.35,
                            fill: true,
                            borderWidth: 3,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            borderColor: "#FF6666",
                            backgroundColor: "rgb(255 204 204)",
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,

                    plugins: {
                        legend: {
                            display: false,
                        },
                    },

                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                            },
                            title: {
                                display: true,
                                text: "Ventas",
                            },
                        },
                        x: {
                            title: {
                                display: true,
                                text: "Hora del día",
                            },
                        },
                    },
                },
            });

            //Method of Payment Doughnut Chart

            paymentChart = new Chart(paymentCtx, {
                type: "doughnut",

                data: {
                    labels: paymentLabels,

                    datasets: [
                        {
                            label: "Métodos de Pago",

                            data: methodPaymentData,

                            backgroundColor: [
                                "#ff0000", 
                                "#FF6666", 
                                "#FF9999", 
                            ],

                            borderColor: "#ffffff",
                            borderWidth: 3,

                            hoverOffset: 12,
                        },
                    ],
                },

                options: {
                    responsive: true,
                    cutout: "68%",

                    plugins: {
                        legend: {
                            position: "bottom",

                            labels: {
                                usePointStyle: true,
                                pointStyle: "circle",
                                padding: 20,
                                font: {
                                    size: 13,
                                    weight: "600",
                                },
                            },
                        },

                        tooltip: {
                            backgroundColor: "#1F2937",
                            titleColor: "#fff",
                            bodyColor: "#fff",
                            padding: 12,

                            callbacks: {
                                label: function (context) {
                                    const labels = [
                                        "Efectivo",
                                        "Tarjeta",
                                        "Transferencia",
                                    ];

                                    const amounts = [
                                        data.methodPaymentData.monto_efectivo,
                                        data.methodPaymentData.monto_tarjeta,
                                        data.methodPaymentData
                                            .monto_transferencia,
                                    ];

                                    return `${labels[context.dataIndex]}: ${context.raw} ventas ($${amounts[context.dataIndex]})`;
                                },
                            },
                        },
                    },
                },
            });
            //DTE Summary Doughnut Chart

            dteChart = new Chart(dteCtx, {
                type: "doughnut",

                data: {
                    labels: dteLabels,

                    datasets: [
                        {
                            label: "DTE Conteo",
                            data: dteSummaryData,

                            backgroundColor: [
                                "#ff0000", 
                                "#FF6666", 
                                "#FF9999", 
                            ],

                            borderColor: "#FFFFFF",
                            borderWidth: 3,
                            hoverOffset: 12,
                        },
                    ],
                },

                options: {
                    responsive: true,
                    cutout: "68%",

                    plugins: {
                        legend: {
                            position: "bottom",

                            labels: {
                                usePointStyle: true,
                                pointStyle: "circle",
                                padding: 18,
                                font: {
                                    size: 13,
                                    weight: "600",
                                },
                            },
                        },

                        tooltip: {
                            backgroundColor: "#1F2937",
                            titleColor: "#FFFFFF",
                            bodyColor: "#FFFFFF",
                            padding: 12,

                            callbacks: {
                                label: function (context) {
                                    const labels = ["Factura", "CCF", "SE"];

                                    const amounts = [
                                        data.dteSummary.monto_factura,
                                        data.dteSummary.monto_CCF,
                                        data.dteSummary.monto_SE,
                                    ];

                                    return `${labels[context.dataIndex]}: ${context.raw} cantidad ($${amounts[context.dataIndex]})`;
                                },
                            },
                        },
                    },
                },
            });
        },

        error: function (error) {
            console.error("error", error);
        },
    });
}

function getPaginationData() {
    console.log("cargando pagination.js");

    $.ajax({
        url: `pagination-data`,
        method: "GET",
        dataType: "json",

        data: {
            //filtros
            from: $("#from").val(),
            to: $("#to").val(),
        },

        success: function (data) {
            console.log("Obteniendo datos para la paginacion de ventas", data);
        },
    });
}

window.getData = getData;

window.getPaginationData = getPaginationData;
