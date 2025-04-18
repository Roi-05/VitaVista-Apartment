const loginForm = document.querySelector("form");

loginForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const email = loginForm.querySelector(".email").value;
    const password = loginForm.querySelector(".password").value;

    firebase.auth().signInWithEmailAndPassword(email, password)
        .then((userCredential) => {
            localStorage.setItem("user", JSON.stringify(userCredential.user));
            alert("Login successful!");
            window.location.href = "index.html"; // Redirect to homepage
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