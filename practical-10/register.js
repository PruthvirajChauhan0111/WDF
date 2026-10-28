
document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("registrationForm");
    const formMessage = document.getElementById("formMessage");

    if (!form) {
        console.error("Registration form not found.");
        return;
    }

    const fields = {
        name: document.getElementById("fullName"),
        email: document.getElementById("email"),
        mobile: document.getElementById("mobile"),
        password: document.getElementById("password"),
        confirmPassword: document.getElementById("confirmPassword"),
        course: document.getElementById("course"),
        year: document.getElementById("year"),
        terms: document.getElementById("terms")
    };

    function showMessage(message, type = "error") {
        if (!formMessage) {
            alert(message);
            return;
        }

        formMessage.textContent = message;
        formMessage.className = `form-message ${type}`;
        formMessage.style.display = "block";
        formMessage.setAttribute("role", "alert");
    }

    function clearMessage() {
        if (!formMessage) return;

        formMessage.textContent = "";
        formMessage.className = "form-message";
        formMessage.style.display = "none";
    }

    function showError(field, message) {
        const errorElement = document.getElementById(
            `${field.id}Error`
        );

        if (errorElement) {
            errorElement.textContent = message;
        }

        field.classList.toggle("invalid", Boolean(message));
        field.setAttribute(
            "aria-invalid",
            message ? "true" : "false"
        );
    }

    function validateField(field) {
        if (!field) return true;

        let message = "";
        const value = field.type === "checkbox"
            ? field.checked
            : field.value.trim();

        switch (field.id) {
            case "fullName":
                if (!value) {
                    message = "Please enter your full name.";
                } else if (value.length < 2) {
                    message = "Name must contain at least 2 characters.";
                }
                break;

            case "email":
                if (!value) {
                    message = "Please enter your email.";
                } else if (
                    !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)
                ) {
                    message = "Please enter a valid email address.";
                }
                break;

            case "mobile": {
                const digits = String(value).replace(/\D/g, "");

                if (!value) {
                    message = "Please enter your mobile number.";
                } else if (
                    !/^\+?[0-9\s()-]+$/.test(value) ||
                    digits.length < 10 ||
                    digits.length > 15
                ) {
                    message = "Please enter a valid mobile number.";
                }
                break;
            }

            case "password":
                if (!value) {
                    message = "Please enter a password.";
                } else if (field.value.length < 8) {
                    message = "Password must contain at least 8 characters.";
                }
                break;

            case "confirmPassword":
                if (!value) {
                    message = "Please confirm your password.";
                } else if (
                    fields.password &&
                    field.value !== fields.password.value
                ) {
                    message = "Passwords do not match.";
                }
                break;

            case "course":
                if (!value) {
                    message = "Please select a course.";
                }
                break;

            case "year":
                if (!value) {
                    message = "Please select your year of study.";
                }
                break;

            case "terms":
                if (!field.checked) {
                    message = "Please accept the terms and conditions.";
                }
                break;
        }

        showError(field, message);
        return !message;
    }

    // Real-time validation
    Object.values(fields).forEach((field) => {
        if (!field) {
            console.error("A registration field ID is missing.");
            return;
        }

        const eventName =
            field.type === "checkbox" || field.tagName === "SELECT"
                ? "change"
                : "input";

        field.addEventListener(eventName, () => {
            validateField(field);
            clearMessage();

            if (
                field.id === "password" &&
                fields.confirmPassword &&
                fields.confirmPassword.value
            ) {
                validateField(fields.confirmPassword);
            }
        });
    });

    // Password strength indicator
    if (fields.password) {
        fields.password.addEventListener("input", () => {
            const strengthBar =
                document.getElementById("passwordStrength");
            const strengthText =
                document.getElementById("strengthText");

            const password = fields.password.value;
            let score = 0;

            if (password.length >= 8) score++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) {
                score++;
            }
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;

            const labels = [
                "Very weak",
                "Weak",
                "Fair",
                "Good",
                "Strong"
            ];

            if (strengthBar) {
                strengthBar.style.width = `${(score / 4) * 100}%`;
                strengthBar.className = `strength-bar strength-${score}`;
                strengthBar.setAttribute("aria-valuenow", score);
            }

            if (strengthText) {
                strengthText.textContent = password
                    ? labels[score]
                    : "Enter a password to check its strength.";
            }
        });
    }

    // Gender validation
    form.querySelectorAll('input[name="gender"]').forEach((radio) => {
        radio.addEventListener("change", () => {
            const genderError = document.getElementById("genderError");
            if (genderError) genderError.textContent = "";
            clearMessage();
        });
    });

    // Submit registration form
    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        clearMessage();

        let isValid = true;

        Object.values(fields).forEach((field) => {
            if (field && !validateField(field)) {
                isValid = false;
            }
        });

        const gender = form.querySelector(
            'input[name="gender"]:checked'
        );

        const genderError = document.getElementById("genderError");

        if (!gender) {
            isValid = false;

            if (genderError) {
                genderError.textContent = "Please select your gender.";
            }
        } else if (genderError) {
            genderError.textContent = "";
        }

        if (!isValid) {
            showMessage("Please correct the errors in the form.");
            return;
        }

        const submitButton = form.querySelector(
            'button[type="submit"], input[type="submit"]'
        );

        const originalButtonText = submitButton
            ? submitButton.tagName === "INPUT"
                ? submitButton.value
                : submitButton.textContent
            : "";

        try {
            if (submitButton) {
                submitButton.disabled = true;

                if (submitButton.tagName === "INPUT") {
                    submitButton.value = "Registering...";
                } else {
                    submitButton.textContent = "Registering...";
                }
            }

            showMessage("Submitting registration...", "success");

            const formData = new FormData(form);

            // Use the correct PHP endpoint
            const response = await fetch("register.php", {
                method: "POST",
                body: formData,
                headers: {
                    Accept: "application/json"
                }
            });

            const responseText = await response.text();
            let result;

            try {
                result = JSON.parse(responseText);
            } catch (error) {
                console.error("Server response:", responseText);

                throw new Error(
                    "The server returned invalid JSON. Check register.php and db.php."
                );
            }

            if (!response.ok || result.success !== true) {
                throw new Error(
                    result.message || "Registration failed. Please try again."
                );
            }

            showMessage(
                result.message || "Registration successful!",
                "success"
            );

            form.reset();

            Object.values(fields).forEach((field) => {
                if (field) showError(field, "");
            });

            if (genderError) genderError.textContent = "";

            const strengthBar =
                document.getElementById("passwordStrength");
            const strengthText =
                document.getElementById("strengthText");

            if (strengthBar) {
                strengthBar.style.width = "0%";
                strengthBar.className = "password-strength";
                strengthBar.setAttribute("aria-valuenow", "0");
            }

            if (strengthText) {
                strengthText.textContent =
                    "Enter a password to check its strength.";
            }
        } catch (error) {
            console.error("Registration error:", error);

            showMessage(
                error.message ||
                "Unable to connect to the server. Please try again."
            );
        } finally {
            if (submitButton) {
                submitButton.disabled = false;

                if (submitButton.tagName === "INPUT") {
                    submitButton.value = originalButtonText;
                } else {
                    submitButton.textContent = originalButtonText;
                }
            }
        }
    });
});
