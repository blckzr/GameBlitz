// Reset password — submits new password with token
(function () {
  var form     = document.getElementById('resetForm');
  var btn      = document.getElementById('resetBtn');
  var pwEl     = document.getElementById('password');
  var confEl   = document.getElementById('confirmPassword');
  var pwErr    = document.getElementById('passwordError');
  var confErr  = document.getElementById('confirmError');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    pwErr.textContent   = '';
    confErr.textContent = '';

    var pw     = pwEl.value;
    var conf   = confEl.value;
    var hasErr = false;

    if (pw.length < 8) {
      pwErr.textContent = 'Password must be at least 8 characters.';
      hasErr = true;
    }
    if (pw !== conf) {
      confErr.textContent = 'Passwords do not match.';
      hasErr = true;
    }
    if (hasErr) return;

    btn.disabled    = true;
    btn.textContent = 'Updating…';

    var data = new FormData(form);

    fetch('api/reset_password.php', { method: 'POST', body: data })
      .then(function (res) {
        if (!res.ok && res.status >= 500) {
          throw new Error('Server error ' + res.status + '. Please try again.');
        }
        return res.json();
      })
      .then(function (json) {
        if (!json.ok) {
          btn.disabled    = false;
          btn.textContent = 'Update Password';
          if (json.errors) {
            if (json.errors.password)         pwErr.textContent   = json.errors.password;
            if (json.errors.password_confirm) confErr.textContent = json.errors.password_confirm;
          } else {
            pwErr.textContent = json.error || 'Could not update password.';
          }
          return;
        }

        // Success — replace card content
        var card = document.querySelector('.auth-card');
        card.innerHTML =
          '<div class="auth-success">' +
            '<div class="auth-success-icon">&#10003;</div>' +
            '<h2>Password updated</h2>' +
            '<p>Your password has been reset. You can now sign in with your new password.</p>' +
            '<a href="signin.php" class="submit-btn" style="display:inline-block;margin-top:1rem;text-decoration:none">Sign In &rsaquo;</a>' +
          '</div>';
        setTimeout(function () { window.location.href = 'signin.php'; }, 3000);
      })
      .catch(function (err) {
        btn.disabled    = false;
        btn.textContent = 'Update Password';
        pwErr.textContent = err.message || 'Network error. Please try again.';
      });
  });
})();
