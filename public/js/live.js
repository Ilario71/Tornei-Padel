(function () {
    'use strict';

    function boot() {
        if (typeof window.tpLive === 'undefined' || !window.tpLive.restUrl) {
            return;
        }

        var root = document.querySelector('[data-tp-live]');
        if (!root) {
            return;
        }

        var pollMs = parseInt(window.tpLive.pollMs, 10) || 10000;
        var statusEl = document.querySelector('[data-tp-live-status]');
        var timerId = null;

        function escapeHtml(str) {
            var div = document.createElement('div');
            div.textContent = str == null ? '' : String(str);
            return div.innerHTML;
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

        function setStatus(text, isError) {
            if (!statusEl) {
                return;
            }
            statusEl.textContent = text;
            statusEl.classList.toggle('is-error', !!isError);
        }

        function renderStandings(standings) {
            var panel = document.getElementById('tp-standings-panel');
            if (!panel || !standings) {
                return;
            }

            var html = '<h2>Classifiche</h2>';
            Object.keys(standings).forEach(function (groupName) {
                var rows = standings[groupName] || [];
                html += '<div class="tp-group-standings"><h3>' + escapeHtml(groupName) + '</h3>';
                if (!rows.length) {
                    html += '<p class="tp-empty-sm">In attesa di risultati.</p>';
                } else {
                    html += '<table class="tp-standings-table"><thead><tr><th>#</th><th>Squadra</th><th>Pt</th><th>V</th><th>P</th></tr></thead><tbody>';
                    rows.forEach(function (st) {
                        html += '<tr><td>' + (st.rank_pos || '-') + '</td><td>' + escapeHtml(st.team_name) + '</td>';
                        html += '<td><strong>' + st.points + '</strong></td><td>' + st.wins + '</td><td>' + st.losses + '</td></tr>';
                    });
                    html += '</tbody></table>';
                }
                html += '</div>';
            });
            panel.innerHTML = html;
        }

        function renderMatches(matches) {
            var panel = document.getElementById('tp-matches-panel');
            if (!panel || !matches) {
                return;
            }

            var group = matches.filter(function (m) { return m.phase === 'group'; });
            var ko = matches.filter(function (m) { return m.phase === 'knockout'; });

            var html = '<h2>Partite</h2>';

            if (group.length) {
                html += '<h3 class="tp-match-phase-title">Gironi</h3><ul class="tp-match-feed">';
                group.forEach(function (m) { html += renderMatchItem(m); });
                html += '</ul>';
            }

            if (ko.length) {
                html += '<h3 class="tp-match-phase-title">Tabelloni</h3><ul class="tp-match-feed">';
                ko.forEach(function (m) { html += renderMatchItem(m); });
                html += '</ul>';
            }

            if (!group.length && !ko.length) {
                html += '<p class="tp-empty-sm">Nessuna partita programmata.</p>';
            }

            panel.innerHTML = html;
        }

        function renderMatchItem(m) {
            var teams = (m.team1_name || 'TBD') + ' vs ' + (m.team2_name || 'TBD');
            var score = m.score_label || '';
            var followUrl = window.tpLive.matchUrlBase
                ? window.tpLive.matchUrlBase + m.id
                : '';
            var html = '<li class="tp-match-item tp-match-item--' + (m.status || 'scheduled') + '">';
            if (m.round_name) {
                html += '<div class="tp-match-round">' + escapeHtml(m.round_name) + '</div>';
            }
            html += '<div class="tp-match-teams">' + escapeHtml(teams) + '</div>';
            html += '<div class="tp-match-meta">';
            html += '<div class="tp-match-meta__left">';
            if (m.court_name) {
                html += '<span class="tp-match-court">' + escapeHtml(m.court_name) + '</span>';
            }
            html += '<span class="tp-status-badge">' + escapeHtml(statusLabel(m.status)) + '</span>';
            if (score) {
                html += '<span class="tp-match-score">' + escapeHtml(score) + '</span>';
            }
            if (m.status === 'finished' && m.winner_name) {
                html += '<span class="tp-match-winner">→ ' + escapeHtml(m.winner_name) + '</span>';
            }
            html += '</div>';
            if (followUrl) {
                html += '<a class="tp-btn tp-btn--segui" href="' + escapeHtml(followUrl) + '">Segui</a>';
            }
            html += '</div></li>';
            return html;
        }

        function poll() {
            var url = window.tpLive.restUrl;
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
                    if (data.standings) {
                        renderStandings(data.standings);
                    }
                    if (data.matches) {
                        renderMatches(data.matches);
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
            if (document.hidden) {
                return;
            }
            poll();
        });

        startPolling();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
