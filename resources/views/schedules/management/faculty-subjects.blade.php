@extends('schedules.management.layout')

@php
    $pageTitle = 'Manage Faculty Subjects';
    $pageSubtitle = 'Assign subjects to instructors with priority and primary flag.';
@endphp

@section('form')
    <h2 id="form-title">Add Faculty Subject</h2>

    <input type="hidden" id="edit-id">

    <div class="form-group">
        <label for="instructor_id">Instructor</label>
        <select id="instructor_id">
            <option value="">Loading instructors...</option>
        </select>
    </div>

    <div class="form-group">
        <label for="subject_id">Subject</label>
        <select id="subject_id">
            <option value="">Loading subjects...</option>
        </select>
    </div>

    <div class="form-group">
        <label for="priority_score">Priority Score</label>
        <input type="number" id="priority_score" value="10" min="0" max="100">
    </div>

    <div class="form-group">
        <label for="is_primary">Primary Assignment</label>
        <select id="is_primary">
            <option value="1">Yes</option>
            <option value="0">No</option>
        </select>
    </div>

    <div class="actions">
        <button type="button" class="save-btn" id="save-btn">Save Faculty Subject</button>
        <button type="button" class="btn-edit" id="cancel-btn" style="display:none;">Cancel</button>
    </div>
@endsection

@section('list')
    <h2>Faculty Subject List</h2>

    <table class="list-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Instructor</th>
                <th>Subject Code</th>
                <th>Subject Name</th>
                <th>Priority</th>
                <th>Primary</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="faculty-subject-table-body">
            <tr>
                <td colspan="7">Loading...</td>
            </tr>
        </tbody>
    </table>

    <script>
        const facultySubjectsApiUrl = "/api/faculty-subjects";
        const instructorsApiUrl = "/api/instructors";
        const subjectsApiUrl = "/api/subjects";

        const tableBody = document.getElementById("faculty-subject-table-body");
        const formTitle = document.getElementById("form-title");
        const editIdInput = document.getElementById("edit-id");
        const instructorIdInput = document.getElementById("instructor_id");
        const subjectIdInput = document.getElementById("subject_id");
        const priorityScoreInput = document.getElementById("priority_score");
        const isPrimaryInput = document.getElementById("is_primary");
        const saveBtn = document.getElementById("save-btn");
        const cancelBtn = document.getElementById("cancel-btn");

        let instructorsMap = {};
        let subjectsMap = {};

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
            instructorIdInput.value = "";
            subjectIdInput.value = "";
            priorityScoreInput.value = "10";
            isPrimaryInput.value = "1";

            formTitle.textContent = "Add Faculty Subject";
            saveBtn.textContent = "Save Faculty Subject";
            cancelBtn.style.display = "none";
        }

        async function loadDropdown(url, selectEl, mapStore, labelBuilder) {
            try {
                const response = await fetch(url, {
                    headers: { "Accept": "application/json" }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || "Failed to load dropdown data.");
                }

                const items = result.data || [];
                Object.keys(mapStore).forEach(key => delete mapStore[key]);

                items.forEach(item => {
                    mapStore[item.id] = item;
                });

                selectEl.innerHTML = `
                    <option value="">Select option</option>
                    ${items.map(item => `
                        <option value="${item.id}">${labelBuilder(item)}</option>
                    `).join("")}
                `;
            } catch (error) {
                selectEl.innerHTML = `<option value="">${escapeHtml(error.message)}</option>`;
            }
        }

        function renderRows(items) {
            if (!Array.isArray(items) || items.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="7">No faculty subject rows found.</td></tr>`;
                return;
            }

            tableBody.innerHTML = items.map(item => {
                const instructor = item.instructor || instructorsMap[item.instructor_id] || {};
                const subject = item.subject || subjectsMap[item.subject_id] || {};

                return `
                    <tr>
                        <td>${escapeHtml(item.id)}</td>
                        <td>${escapeHtml(instructor.instructor_name || "")}</td>
                        <td>${escapeHtml(subject.subject_code || "")}</td>
                        <td>${escapeHtml(subject.subject_name || "")}</td>
                        <td>${escapeHtml(item.priority_score)}</td>
                        <td>
                            <span class="tag ${Number(item.is_primary) === 1 ? 'tag-green' : 'tag-gray'}">
                                ${Number(item.is_primary) === 1 ? 'Yes' : 'No'}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <button type="button" class="btn-edit" onclick='editItem(${JSON.stringify(item)})'>Edit</button>
                                <button type="button" class="btn-delete" onclick="deleteItem(${item.id})">Delete</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join("");
        }

        async function loadFacultySubjects() {
            tableBody.innerHTML = `<tr><td colspan="7">Loading...</td></tr>`;

            try {
                const response = await fetch(facultySubjectsApiUrl, {
                    headers: { "Accept": "application/json" }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || "Failed to load faculty subjects.");
                }

                renderRows(result.data || []);
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="7">${escapeHtml(error.message)}</td></tr>`;
            }
        }

        function editItem(item) {
            editIdInput.value = item.id ?? "";
            instructorIdInput.value = item.instructor_id ?? "";
            subjectIdInput.value = item.subject_id ?? "";
            priorityScoreInput.value = item.priority_score ?? "10";
            isPrimaryInput.value = Number(item.is_primary) === 1 ? "1" : "0";

            formTitle.textContent = "Edit Faculty Subject";
            saveBtn.textContent = "Update Faculty Subject";
            cancelBtn.style.display = "inline-block";

            window.scrollTo({ top: 0, behavior: "smooth" });
        }

        async function saveItem() {
            const id = editIdInput.value.trim();

            const payload = {
                instructor_id: Number(instructorIdInput.value),
                subject_id: Number(subjectIdInput.value),
                priority_score: Number(priorityScoreInput.value),
                is_primary: isPrimaryInput.value === "1"
            };

            if (!payload.instructor_id || !payload.subject_id) {
                alert("Instructor and Subject are required.");
                return;
            }

            const isEdit = id !== "";
            const url = isEdit ? `${facultySubjectsApiUrl}/${id}` : facultySubjectsApiUrl;
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
                await loadFacultySubjects();
                alert(result.message || "Saved successfully.");
            } catch (error) {
                alert(error.message);
            }
        }

        async function deleteItem(id) {
            if (!confirm("Delete this faculty subject row?")) return;

            try {
                const response = await fetch(`${facultySubjectsApiUrl}/${id}`, {
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

                await loadFacultySubjects();
                alert(result.message || "Deleted successfully.");
            } catch (error) {
                alert(error.message);
            }
        }

        saveBtn.addEventListener("click", saveItem);
        cancelBtn.addEventListener("click", resetForm);

        async function initPage() {
            await loadDropdown(
                instructorsApiUrl,
                instructorIdInput,
                instructorsMap,
                item => `${escapeHtml(item.instructor_name)}`
            );

            await loadDropdown(
                subjectsApiUrl,
                subjectIdInput,
                subjectsMap,
                item => `${escapeHtml(item.subject_code)} - ${escapeHtml(item.subject_name)}`
            );

            await loadFacultySubjects();
        }

        initPage();
    </script>
@endsection