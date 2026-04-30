@extends('schedules.management.layout')

@php
    $pageTitle = 'Manage Instructors';
    $pageSubtitle = 'Add, edit, archive, and manage instructors.';
@endphp

@section('form')
    <h2 id="form-title">Add Instructor</h2>

    <input type="hidden" id="edit-id">

    <div class="form-group">
        <label for="employee_no">Employee No</label>
        <input type="text" id="employee_no">
    </div>

    <div class="form-group">
        <label for="instructor_name">Instructor Name</label>
        <input type="text" id="instructor_name">
    </div>

    <div class="form-group">
        <label for="employment_type">Employment Type</label>
        <select id="employment_type">
            <option value="full_time">Full Time</option>
            <option value="part_time">Part Time</option>
        </select>
    </div>

    <div class="form-group">
        <label for="specialization">Specialization</label>
        <input type="text" id="specialization" placeholder="Optional">
    </div>

    <div class="form-group">
        <label for="status">Status</label>
        <select id="status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    <div class="form-group">
        <label for="archived">Archived</label>
        <select id="archived">
            <option value="0">No</option>
            <option value="1">Yes</option>
        </select>
    </div>

    <div class="actions">
        <button type="button" class="save-btn" id="save-btn">Save Instructor</button>
        <button type="button" class="btn-edit" id="cancel-btn" style="display:none;">Cancel</button>
    </div>
@endsection

@section('list')
    <h2>Instructor List</h2>

    <div class="filter-row">
        <button type="button" class="filter-btn active" data-filter="active">Active Only</button>
        <button type="button" class="filter-btn" data-filter="archived">Archived Only</button>
        <button type="button" class="filter-btn" data-filter="all">All</button>
    </div>

    <table class="list-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Employee No</th>
                <th>Name</th>
                <th>Type</th>
                <th>Specialization</th>
                <th>Status</th>
                <th>Archived</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="instructor-table-body">
            <tr>
                <td colspan="8">Loading...</td>
            </tr>
        </tbody>
    </table>

    <script>
        const apiUrl = "/api/instructors";

        const tableBody = document.getElementById("instructor-table-body");
        const formTitle = document.getElementById("form-title");
        const editIdInput = document.getElementById("edit-id");
        const employeeNoInput = document.getElementById("employee_no");
        const instructorNameInput = document.getElementById("instructor_name");
        const employmentTypeInput = document.getElementById("employment_type");
        const specializationInput = document.getElementById("specialization");
        const statusInput = document.getElementById("status");
        const archivedInput = document.getElementById("archived");
        const saveBtn = document.getElementById("save-btn");
        const cancelBtn = document.getElementById("cancel-btn");
        const filterButtons = document.querySelectorAll(".filter-btn");

        let allInstructors = [];
        let currentFilter = "active";

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
            employeeNoInput.value = "";
            instructorNameInput.value = "";
            employmentTypeInput.value = "full_time";
            specializationInput.value = "";
            statusInput.value = "active";
            archivedInput.value = "0";
            formTitle.textContent = "Add Instructor";
            saveBtn.textContent = "Save Instructor";
            cancelBtn.style.display = "none";
        }

        function filterItems(items) {
            if (currentFilter === "active") {
                return items.filter(item => Number(item.archived) !== 1);
            }

            if (currentFilter === "archived") {
                return items.filter(item => Number(item.archived) === 1);
            }

            return items;
        }

        function renderRows(items) {
            const filtered = filterItems(items);

            if (!Array.isArray(filtered) || filtered.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8">No instructors found.</td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = filtered.map(item => `
                <tr>
                    <td>${escapeHtml(item.id)}</td>
                    <td>${escapeHtml(item.employee_no)}</td>
                    <td>${escapeHtml(item.instructor_name)}</td>
                    <td>
                        <span class="tag ${item.employment_type === 'part_time' ? 'tag-yellow' : 'tag-green'}">
                            ${escapeHtml(item.employment_type)}
                        </span>
                    </td>
                    <td>${escapeHtml(item.specialization || '—')}</td>
                    <td>
                        <span class="tag ${item.status === 'active' ? 'tag-green' : 'tag-gray'}">
                            ${escapeHtml(item.status)}
                        </span>
                    </td>
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
                                Delete / Archive
                            </button>
                        </div>
                    </td>
                </tr>
            `).join("");
        }

        async function loadInstructors() {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8">Loading...</td>
                </tr>
            `;

            try {
                const response = await fetch(apiUrl, {
                    headers: { "Accept": "application/json" }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || "Failed to load instructors.");
                }

                allInstructors = result.data || [];
                renderRows(allInstructors);
            } catch (error) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8">${escapeHtml(error.message)}</td>
                    </tr>
                `;
            }
        }

        function editItem(item) {
            editIdInput.value = item.id ?? "";
            employeeNoInput.value = item.employee_no ?? "";
            instructorNameInput.value = item.instructor_name ?? "";
            employmentTypeInput.value = item.employment_type ?? "full_time";
            specializationInput.value = item.specialization ?? "";
            statusInput.value = item.status ?? "active";
            archivedInput.value = Number(item.archived) === 1 ? "1" : "0";

            formTitle.textContent = "Edit Instructor";
            saveBtn.textContent = "Update Instructor";
            cancelBtn.style.display = "inline-block";

            window.scrollTo({ top: 0, behavior: "smooth" });
        }

        async function saveItem() {
            const id = editIdInput.value.trim();

            const payload = {
                employee_no: employeeNoInput.value.trim(),
                instructor_name: instructorNameInput.value.trim(),
                employment_type: employmentTypeInput.value,
                specialization: specializationInput.value.trim(),
                status: statusInput.value,
                archived: archivedInput.value === "1"
            };

            if (!payload.employee_no || !payload.instructor_name || !payload.employment_type) {
                alert("Employee No, Instructor Name, and Employment Type are required.");
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
                await loadInstructors();
                alert(result.message || "Saved successfully.");
            } catch (error) {
                alert(error.message);
            }
        }

        async function deleteItem(id) {
            if (!confirm("Delete or archive this instructor?")) return;

            try {
                const response = await fetch(`${apiUrl}/${id}`, {
                    method: "DELETE",
                    headers: { "Accept": "application/json" }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || "Delete failed.");
                }

                if (String(editIdInput.value) === String(id)) {
                    resetForm();
                }

                await loadInstructors();
                alert(result.message || "Deleted successfully.");
            } catch (error) {
                alert(error.message);
            }
        }

        filterButtons.forEach(button => {
            button.addEventListener("click", () => {
                filterButtons.forEach(btn => btn.classList.remove("active"));
                button.classList.add("active");
                currentFilter = button.dataset.filter;
                renderRows(allInstructors);
            });
        });

        saveBtn.addEventListener("click", saveItem);
        cancelBtn.addEventListener("click", resetForm);

        loadInstructors();
    </script>
@endsection
