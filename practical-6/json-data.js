document.addEventListener("DOMContentLoaded", () => {
  const datasetSelect = document.getElementById("datasetSelect");
  const searchInput = document.getElementById("dataSearch");
  const filterSelect = document.getElementById("dataFilter");
  const sortSelect = document.getElementById("dataSort");
  const results = document.getElementById("dataResults");
  const status = document.getElementById("dataStatus");
  const pagination = document.getElementById("pagination");
  const resultCount = document.getElementById("resultCount");

  const pageSize = 6;
  let allRecords = [];
  let currentPage = 1;

  const configurations = {
    students: {
      url: "students.json",
      searchFields: ["name", "email", "course", "status"],
      filterField: "course",
      filterLabel: "All courses",
      sortFields: {
        name: "name",
        year: "year",
        id: "id"
      }
    },
    events: {
      url: "events.json",
      searchFields: ["title", "category", "location"],
      filterField: "category",
      filterLabel: "All categories",
      sortFields: {
        title: "title",
        date: "date",
        id: "id"
      }
    },
    faqs: {
      url: "faqs.json",
      searchFields: ["question", "answer", "category"],
      filterField: "category",
      filterLabel: "All categories",
      sortFields: {
        question: "question",
        category: "category",
        id: "id"
      }
    }
  };

  function showStatus(message, type = "") {
    status.textContent = message;
    status.className = "data-status" + (type ? " " + type : "");
  }

  function escapeHTML(value) {
    return String(value ?? "").replace(/[&<>"']/g, character => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;"
    })[character]);
  }

  async function loadDataset() {
    const config = configurations[datasetSelect.value];

    currentPage = 1;
    results.innerHTML = "";
    pagination.innerHTML = "";
    resultCount.textContent = "";
    showStatus("Loading data…");

    searchInput.value = "";
    filterSelect.innerHTML = "";
    sortSelect.innerHTML = "";

    try {
      const response = await fetch(config.url);

      if (!response.ok) {
        throw new Error("HTTP error " + response.status);
      }

      const data = await response.json();

      if (!Array.isArray(data)) {
        throw new Error("The JSON file must contain an array of records.");
      }

      allRecords = data;

      buildFilter(config);
      buildSort(config);
      renderData();

      showStatus("Data loaded successfully.", "success");
    } catch (error) {
      allRecords = [];
      results.innerHTML = "";
      pagination.innerHTML = "";
      resultCount.textContent = "";
      showStatus(
        "Unable to load data. Check that the JSON file exists and you are using Live Server. " +
        error.message,
        "error"
      );
    }
  }

  function buildFilter(config) {
    const values = [...new Set(
      allRecords.map(record => record[config.filterField]).filter(Boolean)
    )].sort((a, b) => String(a).localeCompare(String(b)));

    filterSelect.innerHTML =
      `<option value="">${escapeHTML(config.filterLabel)}</option>` +
      values.map(value =>
        `<option value="${escapeHTML(value)}">${escapeHTML(value)}</option>`
      ).join("");
  }

  function buildSort(config) {
    const options = Object.entries(config.sortFields);

    sortSelect.innerHTML = options.map(([value, label]) =>
      `<option value="${escapeHTML(value)}">${escapeHTML(label)}</option>`
    ).join("");

    sortSelect.value = options[0]?.[0] || "";
  }

  function getFilteredRecords() {
    const config = configurations[datasetSelect.value];
    const query = searchInput.value.trim().toLowerCase();
    const filterValue = filterSelect.value;
    const sortKey = sortSelect.value;

    let records = allRecords.filter(record => {
      const matchesSearch = config.searchFields.some(field =>
        String(record[field] ?? "").toLowerCase().includes(query)
      );

      const matchesFilter = !filterValue ||
        String(record[config.filterField]) === filterValue;

      return matchesSearch && matchesFilter;
    });

    if (sortKey) {
      records.sort((a, b) => {
        const first = a[config.sortFields[sortKey]];
        const second = b[config.sortFields[sortKey]];

        if (typeof first === "number" && typeof second === "number") {
          return first - second;
        }

        return String(first ?? "").localeCompare(String(second ?? ""), undefined, {
          numeric: true,
          sensitivity: "base"
        });
      });
    }

    return records;
  }

  function renderData() {
    const records = getFilteredRecords();
    const totalPages = Math.max(1, Math.ceil(records.length / pageSize));

    currentPage = Math.min(currentPage, totalPages);

    const start = (currentPage - 1) * pageSize;
    const pageRecords = records.slice(start, start + pageSize);

    resultCount.textContent =
      `${records.length} record${records.length === 1 ? "" : "s"} found`;

    if (records.length === 0) {
      results.innerHTML = `
        <div class="empty-state">
          <span class="empty-icon" aria-hidden="true">⌕</span>
          <h3>No matching records</h3>
          <p>Try a different search term or filter.</p>
        </div>`;
      pagination.innerHTML = "";
      return;
    }

    results.innerHTML = pageRecords.map(record => {
      if (datasetSelect.value === "students") {
        return `
          <article class="data-card">
            <div class="data-card-top">
              <span class="record-number">STU-${String(record.id).padStart(3, "0")}</span>
              <span class="record-badge ${record.status === "Active" ? "active" : "inactive"}">
                ${escapeHTML(record.status)}
              </span>
            </div>
            <h3>${escapeHTML(record.name)}</h3>
            <p class="record-email">${escapeHTML(record.email)}</p>
            <div class="record-meta">
              <span>${escapeHTML(record.course)}</span>
              <span>Year ${escapeHTML(record.year)}</span>
            </div>
          </article>`;
      }

      if (datasetSelect.value === "events") {
        return `
          <article class="data-card">
            <div class="data-card-top">
              <span class="record-number">EVENT-${String(record.id).padStart(3, "0")}</span>
              <span class="record-badge">${escapeHTML(record.category)}</span>
            </div>
            <h3>${escapeHTML(record.title)}</h3>
            <p class="record-email">${escapeHTML(record.location)}</p>
            <div class="record-meta">
              <span>${escapeHTML(record.date)}</span>
            </div>
          </article>`;
      }

      return `
        <article class="data-card faq-data-card">
          <div class="data-card-top">
            <span class="record-number">FAQ-${String(record.id).padStart(3, "0")}</span>
            <span class="record-badge">${escapeHTML(record.category)}</span>
          </div>
          <h3>${escapeHTML(record.question)}</h3>
          <p class="faq-answer">${escapeHTML(record.answer)}</p>
        </article>`;
    }).join("");

    renderPagination(totalPages);
  }

  function renderPagination(totalPages) {
    pagination.innerHTML = "";

    function addButton(label, page, disabled, current = false) {
      const button = document.createElement("button");
      button.type = "button";
      button.textContent = label;
      button.disabled = disabled;

      if (current) {
        button.setAttribute("aria-current", "page");
      }

      button.addEventListener("click", () => {
        currentPage = page;
        renderData();
      });

      pagination.appendChild(button);
    }

    addButton("← Previous", currentPage - 1, currentPage === 1);

    for (let page = 1; page <= totalPages; page++) {
      addButton(String(page), page, false, page === currentPage);
    }

    addButton("Next →", currentPage + 1, currentPage === totalPages);
  }

  datasetSelect.addEventListener("change", loadDataset);

  searchInput.addEventListener("input", () => {
    currentPage = 1;
    renderData();
  });

  filterSelect.addEventListener("change", () => {
    currentPage = 1;
    renderData();
  });

  sortSelect.addEventListener("change", () => {
    currentPage = 1;
    renderData();
  });

  loadDataset();
});