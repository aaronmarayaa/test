@extends('schedules.management.layout')

@php
    $pageTitle = 'Manage Faculty Subjects';
    $pageSubtitle = 'Assign subjects to instructors by course/department with priority and primary flag.';
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
        <label for="course_id">Course / Department</label>
        <select id="course_id">
            <option value="">Loading courses...</option>
        </select>
    </div>

    <div class="form-group">
        <label for="session_type">Session Type</label>
        <select id="session_type">
            <option value="both">Both Lecture and Laboratory</option>
            <option value="lecture">Lecture Only</option>
            <option value="laboratory">Laboratory Only</option>
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

    <style>
        .fs-summary-cell {
            line-height: 1.55;
        }

        .fs-muted {
            color: #64748b;
            font-size: 13px;
        }

        .fs-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 24px;
        }

        .fs-modal-backdrop.active {
            display: flex;
        }

        .fs-modal {
            width: min(1100px, 100%);
            max-height: 88vh;
            overflow: auto;
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 24px 80px rgba(15, 23, 42, 0.28);
            padding: 22px;
        }

        .fs-modal-header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 18px;
        }

        .fs-modal-title {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
        }

        .fs-modal-subtitle {
            margin-top: 4px;
            color: #64748b;
            font-size: 14px;
        }

        .fs-close-btn {
            border: 0;
            background: #e5e7eb;
            color: #111827;
            padding: 10px 14px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
        }

        .fs-close-btn:hover {
            background: #d1d5db;
        }

        .fs-pill-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .fs-pill {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            background: #eef2ff;
            color: #1e3a8a;
            font-size: 12px;
            font-weight: 700;
            margin: 2px 4px 2px 0;
        }

        .fs-view-switch {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 8px 0 10px;
        }

        .fs-switch-btn {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #334155;
            border-radius: 12px;
            padding: 10px 14px;
            cursor: pointer;
            font-weight: 800;
        }

        .fs-switch-btn.active {
            border-color: #2563eb;
            background: #dbeafe;
            color: #1e3a8a;
        }
    </style>

    <div class="fs-view-switch">
        <button type="button" class="fs-switch-btn active" id="instructor-view-btn" onclick="setViewMode('instructor')">Instructor View</button>
        <button type="button" class="fs-switch-btn" id="subject-view-btn" onclick="setViewMode('subject')">Subject View</button>
    </div>

    <div class="fs-muted" id="view-helper" style="margin-bottom: 12px;">
        Showing one row per instructor. Click View Subjects to see all assigned subjects.
    </div>

    <table class="list-table">
        <thead id="faculty-subject-table-head"></thead>
        <tbody id="faculty-subject-table-body">
            <tr>
                <td colspan="6">Loading...</td>
            </tr>
        </tbody>
    </table>

    <div class="fs-modal-backdrop" id="faculty-subject-modal">
        <div class="fs-modal">
            <div class="fs-modal-header">
                <div>
                    <h3 class="fs-modal-title" id="modal-title">Faculty Subjects</h3>
                    <div class="fs-modal-subtitle" id="modal-subtitle"></div>
                </div>
                <button type="button" class="fs-close-btn" onclick="closeSubjectModal()">Close</button>
            </div>

            <table class="list-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Course</th>
                        <th>Department</th>
                        <th>Session</th>
                        <th>Priority</th>
                        <th>Primary</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="modal-table-body"></tbody>
            </table>
        </div>
    </div>

    <script>
        const facultySubjectsApiUrl = "/api/faculty-subjects";
        const instructorsApiUrl = "/api/instructors";
        const subjectsApiUrl = "/api/subjects";
        const coursesApiUrl = "/api/courses";

        const tableHead = document.getElementById("faculty-subject-table-head");
        const tableBody = document.getElementById("faculty-subject-table-body");
        const viewHelper = document.getElementById("view-helper");
        const instructorViewBtn = document.getElementById("instructor-view-btn");
        const subjectViewBtn = document.getElementById("subject-view-btn");
        const modal = document.getElementById("faculty-subject-modal");
        const modalTitle = document.getElementById("modal-title");
        const modalSubtitle = document.getElementById("modal-subtitle");
        const modalTableBody = document.getElementById("modal-table-body");

        const formTitle = document.getElementById("form-title");
        const editIdInput = document.getElementById("edit-id");
        const instructorIdInput = document.getElementById("instructor_id");
        const subjectIdInput = document.getElementById("subject_id");
        const courseIdInput = document.getElementById("course_id");
        const sessionTypeInput = document.getElementById("session_type");
        const priorityScoreInput = document.getElementById("priority_score");
        const isPrimaryInput = document.getElementById("is_primary");
        const saveBtn = document.getElementById("save-btn");
        const cancelBtn = document.getElementById("cancel-btn");

        let instructorsMap = {};
        let subjectsMap = {};
        let coursesMap = {};
        let facultySubjectRows = [];
        let groupedFacultySubjects = [];
        let groupedSubjectRows = [];
        let currentViewMode = "instructor";

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
            courseIdInput.value = "";
            sessionTypeInput.value = "both";
            priorityScoreInput.value = "10";
            isPrimaryInput.value = "1";

            formTitle.textContent = "Add Faculty Subject";
            saveBtn.textContent = "Save Faculty Subject";
            cancelBtn.style.display = "none";
        }

        function getCourseDisplay(course) {
            if (!course) {
                return { code: "No course assigned", department: "" };
            }

            return {
                code: course.course_code || `Course #${course.id ?? ""}`,
                department: course.department_name || course.course_name || ""
            };
        }

        function uniqueValues(values) {
            return [...new Set(values.filter(value => value !== null && value !== undefined && String(value).trim() !== ""))];
        }

        function formatSessionType(value) {
            if (value === "lecture") return "Lecture";
            if (value === "laboratory") return "Laboratory";
            return "Both";
        }

        function makePills(values, emptyText = "None") {
            const list = uniqueValues(values);

            if (list.length === 0) {
                return `<span class="fs-muted">${escapeHtml(emptyText)}</span>`;
            }

            return list.slice(0, 6).map(value => `<span class="fs-pill">${escapeHtml(value)}</span>`).join("")
                + (list.length > 6 ? `<span class="fs-muted">+${list.length - 6} more</span>` : "");
        }

        function getInstructor(item) {
            return item.instructor || instructorsMap[item.instructor_id] || {};
        }

        function getSubject(item) {
            return item.subject || subjectsMap[item.subject_id] || {};
        }

        function getCourse(item) {
            return item.course || coursesMap[item.course_id] || null;
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

        function groupRowsByInstructor(rows) {
            const groups = new Map();

            rows.forEach(item => {
                const key = String(item.instructor_id ?? "missing");
                const instructor = getInstructor(item);

                if (!groups.has(key)) {
                    groups.set(key, {
                        instructor_id: item.instructor_id,
                        instructor,
                        rows: []
                    });
                }

                groups.get(key).rows.push(item);
            });

            return Array.from(groups.values()).sort((a, b) => {
                const nameA = a.instructor?.instructor_name || "";
                const nameB = b.instructor?.instructor_name || "";
                return nameA.localeCompare(nameB);
            });
        }

        function groupRowsBySubjectCourse(rows) {
            const groups = new Map();

            rows.forEach(item => {
                const subject = getSubject(item);
                const course = getCourse(item);
                const key = `${item.subject_id ?? "missing"}-${item.course_id ?? "missing"}`;

                if (!groups.has(key)) {
                    groups.set(key, {
                        subject_id: item.subject_id,
                        course_id: item.course_id,
                        subject,
                        course,
                        rows: []
                    });
                }

                groups.get(key).rows.push(item);
            });

            return Array.from(groups.values()).sort((a, b) => {
                const codeA = a.subject?.subject_code || "";
                const codeB = b.subject?.subject_code || "";
                const courseA = getCourseDisplay(a.course).code || "";
                const courseB = getCourseDisplay(b.course).code || "";
                return `${codeA} ${courseA}`.localeCompare(`${codeB} ${courseB}`);
            });
        }

        function setViewMode(mode) {
            currentViewMode = mode === "subject" ? "subject" : "instructor";
            instructorViewBtn.classList.toggle("active", currentViewMode === "instructor");
            subjectViewBtn.classList.toggle("active", currentViewMode === "subject");

            viewHelper.textContent = currentViewMode === "instructor"
                ? "Showing one row per instructor. Click View Subjects to see all assigned subjects."
                : "Showing one row per subject and course. Click View Instructors to see who can teach that subject.";

            renderCurrentView();
        }

        function renderRows(items) {
            facultySubjectRows = Array.isArray(items) ? items : [];
            groupedFacultySubjects = groupRowsByInstructor(facultySubjectRows);
            groupedSubjectRows = groupRowsBySubjectCourse(facultySubjectRows);
            renderCurrentView();
        }

        function renderCurrentView() {
            if (currentViewMode === "subject") {
                renderSubjectView();
                return;
            }

            renderInstructorView();
        }

        function renderInstructorView() {
            tableHead.innerHTML = `
                <tr>
                    <th>Instructor</th>
                    <th>Total Assignments</th>
                    <th>Courses / Departments</th>
                    <th>Primary</th>
                    <th>Actions</th>
                </tr>
            `;

            if (groupedFacultySubjects.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="6">No faculty subject rows found.</td></tr>`;
                return;
            }

            tableBody.innerHTML = groupedFacultySubjects.map((group, index) => {
                const instructorName = group.instructor?.instructor_name || "No instructor";
                const rows = group.rows;
                const total = rows.length;
                const primaryCount = rows.filter(item => Number(item.is_primary) === 1).length;

                const courseLabels = rows.map(item => {
                    const display = getCourseDisplay(getCourse(item));
                    return display.department ? `${display.code} - ${display.department}` : display.code;
                });

                const subjectLabels = rows.map(item => {
                    const subject = getSubject(item);
                    const code = subject.subject_code || "";
                    const name = subject.subject_name || "";
                    return `${code}${code && name ? " - " : ""}${name}`;
                });

                return `
                    <tr>
                        <td class="fs-summary-cell">
                            <strong>${escapeHtml(instructorName)}</strong>
                            <div class="fs-muted">Instructor ID: ${escapeHtml(group.instructor_id || "N/A")}</div>
                        </td>
                        <td>${escapeHtml(total)}</td>
                        <td>${makePills(courseLabels, "No course assigned")}</td>
                        <td>
                            <span class="tag ${primaryCount > 0 ? 'tag-green' : 'tag-gray'}">
                                ${escapeHtml(primaryCount)} Primary
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn-edit" onclick="openInstructorSubjectModal(${index})">View Subjects</button>
                        </td>
                    </tr>
                `;
            }).join("");
        }

        function renderSubjectView() {
            tableHead.innerHTML = `
                <tr>
                    <th>Subject</th>
                    <th>Course</th>
                    <th>Department</th>
                    <th>Total Instructors</th>
                    <th>Primary</th>
                    <th>Actions</th>
                </tr>
            `;

            if (groupedSubjectRows.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="7">No faculty subject rows found.</td></tr>`;
                return;
            }

            tableBody.innerHTML = groupedSubjectRows.map((group, index) => {
                const subject = group.subject || {};
                const courseDisplay = getCourseDisplay(group.course);
                const rows = group.rows;
                const instructorLabels = rows.map(item => getInstructor(item).instructor_name || "No instructor");
                const primaryCount = rows.filter(item => Number(item.is_primary) === 1).length;

                return `
                    <tr>
                        <td class="fs-summary-cell">
                            <strong>${escapeHtml(subject.subject_code || "No subject code")}</strong>
                            <div class="fs-muted">${escapeHtml(subject.subject_name || "")}</div>
                        </td>
                        <td>${escapeHtml(courseDisplay.code)}</td>
                        <td>${escapeHtml(courseDisplay.department)}</td>
                        <td>${escapeHtml(rows.length)}</td>
                        <td>
                            <span class="tag ${primaryCount > 0 ? 'tag-green' : 'tag-gray'}">
                                ${escapeHtml(primaryCount)} Primary
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn-edit" onclick="openSubjectInstructorModal(${index})">View Instructors</button>
                        </td>
                    </tr>
                `;
            }).join("");
        }

        function renderInstructorModalRows(rows) {
            modalTableBody.innerHTML = rows.map(item => {
                const subject = getSubject(item);
                const courseDisplay = getCourseDisplay(getCourse(item));

                return `
                    <tr>
                        <td>${escapeHtml(item.id)}</td>
                        <td>${escapeHtml(subject.subject_code || "")}</td>
                        <td>${escapeHtml(subject.subject_name || "")}</td>
                        <td>${escapeHtml(courseDisplay.code)}</td>
                        <td>${escapeHtml(courseDisplay.department)}</td>
                        <td><span class="fs-pill">${escapeHtml(formatSessionType(item.session_type))}</span></td>
                        <td>${escapeHtml(item.priority_score)}</td>
                        <td>
                            <span class="tag ${Number(item.is_primary) === 1 ? 'tag-green' : 'tag-gray'}">
                                ${Number(item.is_primary) === 1 ? 'Yes' : 'No'}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <button type="button" class="btn-edit" onclick="editItem(${Number(item.id)})">Edit</button>
                                <button type="button" class="btn-delete" onclick="deleteItem(${Number(item.id)})">Delete</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join("");
        }

        function renderSubjectInstructorModalRows(rows) {
            modalTableBody.innerHTML = rows.map(item => {
                const instructor = getInstructor(item);

                return `
                    <tr>
                        <td>${escapeHtml(item.id)}</td>
                        <td colspan="2">${escapeHtml(instructor.instructor_name || "No instructor")}</td>
                        <td>${escapeHtml(instructor.status || "")}</td>
                        <td><span class="fs-pill">${escapeHtml(formatSessionType(item.session_type))}</span></td>
                        <td>${escapeHtml(item.priority_score)}</td>
                        <td>
                            <span class="tag ${Number(item.is_primary) === 1 ? 'tag-green' : 'tag-gray'}">
                                ${Number(item.is_primary) === 1 ? 'Yes' : 'No'}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <button type="button" class="btn-edit" onclick="editItem(${Number(item.id)})">Edit</button>
                                <button type="button" class="btn-delete" onclick="deleteItem(${Number(item.id)})">Delete</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join("");
        }

        function setModalHeaderForInstructorView() {
            modalTableBody.closest("table").querySelector("thead").innerHTML = `
                <tr>
                    <th>ID</th>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th>Course</th>
                    <th>Department</th>
                    <th>Session</th>
                    <th>Priority</th>
                    <th>Primary</th>
                    <th>Actions</th>
                </tr>
            `;
        }

        function setModalHeaderForSubjectView() {
            modalTableBody.closest("table").querySelector("thead").innerHTML = `
                <tr>
                    <th>ID</th>
                    <th colspan="2">Instructor</th>
                    <th>Status</th>
                    <th>Session</th>
                    <th>Priority</th>
                    <th>Primary</th>
                    <th>Actions</th>
                </tr>
            `;
        }

        function openInstructorSubjectModal(groupIndex) {
            const group = groupedFacultySubjects[groupIndex];

            if (!group) {
                alert("Faculty subject group not found.");
                return;
            }

            const instructorName = group.instructor?.instructor_name || "No instructor";
            modalTitle.textContent = instructorName;
            modalSubtitle.textContent = `${group.rows.length} subject assignment(s)`;
            setModalHeaderForInstructorView();
            renderInstructorModalRows(group.rows);
            modal.classList.add("active");
        }

        function openSubjectInstructorModal(groupIndex) {
            const group = groupedSubjectRows[groupIndex];

            if (!group) {
                alert("Subject group not found.");
                return;
            }

            const subject = group.subject || {};
            const courseDisplay = getCourseDisplay(group.course);
            modalTitle.textContent = `${subject.subject_code || "No subject code"} - ${subject.subject_name || ""}`;
            modalSubtitle.textContent = `${courseDisplay.code}${courseDisplay.department ? " • " + courseDisplay.department : ""} • ${group.rows.length} instructor(s)`;
            setModalHeaderForSubjectView();
            renderSubjectInstructorModalRows(group.rows);
            modal.classList.add("active");
        }

        function closeSubjectModal() {
            modal.classList.remove("active");
        }

        modal.addEventListener("click", function (event) {
            if (event.target === modal) {
                closeSubjectModal();
            }
        });

        async function loadFacultySubjects() {
            const colspan = currentViewMode === "subject" ? 7 : 6;
            tableBody.innerHTML = `<tr><td colspan="${colspan}">Loading...</td></tr>`;

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
                const colspan = currentViewMode === "subject" ? 7 : 6;
                tableBody.innerHTML = `<tr><td colspan="${colspan}">${escapeHtml(error.message)}</td></tr>`;
            }
        }

        function editItem(id) {
            const item = facultySubjectRows.find(row => Number(row.id) === Number(id));

            if (!item) {
                alert("Faculty subject row not found.");
                return;
            }

            closeSubjectModal();

            editIdInput.value = item.id ?? "";
            instructorIdInput.value = item.instructor_id ?? "";
            subjectIdInput.value = item.subject_id ?? "";
            courseIdInput.value = item.course_id ?? "";
            priorityScoreInput.value = item.priority_score ?? "10";
            sessionTypeInput.value = item.session_type || "both";
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
                course_id: Number(courseIdInput.value),
                session_type: sessionTypeInput.value,
                priority_score: Number(priorityScoreInput.value),
                is_primary: isPrimaryInput.value === "1"
            };

            if (!payload.instructor_id || !payload.subject_id || !payload.course_id || !payload.session_type) {
                alert("Instructor, Subject, Course, and Session Type are required.");
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

                closeSubjectModal();
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

            await loadDropdown(
                coursesApiUrl,
                courseIdInput,
                coursesMap,
                item => `${escapeHtml(item.course_code)} - ${escapeHtml(item.department_name || item.course_name || '')}`
            );

            await loadFacultySubjects();
        }

        initPage();
    </script>
@endsection
