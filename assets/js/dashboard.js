// dashboard.js
document.addEventListener("DOMContentLoaded", () => {
    const cards = document.querySelectorAll(".dashboard-card");

    cards.forEach(card => {
        card.addEventListener("click", () => {
            alert("You clicked: " + card.querySelector("h3").innerText);
        });
    });

    console.log("Dashboard ready 🏠");
});
