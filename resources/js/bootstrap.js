import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY, // ou process.env.PUSHER_APP_KEY selon config
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || "mt1",
    encrypted: true,
    forceTLS: true,
    authEndpoint: "/broadcasting/auth",
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem("token")}`,
        },
    },
});
