/**
 * Lucide global bootstrap + emoji-to-icon converter.
 *
 * Responsibilities:
 * 1) initialize Lucide icons
 * 2) normalize legacy `data-lucide="fa-*"` names
 * 3) auto-refresh icons for dynamic DOM updates
 * 4) replace mapped emojis in visible text nodes with Lucide icons
 */
(function () {
    'use strict';

    const LUCIDE_DEFAULTS = {
        nameAttr: 'data-lucide',
        'stroke-width': 2,
    };

    const LEGACY_ICON_MAP = {
        'fa-bullseye': 'target',
        'fa-target': 'target',
        'fa-wallet': 'wallet',
        'fa-university': 'landmark',
        'fa-arrow-down': 'arrow-down',
        'fa-arrow-up': 'arrow-up',
        'fa-calendar-alt': 'calendar-days',
        'fa-check': 'check',
        'fa-check-circle': 'circle-check',
        'fa-chevron-right': 'chevron-right',
        'fa-credit-card': 'credit-card',
        'fa-exclamation-circle': 'circle-alert',
        'fa-exclamation-triangle': 'triangle-alert',
        'fa-eye': 'eye',
        'fa-eye-slash': 'eye-off',
        'fa-home': 'house',
        'fa-hand-holding-usd': 'hand-coins',
        'fa-info-circle': 'info',
        'fa-pencil': 'pencil',
        'fa-pencil-alt': 'pencil',
        'fa-plane': 'plane',
        'fa-plus': 'plus',
        'fa-plus-circle': 'circle-plus',
        'fa-redo': 'refresh-cw',
        'fa-shopping-cart': 'shopping-cart',
        'fa-sort': 'arrow-up-down',
        'fa-sort-down': 'arrow-down',
        'fa-sort-up': 'arrow-up',
        'fa-spinner': 'loader-2',
        'fa-times': 'x',
        'fa-trash': 'trash-2',
        'fa-undo': 'undo-2',
    };

    const EMOJI_ICON_MAP = Object.freeze({
        '\u2197': 'arrow-up-right',
        '\u2198': 'arrow-down-right',
        '\u21A9': 'undo-2',
        '\u23F0': 'clock',
        '\u23F3': 'hourglass',
        '\u2605': 'star',
        '\u2606': 'star',
        '\u26A0': 'triangle-alert',
        '\u26A1': 'zap',
        '\u2705': 'circle-check',
        '\u2708': 'plane',
        '\u2709': 'mail',
        '\u2713': 'check',
        '\u2714': 'check',
        '\u2728': 'sparkles',
        '\u274C': 'x-circle',
        '\u2B50': 'star',
        '\u{1F381}': 'gift',
        '\u{1F382}': 'cake-slice',
        '\u{1F388}': 'party-popper',
        '\u{1F389}': 'party-popper',
        '\u{1F38A}': 'party-popper',
        '\u{1F393}': 'graduation-cap',
        '\u{1F3AE}': 'target',
        '\u{1F3AF}': 'target',
        '\u{1F3C6}': 'trophy',
        '\u{1F3D6}': 'sun',
        '\u{1F3E0}': 'house',
        '\u{1F3E5}': 'heart-pulse',
        '\u{1F3E6}': 'landmark',
        '\u{1F3EA}': 'store',
        '\u{1F3F7}': 'tag',
        '\u{1F44D}': 'thumbs-up',
        '\u{1F44E}': 'thumbs-down',
        '\u{1F464}': 'user',
        '\u{1F4A1}': 'lightbulb',
        '\u{1F4AA}': 'sparkles',
        '\u{1F4B0}': 'banknote',
        '\u{1F4B3}': 'credit-card',
        '\u{1F4B5}': 'banknote',
        '\u{1F4B8}': 'banknote',
        '\u{1F4C4}': 'file-text',
        '\u{1F4C5}': 'calendar',
        '\u{1F4C6}': 'calendar-days',
        '\u{1F4C8}': 'trending-up',
        '\u{1F4CA}': 'bar-chart-3',
        '\u{1F4CB}': 'file-text',
        '\u{1F4CE}': 'paperclip',
        '\u{1F4DC}': 'scroll-text',
        '\u{1F4DD}': 'pen-line',
        '\u{1F4E6}': 'package',
        '\u{1F4E7}': 'mail',
        '\u{1F4F1}': 'smartphone',
        '\u{1F504}': 'refresh-cw',
        '\u{1F510}': 'lock',
        '\u{1F512}': 'lock',
        '\u{1F514}': 'bell',
        '\u{1F525}': 'flame',
        '\u{1F534}': 'circle',
        '\u{1F610}': 'minus',
        '\u{1F615}': 'circle-alert',
        '\u{1F64F}': 'heart',
        '\u{1F680}': 'rocket',
        '\u{1F697}': 'car',
        '\u{1F6AB}': 'x-circle',
        '\u{1F6D2}': 'shopping-cart',
        '\u{1F6E1}': 'shield',
        '\u{1F7E1}': 'circle',
        '\u{1F7E2}': 'circle',
        '\u{1F973}': 'party-popper',
    });

    const EMOJI_REGEX = /([\u2197\u2198\u21A9\u23F0\u23F3\u2600-\u27BF\u{1F300}-\u{1FAFF}])\uFE0F?/gu;
    const EMOJI_STYLE = 'width:1em;height:1em;vertical-align:-0.125em;display:inline-block;pointer-events:none;';
    const SKIP_TAGS = new Set(['SCRIPT', 'STYLE', 'TEXTAREA', 'INPUT', 'OPTION', 'CODE', 'PRE', 'KBD', 'SAMP', 'NOSCRIPT']);

    function normalizeLegacyIconName(name) {
        const raw = String(name || '').trim();
        if (!raw) return raw;
        if (LEGACY_ICON_MAP[raw]) return LEGACY_ICON_MAP[raw];
        if (raw.indexOf('fa-') === 0) return raw.replace(/^fa-/, '');
        return raw;
    }

    function normalizeLegacyIconAttrs(root) {
        const scope = root || document;
        const icons = scope.querySelectorAll ? scope.querySelectorAll('i[data-lucide]') : [];
        for (let i = 0; i < icons.length; i++) {
            const current = icons[i].getAttribute('data-lucide');
            const normalized = normalizeLegacyIconName(current);
            if (normalized && normalized !== current) {
                icons[i].setAttribute('data-lucide', normalized);
            }
        }
    }

    function normalizeEmojiToken(token) {
        return String(token || '').replace(/\uFE0F/g, '');
    }

    function hasMappedEmoji(text) {
        if (!text) return false;
        EMOJI_REGEX.lastIndex = 0;
        return EMOJI_REGEX.test(text);
    }

    function shouldSkipTextNode(textNode) {
        if (!textNode || textNode.nodeType !== Node.TEXT_NODE) return true;
        const parent = textNode.parentElement;
        if (!parent) return true;
        if (parent.closest('[data-lk-emoji-skip]')) return true;
        if (parent.closest('svg')) return true;

        let current = parent;
        while (current) {
            if (SKIP_TAGS.has(current.tagName)) return true;
            if (current.isContentEditable) return true;
            current = current.parentElement;
        }

        return false;
    }

    function createEmojiIcon(iconName) {
        const icon = document.createElement('i');
        icon.setAttribute('data-lucide', iconName);
        icon.setAttribute('aria-hidden', 'true');
        icon.setAttribute('class', 'lk-emoji-icon');
        icon.setAttribute('style', EMOJI_STYLE);
        return icon;
    }

    function replaceEmojiTextNode(textNode) {
        if (shouldSkipTextNode(textNode)) return false;

        const original = textNode.nodeValue || '';
        if (!hasMappedEmoji(original)) return false;

        const fragment = document.createDocumentFragment();
        let changed = false;
        let lastIndex = 0;

        EMOJI_REGEX.lastIndex = 0;
        let match;
        while ((match = EMOJI_REGEX.exec(original)) !== null) {
            const idx = match.index;
            const token = match[0];

            if (idx > lastIndex) {
                fragment.appendChild(document.createTextNode(original.slice(lastIndex, idx)));
            }

            const iconName = EMOJI_ICON_MAP[normalizeEmojiToken(token)];
            if (iconName) {
                fragment.appendChild(createEmojiIcon(iconName));
                changed = true;
            } else {
                fragment.appendChild(document.createTextNode(token));
            }

            lastIndex = idx + token.length;
        }

        if (!changed) return false;
        if (lastIndex < original.length) {
            fragment.appendChild(document.createTextNode(original.slice(lastIndex)));
        }

        if (textNode.parentNode) {
            textNode.parentNode.replaceChild(fragment, textNode);
            return true;
        }

        return false;
    }

    function convertEmojisInNode(root) {
        if (!root) return false;

        if (root.nodeType === Node.TEXT_NODE) {
            return replaceEmojiTextNode(root);
        }

        if (root.nodeType !== Node.ELEMENT_NODE && root.nodeType !== Node.DOCUMENT_NODE && root.nodeType !== Node.DOCUMENT_FRAGMENT_NODE) {
            return false;
        }

        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode: function (node) {
                if (!node.nodeValue || !hasMappedEmoji(node.nodeValue)) return NodeFilter.FILTER_REJECT;
                return shouldSkipTextNode(node) ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT;
            }
        });

        const nodes = [];
        let current;
        while ((current = walker.nextNode())) {
            nodes.push(current);
        }

        let changed = false;
        for (let i = 0; i < nodes.length; i++) {
            if (replaceEmojiTextNode(nodes[i])) changed = true;
        }
        return changed;
    }

    function stripSvgSizeAttrs() {
        const svgs = document.querySelectorAll('svg.lucide');
        for (let i = 0; i < svgs.length; i++) {
            if (svgs[i].hasAttribute('width')) svgs[i].removeAttribute('width');
            if (svgs[i].hasAttribute('height')) svgs[i].removeAttribute('height');
        }
    }

    function patchCreateIcons() {
        if (typeof lucide === 'undefined' || !lucide.createIcons) return;
        if (lucide._lkPatched) return;

        const originalCreateIcons = lucide.createIcons.bind(lucide);

        lucide.createIcons = function (opts) {
            const existingSvgs = document.querySelectorAll('svg[data-lucide]');
            const savedAttrs = [];
            for (let i = 0; i < existingSvgs.length; i++) {
                savedAttrs.push(existingSvgs[i].getAttribute('data-lucide'));
                existingSvgs[i].removeAttribute('data-lucide');
            }

            try {
                originalCreateIcons(opts);
            } catch (err) {
                console.error('[Lucide] Error in createIcons:', err);
            }

            for (let j = 0; j < existingSvgs.length; j++) {
                if (existingSvgs[j].parentNode && savedAttrs[j]) {
                    existingSvgs[j].setAttribute('data-lucide', savedAttrs[j]);
                }
            }

            stripSvgSizeAttrs();
        };

        lucide._lkPatched = true;
    }

    function initIcons() {
        if (typeof lucide === 'undefined') {
            console.warn('[Lucide] Library not loaded.');
            return;
        }

        patchCreateIcons();
        normalizeLegacyIconAttrs(document);
        convertEmojisInNode(document.body || document);

        try {
            lucide.createIcons({
                nameAttr: LUCIDE_DEFAULTS.nameAttr,
                attrs: {
                    'stroke-width': LUCIDE_DEFAULTS['stroke-width'],
                },
            });
        } catch (err) {
            console.error('[Lucide] Error initializing icons:', err);
        }
    }

    let observerDebounce = null;

    function setupObserver() {
        if (typeof MutationObserver === 'undefined') return;

        const observer = new MutationObserver(function (mutations) {
            let needsRefresh = false;

            for (let i = 0; i < mutations.length; i++) {
                const mutation = mutations[i];

                if (mutation.type === 'childList') {
                    for (let n = 0; n < mutation.addedNodes.length; n++) {
                        const node = mutation.addedNodes[n];
                        if (convertEmojisInNode(node)) needsRefresh = true;

                        if (node.nodeType === Node.ELEMENT_NODE) {
                            if (node.tagName === 'I' && node.hasAttribute('data-lucide')) {
                                needsRefresh = true;
                            } else if (node.querySelector && node.querySelector('i[data-lucide]')) {
                                needsRefresh = true;
                            }
                        }
                    }
                } else if (mutation.type === 'characterData') {
                    if (replaceEmojiTextNode(mutation.target)) {
                        needsRefresh = true;
                    }
                }
            }

            if (needsRefresh) {
                clearTimeout(observerDebounce);
                observerDebounce = setTimeout(function () { initIcons(); }, 50);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
            characterData: true,
        });
    }

    function bootstrap() {
        initIcons();
        setupObserver();

        window.LK = window.LK || {};
        window.LK.refreshIcons = function () {
            initIcons();
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrap);
    } else {
        bootstrap();
    }

    window.addEventListener('load', function () {
        setTimeout(function () { initIcons(); }, 100);
    });
})();
