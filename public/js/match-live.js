(function () {
    'use strict';

    function boot() {
        if (typeof window.tpMatchLive === 'undefined' || !window.tpMatchLive.restUrl) {
            return;
        }

        var root = document.querySelector('[data-tp-match-live]');
        if (!root) {
            return;
        }

        var pollMs = parseInt(window.tpMatchLive.pollMs, 10) || 10000;
        var statusEl = document.querySelector('[data-tp-match-live-status]');
        var timerId = null;

        function setStatus(text, isError) {
            if (!statusEl) {
                return;
            }
            statusEl.textContent = text;
            statusEl.classList.toggle('is-error', !!isError);
        }

        function statusLabel(status) {
            var labels = {
                scheduled: 'Programmata',
                live: 'IN CORSO',
                finished: 'Terminata',
                walkover: 'Walkover',
                retired: 'Ritiro'
            };
            return labels[status] || status;
        }

        function renderSetPills(scores) {
            if (!scores || !scores.length) {
                return '<span class="tp-match-court-view__set-pill">—</span>';
            }
            return scores.map(function (score) {
                return '<span class="tp-match-court-view__set-pill">' + score + '</span>';
            }).join('');
        }

        function renderMatch(match) {
            var panel = document.getElementById('tp-match-court-panel');
            if (!panel || !match) {
                return;
            }

            var statusBadge = panel.querySelector('.tp-status-badge');
            if (statusBadge) {
                statusBadge.textContent = statusLabel(match.status);
                statusBadge.className = 'tp-status-badge tp-status-badge--' + (match.status || 'scheduled');
            }

            var courtBadge = panel.querySelector('.tp-match-live__headline .tp-match-court');
            if (courtBadge) {
                courtBadge.textContent = match.court_name || '';
                courtBadge.style.display = match.court_name ? '' : 'none';
            }

            var fieldMap = {
                team1_name: match.team1_name || 'TBD',
                team2_name: match.team2_name || 'TBD',
                team1_players: match.team1_players || '—',
                team2_players: match.team2_players || '—',
                score_label: match.score_label || '0-0'
            };

            Object.keys(fieldMap).forEach(function (key) {
                var el = panel.querySelector('[data-tp-field="' + key + '"]');
                if (el) {
                    el.textContent = fieldMap[key];
                }
            });

            var team1Sets = panel.querySelector('[data-tp-field="team1_sets"]');
            var team2Sets = panel.querySelector('[data-tp-field="team2_sets"]');
            if (team1Sets) {
                team1Sets.innerHTML = renderSetPills(match.team1_scores);
            }
            if (team2Sets) {
                team2Sets.innerHTML = renderSetPills(match.team2_scores);
            }

            panel.classList.toggle('tp-match-court-view--live', match.status === 'live');
            panel.classList.toggle('tp-match-court-view--finished', match.status === 'finished');
        }

        function poll() {
            var url = window.tpMatchLive.restUrl;
            var separator = url.indexOf('?') === -1 ? '?' : '&';
            url += separator + '_=' + Date.now();

            fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                cache: 'no-store'
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(function (data) {
                    if (data.code) {
                        throw new Error(data.message || data.code);
                    }
                    if (data.match) {
                        renderMatch(data.match);
                    }
                    var updated = data.updated_at ? new Date(data.updated_at) : new Date();
                    setStatus('Aggiornato alle ' + updated.toLocaleTimeString(), false);
                })
                .catch(function () {
                    setStatus('Errore aggiornamento — nuovo tentativo tra poco…', true);
                });
        }

        function startPolling() {
            poll();
            if (timerId) {
                clearInterval(timerId);
            }
            timerId = setInterval(poll, pollMs);
        }

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                poll();
            }
        });

        startPolling();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
