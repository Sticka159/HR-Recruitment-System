const themeLink =
    document.getElementById("themeStylesheet");

const lightBtn =
    document.getElementById("lightBtn");

const darkBtn =
    document.getElementById("darkBtn");

// Načtení uloženého tématu

const savedTheme =
    localStorage.getItem("theme");

if (savedTheme === "dark") {

    setDark();

} else {

    setLight();
}

// Light

lightBtn?.addEventListener(
    "click",
    () => {

        setLight();

        localStorage.setItem(
            "theme",
            "light"
        );
    }
);

// Dark

darkBtn?.addEventListener(
    "click",
    () => {

        setDark();

        localStorage.setItem(
            "theme",
            "dark"
        );
    }
);

// Funkce

function setLight() {

    if (!themeLink) return;

    themeLink.href =
        "../css/style.css";

    lightBtn?.classList.add(
        "active"
    );

    darkBtn?.classList.remove(
        "active"
    );
}

function setDark() {

    if (!themeLink) return;

    themeLink.href =
        "../css/altStyle.css";

    darkBtn?.classList.add(
        "active"
    );

    lightBtn?.classList.remove(
        "active"
    );
}