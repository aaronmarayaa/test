@extends('schedules.management.layout')

@php
    $pageTitle = 'Manage Courses';
    $pageSubtitle = 'Add, edit, archive, and manage courses.';
@endphp

@section('form')
    <h2 id="form-title">Add Course</h2>

    <input type="hidden" id="edit-id">

    <div class="form-group">
        <label for="course_code">Course Code</label>
        <input type="text" id="course_code" placeholder="Example: BSIT">
    </div>

    <div class="form-group">
        <label for="course_name">Course Name</label>
        <input type="text" id="course_name" placeholder="Example: Bachelor of Science in Information Technology">
    </div>

    <div class="form-group">
        <label for="department_name">Department Name</label>
        <input type="text" id="department_name" placeholder="Example: IT Department">
    </div>

    <div class="form-group">
        <label for="archived">Archived</label>
        <select id="archived">
            <option value="0">No</option>
            <option value="1">Yes</option>
        </select>
    </div>

    <div class="actions">
        <button type="button" class="save-btn" id="save-btn">Save Course</button>
        <button type="button" class="btn-edit" id="cancel-btn" style="display:none;">Cancel</button>
    </div>
@endsection

@section('list')
    <h2>Course List</h2>

    <table class="list-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Course Code</th>
                <th>Course Name</th>
                <th>Department</th>
                <th>Archived</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="course-table-body">
            <tr>
                <td colspan="6">Loading...</td>
            </tr>
        </tbody>
    </table>

    <script>
        const apiUrl = "/api/courses";

        const tableBody = document.getElementById("course-table-body");
        const formTitle = document.getElementById("form-title");
        const editIdInput = document.getElementById("edit-id");
        const courseCodeInput = document.getElementById("course_code");
        const courseNameInput = document.getElementById("course_name");
        const departmentNameInput = document.getElementById("department_name");
        const archivedInput = document.getElementById("archived");
        const saveBtn = document.getElementById("save-btn");
        const cancelBtn = document.getElementById("cancel-btn");

        function escapeHtml(value) {
            return String(value ?? "")
                .replaceAll("&", "&amp;")
                .replaceAll("<", "&lt;")
                .replaceAll(">", "&gt;")
                .replaceAll('"', "&quot;")
                .replaceAll("'", "&#039;");
        }

        function resetForm() {
            editIdInput.value = "";
            courseCodeInput.value = "";
            courseNameInput.value = "";
            departmentNameInput.value = "";
            archivedInput.value = "0";
            formTitle.textContent = "Add Course";
            saveBtn.textContent = "Save Course";
            cancelBtn.style.display = "none";
        }

        function renderRows(items) {
            if (!Array.isArray(items) || items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6">No courses found.</td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = items.map(item => `
                <tr>
                    <td>${escapeHtml(item.id)}</td>
                    <td><strong>${escapeHtml(item.course_code)}</strong></td>
                    <td>${escapeHtml(item.course_name)}</td>
                    <td>${escapeHtml(item.department_name)}</td>
                    <td>
                        <span class="tag ${Number(item.archived) === 1 ? 'tag-gray' : 'tag-green'}">
                            ${Number(item.archived) === 1 ? 'Yes' : 'No'}
                        </span>
                    </td>
                    <td>
                        <div class="actions">
                            <button type="button"
                                class="btn-edit"
                                onclick='editItem(${JSON.stringify(item)})'>
                                Edit
                            </button>
                            <button type="button"
                                class="btn-delete"
                                onclick="deleteItem(${item.id})">
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
            `).join("");
        }

        async function loadCourses() {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6">Loading...</td>
                </tr>
            `;

            try {
                const response = await fetch(apiUrl, {
                    headers: {
                        "Accept": "application/json"
                    }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || "Failed to load courses.");
                }

                renderRows(result.data || []);
            } catch (error) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6">${escapeHtml(error.message)}</td>
                    </tr>
                `;
            }
        }

        function editItem(item) {
            editIdInput.value = item.id ?? "";
            courseCodeInput.value = item.course_code ?? "";
            courseNameInput.value = item.course_name ?? "";
            departmentNameInput.value = item.department_name ?? "";
            archivedInput.value = Number(item.archived) === 1 ? "1" : "0";

            formTitle.textContent = "Edit Course";
            saveBtn.textContent = "Update Course";
            cancelBtn.style.display = "inline-block";

            window.scrollTo({ top: 0, behavior: "smooth" });
        }

        async function saveItem() {
            const id = editIdInput.value.trim();

            const payload = {
                course_code: courseCodeInput.value.trim(),
                course_name: courseNameInput.value.trim(),
                department_name: departmentNameInput.value.trim(),
                archived: archivedInput.value === "1"
            };

            if (!payload.course_code || !payload.course_name || !payload.department_name) {
                alert("Course Code, Course Name, and Department Name are required.");
                return;
            }

            const isEdit = id !== "";
            const url = isEdit ? `${apiUrl}/${id}` : apiUrl;
            const method = isEdit ? "PUT" : "POST";

            try {
                const response = await fetch(url, {
                    method,
                    headers: {
                        "Accept": "application/json",
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (!response.ok) {
                    if (result.errors) {
                        const firstError = Object.values(result.errors).flat()[0];
                        throw new Error(firstError || "Validation failed.");
                    }
                    throw new Error(result.message || "Save failed.");
                }

                resetForm();
                await loadCourses();
                alert(result.message || "Saved successfully.");
            } catch (error) {
                alert(error.message);
            }
        }

        async function deleteItem(id) {
            if (!confirm("Delete this course?")) {
                return;
            }

            try {
                const response = await fetch(`${apiUrl}/${id}`, {
                    method: "DELETE",
                    headers: {
                        "Accept": "application/json"
                    }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || "Delete failed.");
                }

                if (String(editIdInput.value) === String(id)) {
                    resetForm();
                }

                await loadCourses();
                alert(result.message || "Deleted successfully.");
            } catch (error) {
                alert(error.message);
            }
        }

        saveBtn.addEventListener("click", saveItem);
        cancelBtn.addEventListener("click", resetForm);

        loadCourses();
    </script>
@endsection