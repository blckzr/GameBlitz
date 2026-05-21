// Products page — AJAX search/filter with dynamic card rendering
(function () {
  var grid         = document.getElementById("productGrid");
  var searchInput  = document.getElementById("gameSearch");
  var spinner      = document.getElementById("searchSpinner");
  var feedback     = document.getElementById("searchFeedback");
  var platformFilt = document.getElementById("platformFilter");
  var categoryFilt = document.getElementById("categoryFilter");
  var sortControl  = document.getElementById("sortControl");
  var emptyState   = document.getElementById("emptyState");

  // Keep original server-rendered cards for no-query fallback
  var originalCards = Array.from(document.querySelectorAll(".product-card"));

  var debounceTimer = null;
  var currentXhr    = null; // track in-flight request

  // ── AJAX fetch + render ──────────────────────────────────────────────────

  function fetchAndRender() {
    var q        = (searchInput ? searchInput.value.trim() : "");
    var platform = platformFilt ? platformFilt.value : "";
    var category = categoryFilt ? categoryFilt.value : "";
    var sort     = sortControl  ? sortControl.value  : "featured";

    // Cancel any previous in-flight request
    if (currentXhr) { currentXhr = null; }

    // Show spinner, clear feedback
    if (spinner) spinner.hidden = false;
    if (feedback) feedback.textContent = "";

    var params = new URLSearchParams({ q: q, platform: platform, category: category, sort: sort });
    var controller = new AbortController();
    currentXhr = controller;

    fetch("api/products.php?" + params.toString(), { signal: controller.signal })
      .then(function (res) {
        if (!res.ok) {
          throw new Error("Server error " + res.status + ": could not load products.");
        }
        return res.json();
      })
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || "Failed to load products.");
        renderCards(data.products, q);
        if (feedback) {
          feedback.textContent = q
            ? "Found " + data.count + " result" + (data.count !== 1 ? "s" : "") + ' for “' + q + '”'
            : (data.count === 0 ? "No products available." : "");
        }
        if (emptyState) emptyState.hidden = data.count > 0;
      })
      .catch(function (err) {
        if (err.name === "AbortError") return; // intentionally cancelled
        if (feedback) feedback.textContent = "⚠ " + (err.message || "Could not load products. Please try again.");
        if (emptyState) emptyState.hidden = false;
      })
      .finally(function () {
        if (spinner) spinner.hidden = true;
        currentXhr = null;
      });
  }

  // ── Dynamic card builder ─────────────────────────────────────────────────

  function renderCards(products, query) {
    grid.innerHTML = "";

    products.forEach(function (p) {
      var price    = parseFloat(p.price) || 0;
      var sale     = p.sale_price !== null ? parseFloat(p.sale_price) : null;
      var badge    = p.badge || "";
      var slugs    = p.platforms || "";
      var platList = slugs ? slugs.split(",") : [];

      var article = document.createElement("article");
      article.className       = "product-card";
      article.dataset.productId   = p.product_id;
      article.dataset.name        = p.name;
      article.dataset.price       = Math.round(price);
      article.dataset.salePrice   = sale !== null ? Math.round(sale) : "";
      article.dataset.platform    = slugs;
      article.dataset.category    = p.category_slug || "";
      article.dataset.stock       = p.stock;
      article.dataset.badge       = badge;
      article.dataset.description = p.description || "";

      var badgeHTML = badge ? '<span class="card-badge badge-' + badge + '">' + badgeLabel(badge, price, sale) + '</span>' : '';
      var platHTML  = platList.map(function (s) {
        return '<span class="platform-tag">' + s.toUpperCase() + '</span>';
      }).join('');
      var priceHTML = sale !== null
        ? '<span class="price-current">&#8369;' + fmt(sale) + '</span><span class="price-original">&#8369;' + fmt(price) + '</span>'
        : '<span class="price-current">&#8369;' + fmt(price) + '</span>';
      var btnLabel = badge === "preorder" ? "Pre-Order Now" : "Add to Cart";

      article.innerHTML =
        '<div class="card-media">' + badgeHTML +
          '<img src="' + esc(p.image_url) + '" alt="' + esc(p.name) + '" loading="lazy" />' +
        '</div>' +
        '<div class="card-body">' +
          '<div class="card-platforms">' + platHTML + '</div>' +
          '<h4 class="card-title">' + esc(p.name) + '</h4>' +
          '<p class="card-price">' + priceHTML + '</p>' +
          '<button class="add-to-cart" type="button">' + btnLabel + '</button>' +
        '</div>';

      // Cart button
      article.querySelector('.add-to-cart').addEventListener('click', function (e) {
        e.stopPropagation();
        if (window.gbCart) {
          window.gbCart.add({
            productId: p.product_id,
            name:      p.name,
            price:     sale !== null ? Math.round(sale) : Math.round(price),
            image:     p.image_url,
            platform:  slugs,
          });
        }
      });

      // Card click → modal
      article.addEventListener('click', function (e) {
        if (e.target.classList.contains('add-to-cart')) return;
        buildModal(article);
      });

      grid.appendChild(article);
    });

    // Re-attach events to any still-visible original cards if grid reused
  }

  // ── Helpers ──────────────────────────────────────────────────────────────

  function fmt(n) { return Math.round(n).toLocaleString(); }

  function esc(str) {
    var d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
  }

  function badgeLabel(badge, price, sale) {
    if (badge === 'sale' && sale && price) return '-' + Math.round((1 - sale / price) * 100) + '%';
    return { hot: 'Hot', new: 'New', preorder: 'Pre-Order' }[badge] || '';
  }

  // ── Modal ────────────────────────────────────────────────────────────────

  function buildModal(card) {
    var name        = card.dataset.name || "";
    var imgSrc      = card.querySelector("img").src;
    var salePrice   = card.dataset.salePrice;
    var basePrice   = card.dataset.price || "0";
    var platforms   = (card.dataset.platform || "").split(",").filter(Boolean);
    var badge       = card.dataset.badge || "";
    var description = card.dataset.description || "";

    var priceHTML = salePrice && salePrice !== ""
      ? '<span class="price-current">&#8369;' + fmt(salePrice) + '</span><span class="price-original">&#8369;' + fmt(basePrice) + '</span>'
      : '<span class="price-current">&#8369;' + fmt(basePrice) + '</span>';

    var platformHTML = platforms.map(function (p) {
      return '<span class="platform-tag">' + p.toUpperCase() + '</span>';
    }).join('');

    var descHTML    = description ? '<p class="modal-description">' + esc(description) + '</p>' : '';
    var btnLabel    = badge === 'preorder' ? 'Pre-Order Now' : 'Add to Cart';

    var modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML =
      '<div class="modal-content">' +
        '<span class="close-btn" aria-label="Close">&times;</span>' +
        '<img src="' + imgSrc + '" alt="' + esc(name) + '" />' +
        '<div class="modal-body">' +
          '<div class="modal-platforms">' + platformHTML + '</div>' +
          '<h2>' + esc(name) + '</h2>' +
          '<p class="modal-price">' + priceHTML + '</p>' +
          descHTML +
          '<button class="modal-add-cart">' + btnLabel + '</button>' +
        '</div>' +
      '</div>';

    modal.querySelector('.close-btn').addEventListener('click', function () { modal.remove(); });
    modal.addEventListener('click', function (e) { if (e.target === modal) modal.remove(); });
    modal.querySelector('.modal-add-cart').addEventListener('click', function () {
      if (window.gbCart) {
        var sp = card.dataset.salePrice;
        window.gbCart.add({
          productId: card.dataset.productId,
          name:      name,
          price:     parseInt((sp && sp !== "" ? sp : card.dataset.price) || "0", 10),
          image:     card.querySelector("img").getAttribute("src"),
          platform:  card.dataset.platform || "",
        });
      }
      modal.remove();
    });

    document.body.appendChild(modal);
  }

  // ── Event wiring ─────────────────────────────────────────────────────────

  function scheduleSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchAndRender, 350);
  }

  if (searchInput)  searchInput.addEventListener("input",  scheduleSearch);
  if (platformFilt) platformFilt.addEventListener("change", fetchAndRender);
  if (categoryFilt) categoryFilt.addEventListener("change", fetchAndRender);
  if (sortControl)  sortControl.addEventListener("change",  fetchAndRender);

  // Attach modal/cart events to server-rendered cards on first load
  originalCards.forEach(function (card) {
    card.addEventListener("click", function (e) {
      if (e.target.classList.contains("add-to-cart")) return;
      buildModal(card);
    });
    var cartBtn = card.querySelector(".add-to-cart");
    if (cartBtn) {
      cartBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        if (window.gbCart) {
          var sp = card.dataset.salePrice;
          window.gbCart.add({
            productId: card.dataset.productId,
            name:      card.dataset.name,
            price:     parseInt((sp && sp !== "" ? sp : card.dataset.price) || "0", 10),
            image:     card.querySelector("img").getAttribute("src"),
            platform:  card.dataset.platform || "",
          });
        }
      });
    }
  });
})();
