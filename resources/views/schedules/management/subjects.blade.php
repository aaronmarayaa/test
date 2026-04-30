@extends('schedules.management.layout')

@php
    $pageTitle = 'Manage Subjects';
    $pageSubtitle = 'Add, edit, archive, and manage subject settings.';
@endphp

@section('form')
    <h2 id="form-title">Add Subject</h2>

    <input type="hidden" id="edit-id">

    <div class="form-group"><label for="subject_code">Subject Code</label><input type="text" id="subject_code"></div>
    <div class="form-group"><label for="subject_name">Subject Name</label><input type="text" id="subject_name"></div>
    <div class="form-group"><label for="units">Units</label><input type="number" step="0.1" id="units" value="3.0"></div>
    <div class="form-group"><label for="total_hours_per_week">Total Hours Per Week</label><input type="number" step="0.01" id="total_hours_per_week" value="3.00"></div>
    <div class="form-group"><label for="lecture_hours">Lecture Hours</label><input type="number" step="0.01" id="lecture_hours" value="0.00"></div>
    <div class="form-group"><label for="laboratory_hours">Laboratory Hours</label><input type="number" step="0.01" id="laboratory_hours" value="0.00"></div>

    <div class="form-group">
        <label for="allow_split_sessions">Allow Split Sessions</label>
        <select id="allow_split_sessions">
            <option value="0">No</option>
            <option value="1">Yes</option>
        </select>
    </div>

    <div class="form-group">
        <label for="room_type_required">Default Room Type Required</label>
        <select id="room_type_required">
            <option value="lecture">lecture</option>
            <option value="computer_lab">computer_lab</option>
            <option value="science_lab">science_lab</option>
            <option value="gym">gym</option>
            <option value="laboratory">laboratory</option>
        </select>
    </div>

    <div class="form-group">
        <label for="lecture_room_type_required">Lecture Room Type Required</label>
        <select id="lecture_room_type_required">
            <option value="lecture">lecture</option>
            <option value="computer_lab">computer_lab</option>
            <option value="science_lab">science_lab</option>
            <option value="gym">gym</option>
            <option value="laboratory">laboratory</option>
        </select>
    </div>

    <div class="form-group">
        <label for="laboratory_room_type_required">Laboratory Room Type Required</label>
        <select id="laboratory_room_type_required">
            <option value="laboratory">laboratory</option>
            <option value="computer_lab">computer_lab</option>
            <option value="science_lab">science_lab</option>
            <option value="lecture">lecture</option>
            <option value="gym">gym</option>
        </select>
    </div>

    <div class="form-group">
        <label for="subject_category">Subject Category</label>
        <select id="subject_category">
            <option value="lecture">lecture</option>
            <option value="laboratory">laboratory</option>
            <option value="mixed">mixed</option>
            <option value="major">major</option>
            <option value="minor">minor</option>
            <option value="ge">ge</option>
            <option value="pe">pe</option>
            <option value="nstp">nstp</option>
        </select>
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
        <button type="button" class="save-btn" id="save-btn">Save Subject</button>
        <button type="button" class="btn-edit" id="cancel-btn" style="display:none;">Cancel</button>
    </div>
@endsection

