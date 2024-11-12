<div class="fixed-plugin">
    <a class="fixed-plugin-button text-dark position-fixed px-3 py-2">
        <i class="material-icons py-2">settings</i>
    </a>
    <div class="card shadow-lg">
        <div class="card-header pb-0 pt-3">
            <div class="float-start">
                <h5 class="mt-3 mb-0">Material UI Configurator</h5>
                <p>See our dashboard options.</p>
            </div>
            <div class="float-end mt-4">
                <button
                    class="btn btn-link text-dark p-0 fixed-plugin-close-button">
                    <i class="material-icons">clear</i>
                </button>
            </div>
            <!-- End Toggle Button -->
        </div>
        <hr class="horizontal dark my-1" />
        <div class="card-body pt-sm-3 pt-0">
            <!-- Sidebar Backgrounds -->
            <div>
                <h6 class="mb-0">Sidebar Colors</h6>
            </div>
            <a href="javascript:void(0)" class="switch-trigger background-color">
                <div class="badge-colors my-2 text-start">
                    <span
                        class="badge filter bg-gradient-primary active"
                        data-color="primary"
                        onclick="sidebarColor(this)"></span>
                    <span
                        class="badge filter bg-gradient-dark"
                        data-color="dark"
                        onclick="sidebarColor(this)"></span>
                    <span
                        class="badge filter bg-gradient-info"
                        data-color="info"
                        onclick="sidebarColor(this)"></span>
                    <span
                        class="badge filter bg-gradient-success"
                        data-color="success"
                        onclick="sidebarColor(this)"></span>
                    <span
                        class="badge filter bg-gradient-warning"
                        data-color="warning"
                        onclick="sidebarColor(this)"></span>
                    <span
                        class="badge filter bg-gradient-danger"
                        data-color="danger"
                        onclick="sidebarColor(this)"></span>
                </div>
            </a>
            <!-- Sidenav Type -->
            <div class="mt-3">
                <h6 class="mb-0">Sidenav Type</h6>
                <p class="text-sm">Choose between 2 different sidenav types.</p>
            </div>
            <div class="d-flex">
                <button
                    class="btn bg-gradient-dark px-3 mb-2 active"
                    data-class="bg-gradient-dark"
                    onclick="sidebarType(this)">
                    Dark
                </button>
                <button
                    class="btn bg-gradient-dark px-3 mb-2 ms-2"
                    data-class="bg-transparent"
                    onclick="sidebarType(this)">
                    Transparent
                </button>
                <button
                    class="btn bg-gradient-dark px-3 mb-2 ms-2"
                    data-class="bg-white"
                    onclick="sidebarType(this)">
                    White
                </button>
            </div>
            <p class="text-sm d-xl-none d-block mt-2">
                You can change the sidenav type just on desktop view.
            </p>
            <!-- Navbar Fixed -->
            <div class="mt-3 d-flex">
                <h6 class="mb-0">Navbar Fixed</h6>
                <div class="form-check form-switch ps-0 ms-auto my-auto">
                    <input
                        class="form-check-input mt-1 ms-auto"
                        type="checkbox"
                        id="navbarFixed"
                        onclick="navbarFixed(this)" />
                </div>
            </div>
            <hr class="horizontal dark my-3" />
            <div class="mt-2 d-flex">
                <h6 class="mb-0">Light / Dark</h6>
                <div class="form-check form-switch ps-0 ms-auto my-auto">
                    <input
                        class="form-check-input mt-1 ms-auto"
                        type="checkbox"
                        id="dark-version"
                        onclick="darkMode(this)" />
                </div>
            </div>
            <hr class="horizontal dark my-sm-4" />
            <a
                class="btn bg-gradient-info w-100"
                href="https://www.creative-tim.com/product/material-dashboard-pro">Free Download</a>
            <a
                class="btn btn-outline-dark w-100"
                href="https://www.creative-tim.com/learning-lab/bootstrap/overview/material-dashboard">View documentation</a>
            <div class="w-100 text-center">
                <a
                    class="github-button"
                    href="https://github.com/creativetimofficial/material-dashboard"
                    data-icon="octicon-star"
                    data-size="large"
                    data-show-count="true"
                    aria-label="Star creativetimofficial/material-dashboard on GitHub">Star</a>
                <h6 class="mt-3">Thank you for sharing!</h6>
                <a
                    href="https://twitter.com/intent/tweet?text=Check%20Material%20UI%20Dashboard%20made%20by%20%40CreativeTim%20%23webdesign%20%23dashboard%20%23bootstrap5&amp;url=https%3A%2F%2Fwww.creative-tim.com%2Fproduct%2Fsoft-ui-dashboard"
                    class="btn btn-dark mb-0 me-2"
                    target="_blank">
                    <i class="fab fa-twitter me-1" aria-hidden="true"></i> Tweet
                </a>
                <a
                    href="https://www.facebook.com/sharer/sharer.php?u=https://www.creative-tim.com/product/material-dashboard"
                    class="btn btn-dark mb-0 me-2"
                    target="_blank">
                    <i class="fab fa-facebook-square me-1" aria-hidden="true"></i>
                    Share
                </a>
            </div>
        </div>
    </div>
