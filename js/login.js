async function login() {

    const username =
        document.getElementById("username").value.trim();

    const password =
        document.getElementById("password").value;

    const errorElement =
        document.getElementById("error");

    errorElement.innerText = "";

    if (!username || !password) {

        errorElement.innerText =
            "❌ Vyplňte uživatelské jméno i heslo";

        return;
    }

    try {

        const res = await fetch(
            "../php/login.php",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: new URLSearchParams({
                    username,
                    password
                })
            }
        );

        const result = await res.json();

        if (result.success) {

            window.location.href = "index.html";

        } else {

            errorElement.innerText =
                "❌ Neplatné uživatelské jméno nebo heslo";
        }

    } catch (err) {

        console.error(err);

        errorElement.innerText =
            "❌ Chyba komunikace se serverem";
    }
}

document.addEventListener("keydown", (e) => {

    if (e.key === "Enter") {
        login();
    }
});