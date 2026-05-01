@extends('schedules.management.layout')

@php
    $pageTitle = 'Manage Faculty Availability';
    $pageSubtitle = 'Set available day and time of instructors per semester and school year.';
@endphp

@section('form')
    <h2 id="form-title">Add Availability</h2>

    <input type="hidden" id="edit-id">

    <div class="notice">Select an instructor first to set or edit their availability.</div>

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
    <h2>Faculty Availability List</h2>

    <table class="list-table">
        <thead>
            <tr>
                <th>Instructor</th>
                <th>Type</th>
                <th>Total Slots</th>
                <th>Available</th>
                <th>Unavailable</th>
                <th>School Year / Semester</th>
                <th>Quick View</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="faculty-availability-table-body">
            <tr>
                <td colspan="8">Loading...</td>
            </tr>
        </tbody>
    </table>

    <div id="availability-modal" class="modal-backdrop" style="display:none;">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h3 id="modal-title">Faculty Availability</h3>
                    <p id="modal-subtitle"></p>
                </div>
                <button type="button" class="btn-edit" id="close-modal-btn">Close</button>
            </div>

            <div class="modal-table-wrap">
                <table class="list-table">
                    <thead>
                        <tr>
                            <th>ID</th>
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
                    <tbody id="modal-availability-body">
                        <tr>
                            <td colspan="9">No rows found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(15, 23, 42, 0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .modal-card {
            width: min(1100px, 96vw);
            max-height: 88vh;
            overflow: auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
            padding: 22px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .modal-header h3 {
            margin: 0 0 4px;
        }

        .modal-header p {
            margin: 0;
            color: #64748b;
        }

        .modal-table-wrap {
            overflow-x: auto;
        }

        .quick-days {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .quick-day-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 9px;
            background: #f1f5f9;
            font-size: 12px;
            color: #334155;
            white-space: nowrap;
        }

        .row-muted {
            color: #64748b;
            font-size: 13px;
        }
    </style>

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
        const modal = document.getElementById("availability-modal");
        const modalTitle = document.getElementById("modal-title");
        const modalSubtitle = document.getElementById("modal-subtitle");
        const modalBody = document.getElementById("modal-availability-body");
        const closeModalBtn = document.getElementById("close-modal-btn");

        let instructorsMap = {};
        let schoolYearsMap = {};
        let semestersMap = {};
        let allAvailabilityRows = [];

        const dayOrder = {
            Monday: 1,
            Tuesday: 2,
            Wednesday: 3,
            Thursday: 4,
            Friday: 5,
            Saturday: 6,
            Sunday: 7
        };

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

        function instructorLabel(instructor) {
            return instructor.instructor_name || instructor.name || `Instructor #${instructor.id}`;
        }

        function getSchoolYear(item) {
            return item.school_year || item.schoolYear || schoolYearsMap[item.school_year_id] || {};
        }

        function getSemester(item) {
            return item.semester || semestersMap[item.semester_id] || {};
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

        async function fetchApi(url, fallbackMessage) {
            const response = await fetch(url, {
                headers: { "Accept": "application/json" }
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || fallbackMessage);
            }

            return Array.isArray(result) ? result : (result.data || []);
        }

        async function loadDropdown(url, selectEl, mapStore, labelBuilder, fallbackMessage) {
            const items = await fetchApi(url, fallbackMessage);

            Object.keys(mapStore).forEach(key => delete mapStore[key]);
            items.forEach(item => {
                mapStore[item.id] = item;
            });

            selectEl.innerHTML = `
                <option value="">Select option</option>
                ${items.map(item => `
                    <option value="${escapeHtml(item.id)}">${escapeHtml(labelBuilder(item))}</option>
                `).join("")}
            `;
        }

        function groupByInstructor(items) {
            const grouped = {};

            items.forEach(item => {
                const instructorId = String(item.instructor_id || "");
                if (!instructorId) return;

                if (!grouped[instructorId]) {
                    grouped[instructorId] = [];
                }

                grouped[instructorId].push(item);
            });

            return Object.entries(grouped).map(([instructorId, rows]) => {
                const first = rows[0] || {};
                const instructor = first.instructor || instructorsMap[instructorId] || { id: instructorId };

                rows.sort((a, b) => {
                    const dayDiff = (dayOrder[a.day] || 99) - (dayOrder[b.day] || 99);
                    if (dayDiff !== 0) return dayDiff;
                    return String(a.start_time || "").localeCompare(String(b.start_time || ""));
                });

                return {
                    instructorId,
                    instructor,
                    rows,
                    total: rows.length,
                    available: rows.filter(row => row.status === "Available").length,
                    unavailable: rows.filter(row => row.status === "Unavailable").length
                };
            }).sort((a, b) => instructorLabel(a.instructor).localeCompare(instructorLabel(b.instructor)));
        }

        function buildSchoolYearSemesterSummary(rows) {
            const labels = new Set();

            rows.forEach(row => {
                const schoolYear = getSchoolYear(row);
                const semester = getSemester(row);
                const syLabel = schoolYear.school_year || "No school year";
                const semLabel = semester.semester_name || "No semester";
                labels.add(`${syLabel} / ${semLabel}`);
            });

            return Array.from(labels).join("<br>");
        }

        function buildQuickView(rows) {
            const availableRows = rows.filter(row => row.status === "Available");
            const displayRows = availableRows.length ? availableRows : rows;

            const quickRows = displayRows.slice(0, 5).map(row => `
                <span class="quick-day-pill">
                    ${escapeHtml(row.day)} ${escapeHtml(normalizeTimeForInput(row.start_time))}-${escapeHtml(normalizeTimeForInput(row.end_time))}
                </span>
            `).join("");

            const moreCount = Math.max(displayRows.length - 5, 0);

            return `
                <div class="quick-days">
                    ${quickRows || '<span class="row-muted">No slots</span>'}
                    ${moreCount > 0 ? `<span class="quick-day-pill">+${moreCount} more</span>` : ""}
                </div>
            `;
        }

        function renderGroupedRows(items) {
            const groups = groupByInstructor(items);

            if (groups.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="8">No availability rows found.</td></tr>`;
                return;
            }

            tableBody.innerHTML = groups.map(group => {
                const employmentType = group.instructor.employment_type || "";

                return `
                    <tr>
                        <td>${escapeHtml(instructorLabel({ id: group.instructorId, ...group.instructor }))}</td>
                        <td>
                            <span class="tag ${employmentType === 'part_time' ? 'tag-yellow' : 'tag-green'}">
                                ${escapeHtml(employmentType || "N/A")}
                            </span>
                        </td>
                        <td>${escapeHtml(group.total)}</td>
                        <td><span class="tag tag-green">${escapeHtml(group.available)}</span></td>
                        <td><span class="tag tag-gray">${escapeHtml(group.unavailable)}</span></td>
                        <td>${buildSchoolYearSemesterSummary(group.rows)}</td>
                        <td>${buildQuickView(group.rows)}</td>
                        <td>
                            <button type="button" class="btn-edit" data-action="view" data-instructor-id="${escapeHtml(group.instructorId)}">
                                View Availability
                            </button>
                        </td>
                    </tr>
                `;
            }).join("");
        }

        function renderModalRows(rows) {
            if (!Array.isArray(rows) || rows.length === 0) {
                modalBody.innerHTML = `<tr><td colspan="9">No availability rows found.</td></tr>`;
                return;
            }

            modalBody.innerHTML = rows.map(item => {
                const schoolYear = getSchoolYear(item);
                const semester = getSemester(item);

                return `
                    <tr>
                        <td>${escapeHtml(item.id)}</td>
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
                                <button type="button" class="btn-edit" data-action="edit" data-id="${escapeHtml(item.id)}">Edit</button>
                                <button type="button" class="btn-delete" data-action="delete" data-id="${escapeHtml(item.id)}">Delete</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join("");
        }

        function openAvailabilityModal(instructorId) {
            const rows = allAvailabilityRows
                .filter(row => String(row.instructor_id) === String(instructorId))
                .sort((a, b) => {
                    const dayDiff = (dayOrder[a.day] || 99) - (dayOrder[b.day] || 99);
                    if (dayDiff !== 0) return dayDiff;
                    return String(a.start_time || "").localeCompare(String(b.start_time || ""));
                });

            const instructor = rows[0]?.instructor || instructorsMap[instructorId] || { id: instructorId };

            modalTitle.textContent = instructorLabel({ id: instructorId, ...instructor });
            modalSubtitle.textContent = `${rows.length} availability slot${rows.length === 1 ? "" : "s"}`;
            renderModalRows(rows);
            modal.style.display = "flex";
        }

        function closeAvailabilityModal() {
            modal.style.display = "none";
        }

        async function loadFacultyAvailabilities() {
            tableBody.innerHTML = `<tr><td colspan="8">Loading...</td></tr>`;

            try {
                allAvailabilityRows = await fetchApi(facultyAvailabilityApiUrl, "Failed to load faculty availabilities.");
                renderGroupedRows(allAvailabilityRows);
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="8">${escapeHtml(error.message)}</td></tr>`;
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
            closeAvailabilityModal();
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
                notes: notesInput.value
            };

            if (!payload.instructor_id || !payload.school_year_id || !payload.semester_id || !payload.day || !payload.start_time || !payload.end_time) {
                alert("Instructor, school year, semester, day, start time, and end time are required.");
                return;
            }

            if (payload.start_time >= payload.end_time) {
                alert("End time must be later than start time.");
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
                closeAvailabilityModal();
                alert(result.message || "Deleted successfully.");
            } catch (error) {
                alert(error.message);
            }
        }

        saveBtn.addEventListener("click", saveItem);
        cancelBtn.addEventListener("click", resetForm);
        closeModalBtn.addEventListener("click", closeAvailabilityModal);

        modal.addEventListener("click", event => {
            if (event.target === modal) {
                closeAvailabilityModal();
            }
        });

        tableBody.addEventListener("click", event => {
            const button = event.target.closest("button[data-action]");
            if (!button) return;

            if (button.dataset.action === "view") {
                openAvailabilityModal(button.dataset.instructorId);
            }
        });

        modalBody.addEventListener("click", event => {
            const button = event.target.closest("button[data-action]");
            if (!button) return;

            const id = button.dataset.id;
            const item = allAvailabilityRows.find(row => String(row.id) === String(id));

            if (button.dataset.action === "edit") {
                if (!item) {
                    alert("Availability row not found.");
                    return;
                }

                editItem(item);
            }

            if (button.dataset.action === "delete") {
                deleteItem(id);
            }
        });

        async function initPage() {
            try {
                await Promise.all([
                    loadDropdown(
                        instructorsApiUrl,
                        instructorIdInput,
                        instructorsMap,
                        item => instructorLabel(item),
                        "Failed to load instructors."
                    ),
                    loadDropdown(
                        schoolYearsApiUrl,
                        schoolYearIdInput,
                        schoolYearsMap,
                        item => item.school_year || `School Year #${item.id}`,
                        "Failed to load school years."
                    ),
                    loadDropdown(
                        semestersApiUrl,
                        semesterIdInput,
                        semestersMap,
                        item => item.semester_name || `Semester #${item.id}`,
                        "Failed to load semesters."
                    )
                ]);

                await loadFacultyAvailabilities();
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="8">${escapeHtml(error.message)}</td></tr>`;
            }
        }

        initPage();
    </script>
@endsection
