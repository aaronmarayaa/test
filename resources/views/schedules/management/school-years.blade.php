@extends('schedules.management.layout')

@php
    $pageTitle = 'Manage School Years';
    $pageSubtitle = 'Add, edit, activate, and delete school years.';
@endphp

@section('form')
    <h2 id="form-title">Add School Year</h2>

    <input type="hidden" id="edit-id">

    <div class="form-group">
        <label for="school_year">School Year</label>
        <input type="text" id="school_year" placeholder="Example: 2025-2026">
    </div>

    <div class="form-group">
        <label for="status">Status</label>
        <select id="status">
            <option value="Inactive">Inactive</option>
            <option value="Active">Active</option>
        </select>
    </div>

    <div class="actions">
        <button type="button" class="save-btn" id="save-btn">Save School Year</button>
        <button type="button" class="btn-edit" id="cancel-btn" style="display:none;">Cancel</button>
    </div>
@endsection

@section('list')
    <h2>School Year List</h2>

    <table class="list-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>School Year</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="school-year-table-body">
            <tr>
                <td colspan="4">Loading...</td>
            </tr>
        </tbody>
    </table>

    <script>
        const apiUrl = "http://127.0.0.1:8000/api/school-years";

        const tableBody = document.getElementById("school-year-table-body");
        const formTitle = document.getElementById("form-title");
        const editIdInput = document.getElementById("edit-id");
        const schoolYearInput = document.getElementById("school_year");
        const statusInput = document.getElementById("status");
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
            schoolYearInput.value = "";
            statusInput.value = "Inactive";
            formTitle.textContent = "Add School Year";
            saveBtn.textContent = "Save School Year";
            cancelBtn.style.display = "none";
        }

        function renderRows(items) {
            if (!Array.isArray(items) || items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="4">No school years found.</td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = items.map(item => `
                <tr>
                    <td>${escapeHtml(item.id)}</td>
                    <td>${escapeHtml(item.school_year)}</td>
                    <td>
                        <span class="tag ${item.status === 'Active' ? 'tag-green' : 'tag-gray'}">
                            ${escapeHtml(item.status)}
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

        async function loadSchoolYears() {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="4">Loading...</td>
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
                    throw new Error(result.message || "Failed to load school years.");
                }

                renderRows(result || []);
            } catch (error) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="4">${escapeHtml(error.message)}</td>
                    </tr>
                `;
            }
        }

        function editItem(item) {
            editIdInput.value = item.id;
            schoolYearInput.value = item.school_year ?? "";
            statusInput.value = item.status ?? "Inactive";
            formTitle.textContent = "Edit School Year";
            saveBtn.textContent = "Update School Year";
            cancelBtn.style.display = "inline-block";
            window.scrollTo({ top: 0, behavior: "smooth" });
        }

        async function saveItem() {
            const id = editIdInput.value.trim();
            const payload = {
                school_year: schoolYearInput.value.trim(),
                status: statusInput.value
            };

            if (!payload.school_year) {
                alert("School Year is required.");
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
                await loadSchoolYears();
                alert(result.message || "Saved successfully.");
            } catch (error) {
                alert(error.message);
            }
        }

        async function deleteItem(id) {
            if (!confirm("Delete this school year?")) {
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

                await loadSchoolYears();
                alert(result.message || "Deleted successfully.");
            } catch (error) {
                alert(error.message);
            }
        }

        saveBtn.addEventListener("click", saveItem);
        cancelBtn.addEventListener("click", resetForm);

        loadSchoolYears();
    </script>
@endsection