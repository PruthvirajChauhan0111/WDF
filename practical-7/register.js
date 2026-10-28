

document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("registrationForm");

    if (!form) return;

    const fields = {
        name: document.getElementById("fullName"),
        email: document.getElementById("email"),
        mobile: document.getElementById("mobile"),
        password: document.getElementById("password"),
        confirmPassword: document.getElementById("confirmPassword"),
        course: document.getElementById("course"),
        year: document.getElementById("year"),
        gender: document.querySelector('input[name="gender"]:checked'),
        terms: document.getElementById("terms")
    };

    const passwordStrength = document.getElementById("passwordStrength");
    const strengthText = document.getElementById("strengthText");
    const formMessage = document.getElementById("formMessage");

    function getField(name) {
        if (name === "gender") {
            return form.querySelector('input[name="gender"]:checked');
        }
        return fields[name];
    }

    function getErrorElement(name) {
        return document.getElementById(name + "Error");
    }

    function showError(name, message) {
        const field = getField(name);
        const error = getErrorElement(name);

        if (error) error.textContent = message;

        if (field) {
            field.setAttribute("aria-invalid", message ? "true" : "false");
        }

        if (name === "gender") {
            form.querySelectorAll('input[name="gender"]').forEach(function (radio) {
                radio.setAttribute("aria-invalid", message ? "true" : "false");
            });
        }

        return message === "";
    }

    function validateName() {
        const value = fields.name.value.trim();
        const valid = /^[A-Za-z][A-Za-z .'-]{1,59}$/.test(value);

        return showError(
            "name",
            value === ""
                ? "Please enter your full name."
                : !valid
                    ? "Use 2–60 letters; spaces, dots, apostrophes and hyphens are allowed."
                    : ""
        );
    }

    function validateEmail() {
        const value = fields.email.value.trim();
        const valid = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value);

        return showError(
            "email",
            value === ""
                ? "Please enter your email address."
                : !valid
                    ? "Enter a valid email, for example student@example.com."
                    : ""
        );
    }

    function validateMobile() {
        const value = fields.mobile.value.trim();
        const digits = value.replace(/\D/g, "");
        const valid = /^\+?[0-9\s()-]+$/.test(value) &&
                      digits.length >= 10 &&
                      digits.length <= 15;

        return showError(
            "mobile",
            value === ""
                ? "Please enter your mobile number."
                : !valid
                    ? "Enter a valid number containing 10–15 digits."
                    : ""
        );
    }

    function getPasswordStrength(value) {
        let score = 0;

        if (value.length >= 8) score++;
        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
        if (/[0-9]/.test(value)) score++;
        if (/[^A-Za-z0-9]/.test(value)) score++;

        return score;
    }

    function updatePasswordStrength() {
        const value = fields.password.value;
        const score = getPasswordStrength(value);
passwordStrength.setAttribute("aria-valuenow", String(score));
        const levels = [
            { label: "Very weak", className: "strength-weak" },
            { label: "Weak", className: "strength-weak" },
            { label: "Fair", className: "strength-fair" },
            { label: "Good", className: "strength-good" },
            { label: "Strong", className: "strength-strong" }
        ];

        passwordStrength.className = "password-strength";

        if (value.length > 0) {
            passwordStrength.classList.add(levels[score].className);
        }

        passwordStrength.style.setProperty(
            "--strength",
            (score / 4 * 100) + "%"
        );

        strengthText.textContent = value
            ? "Password strength: " + levels[score].label
            : "Enter a password to check its strength.";
    }

    function validatePassword() {
        const value = fields.password.value;
        const valid = value.length >= 8 &&
                      /[a-z]/.test(value) &&
                      /[A-Z]/.test(value) &&
                      /[0-9]/.test(value) &&
                      /[^A-Za-z0-9]/.test(value);

        updatePasswordStrength();

        const result = showError(
            "password",
            value === ""
                ? "Please create a password."
                : !valid
                    ? "Use at least 8 characters with uppercase, lowercase, a number and a symbol."
                    : ""
        );

        if (fields.confirmPassword.value !== "") {
            validateConfirmPassword();
        }

        return result;
    }

    function validateConfirmPassword() {
        const value = fields.confirmPassword.value;
        const valid = value !== "" && value === fields.password.value;

        return showError(
            "confirmPassword",
            value === ""
                ? "Please confirm your password."
                : !valid
                    ? "Passwords do not match."
                    : ""
        );
    }

    function validateCourse() {
        return showError(
            "course",
            fields.course.value === ""
                ? "Please select a course."
                : ""
        );
    }

    function validateYear() {
        return showError(
            "year",
            fields.year.value === ""
                ? "Please select your current year."
                : ""
        );
    }

    function validateGender() {
        return showError(
            "gender",
            !getField("gender")
                ? "Please select your gender."
                : ""
        );
    }

    function validateTerms() {
        const valid = fields.terms.checked;

        return showError(
            "terms",
            valid ? "" : "You must accept the terms to continue."
        );
    }

    const validators = {
        name: validateName,
        email: validateEmail,
        mobile: validateMobile,
        password: validatePassword,
        confirmPassword: validateConfirmPassword,
        course: validateCourse,
        year: validateYear,
        gender: validateGender,
        terms: validateTerms
    };

    // Real-time validation for text fields and selects.
    Object.keys(validators).forEach(function (name) {
        const field = getField(name);

        if (name === "gender") {
            form.querySelectorAll('input[name="gender"]').forEach(function (radio) {
                radio.addEventListener("change", validateGender);
            });
        } else if (name === "terms") {
            fields.terms.addEventListener("change", validateTerms);
        } else {
            field.addEventListener("input", validators[name]);

            if (field.tagName === "SELECT") {
                field.addEventListener("change", validators[name]);
            }
        }
    });

    // Submit only when every field is valid.
    form.addEventListener("submit", function (event) {
        event.preventDefault();

        const results = Object.values(validators).map(function (validate) {
            return validate();
        });

        const isValid = results.every(Boolean);

        if (!isValid) {
            formMessage.textContent =
                "Please correct the highlighted fields and try again.";
            formMessage.className = "form-message form-message-error";
            formMessage.focus();

            const firstInvalid = form.querySelector('[aria-invalid="true"]');
            if (firstInvalid) firstInvalid.focus();

            return;
        }

        formMessage.textContent =
            "All validations passed! This front-end demo does not save your registration.";
        formMessage.className = "form-message form-message-success";
        formMessage.focus();
    });

    // Clear messages when the form is reset.
    form.addEventListener("reset", function () {
        window.setTimeout(function () {
            Object.keys(validators).forEach(function (name) {
                showError(name, "");
            });

            updatePasswordStrength();
            formMessage.textContent = "";
            formMessage.className = "form-message";
        }, 0);
    });
});
