@extends('schedules.management.layout')

@php
    $pageTitle = 'Manage Faculty Availability';
    $pageSubtitle = 'Set available day and time of instructors per semester and school year.';
@endphp

@section('form')
    <h2 id="form-title">Add Availability</h2>

    <input type="hidden" id="edit-id">

    <div class="notice">Select an instructor first to see the proper availability rule.</div>

    <div class="form-group">
        <label for="instructor_id">Instructor</label>
        <select id="instructor_id">
            <option value="">Loading instructors...</option>
        </select>
    </div>

    <div class="form-group">
        <label for="school_year_id">School Year</label>
        <select id="school_year_id">
            <option value="">Loading school years...</option>
        </select>
    </div>

    <div class="form-group">
        <label for="semester_id">Semester</label>
        <select id="semester_id">
            <option value="">Loading semesters...</option>
        </select>
    </div>

    <div class="form-group">
        <label for="day">Day</label>
        <select id="day">
            <option value="Monday">Monday</option>
            <option value="Tuesday">Tuesday</option>
            <option value="Wednesday">Wednesday</option>
            <option value="Thursday">Thursday</option>
            <option value="Friday">Friday</option>
            <option value="Saturday">Saturday</option>
            <option value="Sunday">Sunday</option>
        </select>
    </div>

    <div class="form-group">
        <label for="start_time">Start Time</label>
        <input type="time" id="start_time">
    </div>

    <div class="form-group">
        <label for="end_time">End Time</label>
        <input type="time" id="end_time">
    </div>

    <div class="form-group">
        <label for="status">Availability</label>
        <select id="status">
            <option value="Available">Available</option>
            <option value="Unavailable">Unavailable</option>
        </select>
    </div>

    <div class="form-group">
        <label for="notes">Notes</label>
        <textarea id="notes" rows="3"></textarea>
    </div>

    <div class="actions">
        <button type="button" class="save-btn" id="save-btn">Save Availability</button>
        <button type="button" class="btn-edit" id="cancel-btn" style="display:none;">Cancel</button>
    </div>
@endsection