</div>
<!--   Core JS Files   -->
<!-- <script src="
assets/js/core/jquery-3.7.1.min.js"></script> -->
<script src="<?php echo BASE_URL; ?>assets/js/core/popper.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/core/bootstrap.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/plugins/perfect-scrollbar.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/plugins/smooth-scrollbar.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/plugins/chartjs.min.js"></script>

<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/jquery.dataTables.min.css">
<script src="<?php echo BASE_URL; ?>assets/js/core/jquery-3.6.0.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/core/jquery.dataTables.min.js"></script>

<script src="<?php echo BASE_URL; ?>assets/js/core/sweetalert.js"></script>

<!-- Initialize DataTables with pagination, search, and styling -->
<script>
    $(document).ready(function() {
        $('#assignmentsTable').DataTable({
            "pageLength": 10, // Default to 10 rows per page
            "lengthMenu": [
                [10, 25, 50, -1],
                [10, 25, 50, "All"]
            ], // Page size options
            "ordering": true, // Enable sorting
            "searching": false, // Disable search functionality
            "pagingType": "simple_numbers", // Use a simple pagination style
            "dom": '<"top">rt<"bottom"lp><"clear">', // Remove the 'f' option for search input
        });
    });
</script>


<script>
    var ctx = document.getElementById("chart-bars").getContext("2d");

    new Chart(ctx, {
        type: "bar",
        data: {
            labels: ["M", "T", "W", "T", "F", "S", "S"],
            datasets: [{
                label: "Sales",
                tension: 0.4,
                borderWidth: 0,
                borderRadius: 4,
                borderSkipped: false,
                backgroundColor: "rgba(255, 255, 255, .8)",
                data: [50, 20, 10, 22, 50, 10, 40],
                maxBarThickness: 6,
            }, ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
            },
            interaction: {
                intersect: false,
                mode: "index",
            },
            scales: {
                y: {
                    grid: {
                        drawBorder: false,
                        display: true,
                        drawOnChartArea: true,
                        drawTicks: false,
                        borderDash: [5, 5],
                        color: "rgba(255, 255, 255, .2)",
                    },
                    ticks: {
                        suggestedMin: 0,
                        suggestedMax: 500,
                        beginAtZero: true,
                        padding: 10,
                        font: {
                            size: 14,
                            weight: 300,
                            family: "Roboto",
                            style: "normal",
                            lineHeight: 2,
                        },
                        color: "#fff",
                    },
                },
                x: {
                    grid: {
                        drawBorder: false,
                        display: true,
                        drawOnChartArea: true,
                        drawTicks: false,
                        borderDash: [5, 5],
                        color: "rgba(255, 255, 255, .2)",
                    },
                    ticks: {
                        display: true,
                        color: "#f8f9fa",
                        padding: 10,
                        font: {
                            size: 14,
                            weight: 300,
                            family: "Roboto",
                            style: "normal",
                            lineHeight: 2,
                        },
                    },
                },
            },
        },
    });

    var ctx2 = document.getElementById("chart-line").getContext("2d");

    new Chart(ctx2, {
        type: "line",
        data: {
            labels: [
                "Apr",
                "May",
                "Jun",
                "Jul",
                "Aug",
                "Sep",
                "Oct",
                "Nov",
                "Dec",
            ],
            datasets: [{
                label: "Mobile apps",
                tension: 0,
                borderWidth: 0,
                pointRadius: 5,
                pointBackgroundColor: "rgba(255, 255, 255, .8)",
                pointBorderColor: "transparent",
                borderColor: "rgba(255, 255, 255, .8)",
                borderColor: "rgba(255, 255, 255, .8)",
                borderWidth: 4,
                backgroundColor: "transparent",
                fill: true,
                data: [50, 40, 300, 320, 500, 350, 200, 230, 500],
                maxBarThickness: 6,
            }, ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
            },
            interaction: {
                intersect: false,
                mode: "index",
            },
            scales: {
                y: {
                    grid: {
                        drawBorder: false,
                        display: true,
                        drawOnChartArea: true,
                        drawTicks: false,
                        borderDash: [5, 5],
                        color: "rgba(255, 255, 255, .2)",
                    },
                    ticks: {
                        display: true,
                        color: "#f8f9fa",
                        padding: 10,
                        font: {
                            size: 14,
                            weight: 300,
                            family: "Roboto",
                            style: "normal",
                            lineHeight: 2,
                        },
                    },
                },
                x: {
                    grid: {
                        drawBorder: false,
                        display: false,
                        drawOnChartArea: false,
                        drawTicks: false,
                        borderDash: [5, 5],
                    },
                    ticks: {
                        display: true,
                        color: "#f8f9fa",
                        padding: 10,
                        font: {
                            size: 14,
                            weight: 300,
                            family: "Roboto",
                            style: "normal",
                            lineHeight: 2,
                        },
                    },
                },
            },
        },
    });

    var ctx3 = document.getElementById("chart-line-tasks").getContext("2d");

    new Chart(ctx3, {
        type: "line",
        data: {
            labels: [
                "Apr",
                "May",
                "Jun",
                "Jul",
                "Aug",
                "Sep",
                "Oct",
                "Nov",
                "Dec",
            ],
            datasets: [{
                label: "Mobile apps",
                tension: 0,
                borderWidth: 0,
                pointRadius: 5,
                pointBackgroundColor: "rgba(255, 255, 255, .8)",
                pointBorderColor: "transparent",
                borderColor: "rgba(255, 255, 255, .8)",
                borderWidth: 4,
                backgroundColor: "transparent",
                fill: true,
                data: [50, 40, 300, 220, 500, 250, 400, 230, 500],
                maxBarThickness: 6,
            }, ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
            },
            interaction: {
                intersect: false,
                mode: "index",
            },
            scales: {
                y: {
                    grid: {
                        drawBorder: false,
                        display: true,
                        drawOnChartArea: true,
                        drawTicks: false,
                        borderDash: [5, 5],
                        color: "rgba(255, 255, 255, .2)",
                    },
                    ticks: {
                        display: true,
                        padding: 10,
                        color: "#f8f9fa",
                        font: {
                            size: 14,
                            weight: 300,
                            family: "Roboto",
                            style: "normal",
                            lineHeight: 2,
                        },
                    },
                },
                x: {
                    grid: {
                        drawBorder: false,
                        display: false,
                        drawOnChartArea: false,
                        drawTicks: false,
                        borderDash: [5, 5],
                    },
                    ticks: {
                        display: true,
                        color: "#f8f9fa",
                        padding: 10,
                        font: {
                            size: 14,
                            weight: 300,
                            family: "Roboto",
                            style: "normal",
                            lineHeight: 2,
                        },
                    },
                },
            },
        },
    });
