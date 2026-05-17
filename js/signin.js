// Sign-in page — POSTs credentials to api/login.php (PHP session auth)
(function () {
  var form          = document.getElementById("signinForm");
  var emailField    = document.getElementById("email");
  var passwordField = document.getElementById("password");
  var submitBtn     = document.getElementById("signinBtn");
  var togglePwBtn   = document.getElementById("togglePassword");
  var forgotLink    = document.getElementById("forgotPasswordLink");

  if (!form) return;

  // Show/hide password
  if (togglePwBtn) {
    togglePwBtn.addEventListener("click", function () {
      var isText = passwordField.type === "text";
      passwordField.type = isText ? "password" : "text";
      togglePwBtn.setAttribute("aria-label", isText ? "Show password" : "Hide password");
      togglePwBtn.textContent = isText ? "👁" : "🛂";
    });
  }

  if (forgotLink) {
    forgotLink.addEventListener("click", function (e) {
      e.preventDefault();
      showInfo("Password reset is not yet available.");
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
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data.ok) {
          setError("passwordError", data.error || "Login failed.");
          passwordField.classList.add("input-error");
          submitBtn.disabled = false;
          submitBtn.textContent = "Sign In";
          return;
        }
        // Keep localStorage in sync for static pages
        if (window.gbAuth) window.gbAuth.setUser({ name: data.name, email: email });
        showSuccess(data.name, data.is_admin);
      })
      .catch(function () {
        setError("passwordError", "Could not connect. Is XAMPP running?");
        submitBtn.disabled = false;
        submitBtn.textContent = "Sign In";
      });
  });

  function showSuccess(name, isAdmin) {
    var card = document.querySelector(".auth-card");
    var dest = isAdmin ? "admin/products.php" : "index.html";
    card.innerHTML =
      '<div class="auth-success">' +
        '<div class="auth-success-icon">&#10003;</div>' +
        "<h2>Welcome back, " + name.split(" ")[0] + "!</h2>" +
        "<p>You’re now signed in. Redirecting…</p>" +
        '<a href="' + dest + '" class="submit-btn" style="display:inline-block;margin-top:8px;text-align:center">Continue &rsaquo;</a>' +
      "</div>";
    setTimeout(function () { window.location.href = dest; }, 1800);
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
