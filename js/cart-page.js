// Cart page — renders cart items and handles checkout
(function () {
  var itemList        = document.getElementById("cartItemList");
  var emptyState      = document.getElementById("cartEmpty");
  var cartActions     = document.getElementById("cartActions");
  var orderSummary    = document.getElementById("orderSummary");
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

      li.innerHTML =
        '<img class="cart-item-img" src="' + item.image + '" alt="' + item.name + '" />' +
        '<div class="cart-item-info">' +
          '<h4 class="cart-item-name">' + item.name + "</h4>" +
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
        if (item.quantity <= 1) { window.gbCart.remove(item.productId); }
        else { window.gbCart.updateQuantity(item.productId, -1); }
        render();
      });
      li.querySelector(".qty-plus").addEventListener("click", function () {
        window.gbCart.updateQuantity(item.productId, 1); render();
      });
      li.querySelector(".remove-btn").addEventListener("click", function () {
        window.gbCart.remove(item.productId); render();
      });

      itemList.appendChild(li);
    });

    var subtotal = items.reduce(function (sum, i) { return sum + i.price * i.quantity; }, 0);
    summarySubtotal.textContent = fmt(subtotal);
    summaryTotal.textContent    = fmt(subtotal);
  }

  if (clearCartBtn) {
    clearCartBtn.addEventListener("click", function () {
      if (confirm("Remove all items from your cart?")) {
        window.gbCart.clear(); render();
      }
    });
  }

  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", function () {
      var items    = window.gbCart ? window.gbCart.getItems() : [];
      var subtotal = items.reduce(function (s, i) { return s + i.price * i.quantity; }, 0);

      if (!items.length) return;

      // Build item list string for the confirmation
      var itemLines = items.map(function (i) {
        return "• " + i.name + " x" + i.quantity + " — " + fmt(i.price * i.quantity);
      }).join("\n");

      var confirmed = confirm(
        "Confirm your order?\n\n" +
        itemLines +
        "\n\nTotal: " + fmt(subtotal) +
        "\n\nYour order will be marked as paid immediately."
      );

      if (!confirmed) return;

      checkoutBtn.disabled    = true;
      checkoutBtn.textContent = "Placing order…";

      fetch("api/checkout.php", {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({ items: items }),
      })
        .then(function (res) {
          if (!res.ok && res.status >= 500) {
            throw new Error("Server error " + res.status + ". Please try again.");
          }
          return res.json();
        })
        .then(function (json) {
          if (json.redirect) {
            window.location.href = json.redirect; return;
          }
          if (!json.ok) throw new Error(json.error || "Order failed.");

          window.gbCart.clear();
          showOrderSuccess(json.order_number, subtotal);
        })
        .catch(function (err) {
          checkoutBtn.disabled    = false;
          checkoutBtn.textContent = "Proceed to Checkout";
          showErrorModal(err.message || "Network error. Please check your connection and try again.");
        });
    });
  }

  function showOrderSuccess(orderNumber, total) {
    var overlay = document.createElement("div");
    overlay.className = "modal-overlay";
    overlay.innerHTML =
      '<div class="modal-content checkout-modal" style="text-align:center">' +
        '<span class="modal-badge" style="background:#4ade80;color:#14532d">&#10003; Order Placed</span>' +
        "<h2>Thank you!</h2>" +
        "<p>Your order has been placed and marked as paid.</p>" +
        '<p style="font-size:1.1rem;font-weight:700;letter-spacing:0.05em">' + orderNumber + "</p>" +
        '<p style="color:var(--text-muted)">Total: ₱' + parseInt(total).toLocaleString() + "</p>" +
        '<a href="profile.php" class="btn-primary" style="display:block;margin-top:16px">View My Orders</a>' +
        '<a href="products.php" class="btn-secondary" style="display:block;margin-top:8px">Continue Shopping</a>' +
      "</div>";
    document.body.appendChild(overlay);
    render(); // refresh cart UI (will show empty)
  }

  function showErrorModal(msg) {
    var overlay = document.createElement("div");
    overlay.className = "modal-overlay";
    overlay.innerHTML =
      '<div class="modal-content" style="text-align:center">' +
        '<span class="modal-badge" style="background:#ff5555;color:#fff">Error</span>' +
        "<h2>Order Failed</h2>" +
        "<p>" + msg + "</p>" +
        '<button class="btn-primary" style="margin-top:16px" id="closeErrModal">Close</button>' +
      "</div>";
    overlay.querySelector("#closeErrModal").addEventListener("click", function () { overlay.remove(); });
    document.body.appendChild(overlay);
  }

  render();
})();
