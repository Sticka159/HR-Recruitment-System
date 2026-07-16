import {
    getRequests
} from "./api.js";

import {
    openEditModal
} from "./modal.js";

let requestsData = [];
let filteredData = [];
let currentUser = null;

export function setUser(user) {
    currentUser = user;
}

export async function loadData() {

    try {

        requestsData =
            await getRequests();

        initSearch();

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

        const isHR =
            currentUser?.role === "HR" ||
            currentUser?.role === "ADMIN";

        if (isHR) {

            tr.innerHTML = `
                <td>${row.PositionName || ""}</td>
                <td>${row.Department || ""}</td>
                <td>${row.Quantity || ""}</td>
                <td>${row.Shifttype || row.ShiftType || ""}</td>
                <td>${formatDate(row.NeededDate)}</td>
                <td>${row.CandidateName || ""}</td>
                <td>${formatDate(row.ExpectedStartDate)}</td>
                <td>${formatPriority(row.Priority)}</td>
                <td>${formatStatus(row.Status)}</td>
            `;

        } else {

            tr.innerHTML = `
                <td>${row.PositionName || ""}</td>
                <td>${row.Department || ""}</td>
                <td>${row.Quantity || ""}</td>
                <td>${row.Shifttype || row.ShiftType || ""}</td>
                <td>${formatDate(row.NeededDate)}</td>
                <td>${formatPriority(row.Priority)}</td>
                <td>${formatStatus(row.Status)}</td>
            `;
        }

        tr.addEventListener(
            "click",
            () => openEditModal(row)
        );

        tbody.appendChild(tr);
    });
}

function formatDate(dateString) {

    if (!dateString) return "";

    const date =
        new Date(dateString);

    return date.toLocaleDateString(
        "cs-CZ"
    );
}

function formatPriority(priority) {

    const priorities = {
        LOW: "🟢 Nízká",
        MEDIUM: "🟡 Střední",
        HIGH: "🔴 Vysoká"
    };

    return priorities[priority] || priority;
}

function formatStatus(status) {

    const statuses = {
        NEW: "🆕 Nový",
        APPROVED: "✅ Schválený",
        IN_RECRUITMENT: "🔍 Probíhá nábor",
        CANDIDATE_FOUND: "👤 Kandidát nalezen",
        START_CONFIRMED: "📅 Nástup potvrzen",
        COMPLETED: "✔️ Splněno",
        CANCELLED: "❌ Zrušeno"
    };

    return statuses[status] || status;
}