// Shared cart + auth utility — loaded on every page.
// PHP phase: replace localStorage reads with PHP session values echoed into the page.
(function () {
  var CART_KEY = "gb_cart";
  var USER_KEY = "gb_user";

  // ── Cart data ─────────────────────────────────────────────────────────────

  function getItems() {
    try { return JSON.parse(localStorage.getItem(CART_KEY) || "[]"); }
    catch (e) { return []; }
  }

  function saveItems(items) {
    localStorage.setItem(CART_KEY, JSON.stringify(items));
  }

  function getCount() {
    return getItems().reduce(function (sum, i) { return sum + i.quantity; }, 0);
  }

  function addItem(product) {
    var items = getItems();
    var existing = null;
    for (var i = 0; i < items.length; i++) {
      if (items[i].productId === product.productId) { existing = items[i]; break; }
    }
    if (existing) {
      existing.quantity += 1;
    } else {
      items.push({
        productId: product.productId,
        name: product.name,
        price: product.price,
        image: product.image,
        platform: product.platform || "",
        quantity: 1,
      });
    }
    saveItems(items);
    updateBadge();
    showCartToast(product.name);
  }

  function removeItem(productId) {
    var items = getItems().filter(function (i) { return i.productId !== productId; });
    saveItems(items);
    updateBadge();
  }

  function updateQuantity(productId, delta) {
    var items = getItems();
    for (var i = 0; i < items.length; i++) {
      if (items[i].productId === productId) {
        items[i].quantity = Math.max(1, items[i].quantity + delta);
        break;
      }
    }
    saveItems(items);
    updateBadge();
  }

  function clearCart() {
    localStorage.removeItem(CART_KEY);
    updateBadge();
  }

  // ── Badge ─────────────────────────────────────────────────────────────────

  function updateBadge() {
    var badges = document.querySelectorAll("#cartCount");
    var n = getCount();
    badges.forEach(function (badge) {
      badge.textContent = n;
      badge.dataset.empty = n === 0 ? "true" : "false";
    });
  }

  // ── Toast notification ────────────────────────────────────────────────────

  function showCartToast(name) {
    var existing = document.getElementById("gb-toast");
    if (existing) existing.remove();

    var toast = document.createElement("div");
    toast.id = "gb-toast";
    toast.className = "gb-toast";
    toast.innerHTML =
      "<span>&#10003; <strong>" + truncate(name, 28) + "</strong> added to cart</span>" +
      '<a href="cart.html">View Cart &rsaquo;</a>';
    document.body.appendChild(toast);

    requestAnimationFrame(function () { toast.classList.add("gb-toast--show"); });
    setTimeout(function () {
      toast.classList.remove("gb-toast--show");
      setTimeout(function () { toast.remove(); }, 300);
    }, 3000);
  }

  function truncate(str, max) {
    return str.length > max ? str.slice(0, max) + "…" : str;
  }

  // ── Auth helpers ──────────────────────────────────────────────────────────

  function getUser() {
    // PHP pages inject window._gbUser from $_SESSION — use that first.
    if (window._gbUser) return window._gbUser;
    try { return JSON.parse(localStorage.getItem(USER_KEY) || "null"); }
    catch (e) { return null; }
  }

  function setUser(user) {
    localStorage.setItem(USER_KEY, JSON.stringify(user));
  }

  function signOut() {
    localStorage.removeItem(USER_KEY);
    // If a PHP session exists, destroy it via the API endpoint.
    var apiBase = window._gbUser ? "api/logout.php" : null;
    if (apiBase) {
      window.location.href = apiBase;
    } else {
      window.location.href = "signin.php";
    }
  }

  function updateNavAuth() {
    var user = getUser();
    var signInLink = document.getElementById("navSignIn");
    if (!signInLink) return;

    // PHP pages handle the nav label server-side; only update on static pages.
    if (window._gbUser) return;

    if (user) {
      var firstName = user.name.split(" ")[0];
      signInLink.querySelector(".nav-action-label").textContent = firstName + " ▾";
      signInLink.href  = "profile.php";
      signInLink.title = "Signed in as " + user.name;
    }
  }

  // ── Mobile nav toggle ─────────────────────────────────────────────────────

  var toggleBtn = document.getElementById("mobileMenuToggle");
  var nav = document.getElementById("primaryNav");
  if (toggleBtn && nav) {
    toggleBtn.addEventListener("click", function () {
      var isOpen = nav.classList.toggle("open");
      toggleBtn.setAttribute("aria-expanded", String(isOpen));
    });
  }

  // ── Nav user dropdown (click to open/close) ───────────────────────────────

  document.addEventListener('click', function (e) {
    var toggle   = e.target.closest('.nav-user-menu > .nav-action');
    var insideDrop = e.target.closest('.nav-user-dropdown');

    if (toggle) {
      e.preventDefault();
      var menu = toggle.closest('.nav-user-menu');
      var isOpen = menu.classList.toggle('open');
      document.querySelectorAll('.nav-user-menu.open').forEach(function (m) {
        if (m !== menu) m.classList.remove('open');
      });
      return;
    }

    if (!insideDrop) {
      document.querySelectorAll('.nav-user-menu.open').forEach(function (m) {
        m.classList.remove('open');
      });
    }
  });

  // ── Init ──────────────────────────────────────────────────────────────────

  updateBadge();
  updateNavAuth();

  // ── Public API ────────────────────────────────────────────────────────────

  window.gbCart = {
    add: addItem,
    remove: removeItem,
    updateQuantity: updateQuantity,
    clear: clearCart,
    getItems: getItems,
    getCount: getCount,
  };

  window.gbAuth = {
    getUser: getUser,
    setUser: setUser,
    signOut: signOut,
  };
})();
