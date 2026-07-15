(function () {
    'use strict';

    var cfg = window.tpAdmin || {};
    var i18n = cfg.i18n || {};

    document.addEventListener('input', function (event) {
        var target = event.target;
        if (!target.classList || !target.classList.contains('tp-score-input')) {
            return;
        }
        target.value = target.value.replace(/\D/g, '').slice(0, 2);
    });

    document.addEventListener('click', function (event) {
        var selectBtn = event.target.closest('[data-tp-poster-select]');
        if (selectBtn) {
            event.preventDefault();
            openPosterPicker(selectBtn.closest('[data-tp-poster-field]'));
            return;
        }

        var removeBtn = event.target.closest('[data-tp-poster-remove]');
        if (removeBtn) {
            event.preventDefault();
            clearPosterField(removeBtn.closest('[data-tp-poster-field]'));
        }
    });

    document.querySelectorAll('[data-tp-player-autocomplete]').forEach(initPlayerAutocomplete);
    document.querySelectorAll('[data-tp-team-stage-form]').forEach(initTeamStaging);
    document.querySelectorAll('form.tp-form').forEach(function (form) {
        form.addEventListener('submit', syncStagedTeamsInputs);
    });

    initFormulaUi();

    function initFormulaUi() {
        var select = document.querySelector('[data-tp-formula-select]');
        if (!select) {
            return;
        }

        var formulas = {
            champions_padel: {
                teams: 32,
                groups: 8,
                sets: '2',
                games: 6,
                duration: 90,
                qualMode: 'gold_only',
                qualCount: 2,
                hideBrackets: true,
                desc: '32 squadre · 8 gironi da 4 · ottavi stile Champions League · STB a 6-6.'
            }
        };

        function applyFormula() {
            var id = select.value;
            var cfg = formulas[id];
            var teamsEl = document.getElementById('num_teams');
            var groupsEl = document.getElementById('num_groups');
            var descEl = document.querySelector('[data-tp-formula-desc]');
            var bracketRows = document.querySelectorAll('[data-tp-brackets-row]');

            if (!cfg) {
                if (teamsEl) {
                    teamsEl.readOnly = false;
                    teamsEl.min = '2';
                    teamsEl.max = '64';
                }
                if (groupsEl) {
                    groupsEl.readOnly = false;
                }
                bracketRows.forEach(function (row) {
                    row.hidden = false;
                });
                if (descEl) {
                    descEl.textContent = '';
                }
                return;
            }

            if (teamsEl) {
                teamsEl.value = String(cfg.teams);
                teamsEl.min = String(cfg.teams);
                teamsEl.max = String(cfg.teams);
                teamsEl.readOnly = true;
            }
            if (groupsEl) {
                groupsEl.value = String(cfg.groups);
                groupsEl.readOnly = true;
            }
            var setsEl = document.getElementById('sets_to_win');
            var gamesEl = document.getElementById('games_per_set');
            var durEl = document.getElementById('match_duration');
            var qualModeEl = document.getElementById('qualification_mode');
            var qualCountEl = document.getElementById('qualification_count');
            if (setsEl) setsEl.value = cfg.sets;
            if (gamesEl) gamesEl.value = String(cfg.games);
            if (durEl) durEl.value = String(cfg.duration);
            if (qualModeEl) qualModeEl.value = cfg.qualMode;
            if (qualCountEl) qualCountEl.value = String(cfg.qualCount);
            bracketRows.forEach(function (row) {
                row.hidden = !!cfg.hideBrackets;
            });
            if (descEl) {
                descEl.textContent = cfg.desc;
            }
        }

        select.addEventListener('change', applyFormula);
        applyFormula();
    }

    function initPlayerAutocomplete(wrap) {
        var input = wrap.querySelector('.tp-player-autocomplete__input');
        var list = wrap.querySelector('.tp-player-autocomplete__list');
        if (!input || !list) {
            return;
        }

        var debounceTimer = null;
        var activeIndex = -1;
        var lastResults = [];

        input.addEventListener('input', function () {
            window.clearTimeout(debounceTimer);
            var query = input.value.trim();
            if (query.length < 2) {
                hideList();
                return;
            }
            debounceTimer = window.setTimeout(function () {
                fetchPlayers(query, list, input, function (results) {
                    lastResults = results;
                    activeIndex = -1;
                });
            }, 250);
        });

        input.addEventListener('keydown', function (event) {
            if (list.hidden || !lastResults.length) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                activeIndex = Math.min(activeIndex + 1, lastResults.length - 1);
                highlightItem(list, activeIndex);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                highlightItem(list, activeIndex);
            } else if (event.key === 'Enter' && activeIndex >= 0) {
                event.preventDefault();
                selectPlayer(input, list, lastResults[activeIndex]);
            } else if (event.key === 'Escape') {
                hideList(list);
            }
        });

        input.addEventListener('blur', function () {
            window.setTimeout(function () {
                if (!wrap.contains(document.activeElement)) {
                    hideList(list);
                }
            }, 150);
        });

        list.addEventListener('mousedown', function (event) {
            var item = event.target.closest('[data-tp-player-option]');
            if (!item) {
                return;
            }
            event.preventDefault();
            var index = parseInt(item.getAttribute('data-index') || '-1', 10);
            if (index >= 0 && lastResults[index]) {
                selectPlayer(input, list, lastResults[index]);
            }
        });
    }

    function fetchPlayers(query, list, input, onDone) {
        if (!cfg.restUrl || !cfg.restNonce) {
            return;
        }

        list.hidden = false;
        list.innerHTML = '<li class="tp-player-autocomplete__status">' + escapeHtml(i18n.searching || 'Ricerca…') + '</li>';

        var url = cfg.restUrl + 'players/search?q=' + encodeURIComponent(query);

        fetch(url, {
            headers: {
                'X-WP-Nonce': cfg.restNonce
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('search failed');
                }
                return response.json();
            })
            .then(function (data) {
                var players = Array.isArray(data.players) ? data.players : [];
                renderResults(list, players);
                if (typeof onDone === 'function') {
                    onDone(players);
                }
            })
            .catch(function () {
                list.innerHTML = '<li class="tp-player-autocomplete__status">' + escapeHtml(i18n.noResults || 'Nessun giocatore trovato') + '</li>';
                list.hidden = false;
                if (typeof onDone === 'function') {
                    onDone([]);
                }
            });
    }

    function renderResults(list, players) {
        if (!players.length) {
            list.innerHTML = '<li class="tp-player-autocomplete__status">' + escapeHtml(i18n.noResults || 'Nessun giocatore trovato') + '</li>';
            list.hidden = false;
            return;
        }

        list.innerHTML = players.map(function (player, index) {
            return (
                '<li role="option" class="tp-player-autocomplete__option" data-tp-player-option data-index="' + index + '">' +
                    '<span class="tp-player-autocomplete__name">' + escapeHtml(player.name || '') + '</span>' +
                    (player.subtitle ? '<span class="tp-player-autocomplete__meta">' + escapeHtml(player.subtitle) + '</span>' : '') +
                '</li>'
            );
        }).join('');
        list.hidden = false;
    }

    function selectPlayer(input, list, player) {
        if (!player || !player.name) {
            return;
        }
        input.value = player.name;
        hideList(list);
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function highlightItem(list, index) {
        list.querySelectorAll('[data-tp-player-option]').forEach(function (el, i) {
            el.classList.toggle('is-active', i === index);
        });
    }

    function hideList(list) {
        if (!list) {
            document.querySelectorAll('.tp-player-autocomplete__list').forEach(function (el) {
                el.hidden = true;
                el.innerHTML = '';
            });
            return;
        }
        list.hidden = true;
        list.innerHTML = '';
    }

    function initTeamStaging(form) {
        var card = form.closest('[data-tp-teams-create]');
        if (!card) {
            return;
        }

        var stageBtn = form.querySelector('[data-tp-stage-team]');
        var stagedList = card.querySelector('[data-tp-staged-teams]');

        if (!stageBtn || !stagedList) {
            return;
        }

        card._tpStagedTeams = card._tpStagedTeams || [];

        stageBtn.addEventListener('click', function () {
            var p1 = getFieldValue(form, 'player1_name').trim();
            var p2 = getFieldValue(form, 'player2_name').trim();
            var teamName = getFieldValue(form, 'team_name').trim();

            if (!p1 || !p2) {
                window.alert(i18n.teamRequired || 'Inserisci entrambi i giocatori.');
                return;
            }

            if (!teamName) {
                teamName = p1 + ' / ' + p2;
            }

            card._tpStagedTeams.push({
                player1_name: p1,
                player2_name: p2,
                team_name: teamName
            });

            renderStagedTeams(stagedList, card);
            clearStageForm(form);
        });
    }

    function renderStagedTeams(list, card) {
        var teams = card._tpStagedTeams || [];

        if (!teams.length) {
            list.hidden = true;
            list.innerHTML = '';
            syncStagedTeamsInputs();
            return;
        }

        list.hidden = false;
        list.innerHTML = teams.map(function (team, index) {
            return (
                '<li class="tp-staged-teams__item">' +
                    '<strong>' + escapeHtml(team.team_name) + '</strong>' +
                    '<span>' + escapeHtml(team.player1_name + ' · ' + team.player2_name) + '</span>' +
                    '<button type="button" class="button-link-delete" data-tp-remove-staged="' + index + '" aria-label="' + escapeHtml(i18n.removeTeam || 'Rimuovi') + '">×</button>' +
                '</li>'
            );
        }).join('');

        list.querySelectorAll('[data-tp-remove-staged]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var idx = parseInt(btn.getAttribute('data-tp-remove-staged') || '-1', 10);
                if (idx >= 0) {
                    card._tpStagedTeams.splice(idx, 1);
                    renderStagedTeams(list, card);
                }
            });
        });

        syncStagedTeamsInputs();
    }

    function syncStagedTeamsInputs() {
        var card = document.querySelector('[data-tp-teams-create]');
        var container = card ? card.querySelector('[data-tp-staged-teams-inputs]') : null;
        if (!container) {
            return;
        }

        var teams = card._tpStagedTeams || [];
        container.innerHTML = teams.map(function (team, index) {
            return (
                '<input type="hidden" name="staged_teams[' + index + '][player1_name]" value="' + escapeAttr(team.player1_name) + '">' +
                '<input type="hidden" name="staged_teams[' + index + '][player2_name]" value="' + escapeAttr(team.player2_name) + '">' +
                '<input type="hidden" name="staged_teams[' + index + '][team_name]" value="' + escapeAttr(team.team_name) + '">'
            );
        }).join('');
    }

    function getFieldValue(form, field) {
        var el = form.querySelector('[data-tp-field="' + field + '"]');
        return el ? String(el.value || '') : '';
    }

    function clearStageForm(form) {
        form.querySelectorAll('[data-tp-field]').forEach(function (el) {
            el.value = '';
        });
    }

    function openPosterPicker(field) {
        if (!field || typeof wp === 'undefined' || !wp.media) {
            return;
        }

        var input = field.querySelector('[name="poster_id"]');
        var preview = field.querySelector('[data-tp-poster-preview]');
        var removeBtn = field.querySelector('[data-tp-poster-remove]');
        if (!input || !preview) {
            return;
        }

        var frame = wp.media({
            title: 'Seleziona locandina',
            button: { text: 'Usa questa immagine' },
            library: { type: 'image' },
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            input.value = String(attachment.id || 0);

            var imageUrl = attachment.sizes && attachment.sizes.medium
                ? attachment.sizes.medium.url
                : attachment.url;

            preview.innerHTML = '<img src="' + imageUrl + '" alt="">';
            if (removeBtn) {
                removeBtn.hidden = false;
            }
        });

        frame.open();
    }

    function clearPosterField(field) {
        if (!field) {
            return;
        }

        var input = field.querySelector('[name="poster_id"]');
        var preview = field.querySelector('[data-tp-poster-preview]');
        var removeBtn = field.querySelector('[data-tp-poster-remove]');

        if (input) {
            input.value = '0';
        }
        if (preview) {
            preview.innerHTML = '';
        }
        if (removeBtn) {
            removeBtn.hidden = true;
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/'/g, '&#39;');
    }
})();
