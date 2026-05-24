// Forgot password — submits email and shows the generated reset link
(function () {
  var form     = document.getElementById('forgotForm');
  var btn      = document.getElementById('forgotBtn');
  var emailEl  = document.getElementById('email');
  var errEl    = document.getElementById('emailError');
  var result   = document.getElementById('resetResult');
  var linkEl   = document.getElementById('resetLink');
  if (!form) return;

  function isValidEmail(v) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test((v || '').trim());
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    errEl.textContent = '';
    result.style.display = 'none';

    var email = emailEl.value.trim();
    if (!isValidEmail(email)) {
      errEl.textContent = 'Please enter a valid email address.';
      return;
    }

    btn.disabled    = true;
    btn.textContent = 'Generating link…';

    var data = new FormData();
    data.append('email', email);

    fetch('api/forgot_password.php', { method: 'POST', body: data })
      .then(function (res) {
        if (!res.ok && res.status >= 500) {
          throw new Error('Server error ' + res.status + '. Please try again.');
        }
        return res.json();
      })
      .then(function (json) {
        btn.disabled    = false;
        btn.textContent = 'Send Reset Link';

        if (!json.ok) {
          errEl.textContent = json.error || 'Could not process request.';
          return;
        }

        if (json.reset_url) {
          // Show the link directly (no email server on localhost)
          linkEl.href        = json.reset_url;
          linkEl.textContent = json.reset_url;
          result.style.display = 'block';
        } else {
          // Email not found — but we don't reveal that for security
          result.innerHTML = '<p style="margin:0;color:#c8cfe0">' +
            '&#10003; If that email is registered, a reset link has been generated.' +
            '</p>';
          result.style.display = 'block';
        }
      })
      .catch(function (err) {
        btn.disabled    = false;
        btn.textContent = 'Send Reset Link';
        errEl.textContent = err.message || 'Network error. Please try again.';
      });
  });
})();
