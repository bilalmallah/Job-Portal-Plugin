# Care Wave Candidate Portal

A complete job portal for WordPress: candidate accounts, profiles, one click
job applications, and volunteer / internship / field facilitator / tender forms,
with a full admin back office.

---

## Installation

1. Copy this folder to `wp-content/plugins/carewave-candidate-portal`.
2. Activate **Care Wave Candidate Portal** in *Plugins*.
3. Go to *Settings → Permalinks* and click **Save** once (refreshes job URLs).

Activation creates automatically:

* the **Care Wave Candidate** user role,
* six database tables (`wp_cwcp_*`),
* every portal page with its shortcode already inside,
* starter job categories and job types,
* a protected uploads folder `wp-content/uploads/carewave-documents`.

Add the pages you want visitors to see (Jobs, Login, Registration, Volunteer
Form, Internship Form, Field Facilitator Form, Tenders) to your menu.
The full list with links is in *Care Wave → Overview*.

---

## Candidate flow

1. **Register** — name, email, mobile, password. The account is created and the
   candidate lands on the profile page.
2. **Complete the account** — required before applying:
   Full Name, Father Name, Email, Mobile, CNIC, Date of Birth, Gender, Religion,
   Marital Status, Province, District, Address **and an uploaded Resume**.
   Current Position, Current Organization, Expected Salary, LinkedIn and
   Career Objective are optional.
3. **Add Education / Experience / Skills** — optional but shown to the hiring team.
4. **Browse Jobs → Easy Apply** — one click. The candidate's profile, education,
   experience, skills and resume are attached automatically; no forms to fill.
   If the account is incomplete the button becomes
   *"Complete Account to Apply"* and links to the profile.
5. **Track and edit** — Applied Jobs shows the status of every application.
   Until the application is decided (Hired / Not Selected) the candidate can
   **edit** it - change the message to the hiring team and re-attach their
   latest profile, education, experience, skills and resume - or withdraw it.

Everything a candidate enters stays editable: the profile form saves in place,
education and experience rows have edit and delete actions, skills can be
re-added to change their level, and the resume can be replaced or removed.

The completeness percentage is shown in the sidebar of every portal screen.

---

## Admin flow

Everything lives under the **Care Wave** menu.

| Screen | What you can do |
| --- | --- |
| Overview | Counts by status, latest applications, list of portal pages |
| Applications | Filter by job / status / name, open a full application, download the resume, change status, keep internal notes, export CSV, delete |
| Candidates | Every registered candidate, completeness %, CNIC, district, resume, their applications. **Edit Profile** corrects any candidate detail (mistyped CNIC, wrong district) using the same validation as the portal |
| Jobs / Add New Job | Title + description, category, type, location, province, positions, salary, experience, education, preferred gender, deadline, open/closed. Edit or delete any posted job from *All Jobs* |
| Tenders | Tender or donation appeal: reference no., closing date, value, document link, open/closed |
| Form Submissions | Volunteer, internship, field facilitator and tender submissions with their attachments, statuses, notes and CSV export |
| Settings | Organization name, notification email, which emails go out, max upload size, **colours and login branding** (below), recreate missing pages, shortcode reference |

Changing an application's status emails the candidate (New, Under Review,
Shortlisted, Interview, Hired, Not Selected). Turn that off in *Settings*.

A job stops accepting applications when it is set to **Closed**, when its
deadline passes, or when it is unpublished.

---

## Colours and login branding

*Care Wave -> Settings -> Colours* lets an administrator match the portal to the
site theme without touching CSS.

* **Nine colours** are chosen: primary/brand, body text, muted text, page
  background, card background, borders, success, warning and danger.
  Everything else - hover shades, tinted badge backgrounds, focus rings, the
  readable text colour on top of a coloured button - is derived automatically,
  so a single brand colour is usually all you need to change.
* **Quick schemes** (Care Wave Blue, Teal, Forest Green, Maroon, Charcoal) fill
  the fields in one click.
* **From your theme** shows the palette your active theme declares - through
  `theme.json` on block themes, or `add_theme_support('editor-color-palette')`
  on classic themes - so you can pick the exact brand colour the rest of the
  site already uses.
* **Corner style** switches all cards, buttons and inputs between square,
  rounded (default) and extra rounded.
