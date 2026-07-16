export async function getRequests(view = "active") {

    const res = await fetch(
        `../php/getData.php?state=GET_REQUESTS&view=${view}`
    );

    return await res.json();
}

export async function getRequest(id) {

    const res = await fetch(
        `../php/getData.php?state=GET_REQUEST&id=${id}`
    );

    return await res.json();
}

export async function getUsers() {

    const res = await fetch(
        "../php/getData.php?state=GET_USERS"
    );

    return await res.json();
}

export async function createRequest(payload) {

    payload.state = "CREATE_REQUEST";

    const res = await fetch("../php/postData.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams(payload)
    });

    return await res.json();
}

export async function updateRequest(payload) {

    payload.state = "UPDATE_REQUEST";

    const res = await fetch("../php/updateData.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams(payload)
    });

    return await res.json();
}

export async function updateStatus(id, status) {

    const res = await fetch("../php/updateData.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({
            state: "UPDATE_STATUS",
            id,
            status
        })
    });

    return await res.json();
}