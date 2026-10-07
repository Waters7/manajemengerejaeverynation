# STEP 4 — Permission Matrix

Roles (spatie/laravel-permission): `super-admin`, `pastor`, `campus-ministry`, `leader`, `ministry-coordinator`, `welcome-team` (optional), `user`.

Legend: **F** full · **S** scoped (own LifeGroup / disciples, own campus, own ministry) · **R** read only · — none

| Permission | Super Admin | Pastor | Campus Ministry | Leader | Ministry Coord. | Welcome Team | User |
|---|---|---|---|---|---|---|---|
| `admin.access` (enter /admin) | F | F | F | F | F | F | — |
| `members.view` | F | F | S campus | S own group + disciples | S ministry members | R basic | — |
| `members.manage` | F | F | S campus | S own group | — | — | — |
| `newcomers.view` / `.manage` | F | F | S campus | S assigned to me | — | F | — |
| `involvement.view` / `.manage` | F | F | S campus | S assigned to me | S ministry interest | F | — |
| `followups.view` / `.manage` | F | F | S | S assigned to me | S assigned to me | S assigned to me | — |
| `discipleship.view` | F | F | S campus | S own disciples | — | — | own journey |
| `discipleship.manage` (progress, meetings) | F | F | S campus | S own disciples | — | — | — |
| `curriculum.manage` (stages, programs, chapters) | F | F | — | — | — | — | — |
| `classes.view` / `.manage` | F | F | S campus batches | R | — | — | own classes |
| `leadership.view` | F | F | S campus | S own group (recommend) | — | — | — |
| `leadership.approve` | F | F | — | — | — | — | — |
| `lifegroups.view` / `.manage` | F | F | S campus | S own group | — | R | own group |
| `lifegroups.requests` | F | F | S campus | S own group | — | F | — |
| `ministries.view` / `.manage` | F | R | R | — | S own ministry | — | own serving |
| `volunteers.manage` (applications) | F | R | S campus ministry | — | S own ministry | — | apply |
| `campus.manage` | F | F | S assigned campuses | — | — | — | — |
| `events.manage` | F | F | S campus events | — | — | — | register |
| `content.manage` (devotionals, sermons, gallery, pages, homepage) | F | F | — | — | — | — | — |
| `prayer.view` | F | F (all) | S campus, not pastor-only | S own group, `lifegroup_leader` visibility | — | prayer_team visibility* | own |
| `pastoral.view` / `.manage` | F | F | — | — | — | — | — |
| `certificates.manage` (upload baptism / program certificates) | F | F | S campus | S own group & disciples | — | — | own certificates (read) |
| `prophecy.manage` (upload / listen to prophetic words) | F | F | — | — | — | — | own recordings (listen) |
| `store.manage` (products, categories, store settings) | F | F | — | — | — | — | browse & order |
| `orders.manage` (confirm payments, fulfil, cancel) | F | F | — | — | — | — | own orders (My Orders) |
| `announcements.manage` | F | F | — | — | — | — | — |
| `birthdays.view` | F | F | S campus | S own group | S own ministry | — | — |
| `reports.view` / `reports.export` | F | F | S campus | — | — | — | — |
| `users.manage` / `roles.manage` / `settings.manage` / `media.manage` / `audit.view` | F | — | — | — | — | — | — |

\* Prayer Team is configured as a role permission `prayer.team` that can be granted to any role.

Rules enforced in code:
* Public sign-up always gets `user` + `pending_verification`; **no automatic role elevation**. Ministry roles are granted only by a user holding `roles.manage` and every change is written to `audit_logs` as `role_change`.
* Accepting a volunteer application creates a `ministry_members` row — **never** a system role.
* Scopes are applied centrally by `App\Services\AccessScope` (query constraints) and checked again in Policies.

# STEP 5 — Discipleship workflow (4E)

```
Stage (configurable)      Programs (configurable, sequence + prerequisite)
ENGAGE      ─────────────  One 2 One (BOOK, lessons)
ESTABLISH   ─────────────  Preparing for Victory (CLASS) → Victory Weekend (EVENT, milestone) → Purple Book (BOOK, 12 ch)
EQUIP       ─────────────  Church Community (CLASS) → Making Disciples 1 (CLASS) → Making Disciples 2 (CLASS)
EMPOWER     ─────────────  Leadership 113 (TRAINING) → Leadership 215 (TRAINING)
```
1. Discipler relationship created (discipler ⇄ disciple) — optional per program.
2. Start program → `member_program_progress` (in_progress, started_at, expected_completion).
3. BOOK: tick chapters (`member_chapter_progress`) → progress *n / total*; all done → program completed.
   CLASS/TRAINING/EVENT: enrol in a `class_batch` → attendance per session → facilitator marks participant Completed → program completed.
4. On every completion: timeline entry, `profiles.current_stage_id/current_program_id` recalculated (`JourneyService::recalculate`), audit log.
5. Prerequisites are *warnings*, not hard blocks — pastors may override (documented in the note).

# STEP 6 — Newcomer / Get Involved workflow

```
/connect  or  /get-involved
        │  (InvolvementService::submit — DB transaction)
        ├─ find-or-create profile by normalised WhatsApp (62…)
        ├─ create/refresh newcomer record (FIRST VISIT / CONNECT CARD)
        ├─ create involvement_request (status NEW) + interests + ministries
        ├─ if "Prayer request" interest → prayer_request
        ├─ if volunteer/ministry interest → volunteer_application
        └─ notify Welcome Team / Pastors (database notification)
Admin: assign follow-up person (+ due date) → follow_up_task
NEW → CONTACTED → FOLLOW-UP → CONNECTED → ACTIVE      (or NOT CONTINUING)
         ▲ WhatsApp deep link (template with {nickname} {followup_person} {interest})
Actions: Add note · Assign LifeGroup · Assign Ministry · Start One 2 One · Activate Member · Archive
```

# STEP 7 — LifeGroup workflow

```
Public /lifegroups/{slug} → JOIN THIS LIFEGROUP → join_request PENDING
Leader: Contact (WhatsApp) → CONTACTED → Approve → APPROVED (invite link shown to leader to send)
        → Mark Joined → JOINED (life_group_members row, profile timeline)   or REJECTED
Meetings: Leader records meeting (date, topic, location) → attendance Present/Absent/Excused
Dashboard: members, visitors, being discipled, disciplers, potential leaders, attendance rate
```

# STEP 8 — Volunteer / Ministry workflow

```
/get-involved/serve (or ministry interest on Get Involved)
  → volunteer_application SUBMITTED
Coordinator: Review → CONTACTED → INTERVIEW → ORIENTATION → ACCEPTED / DECLINED
  ACCEPTED → ministry_members (status ORIENTATION → ACTIVE) — no system role
Serving schedule: coordinator assigns volunteers per date / role
```
