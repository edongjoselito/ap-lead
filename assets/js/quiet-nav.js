/**
 * Quiet Nav - seamless page navigation for the admin shell.
 *
 * Intercepts internal link clicks (and GET form submissions), fetches the
 * target page in the background, then swaps only the content region and the
 * topbar notification block. The sidebar, topbar menus, and footer scripts
 * stay put, so navigation feels instant with no full-page flash.
 *
 * Anything we cannot safely swap (login/logout screens, printable views,
 * file downloads, external links, failed fetches) falls back to a normal
 * browser navigation, so behavior is never worse than a regular click.
 */
(function () {
    'use strict';

    if (!window.jQuery || !window.fetch || !window.DOMParser ||
        !window.history || !window.history.pushState) {
        return;
    }

    var $ = window.jQuery;
    var CONTENT_SEL = '.content-page';
    var TOPNAV_SEL = '.navbar-custom > ul.topnav-menu.float-right';
    var NAV_NS = '.llcmQuietNav';
    var PER_NAV_SRC = /\/assets\/js\/pages\/|\.init\.js(?:$|\?)/i;
    var SKIP_EXT = /\.(pdf|zip|rar|docx?|xlsx?|pptx?|csv|png|jpe?g|gif|svg|ico|webp|mp4|webm|txt|sql)(\?|#|$)/i;
    var SKIP_PATH = /\/(assets|uploads|resources|system|vendor)\//i;
    var TIMEOUT_MS = 15000;

    var contentEl = document.querySelector(CONTENT_SEL);
    if (!contentEl) {
        return;
    }

    /* ------------------------------------------------ progress indicator */

    var progressEl = document.createElement('div');
    progressEl.id = 'llcm-quiet-progress';
    var style = document.createElement('style');
    style.textContent =
        '#llcm-quiet-progress{position:fixed;top:0;left:0;height:2px;width:0;' +
        'background:#8b1e3f;z-index:100000;opacity:0;pointer-events:none;' +
        'transition:width .25s ease,opacity .3s ease}' +
        '.llcm-quiet-fade{animation:llcmQuietFade .18s ease}' +
        '@keyframes llcmQuietFade{from{opacity:.45}to{opacity:1}}';
    document.head.appendChild(style);
    document.body.appendChild(progressEl);

    var progressTimer = null;

    function progressStart() {
        progressEl.style.transition = 'none';
        progressEl.style.width = '0';
        progressEl.style.opacity = '1';
        void progressEl.offsetWidth;
        progressEl.style.transition = 'width .25s ease,opacity .3s ease';
        progressEl.style.width = '20%';
        clearTimeout(progressTimer);
        progressTimer = setTimeout(function () {
            progressEl.style.width = '75%';
        }, 400);
    }

    function progressDone() {
        clearTimeout(progressTimer);
        progressEl.style.width = '100%';
        setTimeout(function () {
            progressEl.style.opacity = '0';
            setTimeout(function () { progressEl.style.width = '0'; }, 320);
        }, 120);
    }

    /* ---------------------- page-scoped handler collection --------------
     * Inline page scripts bind handlers (often on document). With quiet nav
     * the document persists between pages, so handlers bound while running a
     * page's scripts get the NAV_NS namespace and are removed on the next
     * nav. Otherwise they would stack and fire multiple times. */

    var collecting = false;
    var nativeBindings = [];
    var pendingTimers = [];
    var deferredReady = [];

    function withCollecting(fn) {
        return function () {
            var prev = collecting;
            collecting = true;
            try {
                return fn.apply(this, arguments);
            } finally {
                collecting = prev;
            }
        };
    }

    // Fires callbacks that were registered for document lifecycle events
    // (DOMContentLoaded / window load / ready). Those events only fire once
    // per real load, so quiet-nav replays them after page scripts finish.
    function runDeferredReady() {
        var queue = deferredReady;
        deferredReady = [];
        collecting = true;
        try {
            queue.forEach(function (fn) {
                try {
                    fn(window.jQuery);
                } catch (err) {
                    if (window.console && console.error) {
                        console.error('quiet-nav ready handler:', err);
                    }
                }
            });
        } finally {
            collecting = false;
        }
    }

    var origOn = $.fn.on;
    $.fn.on = function (types) {
        if (collecting && typeof types === 'string') {
            var args = Array.prototype.slice.call(arguments);

            // Lifecycle events that already fired for this document would
            // never re-fire during a quiet nav; defer the handler instead of
            // binding a dead listener. Covers $(window).on('load') and
            // $(document).on('DOMContentLoaded'|'readystatechange').
            var lifecycleRe = /^(load|DOMContentLoaded|readystatechange)(\.\S*)?$/;
            var target0 = this.length ? this[0] : null;
            if ((target0 === window || target0 === document) &&
                lifecycleRe.test(types.trim())) {
                var fn = null;
                for (var i = args.length - 1; i >= 0; i--) {
                    if (typeof args[i] === 'function') {
                        fn = args[i];
                        break;
                    }
                }
                if (fn) {
                    deferredReady.push(withCollecting(fn));
                }
                return this;
            }

            args[0] = types.split(/\s+/).map(function (t) {
                return t && t.indexOf(NAV_NS) === -1 ? t + NAV_NS : t;
            }).join(' ');
            return origOn.apply(this, args);
        }
        return origOn.apply(this, arguments);
    };

    // jQuery ready callbacks registered during a quiet nav are queued and
    // flushed after every script has run, mirroring real load order.
    var origReady = $.fn.ready;
    if (origReady) {
        $.fn.ready = function (fn) {
            if (collecting && typeof fn === 'function') {
                deferredReady.push(withCollecting(fn));
                return this;
            }
            return origReady.call(this, fn);
        };
    }

    /*
     * Many pages ship both a page-specific $('#datatable').DataTable(cfg)
     * and the shared datatables.init.js $("#datatable").DataTable(). On a
     * quiet nav both run, and DataTables' default errMode shows an alert()
     * for a re-init. When every matched node is already initialized we
     * silently return the existing API instead (first config wins), which
     * matches the full-load outcome without the dialog. Only applies while
     * collecting so intentional re-inits elsewhere behave normally.
     */
    var dataTableGuarded = false;

    function ensureDataTableGuard() {
        if (dataTableGuarded || !$.fn.DataTable || !$.fn.dataTable ||
            !$.fn.dataTable.isDataTable) {
            return;
        }
        dataTableGuarded = true;
        var origDataTable = $.fn.DataTable;
        $.fn.DataTable = function (opts) {
            if (collecting && this.length) {
                var allInit = true;
                this.each(function () {
                    if (!$.fn.dataTable.isDataTable(this)) {
                        allInit = false;
                    }
                });
                if (allInit) {
                    return origDataTable.call(this);
                }
            }
            return origDataTable.apply(this, arguments);
        };
    }

    var timerClear = {
        setTimeout: 'clearTimeout',
        setInterval: 'clearInterval',
        requestAnimationFrame: 'cancelAnimationFrame'
    };

    ['setTimeout', 'setInterval', 'requestAnimationFrame'].forEach(function (name) {
        var orig = window[name];
        if (!orig) {
            return;
        }
        window[name] = function (fn) {
            var args = Array.prototype.slice.call(arguments);
            if (collecting && typeof fn === 'function') {
                args[0] = withCollecting(fn);
            }
            var id = orig.apply(window, args);
            if (collecting) {
                pendingTimers.push({ id: id, kind: name });
            }
            return id;
        };
    });

    var origAddEvent = EventTarget.prototype.addEventListener;
    EventTarget.prototype.addEventListener = function (type, listener, options) {
        if (collecting && listener) {
            var onDoc = this === document;
            var onWin = this === window;
            // Lifecycle listeners registered during a quiet nav would never
            // fire; run them deferred after the page's scripts instead.
            if ((onDoc && (type === 'DOMContentLoaded' || type === 'readystatechange')) ||
                ((onDoc || onWin) && (type === 'load' || type === 'pageshow'))) {
                var cb = typeof listener === 'function' ? listener :
                    (listener && typeof listener.handleEvent === 'function' ?
                        function (e) { listener.handleEvent(e); } : null);
                if (cb) {
                    deferredReady.push(withCollecting(function () {
                        cb({ type: type, target: onDoc ? document : window });
                    }));
                }
                return;
            }
            if (onWin || onDoc ||
                this === document.body || this === document.documentElement) {
                nativeBindings.push({
                    target: this, type: type, listener: listener, options: options
                });
            }
        }
        return origAddEvent.call(this, type, listener, options);
    };

    // Body children that are part of the shell and must survive quiet navs.
    var persistentBody = new Set();

    function snapshotBody() {
        persistentBody = new Set(document.body.children);
    }

    function sweepPageHandlers() {
        $(window).off(NAV_NS);
        $(document).off(NAV_NS);
        $(document.documentElement).off(NAV_NS);
        $(document.body).off(NAV_NS);
        $(document.body).find('*').off(NAV_NS);

        nativeBindings.forEach(function (b) {
            b.target.removeEventListener(b.type, b.listener, b.options);
        });
        nativeBindings = [];

        // Kill timers/intervals the old page scheduled; they belong to it.
        pendingTimers.forEach(function (t) {
            var clear = window[timerClear[t.kind]];
            if (clear) {
                clear(t.id);
            }
        });
        pendingTimers = [];

        // Close any modal left open so its backdrop doesn't linger.
        try {
            $('.modal.show, .modal.in').modal('hide');
        } catch (e) { /* noop */ }

        // Remove body-level leftovers (toasts, backdrops, plugin overlays)
        // that were appended after the last snapshot.
        Array.prototype.forEach.call(document.body.children, function (el) {
            if (!persistentBody.has(el) && el !== progressEl) {
                el.parentNode.removeChild(el);
            }
        });

        // Drop Chart.js instances whose canvas was removed with old content.
        if (window.Chart && Chart.instances) {
            Object.keys(Chart.instances).forEach(function (key) {
                var inst = Chart.instances[key];
                if (!inst || !inst.canvas || !document.contains(inst.canvas)) {
                    try {
                        if (inst && inst.destroy) {
                            inst.destroy();
                        }
                    } catch (e) { /* noop */ }
                }
            });
        }

        // Drop DataTables settings whose table node left with old content,
        // so the registry does not grow with every quiet nav.
        try {
            if ($.fn.dataTable && $.fn.dataTable.settings) {
                var settings = $.fn.dataTable.settings;
                for (var i = settings.length - 1; i >= 0; i--) {
                    var nTable = settings[i] && settings[i].nTable;
                    if (nTable && !document.contains(nTable)) {
                        settings.splice(i, 1);
                    }
                }
            }
        } catch (e) { /* noop */ }
    }

    /* ------------------------------------------------ script bookkeeping */

    var loadedSrcs = {};
    var seenOuterInline = [];

    /*
     * Some page views self-include framework libraries (e.g. a CDN jQuery
     * or bootstrap bundle inside the content). Re-running them under quiet
     * nav is destructive: a second jQuery replaces window.$ and wipes out
     * plugin registrations and our handler namespaces, and re-loading
     * bootstrap re-binds its delegated data-api handlers so toggles fire
     * twice. The footer bundle already provides both, so page-level copies
     * are skipped once the framework is present.
     */
    var semLoaded = {};
    if (window.jQuery) {
        semLoaded.jquery = true;
    }
    if (window.bootstrap || ($.fn && $.fn.modal)) {
        semLoaded.bootstrap = true;
    }

    function semanticLibKey(src) {
        var base = src.split('?')[0].split('#')[0].split('/').pop().toLowerCase();
        if (/^jquery([-.][0-9][0-9.]*)?([-.]min)?\.js$/.test(base)) {
            return 'jquery';
        }
        if (/^bootstrap([-.]bundle)?([-.]min)?\.js$/.test(base)) {
            return 'bootstrap';
        }
        return null;
    }

    function absUrl(src) {
        try {
            return new URL(src, window.location.href).href;
        } catch (e) {
            return src;
        }
    }

    function seedBookkeeping() {
        Array.prototype.forEach.call(document.querySelectorAll('script[src]'), function (el) {
            loadedSrcs[absUrl(el.getAttribute('src'))] = true;
        });
        Array.prototype.forEach.call(document.querySelectorAll('script:not([src])'), function (el) {
            if (!el.closest(CONTENT_SEL) && !el.closest(TOPNAV_SEL)) {
                seenOuterInline.push(el.text || el.textContent || '');
            }
        });
    }

    function isJsScript(el) {
        var type = (el.getAttribute('type') || '').toLowerCase();
        return type === '' || type === 'text/javascript' || type === 'application/javascript' ||
            type === 'module';
    }

    /* --------------------------------------------------- script running */

    function execInlineText(text, type, parent) {
        var live = document.createElement('script');
        if (type) {
            live.setAttribute('type', type);
        }
        live.text = text;
        var prev = collecting;
        collecting = true;
        try {
            parent.appendChild(live);
        } finally {
            collecting = prev;
        }
    }

    function execInlineNode(node) {
        // Replace an inert script element in the live DOM with a fresh one so
        // the browser actually executes it, keeping its position in the tree.
        var live = document.createElement('script');
        var type = node.getAttribute('type');
        if (type) {
            live.setAttribute('type', type);
        }
        live.text = node.text || node.textContent || '';
        var prev = collecting;
        collecting = true;
        try {
            node.parentNode.replaceChild(live, node);
        } finally {
            collecting = prev;
        }
    }

    function execSrc(src, sem) {
        return new Promise(function (resolve) {
            var el = document.createElement('script');
            el.src = src;
            var prev = collecting;
            collecting = true;
            el.onload = function () {
                collecting = prev;
                if (sem) {
                    semLoaded[sem] = true;
                }
                resolve();
            };
            el.onerror = function () {
                collecting = prev;
                delete loadedSrcs[src];
                resolve();
            };
            document.body.appendChild(el);
        });
    }

    /**
     * Build the ordered list of script steps for the fetched document, then
     * run them in document order so dependencies behave like a real load.
     *
     * - src under assets/js/pages/ or *.init.js  -> re-run every nav
     * - other src                                 -> load once, ever
     * - inline inside swapped regions             -> re-run every nav
     * - inline elsewhere (footer snippets)        -> run once, ever
     */
    function runScripts(doc, swappedContent, swappedTopnav) {
        var fetched = Array.prototype.slice.call(doc.querySelectorAll('script'))
            .filter(isJsScript);

        var contentInerts = swappedContent ?
            Array.prototype.slice.call(swappedContent.querySelectorAll('script')).filter(isJsScript) : [];
        var topnavInerts = swappedTopnav ?
            Array.prototype.slice.call(swappedTopnav.querySelectorAll('script')).filter(isJsScript) : [];

        var steps = [];
        var contentIdx = 0;
        var topnavIdx = 0;

        fetched.forEach(function (el) {
            var src = el.getAttribute('src');
            var inContent = !!el.closest(CONTENT_SEL);
            var inTopnav = !!el.closest(TOPNAV_SEL);
            // Every script inside a swapped region occupies a slot in the
            // inert node list regardless of how we choose to run it.
            var slot = inContent ? contentIdx++ : (inTopnav ? topnavIdx++ : -1);

            if (src) {
                var abs = absUrl(src);
                var sem = semanticLibKey(abs);
                if (sem && semLoaded[sem]) {
                    // Framework already provided by the shell; the page's
                    // own copy would clobber it. Mark seen and skip.
                    loadedSrcs[abs] = true;
                    return;
                }
                if (PER_NAV_SRC.test(abs) || !loadedSrcs[abs]) {
                    loadedSrcs[abs] = true;
                    steps.push({ kind: 'src', url: abs, sem: sem });
                }
                return;
            }

            if (inContent) {
                steps.push({ kind: 'content-inline', idx: slot });
            } else if (inTopnav) {
                steps.push({ kind: 'topnav-inline', idx: slot });
            } else {
                var text = el.text || el.textContent || '';
                if (seenOuterInline.indexOf(text) === -1) {
                    seenOuterInline.push(text);
                    steps.push({ kind: 'outer-inline', text: text });
                }
            }
        });

        var chain = Promise.resolve();
        steps.forEach(function (step) {
            chain = chain.then(function () {
                try {
                    if (step.kind === 'src') {
                        return execSrc(step.url, step.sem);
                    }
                    if (step.kind === 'content-inline') {
                        if (contentInerts[step.idx]) {
                            execInlineNode(contentInerts[step.idx]);
                        }
                        return null;
                    }
                    if (step.kind === 'topnav-inline') {
                        if (topnavInerts[step.idx]) {
                            execInlineNode(topnavInerts[step.idx]);
                        }
                        return null;
                    }
                    execInlineText(step.text, null, document.body);
                    return null;
                } catch (err) {
                    // A page script error should not break quiet navigation.
                    if (window.console && console.error) {
                        console.error('quiet-nav page script:', err);
                    }
                    return null;
                }
            });
        });
        return chain;
    }

    /* -------------------------------------------------- menu highlight */

    function highlightSideMenu(url) {
        var pageUrl = (url || window.location.href).split(/[?#]/)[0];
        $('#side-menu li').removeClass('mm-active');
        $('#side-menu ul').removeClass('mm-show');
        $('#side-menu a').removeClass('active');
        $('#side-menu a').each(function () {
            if (this.href === pageUrl) {
                $(this).addClass('active');
                $(this).parent().addClass('mm-active');
                $(this).parent().parent().addClass('mm-show');
                $(this).parent().parent().prev().addClass('active');
                $(this).parent().parent().parent().addClass('mm-active');
                $(this).parent().parent().parent().parent().addClass('mm-show');
                $(this).parent().parent().parent().parent().parent().addClass('mm-active');
            }
        });
    }

    /* ----------------------------------------------------- navigation */

    var navSeq = 0;
    var inFlight = null;

    function hardNav(url) {
        window.location.assign(url);
    }

    function navigate(url, opts) {
        opts = opts || {};
        var seq = ++navSeq;

        if (inFlight) {
            inFlight.abort();
        }
        var controller = 'AbortController' in window ? new AbortController() : null;
        inFlight = controller;

        if (opts.push !== false) {
            try {
                history.replaceState({
                    quietNav: true,
                    scrollY: window.scrollY || window.pageYOffset || 0
                }, '', window.location.href);
            } catch (e) { /* noop */ }
        }

        progressStart();

        var timedOut = false;
        var timer = setTimeout(function () {
            timedOut = true;
            if (controller) {
                controller.abort();
            }
        }, TIMEOUT_MS);

        fetch(url, {
            credentials: 'same-origin',
            redirect: 'follow',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Quiet-Nav': '1'
            },
            signal: controller ? controller.signal : undefined
        }).then(function (res) {
            clearTimeout(timer);
            if (seq !== navSeq) {
                return null;
            }
            var type = res.headers.get('Content-Type') || '';
            if (!res.ok || type.indexOf('text/html') === -1) {
                hardNav(res.url || url);
                return null;
            }
            return res.text().then(function (html) {
                return { html: html, finalUrl: res.url || url };
            });
        }).then(function (payload) {
            if (!payload || seq !== navSeq) {
                return null;
            }
            var doc = new DOMParser().parseFromString(payload.html, 'text/html');
            var newContent = doc.querySelector(CONTENT_SEL);
            if (!newContent) {
                hardNav(payload.finalUrl);
                return null;
            }
            var newTopnav = doc.querySelector(TOPNAV_SEL);
            var liveTopnav = document.querySelector(TOPNAV_SEL);

            sweepPageHandlers();
            contentEl.innerHTML = newContent.innerHTML;
            if (newTopnav && liveTopnav) {
                liveTopnav.innerHTML = newTopnav.innerHTML;
            }
            if (doc.title) {
                document.title = doc.title;
            }
            // Update the URL before scripts run so any code reading
            // window.location sees the page being navigated to.
            if (opts.push !== false) {
                try {
                    history.pushState({ quietNav: true, scrollY: 0 }, '', payload.finalUrl);
                } catch (e) { /* noop */ }
            }
            highlightSideMenu(payload.finalUrl);

            contentEl.classList.remove('llcm-quiet-fade');
            void contentEl.offsetWidth;
            contentEl.classList.add('llcm-quiet-fade');

            // Re-run theme plugin init (tooltips, popovers, slimscroll,
            // counterup, custommodal, ...) against the swapped-in content,
            // like app.min.js does on a real load.
            try {
                if ($.Components && $.Components.init) {
                    $.Components.init();
                }
                if (window.Waves && Waves.attach) {
                    Waves.attach('.waves-effect');
                }
            } catch (e) { /* noop */ }
            ensureDataTableGuard();

            // Keep collecting for the whole script phase so handlers bound
            // by page scripts (sync or deferred) are always page-scoped.
            collecting = true;
            return runScripts(doc, contentEl, liveTopnav).then(function () {
                runDeferredReady();
                // Give engines one extra macrotask so any late-queued script
                // still lands inside the collection window.
                return new Promise(function (res) { setTimeout(res, 0); });
            }).then(function () {
                collecting = false;
                if (seq !== navSeq) {
                    return;
                }
                var y = typeof opts.scroll === 'number' ? opts.scroll : 0;
                window.scrollTo(0, y);
                progressDone();
            });
        }).catch(function (err) {
            clearTimeout(timer);
            if (seq !== navSeq) {
                return;
            }
            if (err && err.name === 'AbortError' && !timedOut) {
                // Aborted because a newer navigation took over.
                return;
            }
            hardNav(url);
        });
    }

    /* --------------------------------------------------- event wiring */

    document.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0 ||
            e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
            return;
        }
        var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
        if (!a) {
            return;
        }
        var raw = a.getAttribute('href') || '';
        if (a.target || a.hasAttribute('download') || a.hasAttribute('data-no-pjax') ||
            a.hasAttribute('data-toggle') || a.hasAttribute('data-target') ||
            a.hasAttribute('data-dismiss') ||
            raw === '' || raw.charAt(0) === '#' ||
            /^(javascript|mailto|tel):/i.test(raw)) {
            return;
        }
        var url;
        try {
            url = new URL(raw, window.location.href);
        } catch (err) {
            return;
        }
        if (url.origin !== window.location.origin ||
            SKIP_EXT.test(url.pathname + url.search) ||
            SKIP_PATH.test(url.pathname)) {
            return;
        }
        // Same page + different hash: let the browser do a normal anchor jump.
        if (url.pathname === window.location.pathname &&
            url.search === window.location.search && url.hash !== '') {
            return;
        }
        e.preventDefault();
        navigate(url.href);
    });

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.target || form.hasAttribute('data-no-pjax') ||
            (form.method || 'get').toLowerCase() !== 'get' ||
            form.querySelector('input[type="file"]')) {
            return;
        }
        var url;
        try {
            url = new URL(form.getAttribute('action') || window.location.href,
                window.location.href);
        } catch (err) {
            return;
        }
        if (url.origin !== window.location.origin ||
            SKIP_EXT.test(url.pathname) || SKIP_PATH.test(url.pathname)) {
            return;
        }
        e.preventDefault();
        url.search = new URLSearchParams(new FormData(form)).toString();
        url.hash = '';
        navigate(url.href);
    });

    window.addEventListener('popstate', function (e) {
        var y = e.state && typeof e.state.scrollY === 'number' ? e.state.scrollY : 0;
        navigate(window.location.href, { push: false, scroll: y });
    });

    /* --------------------------------------------------------- startup */

    $(function () {
        // Wait for the parser to reach the end of the page so footer inline
        // scripts are present when we snapshot what already ran.
        seedBookkeeping();
        snapshotBody();
        ensureDataTableGuard();

        /*
         * Page views run before the footer loads jQuery, so inline scripts
         * that call $()/jQuery() die at parse time on a full load. Re-run
         * just those once, now that everything is loaded, so behavior
         * matches a quiet nav visit to the same page. A script preceded by
         * its own jQuery include was NOT dead (it ran at parse), so it is
         * skipped to avoid double-binding its handlers.
         */
        var allScripts = document.scripts;
        Array.prototype.forEach.call(
            contentEl.querySelectorAll('script:not([src])'),
            function (node) {
                if (!isJsScript(node)) {
                    return;
                }
                var text = node.text || node.textContent || '';
                if (!/(\$|jQuery)\s*\(/.test(text)) {
                    return;
                }
                var hadJQuery = false;
                for (var i = 0; i < allScripts.length; i++) {
                    var s = allScripts[i];
                    if (s === node) {
                        break;
                    }
                    var ssrc = s.getAttribute('src');
                    if (ssrc && semanticLibKey(absUrl(ssrc)) === 'jquery') {
                        hadJQuery = true;
                        break;
                    }
                }
                if (hadJQuery) {
                    return;
                }
                try {
                    execInlineNode(node);
                } catch (err) {
                    if (window.console && console.error) {
                        console.error('quiet-nav boot script:', err);
                    }
                }
            }
        );
        runDeferredReady();
    });

    try {
        history.replaceState({
            quietNav: true,
            scrollY: window.scrollY || window.pageYOffset || 0
        }, '', window.location.href);
    } catch (e) { /* noop */ }
})();
