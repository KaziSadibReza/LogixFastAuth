/**
 * Glass modals for deactivate / delete on plugins.php.
 */
(function () {
	'use strict';

	var cfg = window.LOGIXFAST_AUTH_LIFECYCLE;
	if (!cfg) return;

	var overlay = null;

	function sprintf(t, n) {
		return String(t).replace('%d', String(n));
	}

	function closeModal() {
		if (overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);
		overlay = null;
		document.body.style.overflow = '';
	}

	function openModal(html, onMount) {
		closeModal();
		overlay = document.createElement('div');
		overlay.className = 'logixfast-auth-lifecycle-overlay';
		overlay.setAttribute('role', 'dialog');
		overlay.setAttribute('aria-modal', 'true');
		var style = cfg.style || {};
		overlay.style.setProperty('--logixfast-auth-primary', style.primary || '#d6336c');
		overlay.style.setProperty('--logixfast-auth-text', style.text || '#1e293b');
		overlay.style.setProperty('--logixfast-auth-blur', style.blur || '24px');
		overlay.style.setProperty('--logixfast-auth-radius', style.radius || '12px');

		var card = document.createElement('div');
		card.className = 'logixfast-auth-lifecycle-card';
		card.innerHTML = html;
		overlay.appendChild(card);
		overlay.addEventListener('click', function (e) {
			if (e.target === overlay) closeModal();
		});
		document.body.appendChild(overlay);
		document.body.style.overflow = 'hidden';
		if (onMount) onMount(card);
	}

	function isOurLink(href) {
		return href && (href.indexOf(cfg.pluginFile) !== -1 || href.indexOf(encodeURIComponent(cfg.pluginFile)) !== -1);
	}

	function flagPurge() {
		var body = new FormData();
		body.append('action', 'logixfast_auth_flag_purge_data');
		body.append('nonce', cfg.nonce);
		return fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body }).then(function (r) {
			return r.json();
		});
	}

	function warnText(i18n) {
		var text = i18n.purgeSummary;
		if (cfg.passkeyRows > 0) {
			text += ' ' + sprintf(i18n.passkeyCount, cfg.passkeyRows);
		}
		return text;
	}

	function bindLifecycleModal(card, targetUrl, purgeLabel) {
		var i18n = cfg.i18n;
		var toggle = card.querySelector('[data-purge-toggle]');
		var warn = card.querySelector('.logixfast-auth-lifecycle-warn');
		var ackWrap = card.querySelector('.logixfast-auth-lifecycle-ack');
		var ack = card.querySelector('[data-purge-ack]');
		var purgeBtn = card.querySelector('[data-action="purge"]');

		function sync() {
			var on = toggle.checked;
			warn.classList.toggle('is-visible', on);
			ackWrap.classList.toggle('is-visible', on);
			purgeBtn.classList.toggle('is-hidden', !on);
			purgeBtn.disabled = !on || !ack.checked;
		}

		toggle.addEventListener('change', sync);
		ack.addEventListener('change', sync);
		sync();

		card.querySelector('[data-action="cancel"]').addEventListener('click', closeModal);
		card.querySelector('[data-action="keep"]').addEventListener('click', function () {
			window.location.href = targetUrl;
		});
		purgeBtn.addEventListener('click', function () {
			if (!toggle.checked || !ack.checked) return;
			purgeBtn.disabled = true;
			purgeBtn.textContent = i18n.working;
			flagPurge()
				.then(function (res) {
					if (!res || !res.success) throw new Error('fail');
					window.location.href = targetUrl;
				})
				.catch(function () {
					purgeBtn.disabled = false;
					purgeBtn.textContent = purgeLabel;
					window.alert('Could not prepare data removal. Please try again.');
				});
		});
	}

	function showModal(title, targetUrl, keepLabel, purgeLabel) {
		var i18n = cfg.i18n;

		openModal(
			'<h2>' + title + '</h2>' +
				'<label class="logixfast-auth-lifecycle-toggle">' +
				'<input type="checkbox" data-purge-toggle />' +
				'<span class="logixfast-auth-lifecycle-toggle__text">' +
				'<strong>' + i18n.purgeToggle + '</strong>' +
				'<em>' + i18n.purgeHint + '</em>' +
				'</span>' +
				'</label>' +
				'<p class="logixfast-auth-lifecycle-warn">' + warnText(i18n) + '</p>' +
				'<label class="logixfast-auth-lifecycle-ack">' +
				'<input type="checkbox" data-purge-ack />' +
				'<span>' + i18n.purgeAck + '</span>' +
				'</label>' +
				'<div class="logixfast-auth-lifecycle-actions">' +
				'<button type="button" class="logixfast-auth-lifecycle-btn logixfast-auth-lifecycle-btn--ghost" data-action="cancel">' + i18n.cancel + '</button>' +
				'<button type="button" class="logixfast-auth-lifecycle-btn logixfast-auth-lifecycle-btn--primary" data-action="keep">' + keepLabel + '</button>' +
				'<button type="button" class="logixfast-auth-lifecycle-btn logixfast-auth-lifecycle-btn--danger is-hidden" data-action="purge" disabled>' + purgeLabel + '</button>' +
				'</div>',
			function (card) {
				bindLifecycleModal(card, targetUrl, purgeLabel);
			}
		);
	}

	document.addEventListener(
		'click',
		function (e) {
			var link = e.target.closest('a');
			if (!link || !link.href || !isOurLink(link.href)) return;

			if (link.href.indexOf('action=deactivate') !== -1) {
				e.preventDefault();
				e.stopPropagation();
				showModal(cfg.i18n.deactivateTitle, link.href, cfg.i18n.deactivateKeep, cfg.i18n.deactivatePurge);
				return;
			}

			if (link.href.indexOf('delete') !== -1 || link.classList.contains('delete')) {
				e.preventDefault();
				e.stopPropagation();
				showModal(cfg.i18n.deleteTitle, link.href, cfg.i18n.deleteKeep, cfg.i18n.deletePurge);
			}
		},
		true
	);
})();
