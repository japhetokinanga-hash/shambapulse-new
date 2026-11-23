/* ShambaPulse — js/main.js
 *
 * The only JavaScript file for the site. Plain JavaScript, no libraries,
 * no internet needed. It only ADDS behaviour on top of the pages:
 *
 *  - Prices: type to search the prices table, click a column heading
 *    to sort a table, change the Commodity filter to filter at once.
 *  - Login / register: the same checks the server already does
 *    (phone number, 8+ character password, passwords match, consent),
 *    shown before the form is sent. The server still checks everything.
 *  - Contact: a live character count for the message box.
 *  - My account: asks "Are you sure?" before opting out of alerts.
 *  - USSD simulator: the reply box is focused, and only 0-9 and * go in.
 *  - Every form: the button cannot be clicked twice by accident.
 *
 * Nothing in the PHP files, the HTML words, or css/style.css was
 * changed for this. The site works exactly as before if JavaScript
 * is off — every form still sends to the server in the normal way.
 * To remove JavaScript, delete this file and the one
 * <script src="js/main.js"> line in includes/bootstrap.php.
 */
(function () {
    'use strict';

    // Run one small job. If a job fails, the others still run.
    function safe(fn) {
        try { fn(); } catch (e) { /* one broken feature must not break the page */ }
    }

    function text(el) {
        return (el.textContent || '').trim();
    }

    /* ---------- 1. Prices: search box, live filter, sortable tables ----- */

    // Add a search box above the first prices table. It filters the rows
    // as you type; clearing it shows every row again.
    function priceSearch() {
        var table = document.querySelector('.split .content table');
        if (!table || !table.tBodies[0]) return;
        var rows = table.tBodies[0].rows;
        if (rows.length === 0) return;

        var box = document.createElement('input');
        box.type = 'search';
        box.placeholder = 'Search prices';
        box.setAttribute('aria-label', 'Search prices');
        box.style.marginBottom = '0.75rem';
        table.parentNode.insertBefore(box, table);

        // Reuses the page's own "no match" wording.
        var none = document.createElement('p');
        none.className = 'empty';
        none.textContent = 'No prices match that search.';
        none.hidden = true;
        table.parentNode.insertBefore(none, table.nextSibling);

        box.addEventListener('input', function () {
            var q = box.value.trim().toLowerCase();
            var shown = 0;
            for (var i = 0; i < rows.length; i++) {
                var hit = q === '' || text(rows[i]).toLowerCase().indexOf(q) !== -1;
                rows[i].hidden = !hit;
                if (hit) shown++;
            }
            none.hidden = shown !== 0;
        });
    }

    // The Commodity filter on the Prices page filters the table at once,
    // without waiting for the Filter button. The button still works,
    // and the County filter still uses the button (the table has no
    // county column, so the server must do that one).
    function priceFilter() {
        var select = document.querySelector('select[name="commodity"]');
        var table = document.querySelector('.split .content table');
        if (!select || !table || !table.tBodies[0]) return;
        select.addEventListener('change', function () {
            var want = select.value.trim().toLowerCase();
            var rows = table.tBodies[0].rows;
            for (var i = 0; i < rows.length; i++) {
                var cell = rows[i].cells[0];
                var hit = want === '' || (cell && text(cell).toLowerCase() === want);
                rows[i].hidden = !hit;
            }
        });
    }

    // Click a heading to sort any table on the page. Click again to
    // reverse it. Numbers (Ksh 5,000) and dates (21/11/2026) sort
    // as numbers and dates, not as plain text.
    function sortableTables() {
        var tables = document.querySelectorAll('main table');
        for (var t = 0; t < tables.length; t++) {
            (function (table) {
                var head = table.tHead;
                if (!head || !table.tBodies[0]) return;
                var cells = head.rows[0].cells;
                for (var c = 0; c < cells.length; c++) {
                    (function (cell, col) {
                        cell.style.cursor = 'pointer';
                        cell.title = 'Sort';
                        cell.addEventListener('click', function () {
                            var body = table.tBodies[0];
                            var rows = [];
                            for (var i = 0; i < body.rows.length; i++) rows.push(body.rows[i]);
                            var up = cell.getAttribute('aria-sort') !== 'ascending';
                            rows.sort(function (a, b) {
                                return compare(cellText(a, col), cellText(b, col)) * (up ? 1 : -1);
                            });
                            for (var j = 0; j < rows.length; j++) body.appendChild(rows[j]);
                            var all = head.rows[0].cells;
                            for (var k = 0; k < all.length; k++) all[k].removeAttribute('aria-sort');
                            cell.setAttribute('aria-sort', up ? 'ascending' : 'descending');
                        });
                    })(cells[c], c);
                }
            })(tables[t]);
        }
    }

    function cellText(row, col) {
        var cell = row.cells[col];
        return cell ? text(cell) : '';
    }

    // Turn "Ksh 5,000", "21/11/2026" or "5" into a sortable value.
    function compare(a, b) {
        var na = toNumber(a), nb = toNumber(b);
        if (na !== null && nb !== null) return na - nb;
        var da = toDate(a), db = toDate(b);
        if (da !== null && db !== null) return da - db;
        if (a < b) return -1;
        if (a > b) return 1;
        return 0;
    }

    function toNumber(s) {
        var cleaned = s.replace(/[^0-9.\-]/g, '');
        if (cleaned === '' || cleaned === '-' || cleaned === '.') return null;
        var n = Number(cleaned);
        return isNaN(n) ? null : n;
    }

    function toDate(s) {
        var m = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/);
        if (!m) return null;
        var d = new Date(Number(m[3]), Number(m[2]) - 1, Number(m[1]));
        return isNaN(d.getTime()) ? null : d.getTime();
    }

    /* ---------- 2. Login / register: check before sending --------------- */
    // Same rules, same messages as the server in login.php. The server
    // still checks everything again — this only saves a round trip.
    function registerChecks() {
        var form = document.querySelector('form[action^="login.php#register"]');
        if (!form) return;
        var phone = form.querySelector('#phone');
        var pw = form.querySelector('#rpw');
        var pw2 = form.querySelector('#rpw2');
        var consent = form.querySelector('input[name="consent"]');
        if (!phone || !pw || !pw2 || !consent) return;

        form.addEventListener('submit', function (ev) {
            var num = phone.value.replace(/[\s\-()]/g, '');
            if (num.charAt(0) === '0') num = '+254' + num.slice(1);
            else if (num.indexOf('254') === 0) num = '+' + num;
            phone.setCustomValidity(
                /^\+254[17]\d{8}$/.test(num)
                    ? ''
                    : 'Please enter a valid Kenyan phone number, for example 0712 345 678.'
            );
            pw.setCustomValidity(
                pw.value.length >= 8 ? '' : 'Your password must have at least 8 characters.'
            );
            pw2.setCustomValidity(
                pw.value === pw2.value ? '' : 'The two passwords do not match.'
            );
            consent.setCustomValidity(
                consent.checked ? '' : 'We need your consent under the Data Protection Act, 2019 to register you.'
            );
            if (!form.checkValidity()) {
                ev.preventDefault();
                form.reportValidity();
            }
        });
        // Clear an old message as soon as the user fixes the field.
        [phone, pw, pw2, consent].forEach(function (el) {
            el.addEventListener('input', function () { el.setCustomValidity(''); });
        });
    }

    /* ---------- 3. Contact: live character count ------------------------- */
    function messageCount() {
        var box = document.querySelector('#message');
        if (!box) return;
        var max = Number(box.getAttribute('maxlength')) || 2000;
        var count = document.createElement('p');
        count.className = 'note';
        box.parentNode.insertBefore(count, box.nextSibling);
        var update = function () {
            count.textContent = box.value.length + ' / ' + max + ' characters';
        };
        box.addEventListener('input', update);
        update();
    }

    /* ---------- 4. My account: confirm before opting out ----------------- */
    function optOutConfirm() {
        var forms = document.querySelectorAll('form');
        for (var i = 0; i < forms.length; i++) {
            (function (form) {
                var doField = form.querySelector('input[name="do"]');
                if (!doField) return;
                var action = doField.value;
                if (action !== 'optout_ward' && action !== 'subscribe') return;
                var isOptOut = action === 'optout_ward' ||
                    !form.querySelector('button[name="on"]');
                if (!isOptOut) return;
                form.addEventListener('submit', function (ev) {
                    if (!window.confirm('Are you sure you want to opt out of these alerts?')) {
                        ev.preventDefault();
                    }
                });
            })(forms[i]);
        }
    }

    /* ---------- 5. USSD simulator: focus, digits and * only -------------- */
    function ussdSimulator() {
        var reply = document.querySelector('.ussd-form input[name="reply"]');
        if (!reply) return;
        reply.focus();
        reply.addEventListener('input', function () {
            var clean = reply.value.replace(/[^0-9*]/g, '');
            if (clean !== reply.value) reply.value = clean;
        });
    }

    /* ---------- 6. Every form: no double-submit -------------------------- */
    function noDoubleSubmit() {
        var forms = document.querySelectorAll('form');
        for (var i = 0; i < forms.length; i++) {
            forms[i].addEventListener('submit', function (ev) {
                var form = ev.target;
                if (!form.checkValidity()) return; // let the browser show the error
                var btn = form.querySelector('button[type="submit"]');
                if (btn) btn.disabled = true;
            });
        }
    }

    safe(priceSearch);
    safe(priceFilter);
    safe(sortableTables);
    safe(registerChecks);
    safe(messageCount);
    safe(optOutConfirm);
    safe(ussdSimulator);
    safe(noDoubleSubmit);
})();
