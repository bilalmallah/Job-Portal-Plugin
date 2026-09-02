# CareerHub

A complete job portal for WordPress: candidate accounts, profiles, one click
job applications, and volunteer / internship / field facilitator / tender forms,
with a full admin back office.

---

## Installation

1. Copy this folder to `wp-content/plugins/carewave-candidate-portal`.
2. Activate **CareerHub** in *Plugins*.
3. Go to *Settings → Permalinks* and click **Save** once (refreshes job URLs).

Activation creates automatically:

* the **Candidate** user role,
* six database tables (`wp_cwcp_*`),
* starter job categories and job types,
* a protected uploads folder `wp-content/uploads/carewave-documents`.

It then opens the **setup wizard**, which decides the rest.

Add the pages you want visitors to see (Jobs, Login, Registration, Volunteer
Form, Internship Form, Field Facilitator Form, Tenders) to your menu.
The full list with links is in *CareerHub → Overview*.

---

## Setup wizard

On a first activation the plugin opens *CareerHub → Setup Wizard* instead of
creating pages unasked. It is two questions:

**1. How should the portal pages be built?**

| Choice | What happens |
| --- | --- |
| **Create everything for me** | Inserts all 17 pages now, each holding one shortcode. Menu links, login redirects and emails point at them immediately. |
| **I will build them in Elementor** | Creates nothing. You add each screen yourself with the CareerHub widgets. The wizard's last step lists the slug each screen must live at. |

The answer is stored as `cwcp_page_mode`, and it sticks: a manual site is never
topped up with shortcode pages behind your back, on upgrade or re-activation.

**2. Which theme?**

Five presets (Classic Blue, Teal, Forest Green, Maroon, Charcoal), an optional
custom brand colour that overrides the preset, and a corner style. This writes
the same `cwcp_settings` the *Settings* screen edits, so nothing is duplicated
and every value stays editable afterwards.

Re-run it any time from *CareerHub → Setup Wizard*. Re-activating an existing
install does **not** reopen it, and does not touch pages you already have.

### Logos

Two optional brand files live in `assets/images/`:

| File | Shows in |
| --- | --- |
| `careerhub-logo.png` | Setup wizard header, WordPress admin menu icon |
| `bm-infinity-logo.png` | Setup wizard footer |

Both degrade gracefully - a missing file falls back to a dashicon or is simply
omitted, never a broken image. See `assets/images/README.md`.

---

## Candidate flow

1. **Register** — name, email, mobile, password. The account is created and the
   candidate lands on the profile page.
2. **Complete the account** — required before applying:
   Full Name, Father Name, Email, Mobile, CNIC, Date of Birth, Gender, Religion,
   Marital Status, Province, District, Address, **an uploaded Resume, at least
   one Education record and an answered work history**. A candidate with no work
   history yet ticks *"I am a fresh candidate"* on the Experience screen, so
   fresh graduates can still reach 100%.
   Current Position, Current Organization, Expected Salary, LinkedIn, Career
   Objective, Skills and the profile photo are optional.
   A **profile photo** can be uploaded on the profile screen; it becomes the
   candidate's avatar across the portal and the admin screens.
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

Nothing typed is lost on the way: the resume button on the profile screen
**saves the form first and then opens the resume screen**, and leaving any long
form with unsaved edits asks for confirmation first.

The completeness percentage is shown in the sidebar of every portal screen.

---

## Admin flow

Everything lives under the **CareerHub** menu.

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

*CareerHub -> Settings -> Colours* lets an administrator match the portal to the
site theme without touching CSS.

* **Nine colours** are chosen: primary/brand, body text, muted text, page
  background, card background, borders, success, warning and danger.
  Everything else - hover shades, tinted badge backgrounds, focus rings, the
  readable text colour on top of a coloured button - is derived automatically,
  so a single brand colour is usually all you need to change.
* **Quick schemes** (Classic Blue, Teal, Forest Green, Maroon, Charcoal) fill
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

Every shortcode also answers to a `careerhub_` prefix (`[careerhub_login]`);
the `carewave_` names above are kept so existing pages keep working.

---

## Elementor

With Elementor active the plugin adds a **CareerHub** section to the widget
panel holding one widget per screen - the three application forms, the job
listing, tenders, the account screens and every candidate portal screen.

Each widget renders through the same callback as its shortcode, so the two can
never drift apart, and adds these panel controls:

| Tab | Controls |
| --- | --- |
| Content | Show or hide the screen heading. The three application forms also take a custom title, subtitle and intro. |
| Layout | Content width, alignment, padding, screen background |
| Colors | Brand, brand hover, brand tint, body and muted text, borders, base typography, success / error / warning |
| Screen heading | Alignment, title and subtitle colour and typography, spacing |
| Cards | Background, border, radius, padding, box shadow |
| Form fields | Label and input typography, text, background, border and focus colours, radius, height, padding, gap, column count |
| Buttons | Typography, normal and hover colours, padding, radius, full width |

The colour controls write into the same CSS custom properties the portal is
built from (`--cwcp-primary` and friends), set on the widget wrapper. So an
Elementor override is scoped to that one widget, and everything else on the
site keeps the palette from *Portal > Settings*.

Inside a widget the screen drops its own page chrome - the tinted full width
band, the vertical padding and the 1200px column - because the Elementor
container already provides all three. The Layout tab is where you put it back.

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
* **Wrappers centre themselves.** They used to carry `alignwide` to escape the
  narrow content column of block themes, but that class means whatever the
  active theme decides it means - Hello Elementor, for one, gives it
  `margin-inline: -80px`, which drags a `width: 100%` wrapper 80px to the left
  instead of widening it, and the whole portal looks knocked off centre. The
  wrappers now set their own `width`, `max-width`, `margin` and `float`, and a
  two class rule handles the constrained containers of block themes.

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
