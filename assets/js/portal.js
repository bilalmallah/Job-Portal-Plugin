/* =========================================================
   CARE WAVE CANDIDATE PORTAL
   Frontend behaviour: password toggles, province/district
   cascade, one click apply and saved jobs.
   ========================================================= */

(function () {
    'use strict';

    var settings = window.cwcpPortal || {};

    function ready(fn) {

        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function post(action, data, onDone) {

        var body = new URLSearchParams();

        body.append('action', action);
        body.append('nonce', settings.nonce || '');

        Object.keys(data || {}).forEach(function (key) {
            body.append(key, data[key]);
        });

        fetch(settings.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        })
            .then(function (response) { return response.json(); })
            .then(function (json) { onDone(json); })
            .catch(function () {
                onDone({ success: false, data: { message: 'Network error. Please try again.' } });
            });
    }

    function toast(message, type) {

        var box = document.createElement('div');

        box.className = 'cwcp-toast cwcp-toast-' + (type || 'info');
        box.textContent = message;

        document.body.appendChild(box);

        window.setTimeout(function () { box.classList.add('is-visible'); }, 10);

        window.setTimeout(function () {

            box.classList.remove('is-visible');

            window.setTimeout(function () { box.remove(); }, 300);

        }, 3500);
    }


    /* ---------------------------------------------------------
       Password visibility
       --------------------------------------------------------- */

    function initPasswordToggles() {

        document.querySelectorAll('.cwcp-password-toggle').forEach(function (button) {

            button.addEventListener('click', function () {

                var input = document.getElementById(button.getAttribute('data-target'));

                if (!input) {
                    return;
                }

                var showing = input.type === 'text';

                input.type = showing ? 'password' : 'text';

                button.innerHTML = showing
                    ? '<i class="fa-solid fa-eye"></i>'
                    : '<i class="fa-solid fa-eye-slash"></i>';
            });
        });
    }


    /* ---------------------------------------------------------
       Province -> District cascade
       --------------------------------------------------------- */

    function initDistrictCascade() {

        var province = document.querySelector('[data-cwcp-province]');
        var district = document.querySelector('[data-cwcp-district]');

        if (!province || !district) {
            return;
        }

        province.addEventListener('change', function () {

            var selected = district.value;

            district.disabled = true;

            post('cwcp_get_districts', { province: province.value }, function (response) {

                district.disabled = false;

                if (!response || !response.success) {
                    return;
                }

                district.innerHTML = '<option value="">-- Select --</option>';

                response.data.districts.forEach(function (name) {

                    var option = document.createElement('option');

                    option.value = name;
                    option.textContent = name;
                    option.selected = (name === selected);

                    district.appendChild(option);
                });
            });
        });
    }


    /* ---------------------------------------------------------
       Currently working checkbox
       --------------------------------------------------------- */

    function initExperienceForm() {

        var checkbox = document.getElementById('cwcp-currently-working');
        var endDate = document.getElementById('cwcp-end-date');

        if (!checkbox || !endDate) {
            return;
        }

        function sync() {

            endDate.disabled = checkbox.checked;

            if (checkbox.checked) {
                endDate.value = '';
            }
        }

        checkbox.addEventListener('change', sync);

        sync();
    }


    /* ---------------------------------------------------------
       One click apply
       --------------------------------------------------------- */

    function initApply() {

        document.querySelectorAll('.cwcp-apply-form').forEach(function (form) {

            form.addEventListener('submit', function (event) {

                var button = form.querySelector('.cwcp-apply-btn');

                /* Forms with a cover note post normally. */
                if (!button) {
                    return;
                }

                event.preventDefault();

                var jobId = button.getAttribute('data-job-id');

                button.disabled = true;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Applying…';

                post('cwcp_apply', { job_id: jobId }, function (response) {

                    if (response && response.success) {

                        button.outerHTML = '<span class="cwcp-btn-applied">'
                            + '<i class="fa-solid fa-circle-check"></i> Applied</span>';

                        toast(response.data.message, 'success');

                        return;
                    }

                    button.disabled = false;
                    button.innerHTML = '<i class="fa-solid fa-bolt"></i> Easy Apply';

                    var data = (response && response.data) || {};

                    toast(data.message || 'Something went wrong.', 'error');

                    if (data.redirect) {
                        window.setTimeout(function () { window.location.href = data.redirect; }, 1200);
                    }
                });
            });
        });
    }


    /* ---------------------------------------------------------
       Save / unsave a job
       --------------------------------------------------------- */

    function initSaveToggles() {

        document.querySelectorAll('.cwcp-save-toggle').forEach(function (button) {

            button.addEventListener('click', function () {

                button.disabled = true;

                post('cwcp_toggle_saved_job', { job_id: button.getAttribute('data-job-id') }, function (response) {

                    button.disabled = false;

                    if (!response || !response.success) {

                        var data = (response && response.data) || {};

                        toast(data.message || 'Something went wrong.', 'error');

                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }

                        return;
                    }

                    button.classList.toggle('is-saved', response.data.saved);

                    button.innerHTML = response.data.saved
                        ? '<i class="fa-solid fa-bookmark"></i>'
                        : '<i class="fa-regular fa-bookmark"></i>';

                    toast(response.data.message, 'success');
                });
            });
        });
    }


    /* ---------------------------------------------------------
       Input masks (CNIC + mobile)
       --------------------------------------------------------- */

    function initMasks() {

        var cnicInputs = document.querySelectorAll('input[name="cnic"]');

        cnicInputs.forEach(function (input) {

            input.addEventListener('input', function () {

                var digits = input.value.replace(/\D/g, '').slice(0, 13);

                var out = digits;

                if (digits.length > 5) {
                    out = digits.slice(0, 5) + '-' + digits.slice(5, 12);
                }

                if (digits.length > 12) {
                    out = digits.slice(0, 5) + '-' + digits.slice(5, 12) + '-' + digits.slice(12);
                }

                input.value = out;
            });
        });

        document.querySelectorAll('input[type="tel"]').forEach(function (input) {

            input.addEventListener('input', function () {
                input.value = input.value.replace(/[^\d+]/g, '').slice(0, 13);
            });
        });
    }


    /* ---------------------------------------------------------
       Unsaved changes guard

       Long forms such as the profile lose everything if a link is
       clicked before saving, so leaving with pending edits asks first.
       --------------------------------------------------------- */

    function initDirtyGuard() {

        var form = document.querySelector('[data-cwcp-dirty-guard]');

        if (!form) {
            return;
        }

        var dirty = false;

        form.addEventListener('input', function () { dirty = true; });
        form.addEventListener('change', function () { dirty = true; });

        form.addEventListener('submit', function () { dirty = false; });

        window.addEventListener('beforeunload', function (event) {

            if (!dirty) {
                return undefined;
            }

            event.preventDefault();

            /* Browsers show their own wording; a value is still required. */
            event.returnValue = '';

            return '';
        });
    }


    ready(function () {

        initPasswordToggles();
        initDistrictCascade();
        initExperienceForm();
        initApply();
        initSaveToggles();
        initMasks();
        initDirtyGuard();
    });

}());