* A **live preview** on the settings screen shows a sample job card as you pick.

The palette is emitted as CSS custom properties, so it applies to every portal
screen and to the plugin's own admin pages. The defaults reproduce the stock
stylesheet exactly - nothing changes visually until a colour is picked.

### WordPress login screen

Candidates can sign in either at the portal login page or at `wp-login.php`
(both send them to the candidate dashboard). With **Style wp-login.php** enabled
- it is on by default - the WordPress login screen is restyled with the same
palette and your site logo (from *Customizer -> Site Identity*), the logo links
back to your site instead of wordpress.org, and a footer line points candidates
to the portal login and registration pages. Switch the option off to leave
`wp-login.php` completely untouched.

---

## Shortcodes

| Shortcode | Screen |
| --- | --- |
| `[carewave_register]` | Registration |
| `[carewave_login]` | Login |
| `[carewave_lost_password]` | Forgot password |
| `[carewave_reset_password]` | Reset password (link target) |
| `[carewave_dashboard]` | Candidate dashboard |
| `[carewave_profile]` | Profile / complete account |
| `[carewave_education]` | Education history |
| `[carewave_experience]` | Work experience |
| `[carewave_skills]` | Skills |
| `[carewave_resume]` | Resume upload |
| `[carewave_jobs]` | Job listing with search and filters |
| `[carewave_applied_jobs]` | Applications and their status |
| `[carewave_saved_jobs]` | Bookmarked jobs |
| `[carewave_volunteer_form]` | Volunteer form |
| `[carewave_internship_form]` | Internship form |
| `[carewave_field_facilitator_form]` | Field facilitator form |
| `[carewave_tenders]` | Tender / donation list and submission form |

Single job pages are rendered by your theme; the job summary and the apply box
are appended to the content automatically.

---

## Working with your theme

The portal renders inside whatever the active theme outputs, so a few
precautions keep the two from fighting:

* **All portal markup is scoped.** Every screen is wrapped in `.cwcp-page` or
  `.cwcp-scope`, and `assets/css/portal.css` opens with a theme isolation layer
  that resets typography, form controls and tables inside those wrappers. It is
  written with `:where()`, so it overrides a theme's inherited and element level
  styles while the portal's own component styles still win. Nothing outside the
  wrappers is touched.
* **Portal styles load after the theme** (`wp_enqueue_scripts`, priority 100),
  because otherwise a theme stylesheet printed later would win every equal
  specificity match.
* **Layout classes never go on `<body>`.** Single job and tender pages get an
  identifying `cwcp-single-job-page` body class only.
* Wrappers carry `alignwide`, so block themes such as Twenty Twenty-Five do not
  squeeze the portal into the narrow content column.

Single job pages are rendered by your theme; the plugin appends a two column
layout - description on the left, job summary and the apply panel in a sticky
rail on the right - which stacks on tablets and phones.

---

## Security notes

* Every form is nonce protected, sanitized on input and escaped on output; all
  database access uses prepared statements.
* Public forms carry a honeypot field and a per IP throttle.
* Resumes and tender documents are stored outside the media library in
  `uploads/carewave-documents`, are never linked directly, and are streamed by
  `?cwcp_doc=<id>` only to the owner or a portal manager. Only PDF, DOC and
  DOCX are accepted, with the size limit from *Settings*.
* The bundled `.htaccess` blocks direct file access on **Apache**. On **Nginx**
  add the equivalent rule to your server config:

  ```nginx
  location ^~ /wp-content/uploads/carewave-documents/ { deny all; }
  ```

* Candidates cannot reach `wp-admin`; they are redirected to their dashboard.
* A resume that a submitted application points at is never deleted, so the
  hiring team always sees the CV the candidate actually applied with.

---

## Data

| Table | Holds |
| --- | --- |
| `wp_cwcp_applications` | Job applications, status, notes, resume, profile snapshot at apply time |
| `wp_cwcp_education` | Education records |
| `wp_cwcp_experience` | Work experience |
| `wp_cwcp_skills` | Skills |
| `wp_cwcp_saved_jobs` | Bookmarked jobs |
| `wp_cwcp_submissions` | Volunteer, internship, field facilitator and tender submissions |

Deactivating the plugin removes nothing. Candidate accounts, applications and
submissions all survive.
"# Job-Portal-Plugin" 