</script>
<script>
    var win = navigator.platform.indexOf("Win") > -1;
    if (win && document.querySelector("#sidenav-scrollbar")) {
        var options = {
            damping: "0.5",
        };
        Scrollbar.init(document.querySelector("#sidenav-scrollbar"), options);
    }
</script>
<!-- Github buttons -->
<!-- <script async defer src="https://buttons.github.io/buttons.js"></script> -->
<!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
<script src="<?php echo BASE_URL; ?>assets/js/material-dashboard.min.js?v=3.1.0"></script>


<!-- <script>
    $(document).ready(function() {
        $("#basic-datatables").DataTable({});

        $("#multi-filter-select").DataTable({
            pageLength: 5,
            initComplete: function() {
                this.api()
                    .columns()
                    .every(function() {
                        var column = this;
                        var select = $(
                                '<select class="form-select"><option value=""></option></select>'
                            )
                            .appendTo($(column.footer()).empty())
                            .on("change", function() {
                                var val = $.fn.dataTable.util.escapeRegex($(this).val());

                                column
                                    .search(val ? "^" + val + "$" : "", true, false)
                                    .draw();
                            });

                        column
                            .data()
                            .unique()
                            .sort()
                            .each(function(d, j) {
                                select.append(
                                    '<option value="' + d + '">' + d + "</option>"
                                );
                            });
                    });
            },
        });

        // Add Row
        $("#add-row").DataTable({
            pageLength: 5,
        });

        var action =
            '<td> <div class="form-button-action"> <button type="button" data-bs-toggle="tooltip" title="" class="btn btn-link btn-primary btn-lg" data-original-title="Edit Task"> <i class="fa fa-edit"></i> </button> <button type="button" data-bs-toggle="tooltip" title="" class="btn btn-link btn-danger" data-original-title="Remove"> <i class="fa fa-times"></i> </button> </div> </td>';

        $("#addRowButton").click(function() {
            $("#add-row")
                .dataTable()
                .fnAddData([
                    $("#addName").val(),
                    $("#addPosition").val(),
                    $("#addOffice").val(),
                    action,
                ]);
            $("#addRowModal").modal("hide");
        });
    });
