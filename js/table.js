import {
    getRequests
} from "./api.js";

import {
    openEditModal
} from "./modal.js";

let requestsData = [];
let filteredData = [];
let currentUser = null;

let currentSortColumn = null;
let currentSortDirection = "asc";
let currentView = "active";

export function setUser(user) {
    currentUser = user;
}

export async function loadData(view = "active") {

    try {

        currentView = view;

        requestsData =
            await getRequests(view);

        initSearch();
        initSorting();

        applyFilter();

    } catch (err) {

        console.error(
            "Load data error:",
            err
        );
    }
}

function initSearch() {

    const input =
        document.getElementById(
            "searchInput"
        );

    if (!input) return;

    input.oninput = () => {
        applyFilter();
    };
}

function initSorting() {

    const headers =
        document.querySelectorAll(
            "th[data-sort]"
        );

    headers.forEach(header => {

        header.style.cursor =
            "pointer";

        header.onclick = () => {

            const column =
                header.dataset.sort;

            if (
                currentSortColumn ===
                column
            ) {

                currentSortDirection =
                    currentSortDirection === "asc"
                        ? "desc"
                        : "asc";

            } else {

                currentSortColumn =
                    column;

                currentSortDirection =
                    "asc";
            }

            applyFilter();
        };
    });
}

function applyFilter() {

    const search =
        document
            .getElementById("searchInput")
            ?.value
            .toLowerCase() || "";

    filteredData =
        requestsData.filter(row => {

            return Object.values(row)
                .some(value => {

                    if (
                        value === null ||
                        value === undefined
                    ) {
                        return false;
                    }

                    return value
                        .toString()
                        .toLowerCase()
                        .includes(search);

                });
        });

    if (currentSortColumn) {

        filteredData.sort(
            (a, b) => {

                let valueA =
                    a[currentSortColumn];

                let valueB =
                    b[currentSortColumn];

                if (
                    valueA === null ||
                    valueA === undefined
                ) valueA = "";

                if (
                    valueB === null ||
                    valueB === undefined
                ) valueB = "";

                if (
                    currentSortColumn
                        .toLowerCase()
                        .includes("date")
                ) {

                    valueA =
                        new Date(valueA);

                    valueB =
                        new Date(valueB);
                }

                if (
                    valueA < valueB
                ) {
                    return currentSortDirection === "asc"
                        ? -1
                        : 1;
                }

                if (
                    valueA > valueB
                ) {
                    return currentSortDirection === "asc"
                        ? 1
                        : -1;
                }

                return 0;
            }
        );
    }

    renderTable();
}

function renderTable() {

    const tbody =
        document.querySelector(
            "#table tbody"
        );

    if (!tbody) return;

    tbody.innerHTML = "";

    filteredData.forEach(row => {

        const tr =
            document.createElement("tr");

        tr.classList.add(
            "status-" + row.Status
        );

        if (
            currentView === "active" &&
            row.Status !== "COMPLETED" &&
            row.Status !== "CANCELLED"
        ) {

            const urgencyClass =
                getUrgencyClass(
                    row.NeededDate
                );

            if (urgencyClass) {

                tr.classList.add(
                    urgencyClass
                );
            }
        }

        tr.innerHTML = `
            <td>${formatDate(row.CreatedAt)}</td>
            <td>${row.CreatedByName || ""}</td>
            <td>${row.Department || ""}</td>
            <td>${row.PositionName || ""}</td>
            <td>${formatDate(row.NeededDate)}</td>
            <td class="hr-column">${row.WorkerType || "-"}</td>
            <td class="hr-column">${row.CandidateName || "-"}</td>
            <td class="hr-column">${formatDate(row.ExpectedStartDate) || "-"}</td>
            <td>${row.Note || "-"}</td>
            <td>${formatStatus(row.Status)}</td>
        `;

        tr.addEventListener(
            "click",
            () => openEditModal(row)
        );

        tbody.appendChild(tr);
    });
}

function getUrgencyClass(
    neededDate
) {

    if (!neededDate)
        return "";

    const today =
        new Date();

    today.setHours(
        0,
        0,
        0,
        0
    );

    const target =
        new Date(
            neededDate
        );

    target.setHours(
        0,
        0,
        0,
        0
    );

    const diffDays =
        Math.ceil(
            (
                target -
                today
            ) /
            (
                1000 *
                60 *
                60 *
                24
            )
        );

    if (diffDays < 0)
        return "urgent-critical";

    if (diffDays <= 7)
        return "urgent-high";

    if (diffDays <= 14)
        return "urgent-medium";

    if (diffDays <= 30)
        return "urgent-low";

    return "";
}

function formatDate(dateString) {

    if (!dateString) return "-";

    const date =
        new Date(dateString);

    return date.toLocaleDateString(
        "cs-CZ"
    );
}

function formatStatus(status) {

    const statuses = {
        NEW: "🆕 Nový",
        IN_RECRUITMENT: "🔍 Probíhá nábor",
        CANDIDATE_FOUND: "👤 Kandidát nalezen",
        START_CONFIRMED: "📅 Nástup potvrzen",
        COMPLETED: "✔️ Splněno",
        CANCELLED: "❌ Zrušeno"
    };

    return statuses[status] || status;
}