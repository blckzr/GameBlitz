// Cart page — renders and manages the cart item list
(function () {
  var itemList   = document.getElementById("cartItemList");
  var emptyState = document.getElementById("cartEmpty");
  var cartActions = document.getElementById("cartActions");
  var orderSummary = document.getElementById("orderSummary");
  var summarySubtotal = document.getElementById("summarySubtotal");
  var summaryTotal    = document.getElementById("summaryTotal");
  var clearCartBtn    = document.getElementById("clearCartBtn");
  var checkoutBtn     = document.getElementById("checkoutBtn");

  function fmt(n) {
    return "₱" + parseInt(n, 10).toLocaleString();
  }

  function render() {
    var items = window.gbCart ? window.gbCart.getItems() : [];
    itemList.innerHTML = "";

    var isEmpty = items.length === 0;
    emptyState.hidden   = !isEmpty;
    cartActions.hidden  = isEmpty;
    orderSummary.hidden = isEmpty;

    if (isEmpty) return;

    items.forEach(function (item) {
      var li = document.createElement("li");
      li.className = "cart-item";
      li.dataset.productId = item.productId;

      var platformLabel = item.platform
        ? item.platform.split(",").map(function (p) { return p.toUpperCase(); }).join(" / ")
        : "";

      li.innerHTML =
        '<img class="cart-item-img" src="' + item.image + '" alt="' + item.name + '" />' +
        '<div class="cart-item-info">' +
          '<h4 class="cart-item-name">' + item.name + "</h4>" +
          (platformLabel ? '<p class="cart-item-platform">' + platformLabel + "</p>" : "") +
          '<p class="cart-item-unit-price">Unit price: <span>' + fmt(item.price) + "</span></p>" +
          '<div class="qty-stepper">' +
            '<button class="qty-btn qty-minus" aria-label="Decrease quantity">&#8722;</button>' +
            '<span class="qty-value">' + item.quantity + "</span>" +
            '<button class="qty-btn qty-plus" aria-label="Increase quantity">&#43;</button>' +
          "</div>" +
        "</div>" +
        '<div class="cart-item-right">' +
          '<span class="cart-item-subtotal">' + fmt(item.price * item.quantity) + "</span>" +
          '<button class="remove-btn" aria-label="Remove ' + item.name + ' from cart">Remove</button>' +
        "</div>";

      li.querySelector(".qty-minus").addEventListener("click", function () {
        if (item.quantity <= 1) {
          window.gbCart.remove(item.productId);
        } else {
          window.gbCart.updateQuantity(item.productId, -1);
        }
        render();
      });

      li.querySelector(".qty-plus").addEventListener("click", function () {
        window.gbCart.updateQuantity(item.productId, 1);
        render();
      });

      li.querySelector(".remove-btn").addEventListener("click", function () {
        window.gbCart.remove(item.productId);
        render();
      });

      itemList.appendChild(li);
    });

    // Update summary
    var subtotal = items.reduce(function (sum, i) { return sum + i.price * i.quantity; }, 0);
    summarySubtotal.textContent = fmt(subtotal);
    summaryTotal.textContent    = fmt(subtotal);
  }

  // Clear cart
  if (clearCartBtn) {
    clearCartBtn.addEventListener("click", function () {
      if (confirm("Remove all items from your cart?")) {
        window.gbCart.clear();
        render();
      }
    });
  }

  // Checkout placeholder
  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", function () {
      var overlay = document.createElement("div");
      overlay.className = "modal-overlay";
      overlay.innerHTML =
        '<div class="modal-content checkout-modal">' +
          '<span class="modal-badge">Coming Soon</span>' +
          "<h2>Checkout</h2>" +
          "<p>Secure checkout will be available once the PHP &amp; MySQL backend is connected.</p>" +
          "<p>To place an order now, please <a href=\"contact.html\">contact us</a> directly.</p>" +
          '<button class="btn-primary" id="closeCheckoutModal" style="width:100%;margin-top:8px">Got it</button>' +
        "</div>";

      overlay.querySelector("#closeCheckoutModal").addEventListener("click", function () {
        overlay.remove();
      });
      overlay.addEventListener("click", function (e) {
        if (e.target === overlay) overlay.remove();
      });

      document.body.appendChild(overlay);
    });
  }

  render();
})();