@section('list')
    <h2>Availability List</h2>

    <table class="list-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Instructor</th>
                <th>Type</th>
                <th>School Year</th>
                <th>Semester</th>
                <th>Day</th>
                <th>Start</th>
                <th>End</th>
                <th>Status</th>
                <th>Notes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="faculty-availability-table-body">
            <tr>
                <td colspan="11">Loading...</td>
            </tr>
        </tbody>
    </table>

    <script>
        const facultyAvailabilityApiUrl = "/api/faculty-availabilities";
        const instructorsApiUrl = "/api/instructors";
        const schoolYearsApiUrl = "/api/school-years";
        const semestersApiUrl = "/api/semesters";

        const tableBody = document.getElementById("faculty-availability-table-body");
        const formTitle = document.getElementById("form-title");
        const editIdInput = document.getElementById("edit-id");

        const instructorIdInput = document.getElementById("instructor_id");
        const schoolYearIdInput = document.getElementById("school_year_id");
        const semesterIdInput = document.getElementById("semester_id");
        const dayInput = document.getElementById("day");
        const startTimeInput = document.getElementById("start_time");
        const endTimeInput = document.getElementById("end_time");
        const statusInput = document.getElementById("status");
        const notesInput = document.getElementById("notes");

        const saveBtn = document.getElementById("save-btn");
        const cancelBtn = document.getElementById("cancel-btn");

        let instructorsMap = {};
        let schoolYearsMap = {};
        let semestersMap = {};

        function escapeHtml(value) {
            return String(value ?? "")
                .replaceAll("&", "&amp;")
                .replaceAll("<", "&lt;")
                .replaceAll(">", "&gt;")
                .replaceAll('"', "&quot;")
                .replaceAll("'", "&#039;");
        }

        function normalizeTimeForInput(value) {
            if (!value) return "";
            return String(value).slice(0, 5);
        }

        function resetForm() {
            editIdInput.value = "";
            instructorIdInput.value = "";
            schoolYearIdInput.value = "";
            semesterIdInput.value = "";
            dayInput.value = "Monday";
            startTimeInput.value = "";
            endTimeInput.value = "";
            statusInput.value = "Available";
            notesInput.value = "";

            formTitle.textContent = "Add Availability";
            saveBtn.textContent = "Save Availability";
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

                const items = Array.isArray(result) ? result : (result.data || []);;
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
                tableBody.innerHTML = `<tr><td colspan="11">No availability rows found.</td></tr>`;
                return;
            }

            tableBody.innerHTML = items.map(item => {
                const instructor = item.instructor || instructorsMap[item.instructor_id] || {};
                const schoolYear = item.school_year || item.schoolYear || schoolYearsMap[item.school_year_id] || {};
                const semester = item.semester || semestersMap[item.semester_id] || {};

                return `
                    <tr>
                        <td>${escapeHtml(item.id)}</td>
                        <td>${escapeHtml(instructor.instructor_name || "")}</td>
                        <td>
                            <span class="tag ${instructor.employment_type === 'part_time' ? 'tag-yellow' : 'tag-green'}">
                                ${escapeHtml(instructor.employment_type || "")}
                            </span>
                        </td>
                        <td>${escapeHtml(schoolYear.school_year || "")}</td>
                        <td>${escapeHtml(semester.semester_name || "")}</td>
                        <td>${escapeHtml(item.day)}</td>
                        <td>${escapeHtml(normalizeTimeForInput(item.start_time))}</td>
                        <td>${escapeHtml(normalizeTimeForInput(item.end_time))}</td>
                        <td>
                            <span class="tag ${item.status === 'Available' ? 'tag-green' : 'tag-gray'}">
                                ${escapeHtml(item.status)}
                            </span>
                        </td>
                        <td>${escapeHtml(item.notes || "")}</td>
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

        async function loadFacultyAvailabilities() {
            tableBody.innerHTML = `<tr><td colspan="11">Loading...</td></tr>`;

            try {
                const response = await fetch(facultyAvailabilityApiUrl, {
                    headers: { "Accept": "application/json" }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || "Failed to load faculty availabilities.");
                }

                renderRows(result.data || []);
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="11">${escapeHtml(error.message)}</td></tr>`;
            }
        }

        function editItem(item) {
            editIdInput.value = item.id ?? "";
            instructorIdInput.value = item.instructor_id ?? "";
            schoolYearIdInput.value = item.school_year_id ?? "";
            semesterIdInput.value = item.semester_id ?? "";
            dayInput.value = item.day ?? "Monday";
            startTimeInput.value = normalizeTimeForInput(item.start_time);
            endTimeInput.value = normalizeTimeForInput(item.end_time);
            statusInput.value = item.status ?? "Available";
            notesInput.value = item.notes ?? "";

            formTitle.textContent = "Edit Availability";
            saveBtn.textContent = "Update Availability";
            cancelBtn.style.display = "inline-block";

            window.scrollTo({ top: 0, behavior: "smooth" });
        }

        async function saveItem() {
            const id = editIdInput.value.trim();

            const payload = {
                instructor_id: Number(instructorIdInput.value),
                school_year_id: Number(schoolYearIdInput.value),
                semester_id: Number(semesterIdInput.value),
                day: dayInput.value,
                start_time: startTimeInput.value,
                end_time: endTimeInput.value,
                status: statusInput.value,
                notes: notesInput.value.trim() || null
            };

            if (!payload.instructor_id || !payload.school_year_id || !payload.semester_id || !payload.day || !payload.start_time || !payload.end_time || !payload.status) {
                alert("Instructor, School Year, Semester, Day, Start Time, End Time, and Availability are required.");
                return;
            }

            const isEdit = id !== "";
            const url = isEdit ? `${facultyAvailabilityApiUrl}/${id}` : facultyAvailabilityApiUrl;
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
                await loadFacultyAvailabilities();
                alert(result.message || "Saved successfully.");
            } catch (error) {
                alert(error.message);
            }
        }

        async function deleteItem(id) {
            if (!confirm("Delete this availability row?")) return;

            try {
                const response = await fetch(`${facultyAvailabilityApiUrl}/${id}`, {
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

                await loadFacultyAvailabilities();
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
                schoolYearsApiUrl,
                schoolYearIdInput,
                schoolYearsMap,
                item => `${escapeHtml(item.school_year)}`
            );

            await loadDropdown(
                semestersApiUrl,
                semesterIdInput,
                semestersMap,
                item => `${escapeHtml(item.semester_name)}`
            );

            await loadFacultyAvailabilities();
        }

        initPage();
    </script>
@endsection