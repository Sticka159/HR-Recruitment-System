export async function checkAuth() {

    try {

        const res = await fetch(
            "../php/checkAuth.php"
        );

        const data =
            await res.json();

        if (!data.authenticated) {

            window.location.href =
                "./php/auth/login.php";

            return null;
        }

        return data;

    } catch (err) {

        console.error(
            "Auth error:",
            err
        );

        window.location.href =
            "./php/auth/login.php";

        return null;
    }
}