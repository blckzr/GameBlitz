// Contact form — validates then submits to api/contact.php
(function () {
  var form = document.getElementById("contactForm");
  if (!form) return;

  var fields = {
    name:     document.getElementById("name"),
    email:    document.getElementById("email"),
    category: document.getElementById("inquiryCategory"),
    subject:  document.getElementById("subject"),
    message:  document.getElementById("message"),
  };

  var errors = {
    name:     document.getElementById("nameError"),
    email:    document.getElementById("emailError"),
    category: document.getElementById("categoryError"),
    subject:  document.getElementById("subjectError"),
    message:  document.getElementById("messageError"),
  };

  function clearErrors() {
    Object.values(errors).forEach(function (el) { if (el) el.textContent = ""; });
  }

  function setError(key, msg) {
    if (errors[key]) errors[key].textContent = msg;
  }

  function isValidEmail(val) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val.trim());
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    clearErrors();

    var hasError = false;
    if (!fields.name || fields.name.value.trim().length < 2) {
      setError("name", "Please enter your full name."); hasError = true;
    }
    if (!fields.email || !isValidEmail(fields.email.value)) {
      setError("email", "Please enter a valid email address."); hasError = true;
    }
    if (!fields.category || fields.category.value === "") {
      setError("category", "Please select an inquiry type."); hasError = true;
    }
    if (!fields.subject || fields.subject.value.trim().length < 3) {
      setError("subject", "Please enter a subject (at least 3 characters)."); hasError = true;
    }
    if (!fields.message || fields.message.value.trim() === "") {
      setError("message", "Please enter your message."); hasError = true;
    }

    if (hasError) return;

    var submitBtn = form.querySelector(".submit-btn");
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = "Sending…"; }

    var data = new FormData(form);

    fetch("api/contact.php", { method: "POST", body: data })
      .then(function (res) { return res.json(); })
      .then(function (json) {
        if (!json.ok) throw new Error(json.error || "Failed to send.");
        showSuccessModal();
      })
      .catch(function (err) {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = "Send Message"; }
        setError("message", err.message || "Something went wrong. Please try again.");
      });
  });

  function showSuccessModal() {
    var overlay = document.createElement("div");
    overlay.className = "modal-overlay";
    overlay.innerHTML =
      '<div class="modal-content">' +
        "<h2>Message Sent!</h2>" +
        "<p>Thank you for contacting GameBlitz. We will get back to you within 1&ndash;2 business days.</p>" +
        '<p style="font-size:0.9rem;color:var(--text-muted)">Returning to Home in 3 seconds&hellip;</p>' +
      "</div>";
    document.body.appendChild(overlay);
    setTimeout(function () { window.location.href = "index.php"; }, 3000);
  }
})();
