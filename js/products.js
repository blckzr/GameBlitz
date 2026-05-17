// Products page — search, filter, sort, modal, cart
(function () {
  const grid = document.getElementById("productGrid");
  const searchInput = document.getElementById("gameSearch");
  const feedback = document.getElementById("searchFeedback");
  const platformFilter = document.getElementById("platformFilter");
  const categoryFilter = document.getElementById("categoryFilter");
  const sortControl = document.getElementById("sortControl");
  const emptyState = document.getElementById("emptyState");
  const allCards = Array.from(document.querySelectorAll(".product-card"));

  // ── Filtering & sorting ─────────────────────────────────────────────────

  function applyFilters() {
    const query = (searchInput.value || "").toLowerCase().trim();
    const platform = platformFilter ? platformFilter.value : "";
    const category = categoryFilter ? categoryFilter.value : "";

    let visible = allCards.filter((card) => {
      const name = (card.dataset.name || "").toLowerCase();
      const cardPlatforms = card.dataset.platform || "";
      const cardCategory = card.dataset.category || "";

      const matchesSearch = !query || name.includes(query);
      const matchesPlatform = !platform || cardPlatforms.includes(platform);
      const matchesCategory = !category || cardCategory === category;

      return matchesSearch && matchesPlatform && matchesCategory;
    });

    const sort = sortControl ? sortControl.value : "featured";
    visible.sort(function (a, b) {
      if (sort === "price-asc") return getPrice(a) - getPrice(b);
      if (sort === "price-desc") return getPrice(b) - getPrice(a);
      if (sort === "name-asc") return a.dataset.name.localeCompare(b.dataset.name);
      return parseInt(a.dataset.productId || 0) - parseInt(b.dataset.productId || 0);
    });

    allCards.forEach(function (c) { c.style.display = "none"; });
    visible.forEach(function (c) {
      c.style.display = "flex";
      grid.appendChild(c);
    });

    const count = visible.length;
    if (feedback) {
      feedback.textContent = query
        ? "Found " + count + " result" + (count !== 1 ? "s" : "") + ' for "' + searchInput.value.trim() + '"'
        : "";
    }
    if (emptyState) emptyState.hidden = count > 0;
  }

  function getPrice(card) {
    const sale = card.dataset.salePrice;
    return parseInt((sale && sale !== "" ? sale : card.dataset.price) || "0", 10);
  }

  if (searchInput) searchInput.addEventListener("input", applyFilters);
  if (platformFilter) platformFilter.addEventListener("change", applyFilters);
  if (categoryFilter) categoryFilter.addEventListener("change", applyFilters);
  if (sortControl) sortControl.addEventListener("change", applyFilters);

  // ── Modal ───────────────────────────────────────────────────────────────

  function buildModal(card) {
    const name = card.dataset.name || "";
    const imgSrc = card.querySelector("img").src;
    const salePrice = card.dataset.salePrice;
    const basePrice = card.dataset.price || "0";
    const platforms = (card.dataset.platform || "").split(",").filter(Boolean);
    const badge = card.dataset.badge || "";

    const priceHTML = salePrice && salePrice !== ""
      ? '<span class="price-current">&#8369;' + fmt(salePrice) + "</span>" +
        '<span class="price-original">&#8369;' + fmt(basePrice) + "</span>"
      : '<span class="price-current">&#8369;' + fmt(basePrice) + "</span>";

    const platformHTML = platforms
      .map(function (p) { return '<span class="platform-tag">' + p.toUpperCase() + "</span>"; })
      .join("");

    const btnLabel = badge === "preorder" ? "Pre-Order Now" : "Add to Cart";

    const modal = document.createElement("div");
    modal.className = "modal-overlay";
    modal.innerHTML =
      '<div class="modal-content">' +
        '<span class="close-btn" aria-label="Close">&times;</span>' +
        '<img src="' + imgSrc + '" alt="' + name + '" />' +
        '<div class="modal-body">' +
          '<div class="modal-platforms">' + platformHTML + "</div>" +
          "<h2>" + name + "</h2>" +
          '<p class="modal-price">' + priceHTML + "</p>" +
          '<button class="modal-add-cart">' + btnLabel + "</button>" +
        "</div>" +
      "</div>";

    modal.querySelector(".close-btn").addEventListener("click", function () { modal.remove(); });
    modal.addEventListener("click", function (e) { if (e.target === modal) modal.remove(); });
    modal.querySelector(".modal-add-cart").addEventListener("click", function () {
      if (window.gbCart) {
        var salePrice = card.dataset.salePrice;
        window.gbCart.add({
          productId: card.dataset.productId,
          name:      name,
          price:     parseInt((salePrice && salePrice !== "" ? salePrice : card.dataset.price) || "0", 10),
          image:     card.querySelector("img").getAttribute("src"),
          platform:  card.dataset.platform || "",
        });
      }
      modal.remove();
    });

    document.body.appendChild(modal);
  }

  function fmt(n) {
    return parseInt(n, 10).toLocaleString();
  }

  // Card click → modal (skip when clicking Add to Cart button)
  allCards.forEach(function (card) {
    card.addEventListener("click", function (e) {
      if (e.target.classList.contains("add-to-cart")) return;
      buildModal(card);
    });

    var cartBtn = card.querySelector(".add-to-cart");
    if (cartBtn) {
      cartBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        if (window.gbCart) {
          var salePrice = card.dataset.salePrice;
          window.gbCart.add({
            productId: card.dataset.productId,
            name:      card.dataset.name,
            price:     parseInt((salePrice && salePrice !== "" ? salePrice : card.dataset.price) || "0", 10),
            image:     card.querySelector("img").getAttribute("src"),
            platform:  card.dataset.platform || "",
          });
        }
      });
    }
  });
})();
