const registerForm = document.querySelector("form");

registerForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const email = registerForm.querySelector(".email").value;
    const password = registerForm.querySelectorAll(".password")[0].value;
    const confirmPassword = registerForm.querySelectorAll(".password")[1].value;

    if (password !== confirmPassword) {
        showError("Passwords do not match.");
        return;
    }

    firebase.auth().createUserWithEmailAndPassword(email, password)
        .then((userCredential) => {
            alert("Registration successful!");
            window.location.href = "login.html"; // Redirect to login page
        })
        .catch((error) => {
            showError(error.message);
        });
});

function showError(message) {
    const errorDiv = document.getElementById('error-message');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    setTimeout(() => errorDiv.style.display = 'none', 5000);
}