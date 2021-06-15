!(function (e, o) {
    if ('object' == typeof exports && 'object' == typeof module) {
        module.exports = o();
    } else if ('function' == typeof define && define.amd) {
        define([], o);
    } else {
        var t = o();
        for (var n in t) ('object' == typeof exports ? exports : e)[n] = t[n];
    }
})(window, function () {
    return (function (e) {
        var o = {};

        function t(n) {
            if (o[n]) return o[n].exports;
            var r = (o[n] = {
                i: n,
                l: !1,
                exports: {}
            });
            return e[n].call(r.exports, r, r.exports, t), (r.l = !0), r.exports;
        }

        return ((t.m = e), (t.c = o), (t.d = function (e, o, n) {
            t.o(e, o) || Object.defineProperty(e, o, {
                enumerable: !0,
                get: n
            });
        }), (t.r = function (e) {
            'undefined' != typeof Symbol && Symbol.toStringTag && Object.defineProperty(e, Symbol.toStringTag, {value: 'Module'}), Object.defineProperty(e,
                '__esModule',
                {value: !0}
            );
        }), (t.t = function (e, o) {
            if ((1 & o && (e = t(e)), 8 & o)) return e;
            if (4 & o && 'object' == typeof e && e && e.__esModule) return e;
            var n = Object.create(null);
            if ((t.r(n), Object.defineProperty(n, 'default', {
                enumerable: !0,
                value: e
            }), 2 & o && 'string' != typeof e)) {
                for (var r in e) {
                    t.d(n, r, function (o) {
                        return e[o];
                    }.bind(null, r));
                }
            }
            return n;
        }), (t.n = function (e) {
            var o = e && e.__esModule ? function () {
                return e.default;
            } : function () {
                return e;
            };
            return t.d(o, 'a', o), o;
        }), (t.o = function (e, o) {
            return Object.prototype.hasOwnProperty.call(e, o);
        }), (t.p = ''), t((t.s = 0)));
    })([
        function (e, o, t) {
            'use strict';
            t.r(o), t.d(o, 'acymEmailMisspelled', function () {
                return m;
            });
            var n = function () {
                var e = arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : '';
                return 1 === e.replace(/[^@]/g, '').length;
            }, r = function () {
                var e = arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : '';
                return e.includes('@') ? e.replace(/.*@/, '') : '';
            }, i = function () {
                var e = arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : '',
                    o = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : 1;
                return function (t) {
                    var n = e.length - t.length;
                    return n <= o && n >= -o;
                };
            }, l = function () {
                var e = arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : '',
                    o = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : 1;
                return function () {
                    for (var t = arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : {suggest: ''}, n = Array(e.length + 1)
                        .fill(null)
                        .map(function () {
                            return Array(t.suggest.length + 1).fill(null);
                        }), r = 0 ; r <= t.suggest.length ; r += 1) {
                        n[0][r] = r;
                    }
                    for (var i = 0 ; i <= e.length ; i += 1) n[i][0] = i;
                    for (var l = 1 ; l <= e.length ; l += 1) {
                        for (var a = 1 ; a <= t.suggest.length ; a += 1) {
                            var c = t.suggest[a - 1] === e[l - 1] ? 0 : 1;
                            n[l][a] = Math.min(n[l][a - 1] + 1, n[l - 1][a] + 1, n[l - 1][a - 1] + c);
                        }
                    }
                    return (t.misspelledCount = n[e.length][t.suggest.length]), t.misspelledCount <= o;
                };
            }, a = [
                'gmail.com',
                'yahoo.com',
                'hotmail.com',
                'aol.com',
                'hotmail.co.uk',
                'hotmail.fr',
                'msn.com',
                'yahoo.fr',
                'wanadoo.fr',
                'orange.fr',
                'comcast.net',
                'yahoo.co.uk',
                'yahoo.com.br',
                'yahoo.co.in',
                'live.com',
                'rediffmail.com',
                'free.fr',
                'gmx.de',
                'web.de',
                'yandex.ru',
                'ymail.com',
                'libero.it',
                'outlook.com',
                'uol.com.br',
                'bol.com.br',
                'mail.ru',
                'cox.net',
                'hotmail.it',
                'sbcglobal.net',
                'sfr.fr',
                'live.fr',
                'verizon.net',
                'live.co.uk',
                'googlemail.com',
                'yahoo.es',
                'ig.com.br',
                'live.nl',
                'bigpond.com',
                'terra.com.br',
                'yahoo.it',
                'neuf.fr',
                'yahoo.de',
                'alice.it',
                'rocketmail.com',
                'att.net',
                'laposte.net',
                'facebook.com',
                'bellsouth.net',
                'yahoo.in',
                'hotmail.es',
                'charter.net',
                'yahoo.ca',
                'yahoo.com.au',
                'rambler.ru',
                'hotmail.de',
                'tiscali.it',
                'shaw.ca',
                'yahoo.co.jp',
                'sky.com',
                'earthlink.net',
                'optonline.net',
                'freenet.de',
                't-online.de',
                'aliceadsl.fr',
                'virgilio.it',
                'home.nl',
                'qq.com',
                'telenet.be',
                'me.com',
                'yahoo.com.ar',
                'tiscali.co.uk',
                'yahoo.com.mx',
                'voila.fr',
                'gmx.net',
                'mail.com',
                'planet.nl',
                'tin.it',
                'live.it',
                'ntlworld.com',
                'arcor.de',
                'yahoo.co.id',
                'frontiernet.net',
                'hetnet.nl',
                'live.com.au',
                'yahoo.com.sg',
                'zonnet.nl',
                'club-internet.fr',
                'juno.com',
                'optusnet.com.au',
                'blueyonder.co.uk',
                'bluewin.ch',
                'skynet.be',
                'sympatico.ca',
                'windstream.net',
                'mac.com',
                'centurytel.net',
                'chello.nl',
                'live.ca',
                'aim.com',
                'bigpond.net.au'
            ], c = function (e) {
                return {suggest: e};
            }, u = function (e) {
                return function (o) {
                    return e && (null == o ? void 0 : o.suggest) ? ((o.corrected = e.replace(/@.*$/, '@'.concat(o.suggest))), o) : o;
                };
            }, f = function (e, o) {
                return e.misspelledCount - o.misspelledCount;
            }, m = function () {
                var e = arguments.length > 0 && void 0 !== arguments[0] ? arguments[0] : {},
                    o = e.lengthDiffMax,
                    t = void 0 === o ? 2 : o,
                    m = e.maxMisspelled,
                    s = void 0 === m ? 2 : m,
                    d = e.domainList,
                    g = void 0 === d ? a : d;
                return function (e) {
                    if (n(e) && (null == g ? void 0 : g.length)) {
                        var o = r(e);
                        if (!g.includes(o)) {
                            var a = i(o, t), m = l(o, s), d = u(e), h = g.filter(a).map(c).filter(m).map(d).sort(f);
                            return h.length ? h : void 0;
                        }
                    }
                };
            };
            o.default = m;
        }
    ]);
});


