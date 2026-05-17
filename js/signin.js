// Sign-in page logic
// PHP phase: replace gbAuth.setUser() with a real POST to /api/login.php
//            that sets $_SESSION['user'] and returns a JSON response.
(function () {
  var form          = document.getElementById("signinForm");
  var emailField    = document.getElementById("email");
  var passwordField = document.getElementById("password");
  var togglePwBtn   = document.getElementById("togglePassword");
  var forgotLink    = document.getElementById("forgotPasswordLink");

  if (!form) return;

  // Redirect if already signed in
  if (window.gbAuth && window.gbAuth.getUser()) {
    window.location.href = "index.html";
    return;
  }

  // Show/hide password
  if (togglePwBtn) {
    togglePwBtn.addEventListener("click", function () {
      var isText = passwordField.type === "text";
      passwordField.type = isText ? "password" : "text";
      togglePwBtn.setAttribute("aria-label", isText ? "Show password" : "Hide password");
      togglePwBtn.textContent = isText ? "👁" : "🛂";
    });
  }

  // Forgot password placeholder
  if (forgotLink) {
    forgotLink.addEventListener("click", function (e) {
      e.preventDefault();
      showInfo("Password reset will be available once the PHP backend is connected.");
    });
  }

  function clearErrors() {
    setError("emailError", "");
    setError("passwordError", "");
    emailField.classList.remove("input-error");
    passwordField.classList.remove("input-error");
  }

  function setError(id, msg) {
    var el = document.getElementById(id);
    if (el) el.textContent = msg;
  }

  function isValidEmail(val) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val.trim());
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    clearErrors();

    var email    = emailField.value.trim();
    var password = passwordField.value;
    var hasError = false;

    if (!isValidEmail(email)) {
      setError("emailError", "Please enter a valid email address.");
      emailField.classList.add("input-error");
      hasError = true;
    }

    if (password.length < 1) {
      setError("passwordError", "Please enter your password.");
      passwordField.classList.add("input-error");
      hasError = true;
    }

    if (hasError) return;

    // Demo: store user in localStorage (no real password check)
    // PHP phase: POST {email, password} → /api/login.php → set $_SESSION
    var user = { name: email.split("@")[0], email: email };
    if (window.gbAuth) window.gbAuth.setUser(user);

    showSuccess(user.name);
  });

  function showSuccess(name) {
    var card = document.querySelector(".auth-card");
    card.innerHTML =
      '<div class="auth-success">' +
        '<div class="auth-success-icon">&#10003;</div>' +
        "<h2>Welcome back, " + name + "!</h2>" +
        '<p>You\'re now signed in. Redirecting to home&hellip;</p>' +
        '<a href="index.html" class="btn-primary" style="display:inline-block;margin-top:8px">Go to Home</a>' +
      "</div>";

    setTimeout(function () {
      window.location.href = "index.html";
    }, 2000);
  }

  function showInfo(msg) {
    var existing = document.getElementById("gb-info-toast");
    if (existing) existing.remove();

    var toast = document.createElement("div");
    toast.id = "gb-info-toast";
    toast.className = "demo-notice";
    toast.style.cssText = "position:fixed;bottom:24px;right:24px;max-width:320px;z-index:9999;";
    toast.textContent = msg;
    document.body.appendChild(toast);

    setTimeout(function () { toast.remove(); }, 4000);
  }
})();
