import {
    createRequest,
    updateRequest
} from "./api.js";

import {
    loadData
} from "./table.js";

let currentUser = null;
let editingId = null;

export function initModal(user) {

    currentUser = user;

    const btn =
        document.getElementById(
            "addRequestBtn"
        );

    const modal =
        document.getElementById(
            "modal"
        );

    const modalContent =
        document.querySelector(
            ".modal-content"
        );

    if (btn) {
        btn.onclick = () => {
            openAddModal();
        };
    }

    modal.addEventListener(
        "click",
        closeModal
    );

    modalContent.addEventListener(
        "click",
        e => e.stopPropagation()
    );

    window.saveRequest =
        saveRequest;

    window.closeModal =
        closeModal;
}

function openAddModal() {

    editingId = null;

    document.getElementById(
        "modalTitle"
    ).innerText =
        "Nová pozice";

    clearFields();

    document.getElementById(
        "positionsCount"
    ).value = 1;

    document.getElementById(
        "positionsCountContainer"
    ).style.display = "block";

    document.getElementById(
        "department"
    ).value =
        currentUser.department || "";

    document.getElementById(
        "department"
    ).readOnly = true;

    applyRoleVisibility();

    document.getElementById(
        "status"
    ).value = "NEW";

    showModal();
}

export function openEditModal(row) {

    editingId = row.Id;

    document.getElementById(
        "modalTitle"
    ).innerText =
        "Editace pozice";

    document.getElementById(
        "positionsCountContainer"
    ).style.display = "none";

    document.getElementById(
        "workerType"
    ).value =
        row.WorkerType || "";

    document.getElementById(
        "department"
    ).value =
        row.Department || "";

    document.getElementById(
        "department"
    ).readOnly = true;

    document.getElementById(
        "positionName"
    ).value =
        row.PositionName || "";

    document.getElementById(
        "neededDate"
    ).value =
        formatInputDate(
            row.NeededDate
        );

    document.getElementById(
        "candidateName"
    ).value =
        row.CandidateName || "";

    document.getElementById(
        "expectedStartDate"
    ).value =
        formatInputDate(
            row.ExpectedStartDate
        );

    document.getElementById(
        "note"
    ).value =
        row.Note || "";

    applyRoleVisibility();

    const status =
        document.getElementById(
            "status"
        );

    if (
        status.querySelector(
            `option[value="${row.Status}"]`
        )
    ) {
        status.value =
            row.Status;
    } else {
        status.value =
            "NEW";
    }

    showModal();
}

function applyRoleVisibility() {

    const role =
        (
            currentUser?.role ||
            currentUser?.Role ||
            ""
        ).toUpperCase();

    const isHR =
        role === "HR" ||
        role === "ADMIN";

    const hrFields =
        document.querySelectorAll(
            ".hr-fields"
        );

    hrFields.forEach(el => {

        el.style.display =
            isHR
                ? "block"
                : "none";
    });

    const status =
        document.getElementById(
            "status"
        );

    if (!status) return;

    if (isHR) {

        status.innerHTML = `
            <option value="NEW">Nový</option>
            <option value="IN_RECRUITMENT">Probíhá nábor</option>
            <option value="CANDIDATE_FOUND">Kandidát nalezen</option>
            <option value="START_CONFIRMED">Nástup potvrzen</option>
            <option value="COMPLETED">Splněno</option>
            <option value="CANCELLED">Zrušeno</option>
        `;

    } else {

        status.innerHTML = `
            <option value="NEW">Nový</option>
            <option value="CANCELLED">Zrušeno</option>
        `;
    }
}

function clearFields() {

    [
        "workerType",
        "department",
        "positionName",
        "positionsCount",
        "neededDate",
        "candidateName",
        "expectedStartDate",
        "status",
        "note"
    ].forEach(id => {

        const el =
            document.getElementById(id);

        if (el) {
            el.value = "";
        }
    });
}

function showModal() {

    document
        .getElementById(
            "modal"
        )
        .style.display =
        "block";
}

function closeModal() {

    document
        .getElementById(
            "modal"
        )
        .style.display =
        "none";
}

async function saveRequest() {

    const payload = {

        workerType:
        document.getElementById(
            "workerType"
        ).value,

        positionName:
        document.getElementById(
            "positionName"
        ).value,

        positionsCount:
            parseInt(
                document.getElementById(
                    "positionsCount"
                ).value || 1
            ),

        neededDate:
        document.getElementById(
            "neededDate"
        ).value,

        candidateName:
        document.getElementById(
            "candidateName"
        ).value,

        expectedStartDate:
        document.getElementById(
            "expectedStartDate"
        ).value,

        status:
        document.getElementById(
            "status"
        ).value,

        note:
        document.getElementById(
            "note"
        ).value
    };

    let result;

    if (editingId) {

        payload.id =
            editingId;

        result =
            await updateRequest(
                payload
            );

    } else {

        result =
            await createRequest(
                payload
            );
    }

    if (result.success) {

        closeModal();

        loadData();

    } else {

        alert(
            "Chyba při ukládání"
        );

        console.error(
            result
        );
    }
}

function formatInputDate(value) {

    if (!value) return "";

    const date =
        new Date(value);

    return date
        .toISOString()
        .split("T")[0];
}