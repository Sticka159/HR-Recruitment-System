import { checkAuth } from "./auth.js";

import {
    loadData,
    setUser
} from "./table.js";

import {
    initModal
} from "./modal.js";

let currentView = "active";

async function init() {

    const user =
        await checkAuth();

    if (!user) return;

    setUser(user);

    console.log(
        "Přihlášený uživatel:",
        user
    );

    console.log(
        "Přiřazená role:",
        user.role
    );

    console.log(
        "Středisko:",
        user.department
    );

    console.log(
        "Email:",
        user.email
    );

    initModal(user);

    initViewSwitch();

    loadData(currentView);
}

function initViewSwitch() {

    const activeBtn =
        document.getElementById(
            "activeBtn"
        );

    const historyBtn =
        document.getElementById(
            "historyBtn"
        );

    if (
        !activeBtn ||
        !historyBtn
    ) {
        return;
    }

    activeBtn.addEventListener(
        "click",
        () => {

            currentView =
                "active";

            activeBtn.classList.add(
                "active-view"
            );

            historyBtn.classList.remove(
                "active-view"
            );

            loadData(
                currentView
            );
        }
    );

    historyBtn.addEventListener(
        "click",
        () => {

            currentView =
                "history";

            historyBtn.classList.add(
                "active-view"
            );

            activeBtn.classList.remove(
                "active-view"
            );

            loadData(
                currentView
            );
        }
    );
}

init();