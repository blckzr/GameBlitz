// Register page — POSTs to api/register.php (PHP session auth)
(function () {
  var form          = document.getElementById("registerForm");
  var nameField     = document.getElementById("fullName");
  var emailField    = document.getElementById("email");
  var passwordField = document.getElementById("password");
  var confirmField  = document.getElementById("confirmPassword");
  var termsBox      = document.getElementById("agreeTerms");
  var strengthWrap  = document.getElementById("passwordStrength");
  var strengthFill  = document.getElementById("strengthFill");
  var strengthLabel = document.getElementById("strengthLabel");

  if (!form) return;

  // Show/hide password toggles
  function makeToggle(btnId, field) {
    var btn = document.getElementById(btnId);
    if (!btn || !field) return;
    btn.addEventListener("click", function () {
      var isText = field.type === "text";
      field.type = isText ? "password" : "text";
      btn.setAttribute("aria-label", isText ? "Show password" : "Hide password");
      btn.textContent = isText ? "👁" : "🛂";
    });
  }
  makeToggle("togglePassword", passwordField);
  makeToggle("toggleConfirm",  confirmField);

  // Password strength meter
  if (passwordField && strengthWrap) {
    passwordField.addEventListener("input", function () {
      var val = passwordField.value;
      if (!val) { strengthWrap.hidden = true; return; }
      strengthWrap.hidden = false;
      var score = 0;
      if (val.length >= 8)            score++;
      if (val.length >= 12)           score++;
      if (/[A-Z]/.test(val))          score++;
      if (/[0-9]/.test(val))          score++;
      if (/[^A-Za-z0-9]/.test(val))  score++;
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

  // Helpers
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

    var name    = nameField.value.trim();
    var email   = emailField.value.trim();
    var pw      = passwordField.value;
    var confirm = confirmField.value;
    var ok      = true;

    if (name.length < 2)       { setError("nameError",     "Please enter your full name.", nameField);    ok = false; }
    if (!isValidEmail(email))  { setError("emailError",    "Please enter a valid email address.", emailField);  ok = false; }
    if (pw.length < 8)         { setError("passwordError", "Password must be at least 8 characters.", passwordField); ok = false; }
    if (pw !== confirm)        { setError("confirmError",  "Passwords do not match.", confirmField);       ok = false; }
    if (!termsBox.checked)     { setError("termsError",    "You must agree to the Terms of Service.");     ok = false; }
    if (!ok) return;

    var submitBtn = form.querySelector(".submit-btn");
    submitBtn.disabled = true;
    submitBtn.textContent = "Creating account…";

    fetch("api/register.php", { method: "POST", body: new FormData(form) })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data.ok) {
          // Server-side errors (e.g. duplicate email)
          if (data.field === "email") {
            setError("emailError", data.error, emailField);
          } else if (data.errors) {
            if (data.errors.name)     setError("nameError",     data.errors.name,     nameField);
            if (data.errors.email)    setError("emailError",    data.errors.email,    emailField);
            if (data.errors.password) setError("passwordError", data.errors.password, passwordField);
          } else {
            setError("termsError", data.error || "Registration failed.");
          }
          submitBtn.disabled = false;
          submitBtn.textContent = "Create Account";
          return;
        }
        // Keep localStorage in sync for static pages
        if (window.gbAuth) window.gbAuth.setUser({ name: data.name, email: email });
        showSuccess(data.name);
      })
      .catch(function () {
        setError("termsError", "Could not connect. Is XAMPP running?");
        submitBtn.disabled = false;
        submitBtn.textContent = "Create Account";
      });
  });

  function showSuccess(name) {
    var card = document.querySelector(".auth-card");
    card.innerHTML =
      '<div class="auth-success">' +
        '<div class="auth-success-icon">&#127881;</div>' +
        "<h2>Account created!</h2>" +
        "<p>Welcome to GameBlitz, <strong>" + name.split(" ")[0] + "</strong>. Redirecting&hellip;</p>" +
        '<a href="signin.php?registered=1" class="submit-btn" style="display:inline-block;margin-top:8px;text-align:center">Sign In &rsaquo;</a>' +
      "</div>";
    setTimeout(function () { window.location.href = "signin.php?registered=1"; }, 2500);
  }
})();
