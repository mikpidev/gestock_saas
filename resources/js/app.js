import "./bootstrap";
import "../css/app.css";
import "../css/custom.css";
import Alpine from "alpinejs";
import { Chart, registerables } from "chart.js";
import { callback } from "chart.js/helpers";

window.Alpine = Alpine;
Alpine.start();

Chart.register(...registerables);

console.log("dashboard.js cargado");

let salesChart;
let paymentChart;
let dteChart;

//getData Function All Data fro Charts

function getData() {
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
            console.log("getData ejecutado", data);

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
            const paymentLabels = ["Efectivo", "tarjeta", "Transferencia"];
            const dteLabels = ["Factura", "CCF", "SE"];

            //Data Vals
            const salesData = data.chartData.map((item) => Number(item.total));

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

            //Sales Bar Chart

            salesChart = new Chart(salesCtx, {
                type: "bar",
                data: {
                    labels: salesLabels,
                    datasets: [
                        {
                            label: "Ventas",
                            data: salesData,
                            backgroundColor: "#FF6666",
                            borderColor: "#ff0000",
                            borderWidth: 1,
                            borderRadius: 30,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                        },
                    },

                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return "$" + Number(context.raw).toFixed(2);
                                },
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
                            label: "Metodos de Pago",
                            data: methodPaymentData,
                        },
                    ],
                },
                options: {
                    responsive: true,

                    plugins: {
                        legend: {
                            position: "top",
                        },
                        tooltip: {
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
                        },
                    ],
                },

                options: {
                    responsive: true,

                    plugins: {
                        legend: {
                            position: "top",
                        },
                    },
                    tooltip: {
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
            });
        },

        error: function (error) {
            console.error("error", error);
        },
    });
}

document.addEventListener("DOMContentLoaded", () => {
    console.log("DOM listo");
    getData();

    $("#from, #to").on("change", function () {
        getData();
    });
});
