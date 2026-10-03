# Every Nation Bekasi — Sitemap & User Journeys

> HONOR GOD. MAKE DISCIPLES.

## STEP 1 — Sitemap

### Public website

| Route | Page | Notes |
|---|---|---|
| `/` | Home | Hero, About, Upcoming Events, Discipleship Journey (4E), Latest Devotional, Latest Sermon, LifeGroups, Campus, Get Involved, Gallery, Footer |
| `/about` | About | Vision, mission, values, service times (CMS page `about`) |
| `/discipleship` | Discipleship | 4E journey (Engage → Establish → Equip → Empower) driven by `discipleship_stages`/`discipleship_programs` |
| `/lifegroups` | LifeGroup directory | Filter by category / area / day; only `is_public` + active groups |
| `/lifegroups/{slug}` | LifeGroup detail | Leader, day, time, area, description, **JOIN THIS LIFEGROUP** form. WhatsApp invite URL is never rendered |
| `/events` | Events | Upcoming / past, category filter |
| `/events/{slug}` | Event detail | Registration form (capacity + waiting list), map |
| `/events/{slug}/ticket/{code}` | Registration ticket | QR code for check-in |
| `/devotionals`, `/devotionals/{slug}` | Devotionals | Published only |
| `/sermons`, `/sermons/{slug}` | Sermons | YouTube/Spotify embeds, series filter |
| `/campus` | Campus Ministry | "CHANGE THE CAMPUS. CHANGE THE WORLD." |
| `/gallery`, `/gallery/{slug}` | Gallery albums | Lightbox |
| `/get-involved` | Get Involved | Main registration form (interests, ministry interest) |
| `/get-involved/serve` | Serve With Us | Volunteer application |
| `/connect` | Digital Connect Card | Short form, accessed by QR at Sunday Service |
| `/prayer` | Prayer Request | Public submission |
| `/pages/{slug}` | CMS pages | Generic pages |
| `/login`, `/register`, `/forgot-password`, `/reset-password/{token}` | Auth | Public sign-up → role USER |

### Member area (`/my`, role USER and above)

| Route | Page |
|---|---|
| `/my` | Member Dashboard — WELCOME BACK, {nickname} |
| `/my/profile` | Edit Profile |
| `/my/journey` | My Discipleship Journey (4E checklist + timeline) |
| `/my/lifegroup` | My LifeGroup |
| `/my/classes` | My Classes |
| `/my/events` | My Event registrations |
| `/my/serving` | My Ministries / volunteer applications |
| `/my/disciples` | MY DISCIPLES (only if user is a discipler) |

### Admin area (`/admin`, any ministry role)

```
DASHBOARD               /admin                       (role-aware: Pastor / Leader / Campus / Coordinator / Admin)
PEOPLE
  Members               /admin/members
  Newcomers             /admin/newcomers
  Get Involved          /admin/involvement
  Follow-Ups            /admin/follow-ups
DISCIPLESHIP
  Journey               /admin/discipleship/journey
  One 2 One             /admin/discipleship/one2one
  Curriculum            /admin/discipleship/curriculum   (stages → programs)
  Books                 /admin/discipleship/books        (programs of type BOOK + chapters)
  Classes               /admin/discipleship/classes      (batches → sessions → attendance)
  Victory Weekend       /admin/discipleship/victory-weekend
  Disciplers            /admin/discipleship/disciplers   (+ tree)
  Leadership Pipeline   /admin/leadership
COMMUNITY
  LifeGroups            /admin/lifegroups
  Join Requests         /admin/lifegroup-requests
  Meetings/Attendance   /admin/lifegroups/{id}/meetings
MINISTRY
  Ministries            /admin/ministries
  Volunteers            /admin/volunteers
  Applications          /admin/volunteer-applications
  Serving Schedule      /admin/serving-schedule
CAMPUS
  Campus Ministry       /admin/campuses
  Students              /admin/members?campus=…
  Campus LifeGroups     /admin/lifegroups?campus=…
  Campus Events         /admin/events?campus=…
CONTENT
  Devotionals / Sermons / Events / Gallery / Pages / Homepage
CARE
  Prayer Requests       /admin/prayer-requests
  Pastoral Care         /admin/pastoral-care
COMMUNICATION
  Announcements         /admin/announcements
  Birthdays             /admin/birthdays
REPORTS                 /admin/reports
SYSTEM
  Users / Roles / Permissions / Media / Settings / Audit Logs
```

## STEP 2 — User Journeys

### 2.1 Visitor → Disciple Maker (the core journey)

```
VISITOR ──(Sunday Service, QR /connect)──▶ NEWCOMER (connect card / get-involved)
   │                                         │ admin assigns follow-up person (due date)
   │                                         ▼
   │                                   CONTACTED (WhatsApp deep link)
   │                                         ▼
   │                                   CONNECTED ──▶ LifeGroup join request approved
   │                                         ▼
   │                                   LIFEGROUP member
   │                                         ▼
   │                         ENGAGE: One 2 One with a discipler
   │                                         ▼
   │                ESTABLISH: Preparing for Victory → Victory Weekend → Purple Book
   │                                         ▼
   │                EQUIP: Church Community → Making Disciples 1 → 2
   │                                         ▼
   │                EMPOWER: Leadership 113 → Leadership 215
   │                                         ▼
   │                Leadership Pipeline (Potential → Training → Ready → Approved → Active Leader)
   │                                         ▼
   └──────────────────────────────── DISCIPLE MAKER → MULTIPLICATION (discipleship tree)
```

### 2.2 Public visitor
1. Lands on `/` → reads hero, events, LifeGroups.
2. Clicks **JOIN A LIFEGROUP** → `/lifegroups` → picks group → submits join form (Pending).
3. Or clicks **GET INVOLVED** → fills form, chooses interests (+ ministries) → sees thank-you page with next steps.
4. Receives WhatsApp from assigned follow-up person.

### 2.3 Registered user (USER)
1. `/register` → role USER, status *Pending Verification* until admin activates or email verified.
2. `/my` dashboard → My Journey, My LifeGroup, My Classes, My Events, Serving, Get Involved.
3. Registers for events, applies to serve, requests to join a LifeGroup.

### 2.4 LifeGroup Leader
1. Logs in → Leader dashboard: own LifeGroup summary, join requests, birthdays, needs follow-up.
2. Approves join requests → only then WhatsApp group invite is shared.
3. Records meetings + attendance; updates disciples' progress; adds notes.

### 2.5 Pastor
1. Pastor dashboard: totals, discipleship funnel (clickable), needs follow-up, upcoming classes, birthdays.
2. Reviews leadership pipeline, approves leaders, manages pastoral care.

### 2.6 Campus Ministry
1. Campus dashboard scoped to assigned campuses: students, campus LifeGroups, events, discipleship.

### 2.7 Ministry Coordinator
1. Coordinator dashboard: own ministries, volunteer applications (review → interview → orientation → accept), members, serving schedule.
