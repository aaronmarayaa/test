@extends('schedules.management.layout')

@php
    $pageTitle = 'Manage Curriculum';
    $pageSubtitle = 'Assign subjects to course, year level, and semester.';
@endphp

@section('form')
    <h2 id="form-title">Add Curriculum</h2>

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
        <label for="semester_id">Semester</label>
        <select id="semester_id">
            <option value="">Loading semesters...</option>
        </select>
    </div>

    <div class="form-group">
        <label for="subject_id">Subject</label>
        <select id="subject_id">
            <option value="">Loading subjects...</option>
        </select>
    </div>

    <div class="form-group">
        <label for="sort_order">Sort Order</label>
        <input type="number" id="sort_order" value="1" min="1">
    </div>

    <div class="form-group">
        <th>Active</th>
        <th>Action</th>
        <select id="active">
            <option value="1">Yes</option>
            <option value="0">No</option>
        </select>
    </div>

    <div class="actions">
        <button type="button" class="save-btn" id="save-btn">Save Curriculum</button>
        <button type="button" class="btn-edit" id="cancel-btn" style="display:none;">Cancel</button>
    </div>
@endsection

@section('list')
    <h2>Curriculum List</h2>

    <table class="list-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Course</th>
                <th>Year</th>
                <th>Semester</th>
                <th>Total Subjects</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="curriculum-table-body">
            <tr>
                <td colspan="9">Loading...</td>
            </tr>
        </tbody>
    </table>
    <div id="curriculum-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#fff; width:90%; max-width:900px; max-height:85vh; overflow:auto; border-radius:16px; padding:20px; position:relative;">
            <button type="button" id="close-modal-btn"
                style="position:absolute; top:12px; right:12px; border:none; background:#e5e7eb; padding:8px 12px; border-radius:8px; cursor:pointer; font-weight:bold;">
                Close
            </button>

            <h2 id="modal-title" style="margin-top:0;">Curriculum Subjects</h2>

            <table class="list-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Sort</th>
                        <th>Active</th>
                    </tr>
                </thead>
                <tbody id="curriculum-modal-body">
                    <tr>
                        <td colspan="5">No subjects found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        const curriculaApiUrl = "/api/curricula";
        const coursesApiUrl = "/api/courses";
        const semestersApiUrl = "/api/semesters";
        const subjectsApiUrl = "/api/subjects";

        const tableBody = document.getElementById("curriculum-table-body");
        const formTitle = document.getElementById("form-title");
        const editIdInput = document.getElementById("edit-id");
        const courseIdInput = document.getElementById("course_id");
        const yearLevelInput = document.getElementById("year_level");
        const semesterIdInput = document.getElementById("semester_id");
        const subjectIdInput = document.getElementById("subject_id");
        const sortOrderInput = document.getElementById("sort_order");
        const activeInput = document.getElementById("active");
        const saveBtn = document.getElementById("save-btn");
        const cancelBtn = document.getElementById("cancel-btn");

        let coursesMap = {};
        let semestersMap = {};
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
            courseIdInput.value = "";
            yearLevelInput.value = "1";
            semesterIdInput.value = "";
            subjectIdInput.value = "";
            sortOrderInput.value = "1";
            activeInput.value = "1";

            formTitle.textContent = "Add Curriculum";
            saveBtn.textContent = "Save Curriculum";
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

        function groupCurricula(items) {
            const grouped = {};

            items.forEach(item => {
                const key = `${item.course_id}_${item.year_level}_${item.semester_id}`;

                if (!grouped[key]) {
                    const course = item.course || coursesMap[item.course_id] || {};
                    const semester = item.semester || semestersMap[item.semester_id] || {};

                    grouped[key] = {
                        key,
                        id: item.id,
                        course_id: item.course_id,
                        year_level: item.year_level,
                        semester_id: item.semester_id,
                        course_code: course.course_code || "",
                        course_name: course.course_name || "",
                        semester_name: semester.semester_name || "",
                        active: Number(item.active) === 1,
                        items: []
                    };
                }

                grouped[key].items.push(item);

                if (Number(item.id) < Number(grouped[key].id)) {
                    grouped[key].id = item.id;
                }

                if (Number(item.active) === 1) {
                    grouped[key].active = true;
                }
            });

            return Object.values(grouped).map(group => {
                group.items.sort((a, b) => Number(a.sort_order) - Number(b.sort_order));
                return group;
            });
        }

        async function deleteCurriculumGroup(groupKey) {
            const groups = window.curriculumGroups || [];
            const group = groups.find(g => g.key === groupKey);

            if (!group) return;

            if (!confirm("Delete ENTIRE curriculum? This will remove all subjects.")) return;

            try {
                for (const item of group.items) {
                    await fetch(`${curriculaApiUrl}/${item.id}`, {
                        method: "DELETE",
                        headers: { "Accept": "application/json" }
                    });
                }

                await loadCurricula();

                alert("Curriculum deleted successfully.");
            } catch (error) {
                alert("Error deleting curriculum.");
            }
        }

        function renderRows(items) {
            if (!Array.isArray(items) || items.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="7">No curriculum rows found.</td></tr>`;
                return;
            }

            const groupedItems = groupCurricula(items);

            tableBody.innerHTML = groupedItems.map(group => `
                <tr>
                    <td>${escapeHtml(group.id)}</td>
                    <td>${escapeHtml(group.course_code)}</td>
                    <td>${escapeHtml(group.year_level)}</td>
                    <td>${escapeHtml(group.semester_name)}</td>
                    <td>${escapeHtml(group.items.length)}</td>
                    <td>
                        <span class="tag ${group.active ? 'tag-green' : 'tag-gray'}">
                            ${group.active ? 'Yes' : 'No'}
                        </span>
                    </td>
                    <td>
                        <div class="actions">
                            <button class="btn-edit" onclick="viewCurriculumSubjects('${group.key}')">
                                View
                            </button>

                            <button class="btn-delete" onclick="deleteCurriculumGroup('${group.key}')">
                                Delete All
                            </button>
                        </div>
                    </td>
                </tr>
            `).join("");

            window.curriculumGroups = groupedItems;
        }

        function viewCurriculumSubjects(groupKey) {
            const modal = document.getElementById("curriculum-modal");
            const modalTitle = document.getElementById("modal-title");
            const modalBody = document.getElementById("curriculum-modal-body");

            const groups = window.curriculumGroups || [];
            const group = groups.find(g => g.key === groupKey);

            if (!group) {
                modalBody.innerHTML = `<tr><td colspan="5">No subjects found.</td></tr>`;
                modal.style.display = "flex";
                return;
            }

            modalTitle.textContent = `${group.course_code} - Year ${group.year_level} - ${group.semester_name}`;

            modalBody.innerHTML = group.items.map((item, index) => {
            const subject = item.subject || subjectsMap[item.subject_id] || {};

            return `
                <tr>
                    <td>${index + 1}</td>
                    <td>${escapeHtml(subject.subject_code || "")}</td>
                    <td>${escapeHtml(subject.subject_name || "")}</td>
                    <td>${escapeHtml(item.sort_order)}</td>
                    <td>
                        <span class="tag ${Number(item.active) === 1 ? 'tag-green' : 'tag-gray'}">
                            ${Number(item.active) === 1 ? 'Yes' : 'No'}
                        </span>
                    </td>
                    <td>
                        <button class="btn-delete" onclick="deleteSubject(${item.id})">
                            Delete
                        </button>
                    </td>
                </tr>
            `;
        }).join("");

            modal.style.display = "flex";
        }

        async function deleteSubject(id) {
            if (!confirm("Delete this subject from curriculum?")) return;

            try {
                const response = await fetch(`${curriculaApiUrl}/${id}`, {
                    method: "DELETE",
                    headers: { "Accept": "application/json" }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || "Delete failed.");
                }

                await loadCurricula();

                document.getElementById("curriculum-modal").style.display = "none";

                alert("Subject removed from curriculum.");
            } catch (error) {
                alert(error.message);
            }
        }

        async function loadCurricula() {
            tableBody.innerHTML = `<tr><td colspan="9">Loading...</td></tr>`;

            try {
                const response = await fetch(curriculaApiUrl, {
                    headers: { "Accept": "application/json" }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || "Failed to load curricula.");
                }

                renderRows(result.data || []);
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="9">${escapeHtml(error.message)}</td></tr>`;
            }
        }

        function editItem(item) {
            editIdInput.value = item.id ?? "";
            courseIdInput.value = item.course_id ?? "";
            yearLevelInput.value = String(item.year_level ?? "1");
            semesterIdInput.value = item.semester_id ?? "";
            subjectIdInput.value = item.subject_id ?? "";
            sortOrderInput.value = item.sort_order ?? "1";
            activeInput.value = Number(item.active) === 1 ? "1" : "0";

            formTitle.textContent = "Edit Curriculum";
            saveBtn.textContent = "Update Curriculum";
            cancelBtn.style.display = "inline-block";

            window.scrollTo({ top: 0, behavior: "smooth" });
        }

        async function saveItem() {
            const id = editIdInput.value.trim();

            const payload = {
                course_id: Number(courseIdInput.value),
                year_level: Number(yearLevelInput.value),
                semester_id: Number(semesterIdInput.value),
                subject_id: Number(subjectIdInput.value),
                sort_order: Number(sortOrderInput.value),
                active: activeInput.value === "1"
            };

            if (!payload.course_id || !payload.year_level || !payload.semester_id || !payload.subject_id || !payload.sort_order) {
                alert("Course, Year Level, Semester, Subject, and Sort Order are required.");
                return;
            }

            const isEdit = id !== "";
            const url = isEdit ? `${curriculaApiUrl}/${id}` : curriculaApiUrl;
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
                await loadCurricula();
                alert(result.message || "Saved successfully.");
            } catch (error) {
                alert(error.message);
            }
        }

        async function deleteItem(id) {
            if (!confirm("Delete this curriculum row?")) return;

            try {
                const response = await fetch(`${curriculaApiUrl}/${id}`, {
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

                await loadCurricula();
                alert(result.message || "Deleted successfully.");
            } catch (error) {
                alert(error.message);
            }
        }

        saveBtn.addEventListener("click", saveItem);
        cancelBtn.addEventListener("click", resetForm);

        document.getElementById("close-modal-btn").addEventListener("click", () => {
            document.getElementById("curriculum-modal").style.display = "none";
        });

        document.getElementById("curriculum-modal").addEventListener("click", (e) => {
            if (e.target.id === "curriculum-modal") {
                document.getElementById("curriculum-modal").style.display = "none";
            }
        });

        async function initPage() {
            await loadDropdown(
                coursesApiUrl,
                courseIdInput,
                coursesMap,
                item => `${escapeHtml(item.course_code)} - ${escapeHtml(item.course_name)}`
            );

            await loadDropdown(
                semestersApiUrl,
                semesterIdInput,
                semestersMap,
                item => `${escapeHtml(item.semester_name)}`
            );

            await loadDropdown(
                subjectsApiUrl,
                subjectIdInput,
                subjectsMap,
                item => `${escapeHtml(item.subject_code)} - ${escapeHtml(item.subject_name)}`
            );

            await loadCurricula();
        }

        initPage();
    </script>
@endsection