@section('list')
    <h2>Subject List</h2>

    <table class="list-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Code</th>
                <th>Name</th>
                <th>Units</th>
                <th>Total Hrs</th>
                <th>Lec</th>
                <th>Lab</th>
                <th>Split</th>
                <th>Default Room</th>
                <th>Lec Room</th>
                <th>Lab Room</th>
                <th>Category</th>
                <th>Status</th>
                <th>Archived</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="subject-table-body">
            <tr><td colspan="15">Loading...</td></tr>
        </tbody>
    </table>

    <script>
        const apiUrl = "/api/subjects";

        const tableBody = document.getElementById("subject-table-body");
        const formTitle = document.getElementById("form-title");
        const editIdInput = document.getElementById("edit-id");
        const subjectCodeInput = document.getElementById("subject_code");
        const subjectNameInput = document.getElementById("subject_name");
        const unitsInput = document.getElementById("units");
        const totalHoursInput = document.getElementById("total_hours_per_week");
        const lectureHoursInput = document.getElementById("lecture_hours");
        const laboratoryHoursInput = document.getElementById("laboratory_hours");
        const allowSplitInput = document.getElementById("allow_split_sessions");
        const roomTypeInput = document.getElementById("room_type_required");
        const lectureRoomTypeInput = document.getElementById("lecture_room_type_required");
        const laboratoryRoomTypeInput = document.getElementById("laboratory_room_type_required");
        const subjectCategoryInput = document.getElementById("subject_category");
        const statusInput = document.getElementById("status");
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
            subjectCodeInput.value = "";
            subjectNameInput.value = "";
            unitsInput.value = "3.0";
            totalHoursInput.value = "3.00";
            lectureHoursInput.value = "0.00";
            laboratoryHoursInput.value = "0.00";
            allowSplitInput.value = "0";
            roomTypeInput.value = "lecture";
            lectureRoomTypeInput.value = "lecture";
            laboratoryRoomTypeInput.value = "laboratory";
            subjectCategoryInput.value = "lecture";
            statusInput.value = "active";
            archivedInput.value = "0";
            formTitle.textContent = "Add Subject";
            saveBtn.textContent = "Save Subject";
            cancelBtn.style.display = "none";
        }

        function renderRows(items) {
            if (!Array.isArray(items) || items.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="15">No subjects found.</td></tr>`;
                return;
            }

            tableBody.innerHTML = items.map(item => `
                <tr>
                    <td>${escapeHtml(item.id)}</td>
                    <td>${escapeHtml(item.subject_code)}</td>
                    <td>${escapeHtml(item.subject_name)}</td>
                    <td>${escapeHtml(item.units)}</td>
                    <td>${escapeHtml(item.total_hours_per_week)}</td>
                    <td>${escapeHtml(item.lecture_hours)}</td>
                    <td>${escapeHtml(item.laboratory_hours)}</td>
                    <td><span class="tag ${Number(item.allow_split_sessions) === 1 ? 'tag-yellow' : 'tag-green'}">${Number(item.allow_split_sessions) === 1 ? 'Yes' : 'No'}</span></td>
                    <td>${escapeHtml(item.room_type_required || '—')}</td>
                    <td>${escapeHtml(item.lecture_room_type_required || '—')}</td>
                    <td>${escapeHtml(item.laboratory_room_type_required || '—')}</td>
                    <td>${escapeHtml(item.subject_category || '—')}</td>
                    <td><span class="tag ${String(item.status).toLowerCase() === 'active' ? 'tag-green' : 'tag-gray'}">${escapeHtml(item.status || 'active')}</span></td>
                    <td><span class="tag ${Number(item.archived) === 1 ? 'tag-gray' : 'tag-green'}">${Number(item.archived) === 1 ? 'Yes' : 'No'}</span></td>
                    <td>
                        <div class="actions">
                            <button type="button" class="btn-edit" onclick='editItem(${JSON.stringify(item)})'>Edit</button>
                            <button type="button" class="btn-delete" onclick="deleteItem(${item.id})">Delete / Archive</button>
                        </div>
                    </td>
                </tr>
            `).join("");
        }

        async function loadSubjects() {
            tableBody.innerHTML = `<tr><td colspan="15">Loading...</td></tr>`;
            try {
                const response = await fetch(apiUrl, { headers: { "Accept": "application/json" } });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || "Failed to load subjects.");
                renderRows(result.data || []);
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="15">${escapeHtml(error.message)}</td></tr>`;
            }
        }

        function editItem(item) {
            editIdInput.value = item.id ?? "";
            subjectCodeInput.value = item.subject_code ?? "";
            subjectNameInput.value = item.subject_name ?? "";
            unitsInput.value = item.units ?? "3.0";
            totalHoursInput.value = item.total_hours_per_week ?? "3.00";
            lectureHoursInput.value = item.lecture_hours ?? "0.00";
            laboratoryHoursInput.value = item.laboratory_hours ?? "0.00";
            allowSplitInput.value = Number(item.allow_split_sessions) === 1 ? "1" : "0";
            roomTypeInput.value = item.room_type_required ?? "lecture";
            lectureRoomTypeInput.value = item.lecture_room_type_required ?? "lecture";
            laboratoryRoomTypeInput.value = item.laboratory_room_type_required ?? "laboratory";
            subjectCategoryInput.value = item.subject_category ?? "lecture";
            statusInput.value = String(item.status ?? "active").toLowerCase();
            archivedInput.value = Number(item.archived) === 1 ? "1" : "0";
            formTitle.textContent = "Edit Subject";
            saveBtn.textContent = "Update Subject";
            cancelBtn.style.display = "inline-block";
            window.scrollTo({ top: 0, behavior: "smooth" });
        }

        async function saveItem() {
            const id = editIdInput.value.trim();
            const payload = {
                subject_code: subjectCodeInput.value.trim(),
                subject_name: subjectNameInput.value.trim(),
                units: Number(unitsInput.value),
                total_hours_per_week: Number(totalHoursInput.value),
                lecture_hours: Number(lectureHoursInput.value),
                laboratory_hours: Number(laboratoryHoursInput.value),
                allow_split_sessions: allowSplitInput.value === "1",
                room_type_required: roomTypeInput.value,
                lecture_room_type_required: lectureRoomTypeInput.value,
                laboratory_room_type_required: laboratoryRoomTypeInput.value,
                subject_category: subjectCategoryInput.value,
                status: statusInput.value,
                archived: archivedInput.value === "1"
            };

            if (!payload.subject_code || !payload.subject_name) {
                alert("Subject Code and Subject Name are required.");
                return;
            }

            const isEdit = id !== "";
            const url = isEdit ? `${apiUrl}/${id}` : apiUrl;
            const method = isEdit ? "PUT" : "POST";

            try {
                const response = await fetch(url, {
                    method,
                    headers: { "Accept": "application/json", "Content-Type": "application/json" },
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
                await loadSubjects();
                alert(result.message || "Saved successfully.");
            } catch (error) {
                alert(error.message);
            }
        }

        async function deleteItem(id) {
            if (!confirm("Delete or archive this subject?")) return;
            try {
                const response = await fetch(`${apiUrl}/${id}`, { method: "DELETE", headers: { "Accept": "application/json" } });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || "Delete failed.");
                if (String(editIdInput.value) === String(id)) resetForm();
                await loadSubjects();
                alert(result.message || "Deleted successfully.");
            } catch (error) {
                alert(error.message);
            }
        }

        saveBtn.addEventListener("click", saveItem);
        cancelBtn.addEventListener("click", resetForm);
        loadSubjects();
    </script>
@endsection