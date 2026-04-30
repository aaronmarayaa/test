@extends('schedules.management.layout')

@php
    $pageTitle = 'Manage Sections';
    $pageSubtitle = 'Add, edit, archive, and manage sections.';
@endphp

@section('form')
    <h2 id="form-title">Add Section</h2>

    <input type="hidden" id="edit-id">

    <div class="form-group">
        <label for="course_id">Course</label>
        <select id="course_id">
            <option value="">Loading courses...</option>
        </select>
    </div>

    <div class="form-group">
        <label for="year_level">Year Level</label>
        <select id="year_level">
            <option value="1">1st Year</option>
            <option value="2">2nd Year</option>
            <option value="3">3rd Year</option>
            <option value="4">4th Year</option>
        </select>
    </div>

    <div class="form-group">
        <label for="section_name">Section Name</label>
        <input type="text" id="section_name" placeholder="Example: A">
    </div>

    <div class="form-group">
        <label for="section_code">Section Code</label>
        <input type="text" id="section_code" placeholder="Example: BSCRIM-1A">
    </div>

    <div class="form-group">
        <label for="capacity">Capacity</label>
        <input type="number" id="capacity" min="1">
    </div>

    <div class="form-group">
        <label for="archived">Archived</label>
        <select id="archived">
            <option value="0">No</option>
            <option value="1">Yes</option>
        </select>
    </div>

    <div class="actions">
        <button type="button" class="save-btn" id="save-btn">Save Section</button>
        <button type="button" class="btn-edit" id="cancel-btn" style="display:none;">Cancel</button>
    </div>
@endsection

@section('list')
    <h2>Section List</h2>

    <table class="list-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Course</th>
                <th>Year</th>
                <th>Section</th>
                <th>Code</th>
                <th>Capacity</th>
                <th>Archived</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="section-table-body">
            <tr>
                <td colspan="8">Loading...</td>
            </tr>
        </tbody>
    </table>

    <script>
        const sectionsApiUrl = "/api/sections";
        const coursesApiUrl = "/api/courses";

        const tableBody = document.getElementById("section-table-body");
        const formTitle = document.getElementById("form-title");
        const editIdInput = document.getElementById("edit-id");
        const courseIdInput = document.getElementById("course_id");
        const yearLevelInput = document.getElementById("year_level");
        const sectionNameInput = document.getElementById("section_name");
        const sectionCodeInput = document.getElementById("section_code");
        const capacityInput = document.getElementById("capacity");
        const archivedInput = document.getElementById("archived");
        const saveBtn = document.getElementById("save-btn");
        const cancelBtn = document.getElementById("cancel-btn");

        let coursesMap = {};

        function escapeHtml(value) {
            return String(value ?? "")
                .replaceAll("&", "&amp;")
                .replaceAll("<", "&lt;")
                .replaceAll(">", "&gt;")
                .replaceAll('"', "&quot;")
                .replaceAll("'", "&#039;");
        }

        function yearLabel(value) {
            const map = {
                1: "1st Year",
                2: "2nd Year",
                3: "3rd Year",
                4: "4th Year"
            };
            return map[value] || value;
        }

        function resetForm() {
            editIdInput.value = "";
            courseIdInput.value = "";
            yearLevelInput.value = "1";
            sectionNameInput.value = "";
            sectionCodeInput.value = "";
            capacityInput.value = "";
            archivedInput.value = "0";
            formTitle.textContent = "Add Section";
            saveBtn.textContent = "Save Section";
            cancelBtn.style.display = "none";
        }

        async function loadCoursesDropdown() {
            try {
                const response = await fetch(coursesApiUrl, {
                    headers: {
                        "Accept": "application/json"
                    }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || "Failed to load courses.");
                }

                const items = result.data || [];
                coursesMap = {};

                items.forEach(item => {
                    coursesMap[item.id] = item;
                });

                courseIdInput.innerHTML = `
                    <option value="">Select course</option>
                    ${items.map(item => `
                        <option value="${item.id}">
                            ${escapeHtml(item.course_code)} - ${escapeHtml(item.course_name)}
                        </option>
                    `).join("")}
                `;
            } catch (error) {
                courseIdInput.innerHTML = `<option value="">${escapeHtml(error.message)}</option>`;
            }
        }

        function renderRows(items) {
            if (!Array.isArray(items) || items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8">No sections found.</td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = items.map(item => {
                const course = item.course || coursesMap[item.course_id] || {};
                return `
                    <tr>
                        <td>${escapeHtml(item.id)}</td>
                        <td>${escapeHtml(course.course_code || item.course_code || "")}</td>
                        <td>${escapeHtml(item.year_level)}</td>
                        <td>${escapeHtml(item.section_name)}</td>
                        <td>${escapeHtml(item.section_code)}</td>
                        <td>${escapeHtml(item.capacity)}</td>
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
                `;
            }).join("");
        }

        async function loadSections() {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8">Loading...</td>
                </tr>
            `;

            try {
                const response = await fetch(sectionsApiUrl, {
                    headers: {
                        "Accept": "application/json"
                    }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || "Failed to load sections.");
                }

                renderRows(result.data || []);
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
            courseIdInput.value = item.course_id ?? "";
            yearLevelInput.value = String(item.year_level ?? "1");
            sectionNameInput.value = item.section_name ?? "";
            sectionCodeInput.value = item.section_code ?? "";
            capacityInput.value = item.capacity ?? "";
            archivedInput.value = Number(item.archived) === 1 ? "1" : "0";

            formTitle.textContent = "Edit Section";
            saveBtn.textContent = "Update Section";
            cancelBtn.style.display = "inline-block";

            window.scrollTo({ top: 0, behavior: "smooth" });
        }

        async function saveItem() {
            const id = editIdInput.value.trim();

            const payload = {
                course_id: Number(courseIdInput.value),
                year_level: Number(yearLevelInput.value),
                section_name: sectionNameInput.value.trim(),
                section_code: sectionCodeInput.value.trim(),
                capacity: Number(capacityInput.value),
                archived: archivedInput.value === "1"
            };

            if (!payload.course_id || !payload.year_level || !payload.section_name || !payload.section_code || !payload.capacity) {
                alert("Course, Year Level, Section Name, Section Code, and Capacity are required.");
                return;
            }

            const isEdit = id !== "";
            const url = isEdit ? `${sectionsApiUrl}/${id}` : sectionsApiUrl;
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
                await loadSections();
                alert(result.message || "Saved successfully.");
            } catch (error) {
                alert(error.message);
            }
        }

        async function deleteItem(id) {
            if (!confirm("Delete this section?")) {
                return;
            }

            try {
                const response = await fetch(`${sectionsApiUrl}/${id}`, {
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

                await loadSections();
                alert(result.message || "Deleted successfully.");
            } catch (error) {
                alert(error.message);
            }
        }

        saveBtn.addEventListener("click", saveItem);
        cancelBtn.addEventListener("click", resetForm);

        async function initPage() {
            await loadCoursesDropdown();
            await loadSections();
        }

        initPage();
    </script>
@endsection