let acymSuggestionBoxes = document.querySelectorAll('.acym_email_suggestions');

if (acymSuggestionBoxes.length > 0) {
    for (let acymSuggestionBox of acymSuggestionBoxes) {
        let acymUserInput = document.querySelector('#' + acymSuggestionBox.getAttribute('acym-data-field'));
        acymSuggestionBox.style.width = (acymUserInput.offsetWidth - 2) + 'px';
        acymSuggestionBox.parentElement.style.position = 'relative';

        acymUserInput.addEventListener('keyup', function (event) {
            if (event.key === 'Escape') {
                setTimeout(function () {
                    acymSuggestionBox.style.display = 'none';
                }, 200);
                return;
            }

            let acymSuggestions = acymEmailMisspelled()(event.currentTarget.value);
            if (acymSuggestions === null || acymSuggestions === void 0 || !acymSuggestions.length || acymSuggestions.length < 1) {
                acymSuggestionBox.style.display = 'none';
                return;
            }

            acymSuggestionBox.innerHTML = '';
            acymSuggestionBox.style.display = 'block';

            for (let acymSuggestion of acymSuggestions) {
                let acymSuggestionLi = document.createElement('li');
                acymSuggestionLi.classList.add('acym_email_suggestions_suggestion');
                acymSuggestionLi.innerHTML = acymSuggestion.corrected;
                acymSuggestionLi.addEventListener('click', function (event) {
                    acymUserInput.value = event.target.innerHTML;
                    acymSuggestionBox.style.display = 'none';
                });
                acymSuggestionBox.appendChild(acymSuggestionLi);
            }
        });

        acymUserInput.addEventListener('blur', function (event) {
            setTimeout(function () {
                acymSuggestionBox.style.display = 'none';
            }, 200);
        });
    }
}
