// Register page logic
// PHP phase: replace gbAuth.setUser() with a POST to /api/register.php
//            that INSERTs into users table, sets $_SESSION, and returns JSON.
(function () {
  var form            = document.getElementById("registerForm");
  var nameField       = document.getElementById("fullName");
  var emailField      = document.getElementById("email");
  var passwordField   = document.getElementById("password");
  var confirmField    = document.getElementById("confirmPassword");
  var termsCheckbox   = document.getElementById("agreeTerms");
  var togglePwBtn     = document.getElementById("togglePassword");
  var toggleConfBtn   = document.getElementById("toggleConfirm");
  var strengthWrap    = document.getElementById("passwordStrength");
  var strengthFill    = document.getElementById("strengthFill");
  var strengthLabel   = document.getElementById("strengthLabel");

  if (!form) return;

  // Redirect if already signed in
  if (window.gbAuth && window.gbAuth.getUser()) {
    window.location.href = "index.html";
    return;
  }

  // Show/hide password toggles
  function makeToggle(btn, field) {
    if (!btn || !field) return;
    btn.addEventListener("click", function () {
      var isText = field.type === "text";
      field.type = isText ? "password" : "text";
      btn.setAttribute("aria-label", isText ? "Show password" : "Hide password");
      btn.textContent = isText ? "👁" : "🛂";
    });
  }
  makeToggle(togglePwBtn, passwordField);
  makeToggle(toggleConfBtn, confirmField);

  // Password strength meter
  if (passwordField && strengthWrap) {
    passwordField.addEventListener("input", function () {
      var val = passwordField.value;
      if (!val) { strengthWrap.hidden = true; return; }
      strengthWrap.hidden = false;

      var score = 0;
      if (val.length >= 8)  score++;
      if (val.length >= 12) score++;
      if (/[A-Z]/.test(val)) score++;
      if (/[0-9]/.test(val)) score++;
      if (/[^A-Za-z0-9]/.test(val)) score++;

      var levels = [
        { pct: "20%",  color: "#ff5555", label: "Very weak" },
        { pct: "40%",  color: "#ff7a59", label: "Weak" },
        { pct: "60%",  color: "#fbbf24", label: "Fair" },
        { pct: "80%",  color: "#60a5fa", label: "Good" },
        { pct: "100%", color: "#4ade80", label: "Strong" },
      ];
      var lvl = levels[Math.min(score, 4)];
      strengthFill.style.width = lvl.pct;
      strengthFill.style.backgroundColor = lvl.color;
      strengthLabel.textContent = lvl.label;
      strengthLabel.style.color = lvl.color;
    });
  }

  // Validation helpers
  function clearErrors() {
    ["nameError","emailError","passwordError","confirmError","termsError"].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.textContent = "";
    });
    [nameField, emailField, passwordField, confirmField].forEach(function (f) {
      if (f) f.classList.remove("input-error");
    });
  }

  function setError(id, msg, field) {
    var el = document.getElementById(id);
    if (el) el.textContent = msg;
    if (field) field.classList.add("input-error");
  }

  function isValidEmail(val) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val.trim());
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    clearErrors();

    var name     = nameField.value.trim();
    var email    = emailField.value.trim();
    var password = passwordField.value;
    var confirm  = confirmField.value;
    var hasError = false;

    if (name.length < 2) {
      setError("nameError", "Please enter your full name.", nameField);
      hasError = true;
    }

    if (!isValidEmail(email)) {
      setError("emailError", "Please enter a valid email address.", emailField);
      hasError = true;
    }

    if (password.length < 8) {
      setError("passwordError", "Password must be at least 8 characters.", passwordField);
      hasError = true;
    }

    if (confirm !== password) {
      setError("confirmError", "Passwords do not match.", confirmField);
      hasError = true;
    }

    if (!termsCheckbox.checked) {
      setError("termsError", "You must agree to the Terms of Service to continue.");
      hasError = true;
    }

    if (hasError) return;

    // Demo: store user in localStorage (no password stored — demo only)
    // PHP phase: POST {name, email, password} → /api/register.php
    //            → password_hash(), INSERT INTO users, set $_SESSION
    var user = { name: name, email: email };
    if (window.gbAuth) window.gbAuth.setUser(user);

    showSuccess(name);
  });

  function showSuccess(name) {
    var card = document.querySelector(".auth-card");
    card.innerHTML =
      '<div class="auth-success">' +
        '<div class="auth-success-icon">&#127881;</div>' +
        "<h2>Account created!</h2>" +
        "<p>Welcome to GameBlitz, <strong>" + name + "</strong>. Redirecting you now&hellip;</p>" +
        '<a href="index.html" class="btn-primary" style="display:inline-block;margin-top:8px">Go to Home</a>' +
      "</div>";

    setTimeout(function () {
      window.location.href = "index.html";
    }, 2500);
  }
})();
