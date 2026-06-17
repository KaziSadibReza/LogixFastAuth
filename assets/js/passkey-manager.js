/**
 * Passkey manager UI (profile, WooCommerce My Account, Tutor dashboard).
 */
(function () {
	'use strict';

	var cfg = window.SLR_PASSKEY_MANAGER;
	if (!cfg) {
		return;
	}

	function $(id) {
		return document.getElementById(id);
	}

	function b64urlToUint8(str) {
		str = str.replace(/-/g, '+').replace(/_/g, '/');
		var pad = str.length % 4;
		if (pad) {
			str += '===='.slice(pad);
		}
		var raw = atob(str);
		var arr = new Uint8Array(raw.length);
		for (var i = 0; i < raw.length; i++) {
			arr[i] = raw.charCodeAt(i);
		}
		return arr;
	}

	function uint8ToB64url(buf) {
		var str = '';
		var bytes = new Uint8Array(buf);
		for (var i = 0; i < bytes.byteLength; i++) {
			str += String.fromCharCode(bytes[i]);
		}
		return btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
	}

	function parseJson(response) {
		return response.json().then(function (data) {
			if (!response.ok) {
				var message = data && data.message ? data.message : 'HTTP ' + response.status;
				var error = new Error(message);
				error.data = data;
				throw error;
			}
			return data;
		});
	}

	function initPasskeyManager(root) {
		if (!root || root.dataset.slrPkReady === '1') {
			return;
		}

		var listEl = root.querySelector('.slr-pk-list');
		var emptyEl = root.querySelector('.slr-pk-empty');
		var addBtn = root.querySelector('.slr-pk-add');
		var toastEl = root.querySelector('.slr-pk-toast');
		var iconSvg = cfg.iconSvg || '';
		var i18n = cfg.i18n || {};

		if (!listEl || !emptyEl || !addBtn || !toastEl) {
			return;
		}

		root.dataset.slrPkReady = '1';

		function iconMarkup() {
			return iconSvg;
		}

		function toast(message, ok) {
			toastEl.textContent = message;
			toastEl.classList.toggle('is-success', !!ok);
			toastEl.classList.toggle('is-error', !ok);
			toastEl.hidden = false;
			setTimeout(function () {
				toastEl.hidden = true;
			}, 3000);
		}

		function fmtDate(value) {
			if (!value) {
				return '—';
			}
			var dt = new Date(value + 'Z');
			return dt.toLocaleDateString(undefined, {
				year: 'numeric',
				month: 'short',
				day: 'numeric',
			});
		}

		function render(list) {
			if (!list.length) {
				listEl.innerHTML = '';
				emptyEl.hidden = false;
				return;
			}

			emptyEl.hidden = true;
			listEl.innerHTML = list
				.map(function (pk, index) {
					return (
						'<div class="slr-pk-card" data-id="' +
						pk.id +
						'">' +
						'<div class="slr-pk-card-info">' +
						'<div class="slr-pk-icon">' +
						iconMarkup() +
						'</div>' +
						'<div class="slr-pk-meta">' +
						'<strong>' +
						(i18n.passkeyLabel || 'Passkey') +
						' ' +
						(index + 1) +
						'</strong>' +
						'<span>' +
						(i18n.added || 'Added') +
						': ' +
						fmtDate(pk.created_at) +
						'</span>' +
						(pk.last_used_at
							? '<span>' + (i18n.lastUsed || 'Last used') + ': ' + fmtDate(pk.last_used_at) + '</span>'
							: '') +
						'</div></div>' +
						'<button type="button" class="slr-pk-del" data-id="' +
						pk.id +
						'">' +
						(i18n.remove || 'Remove') +
						'</button>' +
						'</div>'
					);
				})
				.join('');
		}

		function load() {
			listEl.innerHTML = '<div class="slr-pk-loading">' + (i18n.loading || 'Loading…') + '</div>';

			fetch(cfg.apiUrl + 'webauthn/credentials', {
				headers: { 'X-WP-Nonce': cfg.nonce },
			})
				.then(parseJson)
				.then(function (data) {
					render(Array.isArray(data) ? data : []);
				})
				.catch(function (err) {
					listEl.innerHTML =
						'<p class="slr-pk-error">' +
						(i18n.loadFailed || 'Failed to load passkeys:') +
						' ' +
						(err.message || 'error') +
						'</p>';
				});
		}

		listEl.addEventListener('click', function (event) {
			var btn = event.target.closest('.slr-pk-del');
			if (!btn) {
				return;
			}

			if (!window.confirm(i18n.confirmRemove || 'Remove this passkey?')) {
				return;
			}

			var id = btn.getAttribute('data-id');
			btn.disabled = true;
			btn.textContent = '…';

			fetch(cfg.apiUrl + 'webauthn/credentials/' + id, {
				method: 'DELETE',
				headers: { 'X-WP-Nonce': cfg.nonce },
			})
				.then(parseJson)
				.then(function (data) {
					if (data.success) {
						toast(i18n.removed || 'Passkey removed.', true);
					} else {
						toast(data.message || i18n.removeFailed || 'Could not remove passkey.', false);
					}
					load();
				})
				.catch(function (err) {
					toast(err.message || i18n.networkError || 'Network error.', false);
					load();
				});
		});

		addBtn.addEventListener('click', function () {
			if (!window.PublicKeyCredential) {
				toast(i18n.unsupported || 'Your browser does not support passkeys.', false);
				return;
			}

			var defaultLabel = addBtn.textContent;
			addBtn.disabled = true;
			addBtn.textContent = i18n.registering || 'Registering…';

			fetch(cfg.apiUrl + 'webauthn/register/options', {
				method: 'POST',
				headers: {
					'X-WP-Nonce': cfg.nonce,
					'Content-Type': 'application/json',
				},
			})
				.then(parseJson)
				.then(function (opts) {
					if (!opts || !opts.challenge) {
						throw new Error(i18n.invalidOptions || 'Invalid registration options received.');
					}

					var publicKey = {
						challenge: b64urlToUint8(opts.challenge),
						rp: opts.rp,
						user: {
							id: b64urlToUint8(opts.user.id),
							name: opts.user.name,
							displayName: opts.user.displayName,
						},
						pubKeyCredParams: opts.pubKeyCredParams,
						timeout: opts.timeout || 60000,
						attestation: opts.attestation || 'none',
						authenticatorSelection: opts.authenticatorSelection || {
							authenticatorAttachment: 'platform',
							requireResidentKey: true,
							residentKey: 'required',
							userVerification: 'preferred',
						},
					};

					if (opts.excludeCredentials) {
						publicKey.excludeCredentials = opts.excludeCredentials.map(function (cred) {
							return {
								type: cred.type,
								id: b64urlToUint8(cred.id),
								transports: cred.transports,
							};
						});
					}

					return navigator.credentials.create({ publicKey: publicKey });
				})
				.then(function (cred) {
					var body = {
						id: cred.id,
						rawId: uint8ToB64url(cred.rawId),
						type: cred.type,
						response: {
							attestationObject: uint8ToB64url(cred.response.attestationObject),
							clientDataJSON: uint8ToB64url(cred.response.clientDataJSON),
						},
					};

					if (cred.response.getTransports) {
						body.response.transports = cred.response.getTransports();
					}

					return fetch(cfg.apiUrl + 'webauthn/register/verify', {
						method: 'POST',
						headers: {
							'X-WP-Nonce': cfg.nonce,
							'Content-Type': 'application/json',
						},
						body: JSON.stringify(body),
					}).then(parseJson);
				})
				.then(function (data) {
					if (data.success) {
						toast(i18n.passkeyAdded || 'Passkey added!', true);
						load();
					} else {
						toast(data.message || i18n.addFailed || 'Could not add passkey.', false);
					}
				})
				.catch(function (err) {
					if (err.name !== 'NotAllowedError') {
						toast(err.message || i18n.addFailed || 'Could not add passkey.', false);
					}
				})
				.finally(function () {
					addBtn.disabled = false;
					addBtn.textContent = defaultLabel;
				});
		});

		load();
	}

	function boot() {
		var roots = document.querySelectorAll('.slr-passkey-manager');
		for (var i = 0; i < roots.length; i++) {
			initPasskeyManager(roots[i]);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
