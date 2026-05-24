// Sign-in page — POSTs credentials to api/login.php (PHP session auth)
(function () {
  var form = document.getElementById("signinForm");
  var emailField = document.getElementById("email");
  var passwordField = document.getElementById("password");
  var submitBtn = document.getElementById("signinBtn");
  var togglePwBtn = document.getElementById("togglePassword");
  var forgotLink = document.getElementById("forgotPasswordLink");

  if (!form) return;

  // Show/hide password
  var SVG_EYE_OPEN =
    '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
    '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>' +
    '</svg>';
  var SVG_EYE_SLASH =
    '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
    '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>' +
    '<path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>' +
    '<path d="M10.73 10.73A3 3 0 0 0 14.12 14.12"/>' +
    '<line x1="1" y1="1" x2="23" y2="23"/>' +
    '</svg>';

  if (togglePwBtn) {
    togglePwBtn.addEventListener("click", function () {
      var isText = passwordField.type === "text";
      passwordField.type = isText ? "password" : "text";
      togglePwBtn.setAttribute("aria-label", isText ? "Show password" : "Hide password");
      togglePwBtn.innerHTML = isText ? SVG_EYE_OPEN : SVG_EYE_SLASH;
    });
  }

  // forgotLink now navigates to forgot_password.php directly — no JS handler needed

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

    var email = emailField.value.trim();
    var password = passwordField.value;
    var hasError = false;

    if (!isValidEmail(email)) {
      setError("emailError", "Please enter a valid email address.");
      emailField.classList.add("input-error");
      hasError = true;
    }
    if (!password) {
      setError("passwordError", "Please enter your password.");
      passwordField.classList.add("input-error");
      hasError = true;
    }
    if (hasError) return;

    var body = new FormData(form);
    submitBtn.disabled = true;
    submitBtn.textContent = "Signing in…";

    fetch("api/login.php", { method: "POST", body: body })
      .then(function (res) {
        if (!res.ok && res.status >= 500) {
          throw new Error("Server error " + res.status + ". Please try again later.");
        }
        return res.json();
      })
      .then(function (data) {
        if (!data.ok) {
          setError("passwordError", data.error || "Login failed.");
          passwordField.classList.add("input-error");
          submitBtn.disabled = false;
          submitBtn.textContent = "Sign In";
          return;
        }
        // Keep localStorage in sync for static pages
        if (window.gbAuth)
          window.gbAuth.setUser({ name: data.name, email: email });
        showSuccess(data.name, data.is_admin);
      })
      .catch(function (err) {
        setError("passwordError", err.message || "Could not connect. Is XAMPP running?");
        submitBtn.disabled = false;
        submitBtn.textContent = "Sign In";
      });
  });

  function showSuccess(name, isAdmin) {
    var card = document.querySelector(".auth-card");
    var dest = isAdmin ? "admin/products.php" : "index.php";
    card.innerHTML =
      '<div class="auth-success">' +
      '<div class="auth-success-icon">&#10003;</div>' +
      "<h2>Welcome back, " +
      name.split(" ")[0] +
      "!</h2>" +
      "<p>You’re now signed in. Redirecting…</p>" +
      '<a href="' +
      dest +
      '" class="submit-btn" style="display:inline-block;margin-top:8px;text-align:center">Continue &rsaquo;</a>' +
      "</div>";
    setTimeout(function () {
      window.location.href = dest;
    }, 1800);
  }

  function showInfo(msg) {
    var existing = document.getElementById("gb-info-toast");
    if (existing) existing.remove();
    var toast = document.createElement("div");
    toast.id = "gb-info-toast";
    toast.className = "demo-notice";
    toast.style.cssText =
      "position:fixed;bottom:24px;right:24px;max-width:320px;z-index:9999;";
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(function () {
      toast.remove();
    }, 4000);
  }
})();
