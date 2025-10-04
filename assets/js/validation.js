// validation.js
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener("submit", function(event) {
        let valid = true;
        let inputs = form.querySelectorAll("input[required], select[required], textarea[required]");

        inputs.forEach(input => {
            if (input.value.trim() === "") {
                input.classList.add("is-invalid");
                valid = false;
            } else {
                input.classList.remove("is-invalid");
            }
        });

        if (!valid) {
            event.preventDefault();
            alert("⚠️ Please fill in all required fields.");
        }
    });
}
