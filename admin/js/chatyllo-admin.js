(function($){
    'use strict';

    const C = window.chatylloAdmin || {};

    /* ── Toast ────────────────────────────────────────────────────── */
    function toast(msg, type) {
        type = type || 'success';
        const $t = $('#chatyllo-toast');
        $t.text(msg).removeClass('chatyllo-toast--success chatyllo-toast--error chatyllo-toast--info')
          .addClass('chatyllo-toast--' + type + ' chatyllo-toast--show');
        setTimeout(() => $t.removeClass('chatyllo-toast--show'), 3500);
    }

    /* ── AJAX helper ──────────────────────────────────────────────── */
    function ajax(action, data, onSuccess, onError) {
        data = data || {};
        data.action = action;
        data.nonce  = C.nonce;
        $.post(C.ajaxUrl, data, function(res) {
            if (res.success && onSuccess) onSuccess(res.data);
            else if (!res.success && onError) onError(res.data);
            else if (!res.success) toast(res.data?.message || C.i18n.error, 'error');
        }).fail(function() { toast(C.i18n.error, 'error'); });
    }

    /* ═══════════════════════════════════════════════════════════════
       TABS
       ═══════════════════════════════════════════════════════════════ */
    $(document).on('click', '.chatyllo-tab', function(e) {
        e.preventDefault();
        const tab = $(this).data('tab');
        $('.chatyllo-tab').removeClass('chatyllo-tab--active');
        $(this).addClass('chatyllo-tab--active');
        $('.chatyllo-tab-panel').removeClass('chatyllo-tab-panel--active');
        $('#' + tab).addClass('chatyllo-tab-panel--active');
    });

    /* ═══════════════════════════════════════════════════════════════
       SETTINGS
       ═══════════════════════════════════════════════════════════════ */
    $(document).on('submit', '#chatyllo-settings-form', function(e) {
        e.preventDefault();
        const $btn = $('#chatyllo-save-settings');
        $btn.prop('disabled', true).prepend('<span class="chatyllo-spinner"></span>');

        const settings = {};
        $(this).find('input, select, textarea').each(function() {
            const $el = $(this);
            const name = $el.attr('name');
            if (!name) return;
            // Skip temp picker fields that merge into exclude_ids.
            if (name === 'exclude_post_ids' || name === 'exclude_page_ids') return;
            if ($el.is(':checkbox')) {
                settings[name] = $el.is(':checked') ? true : false;
            } else if ($el.is(':radio')) {
                if ($el.is(':checked')) settings[name] = $el.val();
            } else {
                settings[name] = $el.val();
            }
        });

        // Merge exclude_post_ids + exclude_page_ids → exclude_ids.
        var pIds = ($('input[name="exclude_post_ids"]').val() || '').split(',').map(function(s){return s.trim();}).filter(Boolean);
        var gIds = ($('input[name="exclude_page_ids"]').val() || '').split(',').map(function(s){return s.trim();}).filter(Boolean);
        settings['exclude_ids'] = pIds.concat(gIds).join(', ');

        ajax('chatyllo_save_settings', { settings: JSON.stringify(settings) }, function(d) {
            toast(d.message || C.i18n.saved);
            $btn.prop('disabled', false).find('.chatyllo-spinner').remove();
        }, function(d) {
            toast(d?.message || C.i18n.error, 'error');
            $btn.prop('disabled', false).find('.chatyllo-spinner').remove();
        });
    });

    /* ═══════════════════════════════════════════════════════════════
       REINDEX
       ═══════════════════════════════════════════════════════════════ */
    $(document).on('click', '#chatyllo-btn-reindex', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).prepend('<span class="chatyllo-spinner"></span>');
        toast(C.i18n.reindexing, 'info');

        ajax('chatyllo_reindex', {}, function(d) {
            toast(d.message || C.i18n.reindexDone);
            $btn.prop('disabled', false).find('.chatyllo-spinner').remove();
            // Refresh page to update stats.
            setTimeout(() => location.reload(), 1000);
        }, function() {
            $btn.prop('disabled', false).find('.chatyllo-spinner').remove();
        });
    });

    /* ═══════════════════════════════════════════════════════════════
       CLEAR CACHE
       ═══════════════════════════════════════════════════════════════ */
    $(document).on('click', '#chatyllo-btn-clear-cache', function() {
        const $btn = $(this);
        $btn.prop('disabled', true);
        ajax('chatyllo_clear_cache', {}, function(d) {
            toast(d.message);
            $btn.prop('disabled', false);
        });
    });

    /* ═══════════════════════════════════════════════════════════════
       REFRESH AI STATUS
       ═══════════════════════════════════════════════════════════════ */
    $(document).on('click', '#chatyllo-refresh-status', function() {
        const $btn = $(this);
        $btn.find('.dashicons').addClass('chatyllo-spin-icon');
        ajax('chatyllo_refresh_ai_status', {}, function(d) {
            const $badge = $('#chatyllo-ai-status');
            if (d.ai_active) {
                $badge.removeClass('chatyllo-ai-badge--inactive').addClass('chatyllo-ai-badge--active');
                $badge.find('.chatyllo-ai-badge__text').text(C.i18n.aiOn);
            } else {
                $badge.removeClass('chatyllo-ai-badge--active').addClass('chatyllo-ai-badge--inactive');
                $badge.find('.chatyllo-ai-badge__text').text(C.i18n.aiOff);
            }
            if (d.usage) updateUsageMeters(d.usage, d.ai_active);
            $btn.find('.dashicons').removeClass('chatyllo-spin-icon');
        });
    });

    /* ═══════════════════════════════════════════════════════════════
       AI USAGE METERS
       ═══════════════════════════════════════════════════════════════ */
    function updateUsageMeters(usage, aiActive) {
        if (!$('#chatyllo-usage-card').length) return;

        var daily  = usage || {};
        var dUsed  = parseInt(daily.daily_used) || 0;
        var dLimit = parseInt(daily.daily_limit) || 0;
        var mUsed  = parseInt(daily.monthly_used) || 0;
        var mLimit = parseInt(daily.monthly_limit) || 0;
        var isDisabled = !aiActive || dLimit <= 0;

        // Daily meter.
        var dRemaining = Math.max(0, dLimit - dUsed);
        var dPct = dLimit > 0 ? Math.round((dRemaining / dLimit) * 100) : 0;
        $('#chatyllo-daily-count').text(isDisabled ? '-' : dRemaining + ' / ' + dLimit);
        var $dFill = $('#chatyllo-daily-fill');
        $dFill.css('width', isDisabled ? '100%' : dPct + '%');
        $dFill.removeClass('chatyllo-meter__fill--warning chatyllo-meter__fill--critical chatyllo-meter__fill--empty');
        if (isDisabled) { $dFill.addClass('chatyllo-meter__fill--empty'); }
        else if (dPct <= 0) { $dFill.addClass('chatyllo-meter__fill--critical'); $dFill.css('width', '100%'); }
        else if (dPct <= 20) { $dFill.addClass('chatyllo-meter__fill--critical'); }
        else if (dPct <= 40) { $dFill.addClass('chatyllo-meter__fill--warning'); }

        // Monthly meter.
        var mRemaining = Math.max(0, mLimit - mUsed);
        var mPct = mLimit > 0 ? Math.round((mRemaining / mLimit) * 100) : 0;
        $('#chatyllo-monthly-count').text(isDisabled ? '-' : mRemaining + ' / ' + mLimit);
        var $mFill = $('#chatyllo-monthly-fill');
        $mFill.css('width', isDisabled ? '100%' : mPct + '%');
        $mFill.removeClass('chatyllo-meter__fill--warning chatyllo-meter__fill--critical chatyllo-meter__fill--empty');
        if (isDisabled) { $mFill.addClass('chatyllo-meter__fill--empty'); }
        else if (mPct <= 0) { $mFill.addClass('chatyllo-meter__fill--critical'); $mFill.css('width', '100%'); }
        else if (mPct <= 20) { $mFill.addClass('chatyllo-meter__fill--critical'); }
        else if (mPct <= 40) { $mFill.addClass('chatyllo-meter__fill--warning'); }

        // Inactive state.
        $('#chatyllo-meter-daily, #chatyllo-meter-monthly').toggleClass('chatyllo-meter--inactive', isDisabled);

        // Status text.
        var $status = $('#chatyllo-usage-status');
        var $info = $('#chatyllo-usage-info');
        if (!C.canUseAi) {
            $status.html('<span style="color:#94A3B8">Requires paid plan</span>');
            $info.html('AI features are included with any paid plan, no personal API key needed. <a href="' + (C.upgradeUrl || '#') + '" style="color:var(--chy-primary);font-weight:600">Upgrade now</a>');
        } else if (isDisabled) {
            $status.html('<span style="color:#F59E0B">AI temporarily offline</span>');
            $info.text('AI service is temporarily unavailable. FAQ mode is active as fallback.');
        } else if (dRemaining <= 0 || mRemaining <= 0) {
            var which = dRemaining <= 0 ? 'daily' : 'monthly';
            $status.html('<span style="color:#EF4444">Limit reached (' + which + ')</span>');
            $info.text('Usage limit reached. FAQ mode is active until the limit resets. AI will resume automatically.');
        } else {
            $status.html('<span style="color:#22C55E">Active</span>');
            $info.text('');
        }

        // Daily footer.
        $('#chatyllo-daily-footer').text(dPct <= 0 && !isDisabled ? 'Resets at midnight (server time)' : 'Resets daily');
        $('#chatyllo-monthly-footer').text(mPct <= 0 && !isDisabled ? 'Resets on the 1st of next month' : 'Resets monthly');
    }

    // Init meters on dashboard load.
    if ($('#chatyllo-usage-card').length) {
        updateUsageMeters(C.usage || {}, C.aiActive);
        // Refresh from server for fresh data.
        ajax('chatyllo_refresh_ai_status', {}, function(d) {
            if (d.usage) updateUsageMeters(d.usage, d.ai_active);
        });
    }

    /* ═══════════════════════════════════════════════════════════════
       FAQ MANAGEMENT
       ═══════════════════════════════════════════════════════════════ */
    var faqCache = [];
    var faqCategories = [];
    var faqLanguages = [
        {code:'en_US',flag:'\ud83c\uddfa\ud83c\uddf8',name:'English'},
        {code:'it_IT',flag:'\ud83c\uddee\ud83c\uddf9',name:'Italiano'},
        {code:'es_ES',flag:'\ud83c\uddea\ud83c\uddf8',name:'Espa\u00f1ol'},
        {code:'fr_FR',flag:'\ud83c\uddeb\ud83c\uddf7',name:'Fran\u00e7ais'},
        {code:'de_DE',flag:'\ud83c\udde9\ud83c\uddea',name:'Deutsch'},
        {code:'pt_BR',flag:'\ud83c\udde7\ud83c\uddf7',name:'Portugu\u00eas'},
        {code:'nl_NL',flag:'\ud83c\uddf3\ud83c\uddf1',name:'Nederlands'},
        {code:'ru_RU',flag:'\ud83c\uddf7\ud83c\uddfa',name:'\u0420\u0443\u0441\u0441\u043a\u0438\u0439'},
        {code:'ja',flag:'\ud83c\uddef\ud83c\uddf5',name:'\u65e5\u672c\u8a9e'},
        {code:'ko',flag:'\ud83c\uddf0\ud83c\uddf7',name:'\ud55c\uad6d\uc5b4'},
        {code:'zh_CN',flag:'\ud83c\udde8\ud83c\uddf3',name:'\u4e2d\u6587'},
        {code:'ar',flag:'\ud83c\uddf8\ud83c\udde6',name:'\u0627\u0644\u0639\u0631\u0628\u064a\u0629'},
        {code:'tr_TR',flag:'\ud83c\uddf9\ud83c\uddf7',name:'T\u00fcrk\u00e7e'},
        {code:'pl_PL',flag:'\ud83c\uddf5\ud83c\uddf1',name:'Polski'},
        {code:'sv_SE',flag:'\ud83c\uddf8\ud83c\uddea',name:'Svenska'},
        {code:'da_DK',flag:'\ud83c\udde9\ud83c\uddf0',name:'Dansk'},
        {code:'nb_NO',flag:'\ud83c\uddf3\ud83c\uddf4',name:'Norsk'},
        {code:'fi',flag:'\ud83c\uddeb\ud83c\uddee',name:'Suomi'},
        {code:'cs_CZ',flag:'\ud83c\udde8\ud83c\uddff',name:'\u010ce\u0161tina'},
        {code:'ro_RO',flag:'\ud83c\uddf7\ud83c\uddf4',name:'Rom\u00e2n\u0103'}
    ];

    function populateLanguageSelects() {
        var locale = (C.wpLocale || 'en_US');
        $('.chatyllo-faq-lang-select, #chatyllo-ai-faq-language').each(function() {
            var $sel = $(this);
            if ($sel.find('option').length > 0) return;
            faqLanguages.forEach(function(lang) {
                $sel.append('<option value="' + lang.code + '"' + (lang.code === locale ? ' selected' : '') + '>' + lang.flag + ' ' + lang.name + '</option>');
            });
        });
    }

    function loadFaqs() {
        ajax('chatyllo_get_faqs', {}, function(d) {
            faqCache = d.faqs || [];
            extractCategories();
            renderFaqList();
        });
    }

    function extractCategories() {
        var cats = {};
        faqCache.forEach(function(f) {
            if (f.category && f.category.trim()) cats[f.category.trim()] = true;
        });
        faqCategories = Object.keys(cats).sort();

        // Update category filter dropdown.
        var $filter = $('#chatyllo-faq-filter-cat');
        var current = $filter.val();
        $filter.find('option:not(:first)').remove();
        faqCategories.forEach(function(cat) {
            $filter.append('<option value="' + escHtml(cat) + '">' + escHtml(cat) + '</option>');
        });
        if (current) $filter.val(current);

        // Update category count.
        $('#chatyllo-faq-cat-count').text(faqCategories.length);
    }

    var faqPage = 1;
    var faqPerPage = 15;

    function renderFaqList() {
        var catFilter    = $('#chatyllo-faq-filter-cat').val();
        var sourceFilter = $('#chatyllo-faq-filter-source').val();
        var $body        = $('#chatyllo-faq-list');
        $body.empty();

        var filtered = faqCache;
        if (catFilter) filtered = filtered.filter(function(f) { return f.category === catFilter; });
        if (sourceFilter) filtered = filtered.filter(function(f) { return (f.source || 'manual') === sourceFilter; });

        if (filtered.length === 0) {
            $body.html('<tr><td colspan="7" style="text-align:center;color:#94A3B8;padding:24px;">' +
                (faqCache.length === 0 ? 'No FAQ entries yet. Add your first one above!' : 'No FAQs match your filter.') +
                '</td></tr>');
            renderFaqPagination(0);
            return;
        }

        // Pagination.
        var totalPages = Math.ceil(filtered.length / faqPerPage);
        if (faqPage > totalPages) faqPage = totalPages;
        var start = (faqPage - 1) * faqPerPage;
        var pageItems = filtered.slice(start, start + faqPerPage);

        // Find language label helper.
        function langLabel(code) {
            if (!code) return '';
            var lang = faqLanguages.find(function(l) { return l.code === code; });
            return lang ? lang.flag : code;
        }

        pageItems.forEach(function(f) {
            var activeHtml = parseInt(f.is_active)
                ? '<span class="chatyllo-dot chatyllo-dot--green"></span>'
                : '<span class="chatyllo-dot chatyllo-dot--red"></span>';
            var catHtml = f.category
                ? '<span style="background:#EEF2FF;color:#4338CA;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:500">' + escHtml(f.category) + '</span>'
                : '<span style="color:#CBD5E1">\u2014</span>';
            var sourceHtml = (f.source === 'ai')
                ? '<span class="chatyllo-ai-badge" title="Generated by AI">AI</span>'
                : '<span style="font-size:11px;color:#94A3B8">Manual</span>';
            var langHtml = f.language
                ? '<span title="' + escHtml(f.language) + '" style="font-size:16px">' + langLabel(f.language) + '</span>'
                : '<span style="color:#CBD5E1;font-size:11px">\u2014</span>';

            $body.append(
                '<tr data-id="' + f.id + '">' +
                '<td style="color:#94A3B8">' + f.id + '</td>' +
                '<td><strong>' + escHtml(f.question) + '</strong>' +
                    '<div style="font-size:12px;color:#94A3B8;margin-top:2px;max-width:400px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escHtml(truncate(f.answer, 80)) + '</div>' +
                '</td>' +
                '<td>' + catHtml + '</td>' +
                '<td style="text-align:center">' + langHtml + '</td>' +
                '<td style="text-align:center">' + sourceHtml + '</td>' +
                '<td style="text-align:center">' + activeHtml + '</td>' +
                '<td>' +
                    '<button class="button button-small chatyllo-faq-toggle" data-id="' + f.id + '" data-active="' + f.is_active + '" title="' + (parseInt(f.is_active) ? 'Deactivate' : 'Activate') + '">' +
                        '<span class="dashicons dashicons-' + (parseInt(f.is_active) ? 'hidden' : 'visibility') + '"></span>' +
                    '</button> ' +
                    '<button class="button button-small chatyllo-faq-edit" data-id="' + f.id + '" title="Edit"><span class="dashicons dashicons-edit"></span></button> ' +
                    '<button class="button button-small chatyllo-faq-delete" data-id="' + f.id + '" title="Delete" style="color:#DC2626"><span class="dashicons dashicons-trash"></span></button>' +
                '</td>' +
                '</tr>'
            );
        });

        renderFaqPagination(filtered.length);
    }

    function renderFaqPagination(total) {
        var $pag = $('#chatyllo-faq-pagination');
        $pag.empty();
        var totalPages = Math.ceil(total / faqPerPage);
        if (totalPages <= 1) return;
        for (var i = 1; i <= totalPages; i++) {
            $pag.append('<button class="button button-small chatyllo-faq-page' + (i === faqPage ? ' button-primary' : '') + '" data-page="' + i + '">' + i + '</button> ');
        }
        $pag.prepend('<span style="font-size:12px;color:#64748B;margin-right:8px;">' + total + ' FAQs — Page ' + faqPage + '/' + totalPages + '</span>');
    }

    $(document).on('click', '.chatyllo-faq-page', function() {
        faqPage = parseInt($(this).data('page'));
        renderFaqList();
    });

    // Filters — reset to page 1 on filter change.
    $(document).on('change', '#chatyllo-faq-filter-cat, #chatyllo-faq-filter-source', function() { faqPage = 1; renderFaqList(); });

    // Category input dropdown.
    $(document).on('focus', '#chatyllo-faq-category', function() { showCategoryDropdown(); });
    $(document).on('input', '#chatyllo-faq-category', function() { showCategoryDropdown(); });
    $(document).on('mousedown', function(e) {
        if (!$(e.target).closest('#chatyllo-faq-cat-dropdown, #chatyllo-faq-category').length) {
            $('#chatyllo-faq-cat-dropdown').removeClass('chatyllo-picker__dropdown--open');
        }
    });

    function showCategoryDropdown() {
        var q = $('#chatyllo-faq-category').val().toLowerCase();
        var $dd = $('#chatyllo-faq-cat-dropdown');
        $dd.empty();

        var filtered = faqCategories.filter(function(cat) {
            return !q || cat.toLowerCase().indexOf(q) !== -1;
        });

        if (filtered.length === 0) {
            if (q) {
                $dd.html('<div class="chatyllo-picker__item" style="color:#94A3B8;font-style:italic">Press Enter to create "' + escHtml($('#chatyllo-faq-category').val()) + '"</div>');
            } else {
                $dd.html('<div class="chatyllo-picker__empty">No categories yet</div>');
            }
        } else {
            filtered.forEach(function(cat) {
                var $opt = $('<div class="chatyllo-picker__item">' + escHtml(cat) + '</div>');
                $opt.on('mousedown', function(e) {
                    e.preventDefault();
                    $('#chatyllo-faq-category').val(cat);
                    $dd.removeClass('chatyllo-picker__dropdown--open');
                });
                $dd.append($opt);
            });
        }
        $dd.addClass('chatyllo-picker__dropdown--open');
    }

    // Save FAQ.
    $(document).on('submit', '#chatyllo-faq-form', function(e) {
        e.preventDefault();
        var isEdit = parseInt($('#chatyllo-faq-id').val()) > 0;
        const faq = {
            id:         $('#chatyllo-faq-id').val(),
            question:   $('#chatyllo-faq-question').val(),
            answer:     $('#chatyllo-faq-answer').val(),
            keywords:   $('#chatyllo-faq-keywords').val(),
            category:   $('#chatyllo-faq-category').val(),
            language:   $('#chatyllo-faq-language').val(),
            sort_order: $('#chatyllo-faq-order').val(),
            is_active:  isEdit ? ($('#chatyllo-faq-active').is(':checked') ? 1 : 0) : 1
        };
        ajax('chatyllo_save_faq', { faq: JSON.stringify(faq) }, function(d) {
            toast(d.message);
            resetFaqForm();
            loadFaqs();
        });
    });

    // Edit FAQ.
    $(document).on('click', '.chatyllo-faq-edit', function() {
        const id = $(this).data('id');
        const faq = faqCache.find(f => parseInt(f.id) === parseInt(id));
        if (!faq) return;
        $('#chatyllo-faq-id').val(faq.id);
        $('#chatyllo-faq-question').val(faq.question);
        $('#chatyllo-faq-answer').val(faq.answer);
        $('#chatyllo-faq-keywords').val(faq.keywords);
        $('#chatyllo-faq-category').val(faq.category);
        $('#chatyllo-faq-order').val(faq.sort_order);
        if (faq.language) $('#chatyllo-faq-language').val(faq.language);
        $('#chatyllo-faq-active').prop('checked', parseInt(faq.is_active));
        $('#chatyllo-faq-active-field').show();
        $('#chatyllo-faq-form-title').html('<span class="dashicons dashicons-edit"></span> Edit FAQ #' + faq.id);
        $('#chatyllo-faq-submit').html('<span class="dashicons dashicons-saved"></span> Update FAQ');
        $('#chatyllo-faq-cancel').show();
        $('html, body').animate({ scrollTop: $('#chatyllo-faq-form').offset().top - 50 }, 300);
    });

    // Toggle active/inactive.
    $(document).on('click', '.chatyllo-faq-toggle', function() {
        var id = $(this).data('id');
        var current = parseInt($(this).data('active'));
        var faq = faqCache.find(f => parseInt(f.id) === parseInt(id));
        if (!faq) return;
        faq.is_active = current ? 0 : 1;
        ajax('chatyllo_save_faq', { faq: JSON.stringify(faq) }, function(d) {
            toast(d.message);
            loadFaqs();
        });
    });

    // Delete FAQ.
    $(document).on('click', '.chatyllo-faq-delete', function() {
        if (!confirm(C.i18n.confirm)) return;
        const id = $(this).data('id');
        ajax('chatyllo_delete_faq', { id: id }, function(d) {
            toast(d.message);
            loadFaqs();
        });
    });

    // Cancel edit.
    $(document).on('click', '#chatyllo-faq-cancel', function() { resetFaqForm(); });

    function resetFaqForm() {
        $('#chatyllo-faq-id').val(0);
        $('#chatyllo-faq-question, #chatyllo-faq-answer, #chatyllo-faq-keywords, #chatyllo-faq-category').val('');
        $('#chatyllo-faq-order').val(0);
        $('#chatyllo-faq-active').prop('checked', true);
        $('#chatyllo-faq-active-field').hide();
        $('#chatyllo-faq-form-title').html('<span class="dashicons dashicons-plus-alt2"></span> Add New FAQ');
        $('#chatyllo-faq-submit').html('<span class="dashicons dashicons-plus-alt2"></span> Save FAQ');
        $('#chatyllo-faq-cancel').hide();
    }

    /* ═══════════════════════════════════════════════════════════════
       AI FAQ GENERATION
       ═══════════════════════════════════════════════════════════════ */
    var faqGenTimer = null;

    $(document).on('click', '#chatyllo-generate-faqs-btn', function() {
        var language = $('#chatyllo-ai-faq-language').val() || 'en_US';
        var $btn = $(this);

        $btn.prop('disabled', true);
        $('#chatyllo-faq-gen-progress').show();
        startFaqGenProgress();

        ajax('chatyllo_generate_faqs', { language: language }, function(d) {
            stopFaqGenProgress();
            $('#chatyllo-faq-gen-progress').hide();
            $btn.prop('disabled', false);
            toast(d.message, 'success');
            loadFaqs();
            if (d.faq_gen_used !== undefined && d.faq_gen_limit !== undefined) {
                updateFaqGenUsage(d.faq_gen_used, d.faq_gen_limit);
            }
        }, function(err) {
            stopFaqGenProgress();
            $('#chatyllo-faq-gen-progress').hide();
            $btn.prop('disabled', false);
        });
    });

    function startFaqGenProgress() {
        var $fill = $('#chatyllo-faq-gen-fill');
        var $text = $('#chatyllo-faq-gen-text');
        var progress = 0;
        var messages = [
            'AI is analyzing your content...',
            'Reading your knowledge base...',
            'Generating questions and answers...',
            'Organizing categories...',
            'Almost done...'
        ];
        var msgIdx = 0;
        $fill.css('width', '0%');
        faqGenTimer = setInterval(function() {
            progress += (92 - progress) * 0.04;
            $fill.css('width', progress + '%');
            if (progress > (msgIdx + 1) * 18 && msgIdx < messages.length - 1) {
                msgIdx++;
                $text.text(messages[msgIdx]);
            }
        }, 400);
    }

    function stopFaqGenProgress() {
        clearInterval(faqGenTimer);
        $('#chatyllo-faq-gen-fill').css('width', '100%');
        $('#chatyllo-faq-gen-text').text('Done!');
    }

    function updateFaqGenUsage(used, limit) {
        var remaining = Math.max(0, limit - used);
        $('#chatyllo-faq-gen-usage').html(remaining + ' / ' + limit + ' <span style="font-weight:400">remaining this month</span>');

        var $btn = $('#chatyllo-generate-faqs-btn');
        if (remaining <= 0) {
            $btn.prop('disabled', true).css({opacity: .5, cursor: 'not-allowed'});
            $btn.attr('title', 'Monthly limit reached');
        } else {
            $btn.prop('disabled', false).css({opacity: 1, cursor: 'pointer'});
            $btn.attr('title', '');
        }
    }

    /* ═══════════════════════════════════════════════════════════════
       CHAT LOGS
       ═══════════════════════════════════════════════════════════════ */
    var logsCurrentPage = 1;

    function loadLogs(page) {
        page = page || 1;
        logsCurrentPage = page;
        ajax('chatyllo_get_chat_logs', { page: page }, function(d) {
            var $body = $('#chatyllo-logs-body');
            $body.empty();
            var sessions = d.sessions || [];
            if (sessions.length === 0) {
                $body.html('<tr><td colspan="7" style="text-align:center;color:#94A3B8;padding:24px;">No conversations yet.</td></tr>');
                renderLogsPagination(0, 0, 0);
                return;
            }
            sessions.forEach(function(s) {
                // Parse modes.
                var modes = (s.modes || '').split(',');
                var modeHtml = '';
                if (modes.indexOf('ai') !== -1) modeHtml += '<span class="chatyllo-dot chatyllo-dot--green"></span>';
                if (modes.indexOf('faq') !== -1) modeHtml += '<span class="chatyllo-dot chatyllo-dot--blue"></span>';
                if (!modeHtml) modeHtml = '<span class="chatyllo-dot chatyllo-dot--green"></span>';

                // Duration.
                var start = new Date(s.started_at.replace(' ', 'T'));
                var end = new Date(s.ended_at.replace(' ', 'T'));
                var durSec = Math.max(0, Math.round((end - start) / 1000));
                var durStr = durSec < 60 ? durSec + 's' : Math.round(durSec / 60) + 'min';

                $body.append(
                    '<tr style="cursor:pointer" class="chatyllo-log-row" data-session="' + escHtml(s.session_id) + '">' +
                    '<td style="white-space:nowrap;font-size:12px;color:#64748B">' + escHtml(s.started_at) + '</td>' +
                    '<td><span style="background:#F1F5F9;padding:2px 6px;border-radius:4px;font-family:monospace;font-size:11px">' + escHtml(s.session_id.substring(0,8)) + '</span></td>' +
                    '<td style="font-size:13px">' + escHtml(truncate(s.first_message || '', 50)) + '</td>' +
                    '<td style="text-align:center;font-size:13px;font-weight:600">' + s.message_count + '</td>' +
                    '<td style="text-align:center">' + modeHtml + '</td>' +
                    '<td style="text-align:center;font-size:12px;color:#64748B">' + (s.total_tokens || 0) + '</td>' +
                    '<td style="text-align:center;font-size:12px;color:#64748B">' + durStr + '</td>' +
                    '</tr>'
                );
            });

            renderLogsPagination(d.total, d.page, d.total_pages);
        });
    }

    function renderLogsPagination(total, page, totalPages) {
        var $pag = $('#chatyllo-logs-pagination');
        $pag.empty();
        if (totalPages <= 1) return;
        $pag.append('<span style="font-size:12px;color:#64748B;margin-right:8px">' + total + ' entries</span>');
        if (page > 1) $pag.append('<button class="button button-small chatyllo-log-page" data-page="' + (page - 1) + '">&laquo;</button> ');
        var start = Math.max(1, page - 3);
        var end = Math.min(totalPages, page + 3);
        for (var i = start; i <= end; i++) {
            $pag.append('<button class="button button-small chatyllo-log-page' + (i === page ? ' button-primary' : '') + '" data-page="' + i + '">' + i + '</button> ');
        }
        if (page < totalPages) $pag.append('<button class="button button-small chatyllo-log-page" data-page="' + (page + 1) + '">&raquo;</button>');
    }

    $(document).on('click', '.chatyllo-log-page', function() {
        loadLogs(parseInt($(this).data('page')));
    });

    // View session detail modal — click row or view button.
    $(document).on('click', '.chatyllo-log-row', function() {
        var sid = $(this).data('session');
        var $modal = $('#chatyllo-session-modal');
        var $body = $('#chatyllo-modal-body');
        $body.html('<p style="text-align:center;color:#94A3B8;padding:40px;">Loading conversation...</p>');
        $modal.show();

        ajax('chatyllo_get_session', { session_id: sid }, function(d) {
            var html = '';

            // Session info bar.
            html += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:16px;padding:12px;background:#F8FAFC;border-radius:8px;">';
            html += '<div><div style="font-size:11px;color:#94A3B8;text-transform:uppercase;letter-spacing:.5px">Started</div><div style="font-size:13px;font-weight:500">' + escHtml(d.started_at) + '</div></div>';
            html += '<div><div style="font-size:11px;color:#94A3B8;text-transform:uppercase;letter-spacing:.5px">Messages</div><div style="font-size:13px;font-weight:500">' + d.message_count + '</div></div>';
            html += '<div><div style="font-size:11px;color:#94A3B8;text-transform:uppercase;letter-spacing:.5px">Tokens</div><div style="font-size:13px;font-weight:500">' + d.total_tokens + '</div></div>';
            html += '<div><div style="font-size:11px;color:#94A3B8;text-transform:uppercase;letter-spacing:.5px">Avg Response</div><div style="font-size:13px;font-weight:500">' + d.avg_response + 'ms</div></div>';
            html += '<div><div style="font-size:11px;color:#94A3B8;text-transform:uppercase;letter-spacing:.5px">Browser</div><div style="font-size:13px;font-weight:500">' + escHtml(d.browser) + '</div></div>';
            html += '<div><div style="font-size:11px;color:#94A3B8;text-transform:uppercase;letter-spacing:.5px">OS / Device</div><div style="font-size:13px;font-weight:500">' + escHtml(d.os) + ' / ' + escHtml(d.device) + '</div></div>';
            if (d.page_url) {
                html += '<div style="grid-column:1/-1"><div style="font-size:11px;color:#94A3B8;text-transform:uppercase;letter-spacing:.5px">Page</div><div style="font-size:13px"><a href="' + escHtml(d.page_url) + '" target="_blank" style="color:var(--chy-primary)">' + escHtml(truncate(d.page_url, 60)) + '</a></div></div>';
            }
            html += '</div>';

            // Conversation messages.
            html += '<div class="chatyllo-modal__conversation">';
            (d.messages || []).forEach(function(m) {
                var time = m.created_at ? m.created_at.split(' ')[1] || '' : '';
                // User message.
                html += '<div class="chatyllo-modal__msg chatyllo-modal__msg--user">';
                html += '<div class="chatyllo-modal__msg-bubble chatyllo-modal__msg-bubble--user">' + escHtml(m.user_message) + '</div>';
                html += '<div class="chatyllo-modal__msg-meta">' + time + '</div>';
                html += '</div>';
                // Bot response.
                var modeTag = m.response_mode === 'ai' ? '<span class="chatyllo-dot chatyllo-dot--green" style="margin-right:3px"></span>AI' :
                              m.response_mode === 'faq' ? '<span class="chatyllo-dot chatyllo-dot--blue" style="margin-right:3px"></span>FAQ' : m.response_mode;
                html += '<div class="chatyllo-modal__msg chatyllo-modal__msg--bot">';
                html += '<div class="chatyllo-modal__msg-bubble chatyllo-modal__msg-bubble--bot">' + escHtml(m.bot_response) + '</div>';
                html += '<div class="chatyllo-modal__msg-meta">' + time + ' &middot; ' + modeTag + ' &middot; ' + (m.tokens_used || 0) + ' tokens &middot; ' + m.response_time_ms + 'ms</div>';
                html += '</div>';
            });
            html += '</div>';

            $body.html(html);
            $('#chatyllo-modal-title').text('Session ' + sid.substring(0, 12) + '...');
        }, function() {
            $body.html('<p style="text-align:center;color:#DC2626;padding:40px;">Failed to load conversation.</p>');
        });
    });

    // Close modal.
    $(document).on('click', '#chatyllo-modal-close, #chatyllo-modal-overlay', function() {
        $('#chatyllo-session-modal').hide();
    });

    /* ═══════════════════════════════════════════════════════════════
       STATISTICS PAGE
       ═══════════════════════════════════════════════════════════════ */
    function loadStats() {
        var days = $('#chatyllo-stats-period').val() || 30;
        ajax('chatyllo_get_detailed_stats', { days: days }, function(d) {
            renderStatsOverview(d);
            renderTrendChart(d.daily_trend || []);
            renderModesChart(d.modes || []);
            renderBreakdown('#chatyllo-devices-chart', d.devices || {});
            renderBreakdown('#chatyllo-browsers-chart', d.browsers || {});
            renderTopPages(d.top_pages || []);
            renderSessionQuality(d.sessions || {});
        });
    }

    $(document).on('change', '#chatyllo-stats-period', function() { loadStats(); });

    function renderStatsOverview(d) {
        var perf = d.performance || {};
        $('#chy-stat-total').text(numberFormat(perf.total_msgs || 0));
        $('#chy-stat-sessions').text(numberFormat((d.sessions || {}).total_sessions || 0));
        $('#chy-stat-avgms').text(Math.round(perf.avg_ms || 0) + 'ms');
        $('#chy-stat-tokens').text(numberFormat(perf.total_tokens || 0));
    }

    function renderTrendChart(data) {
        var $chart = $('#chatyllo-trend-chart').empty();
        if (!data.length) { $chart.html('<p style="color:#94A3B8;text-align:center;width:100%">No data yet</p>'); return; }
        var max = Math.max.apply(null, data.map(function(d) { return parseInt(d.cnt); })) || 1;
        data.forEach(function(d) {
            var pct = Math.max(4, (parseInt(d.cnt) / max) * 100);
            var $bar = $('<div title="' + d.day + ': ' + d.cnt + ' messages" style="flex:1;min-width:6px;max-width:24px;background:linear-gradient(180deg,var(--chy-primary),#818CF8);border-radius:3px 3px 0 0;height:' + pct + '%;cursor:pointer;transition:opacity .2s"></div>');
            $bar.on('mouseenter', function() { $(this).css('opacity', .7); }).on('mouseleave', function() { $(this).css('opacity', 1); });
            $chart.append($bar);
        });
    }

    function renderModesChart(modes) {
        var $el = $('#chatyllo-modes-chart').empty();
        var total = 0;
        var colors = { ai: '#22C55E', faq: '#3B82F6', cached: '#8B5CF6', no_match: '#F59E0B', error: '#94A3B8' };
        var labels = { ai: 'AI', faq: 'FAQ', cached: 'Cached', no_match: 'FAQ Mode', error: 'Error' };
        modes.forEach(function(m) { total += parseInt(m.cnt); });
        if (!total) { $el.html('<p style="color:#94A3B8">No data yet</p>'); return; }
        modes.forEach(function(m) {
            var pct = Math.round((parseInt(m.cnt) / total) * 100);
            var color = colors[m.response_mode] || '#94A3B8';
            var label = labels[m.response_mode] || m.response_mode;
            $el.append(
                '<div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">' +
                '<div style="flex:1;height:24px;background:#E2E8F0;border-radius:4px;overflow:hidden"><div style="height:100%;width:' + pct + '%;background:' + color + ';border-radius:4px;transition:width .5s"></div></div>' +
                '<span style="min-width:90px;font-size:13px;font-weight:500">' + label + ' <span style="color:#94A3B8">' + pct + '%</span></span>' +
                '</div>'
            );
        });
    }

    function renderBreakdown(selector, data) {
        var $el = $(selector).empty();
        var total = 0;
        Object.values(data).forEach(function(v) { total += v; });
        if (!total) { $el.html('<p style="color:#94A3B8">No data yet</p>'); return; }
        Object.keys(data).forEach(function(key) {
            var pct = Math.round((data[key] / total) * 100);
            if (pct === 0) return;
            $el.append(
                '<div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #F1F5F9">' +
                '<span style="font-size:13px">' + escHtml(key) + '</span>' +
                '<span style="font-size:13px;font-weight:600;color:#64748B">' + pct + '%</span>' +
                '</div>'
            );
        });
    }

    function renderTopPages(pages) {
        var $el = $('#chatyllo-top-pages').empty();
        if (!pages.length) { $el.html('<p style="color:#94A3B8">No data yet</p>'); return; }
        pages.forEach(function(p) {
            var url = p.page_url || '';
            var short = url.replace(/https?:\/\/[^\/]+/, '');
            $el.append(
                '<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #F1F5F9">' +
                '<a href="' + escHtml(url) + '" target="_blank" style="font-size:13px;color:var(--chy-primary);text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:80%">' + escHtml(short || url) + '</a>' +
                '<span style="font-size:13px;font-weight:600;color:#64748B">' + p.cnt + '</span>' +
                '</div>'
            );
        });
    }

    function renderSessionQuality(sessions) {
        var $el = $('#chatyllo-session-quality').empty();
        var totalSess = parseInt(sessions.total_sessions) || 0;
        var avgMsgs = parseFloat(sessions.avg_msgs_per_session) || 0;
        $el.append(
            '<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #F1F5F9"><span style="font-size:13px">Total Conversations</span><span style="font-weight:600">' + totalSess + '</span></div>' +
            '<div style="display:flex;justify-content:space-between;padding:8px 0"><span style="font-size:13px">Avg Messages / Session</span><span style="font-weight:600">' + avgMsgs.toFixed(1) + '</span></div>'
        );
    }

    function numberFormat(n) { return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ','); }

    // Export stats as CSV download.
    $(document).on('click', '#chatyllo-export-stats-btn', function() {
        var days = $('#chatyllo-stats-period').val() || 30;
        ajax('chatyllo_get_detailed_stats', { days: days }, function(d) {
            var csv = '# Chatyllo Statistics Export - ' + (C.settings ? '' : '') + new Date().toISOString() + '\n';
            csv += '# Generated by Chatyllo (https://wpezo.com/plugins/chatyllo)\n\n';
            csv += 'Metric,Value\n';
            csv += 'Total Messages,' + ((d.performance || {}).total_msgs || 0) + '\n';
            csv += 'Total Tokens,' + ((d.performance || {}).total_tokens || 0) + '\n';
            csv += 'Avg Response (ms),' + Math.round((d.performance || {}).avg_ms || 0) + '\n';
            csv += 'Total Conversations,' + ((d.sessions || {}).total_sessions || 0) + '\n\n';
            csv += 'Response Mode,Count\n';
            (d.modes || []).forEach(function(m) { csv += m.response_mode + ',' + m.cnt + '\n'; });
            csv += '\nDevice,Count\n';
            var dev = d.devices || {};
            Object.keys(dev).forEach(function(k) { csv += k + ',' + dev[k] + '\n'; });
            csv += '\nBrowser,Count\n';
            var br = d.browsers || {};
            Object.keys(br).forEach(function(k) { csv += k + ',' + br[k] + '\n'; });
            csv += '\n# Generated by Chatyllo - https://wpezo.com/plugins/chatyllo\n';

            var blob = new Blob([csv], { type: 'text/csv' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'chatyllo-stats-' + new Date().toISOString().slice(0,10) + '.csv';
            a.click();
            URL.revokeObjectURL(url);
            toast('Statistics exported!', 'success');
        });
    });

    // Quick Help accordion.
    $(document).on('click', '.chatyllo-accordion__toggle', function() {
        var idx = $(this).data('idx');
        var $content = $('.chatyllo-accordion__content[data-idx="' + idx + '"]');
        var isOpen = $content.is(':visible');

        // Close all others.
        $('.chatyllo-accordion__content').slideUp(200);
        $('.chatyllo-accordion__icon').css('transform', 'rotate(0deg)');

        // Toggle current.
        if (!isOpen) {
            $content.slideDown(200);
            $(this).find('.chatyllo-accordion__icon').css('transform', 'rotate(90deg)');
        }
    });

    // Run maintenance manually.
    $(document).on('click', '#chatyllo-run-maintenance-btn', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Running...');
        ajax('chatyllo_run_maintenance', {}, function(d) {
            toast(d.message || 'Maintenance completed!', 'success');
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-tools"></span> Run Maintenance Now');
        }, function() {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-tools"></span> Run Maintenance Now');
        });
    });

    /* ═══════════════════════════════════════════════════════════════
       SERVICE STATUS PAGE
       ═══════════════════════════════════════════════════════════════ */
    $(document).on('click', '#chatyllo-status-refresh', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).find('.dashicons').addClass('chatyllo-spin-icon');
        ajax('chatyllo_get_service_status', { refresh: 1 }, function(d) {
            var s = d.current || {};
            var $el = $('#chatyllo-current-status');
            $el.find('[id="chatyllo-status-text"]').text(s.text || 'Unknown').css('color', s.color || '#94A3B8');
            $el.find('.dashicons').first().attr('class', 'dashicons dashicons-' + (s.icon || 'marker')).css('color', s.color || '#94A3B8');
            $el.find('div').first().css('background', (s.color || '#94A3B8') + '1a');
            $('#chatyllo-status-time').text(s.checked_at ? 'Last checked: ' + s.checked_at : 'Just now');
            $btn.prop('disabled', false).find('.dashicons').removeClass('chatyllo-spin-icon');
            toast('Status refreshed', 'success');
        }, function() {
            $btn.prop('disabled', false).find('.dashicons').removeClass('chatyllo-spin-icon');
        });
    });

    // Timeline bar hover.
    $(document).on('mouseenter', '.chatyllo-timeline-bar', function() {
        $(this).css('opacity', .7);
    }).on('mouseleave', '.chatyllo-timeline-bar', function() {
        $(this).css('opacity', 1);
    });

    // Export plugin data as JSON.
    $(document).on('click', '#chatyllo-export-data-btn', function() {
        ajax('chatyllo_export_data', { type: 'all' }, function(d) {
            var json = JSON.stringify(d, null, 2);
            var blob = new Blob([json], { type: 'application/json' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'chatyllo-export-' + new Date().toISOString().slice(0,10) + '.json';
            a.click();
            URL.revokeObjectURL(url);
            toast('Data exported!', 'success');
        });
    });

    /* ═══════════════════════════════════════════════════════════════
       UTILITIES
       ═══════════════════════════════════════════════════════════════ */
    function escHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function truncate(str, len) {
        if (!str) return '';
        return str.length > len ? str.substring(0, len) + '…' : str;
    }

    /* Branding toggle is now handled by initBrandingRadio() at bottom. */

    /* ── Color picker init ────────────────────────────────────────── */
    $(function() {
        if ($.fn.wpColorPicker) {
            $('.chatyllo-color-picker').wpColorPicker();
        }

        // Media upload button (generic).
        $(document).on('click', '.chatyllo-upload-btn', function(e) {
            e.preventDefault();
            const target = $(this).data('target');
            const frame = wp.media({ title: 'Choose Image', multiple: false });
            frame.on('select', function() {
                const url = frame.state().get('selection').first().toJSON().url;
                $('input[name="' + target + '"]').val(url);
            });
            frame.open();
        });

        // Avatar picker — Upload.
        $(document).on('click', '#chatyllo-avatar-upload', function(e) {
            e.preventDefault();
            const frame = wp.media({ title: 'Choose Avatar', multiple: false, library: { type: 'image' } });
            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                // Prefer medium size for optimization, fallback to full.
                const url = (attachment.sizes && attachment.sizes.medium)
                    ? attachment.sizes.medium.url
                    : attachment.url;
                $('#chatyllo-avatar-url').val(url);
                $('#chatyllo-avatar-preview').html('<img src="' + url + '" alt="Avatar" />');
                $('#chatyllo-avatar-remove').show();
            });
            frame.open();
        });

        // Avatar picker — Remove.
        $(document).on('click', '#chatyllo-avatar-remove', function(e) {
            e.preventDefault();
            $('#chatyllo-avatar-url').val('');
            $('#chatyllo-avatar-preview').html('<svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="20" cy="20" r="20" fill="#4F46E5"/><path d="M20 12c-2.2 0-4 1.8-4 4v1c0 2.2 1.8 4 4 4s4-1.8 4-4v-1c0-2.2-1.8-4-4-4zm-7 16c0-3 2-5.5 5-6.3.6-.2 1.3-.2 2-.2s1.4 0 2 .2c3 .8 5 3.3 5 6.3" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>');
            $(this).hide();
        });

        // Auto-load FAQ list if on FAQ page.
        if ($('#chatyllo-faq-table').length) {
            populateLanguageSelects();
            loadFaqs();
            // Show generation counter — fetch fresh data from proxy via status refresh.
            if (C.planLimits && C.planLimits.faq_gen_limit && C.canGenerateFaqs) {
                var initUsed = C.faqGenUsed || 0;
                updateFaqGenUsage(initUsed, C.planLimits.faq_gen_limit);
                // Refresh from proxy to get actual server-side count.
                ajax('chatyllo_refresh_ai_status', {}, function(d) {
                    if (d.faq_gen_used !== undefined) {
                        updateFaqGenUsage(d.faq_gen_used, C.planLimits.faq_gen_limit);
                    }
                });
            }
        }

        // Auto-load logs if on logs page.
        if ($('#chatyllo-logs-table').length) loadLogs(1);

        // Auto-load stats if on stats page.
        if ($('#chatyllo-stats-overview').length) loadStats();

        // Init smart selects on settings page.
        if ($('#chatyllo-settings-form').length) {
            initSmartSelects();
            initDisplayRulesToggle();
            initBrandingRadio();
        }
    });

    /* ═══════════════════════════════════════════════════════════════
       SEARCH-PICK WIDGET — input + AJAX dropdown + tags
       ═══════════════════════════════════════════════════════════════ */
    function initSmartSelects() {
        $('.chatyllo-picker').each(function() {
            var $wrap     = $(this);
            var $input    = $wrap.find('.chatyllo-picker__input');
            var $dropdown = $wrap.find('.chatyllo-picker__dropdown');
            var $hidden   = $wrap.find('.chatyllo-picker__value');
            var $tags     = $wrap.find('.chatyllo-picker__tags');
            var source    = $wrap.data('source');
            var postType  = $wrap.data('post-type') || 'any';
            var selected  = {};
            var debounce  = null;
            var staticItems = null; // Cache for roles/post_types.

            // Load saved values.
            var saved = $hidden.val();
            if (saved && saved.trim()) {
                var ids = saved.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
                if (source === 'content' && ids.length) {
                    ajax('chatyllo_search_content', { ids: ids.join(',') }, function(d) {
                        (d.items || []).forEach(function(item) { pickItem('' + item.id, item.title + ' (' + item.type + ')'); });
                    });
                } else if (source === 'roles') {
                    loadStatic(function(items) {
                        items.forEach(function(item) {
                            if (ids.indexOf(item.slug) !== -1) pickItem(item.slug, item.name);
                        });
                    });
                } else if (source === 'post_types') {
                    loadStatic(function(items) {
                        items.forEach(function(item) {
                            if (ids.indexOf(item.slug) !== -1) pickItem(item.slug, item.label);
                        });
                    });
                }
            }

            // Load static items (roles, post_types).
            function loadStatic(cb) {
                if (staticItems) { cb(staticItems); return; }
                var action = source === 'roles' ? 'chatyllo_get_roles' : 'chatyllo_get_post_types';
                ajax(action, {}, function(d) {
                    staticItems = d.items || [];
                    cb(staticItems);
                });
            }

            // Input events.
            $input.on('input', function() {
                clearTimeout(debounce);
                var q = $(this).val().trim();

                if (source === 'content') {
                    if (q.length < 2) { closeDropdown(); return; }
                    debounce = setTimeout(function() {
                        showLoading();
                        ajax('chatyllo_search_content', { search: q, post_type: postType }, function(d) {
                            renderItems(d.items || [], 'content');
                        });
                    }, 300);
                } else {
                    loadStatic(function(items) {
                        var filtered = items.filter(function(item) {
                            var label = item.name || item.label || '';
                            return !q || label.toLowerCase().indexOf(q.toLowerCase()) !== -1;
                        });
                        renderItems(filtered, source);
                    });
                }
            });

            $input.on('focus', function() {
                if (source !== 'content') {
                    loadStatic(function(items) { renderItems(items, source); });
                }
            });

            $(document).on('mousedown', function(e) {
                if (!$(e.target).closest($wrap).length) closeDropdown();
            });

            function renderItems(items, type) {
                $dropdown.empty();
                var available = items.filter(function(item) {
                    var val = '' + (item.id || item.slug);
                    return !selected[val];
                });
                if (!available.length) {
                    $dropdown.html('<div class="chatyllo-picker__empty">' + (items.length ? 'All selected' : 'No results') + '</div>');
                } else {
                    available.forEach(function(item) {
                        var val   = '' + (item.id || item.slug);
                        var label = item.title || item.name || item.label;
                        var extra = item.type ? ' <span style="color:#94A3B8;font-size:11px">(' + escHtml(item.type) + ')</span>' : '';
                        var $opt  = $('<div class="chatyllo-picker__item">' + escHtml(label) + extra + '</div>');
                        $opt.on('mousedown', function(e) {
                            e.preventDefault();
                            var displayLabel = type === 'content' ? label + ' (' + (item.type || '') + ')' : label;
                            pickItem(val, displayLabel);
                            closeDropdown();
                            $input.val('');
                        });
                        $dropdown.append($opt);
                    });
                }
                $dropdown.addClass('chatyllo-picker__dropdown--open');
            }

            function pickItem(val, label) {
                if (selected[val]) return;
                selected[val] = label;
                var $tag = $('<span class="chatyllo-picker__tag">' + escHtml(label) + '<span class="chatyllo-picker__tag-x" data-val="' + escHtml(val) + '">&times;</span></span>');
                $tags.append($tag);
                syncValue();
            }

            // Remove tag.
            $tags.on('click', '.chatyllo-picker__tag-x', function() {
                var val = $(this).data('val') + '';
                delete selected[val];
                $(this).parent().remove();
                syncValue();
            });

            function syncValue() {
                $hidden.val(Object.keys(selected).join(', '));
            }

            function showLoading() {
                $dropdown.html('<div class="chatyllo-picker__empty">Searching...</div>').addClass('chatyllo-picker__dropdown--open');
            }

            function closeDropdown() {
                $dropdown.removeClass('chatyllo-picker__dropdown--open');
            }
        });
    }

    /* ═══════════════════════════════════════════════════════════════
       DISPLAY RULES TOGGLE
       ═══════════════════════════════════════════════════════════════ */
    function initDisplayRulesToggle() {
        function toggleDisplayFields() {
            var isAll = $('input[name="show_on_all_pages"]').is(':checked');
            var $fields = $('#chatyllo-display-conditional');
            if (isAll) {
                $fields.css({ opacity: .45, pointerEvents: 'none' });
            } else {
                $fields.css({ opacity: 1, pointerEvents: 'auto' });
            }
        }
        $(document).on('change', 'input[name="show_on_all_pages"]', toggleDisplayFields);
        toggleDisplayFields();
    }

    /* ═══════════════════════════════════════════════════════════════
       BRANDING RADIO OPTIONS
       ═══════════════════════════════════════════════════════════════ */
    function initBrandingRadio() {
        $(document).on('change', 'input[name="branding_mode"]', function() {
            var mode = $(this).val();
            $('.chatyllo-branding-option').removeClass('chatyllo-branding-option--active');
            $(this).closest('.chatyllo-branding-option').addClass('chatyllo-branding-option--active');
            if (mode === 'custom') {
                $('#chatyllo-custom-brand-field').show();
            } else {
                $('#chatyllo-custom-brand-field').hide();
            }
            $('input[name="show_powered_by"]').val(mode === 'powered_by' ? '1' : '0');
        });
    }

})(jQuery);
