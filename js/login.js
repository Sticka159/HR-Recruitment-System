function login() {

    window.location.href = "../php/login.php";

}

document.addEventListener("keydown", (e) => {

    if (e.key === "Enter") {
        login();
    }

});