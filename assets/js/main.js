// main.js
document.addEventListener("DOMContentLoaded", () => {
    console.log("HDMS website loaded ✅");

    // Navbar active link highlight
    let currentPath = window.location.pathname;
    document.querySelectorAll(".nav-link").forEach(link => {
        if (link.getAttribute("href") === currentPath) {
            link.classList.add("active");
        }
    });
});
