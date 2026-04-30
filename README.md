# Workflow

- Everytime na kukuha tayo ng changes from master branch(sa github repository), `git rebase` gamitin natin(para hindi magulo git history natin), tas tsaka ko immerge sa main/master branch. 

- Para ma-rebase, git fetch gamitin niyo, ***huwag git pull***, then i-rebase niyo sa working branch niyo. 
    >Ex. `git fetch` `git rebase <branch_name>`

- Huwag niyo directly i-edit yung main/master branch, create kayo ng branch niyo, and dun niyo ilagay mga changes na gagawin niyo.
Sa branching, gagamitin tayo ng simpleng naming convention(name-niyo/***feature-na-ginagawa-niyo***).
    >Ex. `git branch` ***`john/homepage`***

- Gamit din tayo ng message conventions sa git messages natin, make sure na descriptive and concise lalagay natin para madaling balikan yung history if ever may problem.
    - ***`feat:`*** kapag maglalagay lang ng panibagong feature sa api or UI.
        >Ex. feat: Add logout button in home page.
    - ***`ref:`*** kapag may binago sa code pero hindi nabago feature ng program 
        >Ex. ref: remove unused variables.
    - ***`style:`*** kapag maglalagay ng style or UI/UX related na design/changes.
        >Ex. style: change the background color.
    - ***`fix:`*** bago gamitin yung message convention na to, gagawa ulit kay ng branch, papangalanan niyo ng (name-niyo/bugfix/saang-part-yung-inaayos).
        >Ex. fix: configure the api fetching.
    - ***`docs:`*** kapag documentation lang uupload natin.
    - ***`add`***, kapag binubuo pa lang yung program pero gusto niyo na isave yung commit niyo.
        >Ex. add an input for username and password.

Yung API Endpoints, to follow nlng muna:>

---

### Get All Schedules
- **URL:** `/api/schedules`
- **Method:** `GET`
cookie`***
- **Response (Success):**
    - **Status Code:** `200 OK`
    - ```JSON
        [
            {
                "id": 1,
                "instructor_name": "John Doe",
                "instructor_type": "full-time",
                "course": "Psychology",
                "day": "Monday",
                "start_time": "10:30:00",
                "end_time": "17:30:00",
                "room": "Room 101"
            }
        ]
        ```

### Get Single Schedule
- **URL:** `/api/schedules/{id}`
- **Method:** `GET`
- **Response (Success):**
    - **Status Code:** `200 OK`
    - ```JSON
        {
            "id": 1,
            "instructor_name": "John Doe",
            "instructor_type": "full-time",
            "course": "Psychology",
            "day": "Monday",
            "start_time": "10:30:00",
            "end_time": "17:30:00",
            "room": "Room 101"
        }
        ```
- **Response (Not Found):**
    - **Status Code:** `404 Not Found`
    - ```JSON
        {
            "message": "No query results for model"
        }
        ```

### Create Schedule
- **URL:** `/api/schedules`
- **Method:** `POST`
- **Request Body:**
    - ```JSON
        {
            "instructor_name": "John Doe",
            "instructor_type": "part-time",
            "course": "Psychology",
            "day": "Monday",
            "start_time": "10:30",
            "end_time": "17:30",
            "room": "Room 101"
        }
        ```
- **Response (Success):**
    - **Status Code:** `201 Created`
    - ```JSON
        {
            "message": "Schedule created successfully.",
            "data": {
                "id": 1,
                "instructor_name": "John Doe",
                "instructor_type": "part-time",
                "course": "Psychology",
                "day": "Monday",
                "start_time": "10:30:00",
                "end_time": "17:30:00",
                "room": "Room 101"
            }
        }
        ```
- **Response (Validation Error):**
    - **Status Code:** `422 Unprocessable Entity`
    - ```JSON
        {
            "message": "Invalid time schedule.",
            "errors": {
                "end_time": [
                "Invalid time schedule. Use HH:mm format like 07:00 or 17:30."
                ]
            }
        }
        ```
- **Response (Room Conflict):**
    - **Status Code:** `409 Conflict`
    - ```JSON
        {
            "message": "Room conflict detected. The selected room is already occupied at that time."
        }
        ```
- **Response (Instructor Conflict):**
    - **Status Code:** `409 Conflict`
    - ```JSON
        {
            "message": "Instructor conflict detected. This instructor already has a schedule at that time."
        }
        ```

### Update Schedule
- **URL:** `/api/schedules/{id}`
- **Method:** `PUT`
- **Request Body:**
    - ```JSON
        {
            "instructor_name": "John Doe",
            "instructor_type": "full-time",
            "course": "Tourism",
            "day": "Tuesday",
            "start_time": "13:00",
            "end_time": "15:00",
            "room": "Room 102"
        }
        ```
- **Response (Success):**
    - **Status Code:** `200 OK`
    - ```JSON
        {
            "message": "Schedule updated successfully.",
            "data": {
                "id": 1,
                "instructor_name": "John Doe",
                "instructor_type": "full-time",
                "course": "Tourism",
                "day": "Tuesday",
                "start_time": "13:00:00",
                "end_time": "15:00:00",
                "room": "Room 102"
            }
        }
        ```
### Delete Schedule
- **URL:** `/api/schedules/{id}`
- **Method:** `DELETE`
- **Response (Success):**
    - **Status Code:** `200 OK`
    - ```JSON
        {
            "message": "Schedule deleted successfully."
        }

---

### Notes
- instructor_type is optional
    - Default: "full-time"
- Time must be:
    - Format: HH:mm
    - Range: 07:00 to 20:30
- No overlapping:
    - same room
    - same instructor