</script> -->

<script>
    function updateBreadcrumbs(crumbs) {
        const breadcrumbList = document.querySelector('.breadcrumb');
        breadcrumbList.innerHTML = ''; // Clear existing items

        // Add each crumb to the breadcrumb list
        crumbs.forEach((crumb, index) => {
            const listItem = document.createElement('li');
            listItem.className = 'breadcrumb-item text-sm';

            // If it’s the last crumb, mark it as active
            if (index === crumbs.length - 1) {
                listItem.classList.add('active', 'text-dark');
                listItem.setAttribute('aria-current', 'page');
                listItem.textContent = crumb;
            } else {
                const link = document.createElement('a');
                link.className = 'opacity-5 text-dark';
                link.href = 'javascript:;'; // Replace with actual link
                link.textContent = crumb;

                listItem.appendChild(link);
            }

            breadcrumbList.appendChild(listItem);
        });
    }

    // Example usage:
    const pageTitle = 'Map';
    updateBreadcrumbs(['Pages', pageTitle]);
</script>

<script>
    function updateBreadcrumbs(crumbs) {
        const breadcrumbList = document.querySelector('.breadcrumb');
        const pageTitleElement = document.getElementById('pageTitle');
        breadcrumbList.innerHTML = ''; // Clear existing items

        // Update the title based on the last crumb
        pageTitleElement.textContent = crumbs[crumbs.length - 1];

        // Add each crumb to the breadcrumb list
        crumbs.forEach((crumb, index) => {
            const listItem = document.createElement('li');
            listItem.className = 'breadcrumb-item text-sm';

            // If it’s the last crumb, mark it as active
            if (index === crumbs.length - 1) {
                listItem.classList.add('active', 'text-dark');
                listItem.setAttribute('aria-current', 'page');
                listItem.textContent = crumb;
            } else {
                const link = document.createElement('a');
                link.className = 'opacity-5 text-dark';
                link.href = 'javascript:;'; // Replace with actual link
                link.textContent = crumb;

                listItem.appendChild(link);
            }

            breadcrumbList.appendChild(listItem);
        });
    }
</script>