

document.addEventListener("DOMContentLoaded", function () {
    // 1. DARK / LIGHT THEME WITH localStorage
    const themeToggle = document.getElementById("themeToggle");
    const savedTheme = localStorage.getItem("studentHubTheme");

    if (savedTheme === "dark") {
        document.body.classList.add("dark-theme");
    }

    function updateThemeButton() {
        if (!themeToggle) return;

        const darkMode = document.body.classList.contains("dark-theme");
        themeToggle.textContent = darkMode ? "☀️ Light Mode" : "🌙 Dark Mode";
        themeToggle.setAttribute("aria-pressed", String(darkMode));
    }

    updateThemeButton();

    if (themeToggle) {
        themeToggle.addEventListener("click", function () {
            document.body.classList.toggle("dark-theme");

            const darkMode = document.body.classList.contains("dark-theme");
            localStorage.setItem("studentHubTheme", darkMode ? "dark" : "light");

            updateThemeButton();
        });
    }

    // 2. HAMBURGER MENU
    const menuToggle = document.getElementById("menuToggle");
    const mainNav = document.querySelector("header nav");

    if (menuToggle && mainNav) {
        menuToggle.addEventListener("click", function () {
            const isOpen = mainNav.classList.toggle("nav-open");

            menuToggle.setAttribute("aria-expanded", String(isOpen));
            menuToggle.setAttribute(
                "aria-label",
                isOpen ? "Close navigation menu" : "Open navigation menu"
            );
            menuToggle.textContent = isOpen ? "✕ Close Menu" : "☰ Menu";
        });

        mainNav.querySelectorAll("a").forEach(function (link) {
            link.addEventListener("click", function () {
                mainNav.classList.remove("nav-open");
                menuToggle.setAttribute("aria-expanded", "false");
                menuToggle.setAttribute("aria-label", "Open navigation menu");
                menuToggle.textContent = "☰ Menu";
            });
        });
    }

    // 3. COLLAPSIBLE FAQ
    const faqButtons = document.querySelectorAll(".faq-question");

    faqButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            const answer = button.nextElementSibling;
            const isExpanded = button.getAttribute("aria-expanded") === "true";

            button.setAttribute("aria-expanded", String(!isExpanded));

            if (answer) {
                answer.hidden = isExpanded;
            }

            const symbol = button.querySelector(".faq-symbol");
            if (symbol) {
                symbol.textContent = isExpanded ? "+" : "−";
            }
        });
    });

    // 4. MODAL POPUP
    const modal = document.getElementById("infoModal");
    const openModalButton = document.getElementById("openModal");
    const closeModalButtons = document.querySelectorAll("[data-close-modal]");

    function openModal() {
        if (!modal) return;

        modal.hidden = false;
        document.body.classList.add("modal-open");

        const closeButton = modal.querySelector(".modal-close");
        if (closeButton) closeButton.focus();
    }

    function closeModal() {
        if (!modal) return;

        modal.hidden = true;
        document.body.classList.remove("modal-open");

        if (openModalButton) openModalButton.focus();
    }

    if (openModalButton) {
        openModalButton.addEventListener("click", openModal);
    }

    closeModalButtons.forEach(function (button) {
        button.addEventListener("click", closeModal);
    });

    if (modal) {
        modal.addEventListener("click", function (event) {
            if (event.target === modal) closeModal();
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && !modal.hidden) {
                closeModal();
            }
        });
    }

    // 5. CONTENT SLIDER
    const slides = document.querySelectorAll(".slide");
    const previousButton = document.getElementById("prevSlide");
    const nextButton = document.getElementById("nextSlide");
    const slideStatus = document.getElementById("slideStatus");
    let currentSlide = 0;

    function showSlide(index) {
        if (slides.length === 0) return;

        currentSlide = (index + slides.length) % slides.length;

        slides.forEach(function (slide, i) {
            const active = i === currentSlide;
            slide.hidden = !active;
            slide.setAttribute("aria-hidden", String(!active));
        });

        if (slideStatus) {
            slideStatus.textContent =
                "Slide " + (currentSlide + 1) + " of " + slides.length;
        }
    }

    if (slides.length > 0) {
        showSlide(0);
    }

    if (previousButton) {
        previousButton.addEventListener("click", function () {
            showSlide(currentSlide - 1);
        });
    }

    if (nextButton) {
        nextButton.addEventListener("click", function () {
            showSlide(currentSlide + 1);
        });
    }

    // 6. DISMISSIBLE NOTIFICATION BANNER
    const notification = document.getElementById("notificationBanner");
    const dismissNotification = document.getElementById("dismissNotification");
    const showNotificationButton = document.getElementById("showNotification");

    if (dismissNotification && notification) {
        dismissNotification.addEventListener("click", function () {
            notification.hidden = true;
        });
    }

    if (showNotificationButton && notification) {
        showNotificationButton.addEventListener("click", function () {
            notification.hidden = false;
        });
    }
});
