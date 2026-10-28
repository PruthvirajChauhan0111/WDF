const API_URL = "api.php";

const form = document.getElementById("studentForm");
const studentId = document.getElementById("studentId");
const fullName = document.getElementById("fullName");
const email = document.getElementById("email");
const course = document.getElementById("course");
const tableBody = document.getElementById("studentTable");
const messageBox = document.getElementById("message");
const formTitle = document.getElementById("formTitle");
const submitButton = document.getElementById("submitButton");
const cancelButton = document.getElementById("cancelButton");
const searchInput = document.getElementById("search");
const refreshButton = document.getElementById("refreshButton");

let students = [];

function showMessage(message, type = "success") {
    messageBox.textContent = message;
    messageBox.className = `message ${type}`;
}

function clearMessage() {
    messageBox.textContent = "";
    messageBox.className = "message";
}

async function requestApi(options = {}) {
    const response = await fetch(API_URL, {
        ...options,
        headers: {
            ...(options.body ? { "Content-Type": "application/json" } : {}),
            ...(options.headers || {})
        }
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
        throw new Error(result.message || "Request failed.");
    }

    return result;
}

async function loadStudents() {
    tableBody.innerHTML =
        '<tr><td colspan="5">Loading students...</td></tr>';

    try {
        const result = await requestApi();
        students = result.data || [];
        displayStudents();
    } catch (error) {
        tableBody.innerHTML =
            '<tr><td colspan="5">Unable to load student records.</td></tr>';

        showMessage(error.message, "error");
    }
}

function displayStudents() {
    const query = searchInput.value.trim().toLowerCase();

    const filteredStudents = students.filter(student => {
        return [
            student.full_name,
            student.email,
            student.course
        ].some(value =>
            String(value || "").toLowerCase().includes(query)
        );
    });

    tableBody.replaceChildren();

    if (filteredStudents.length === 0) {
        const row = document.createElement("tr");
        const cell = document.createElement("td");

        cell.colSpan = 5;
        cell.textContent = "No student records found.";
        row.appendChild(cell);
        tableBody.appendChild(row);
        return;
    }

    filteredStudents.forEach(student => {
        const row = document.createElement("tr");

        addCell(row, student.student_id);
        addCell(row, student.full_name);
        addCell(row, student.email);
        addCell(row, student.course);

        const actionsCell = document.createElement("td");
        actionsCell.className = "actions";

        const editButton = document.createElement("button");
        editButton.type = "button";
        editButton.className = "btn edit";
        editButton.textContent = "Edit";
        editButton.addEventListener("click", () => editStudent(student));

        const deleteButton = document.createElement("button");
        deleteButton.type = "button";
        deleteButton.className = "btn delete";
        deleteButton.textContent = "Delete";
        deleteButton.addEventListener("click", () => deleteStudent(student));

        actionsCell.append(editButton, deleteButton);
        row.appendChild(actionsCell);
        tableBody.appendChild(row);
    });
}

function addCell(row, value) {
    const cell = document.createElement("td");
    cell.textContent = String(value ?? "");
    row.appendChild(cell);
}

function editStudent(student) {
    studentId.value = student.student_id;
    fullName.value = student.full_name;
    email.value = student.email;
    course.value = student.course;

    formTitle.textContent = "Edit Student";
    submitButton.textContent = "Update Student";
    cancelButton.hidden = false;

    fullName.focus();
    clearMessage();
}

function resetForm() {
    form.reset();
    studentId.value = "";
    formTitle.textContent = "Add Student";
    submitButton.textContent = "Add Student";
    cancelButton.hidden = true;
}

form.addEventListener("submit", async event => {
    event.preventDefault();
    clearMessage();

    const editing = studentId.value !== "";

    const payload = {
        action: editing ? "update" : "add",
        full_name: fullName.value.trim(),
        email: email.value.trim(),
        course: course.value.trim()
    };

    if (editing) {
        payload.student_id = Number(studentId.value);
    }

    submitButton.disabled = true;

    try {
        const result = await requestApi({
            method: "POST",
            headers: {
                "X-CSRF-Token": window.APP_CSRF_TOKEN
            },
            body: JSON.stringify(payload)
        });

        showMessage(result.message);
        resetForm();
        await loadStudents();
    } catch (error) {
        showMessage(error.message, "error");
    } finally {
        submitButton.disabled = false;
    }
});

async function deleteStudent(student) {
    const confirmed = confirm(
        `Are you sure you want to delete ${student.full_name}?`
    );

    if (!confirmed) return;

    clearMessage();

    try {
        const result = await requestApi({
            method: "POST",
            headers: {
                "X-CSRF-Token": window.APP_CSRF_TOKEN
            },
            body: JSON.stringify({
                action: "delete",
                student_id: Number(student.student_id)
            })
        });

        showMessage(result.message);

        if (studentId.value === String(student.student_id)) {
            resetForm();
        }

        await loadStudents();
    } catch (error) {
        showMessage(error.message, "error");
    }
}

searchInput.addEventListener("input", displayStudents);

refreshButton.addEventListener("click", () => {
    clearMessage();
    loadStudents();
});

cancelButton.addEventListener("click", resetForm);

loadStudents();