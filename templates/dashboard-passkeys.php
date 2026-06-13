<?php
/**
 * Tutor LMS Dashboard – Settings – Passkeys tab.
 *
 * @package SLR
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$api_url = esc_url_raw( trailingslashit( rest_url( 'slr/v1' ) ) );
$nonce   = wp_create_nonce( 'wp_rest' );

// Passkey icon — uses currentColor so it inherits the brand color from CSS.
$pk_icon_svg = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M16.6514 7.34861L17.1817 6.81828V6.81828L16.6514 7.34861ZM16.6514 13.8603L17.1817 14.3906L16.6514 13.8603ZM8.76372 12.4456L8.23339 11.9152L8.23339 11.9152L8.76372 12.4456ZM6.28899 14.9203L6.81932 15.4506H6.81932L6.28899 14.9203ZM9.0797 17.711L8.54937 17.1807L8.54937 17.1807L9.0797 17.711ZM11.5546 15.2361L11.0243 14.7058H11.0243L11.5546 15.2361ZM6.00604 15.7269L5.26063 15.8098H5.26063L6.00604 15.7269ZM6.16075 17.1193L5.41534 17.2021L5.41534 17.2022L6.16075 17.1193ZM6.88068 17.8392L6.79785 18.5847H6.79785L6.88068 17.8392ZM8.27307 17.994L8.35589 17.2485H8.35589L8.27307 17.994ZM6.34939 17.5118L6.87972 16.9815L6.87972 16.9815L6.34939 17.5118ZM6.4882 17.6506L5.95786 18.1809L5.95787 18.1809L6.4882 17.6506ZM8.93621 11.7541L8.20978 11.9406H8.20978L8.93621 11.7541ZM12.2459 15.0638L12.0594 15.7902H12.0594L12.2459 15.0638ZM9.26801 15.1976C8.97351 14.9063 8.49865 14.9089 8.20737 15.2034C7.91609 15.4979 7.9187 15.9728 8.2132 16.264L9.26801 15.1976ZM12.9209 11.0791C12.693 10.8513 12.693 10.482 12.9209 10.2542L11.8602 9.19353C11.0466 10.0071 11.0466 11.3262 11.8602 12.1398L12.9209 11.0791ZM13.7458 11.0791C13.518 11.307 13.1487 11.307 12.9209 11.0791L11.8602 12.1398C12.6738 12.9534 13.9929 12.9534 14.8065 12.1398L13.7458 11.0791ZM13.7458 10.2542C13.9736 10.482 13.9736 10.8513 13.7458 11.0791L14.8065 12.1398C15.6201 11.3262 15.6201 10.0071 14.8065 9.19353L13.7458 10.2542ZM14.8065 9.19353C13.9929 8.37994 12.6738 8.37994 11.8602 9.19353L12.9209 10.2542C13.1487 10.0264 13.518 10.0264 13.7458 10.2542L14.8065 9.19353ZM16.1211 7.87894C17.6263 9.38419 17.6263 11.8247 16.1211 13.3299L17.1817 14.3906C19.2728 12.2996 19.2728 8.90932 17.1817 6.81828L16.1211 7.87894ZM17.1817 6.81828C15.0907 4.72724 11.7004 4.72724 9.60941 6.81828L10.6701 7.87894C12.1753 6.37369 14.6158 6.37369 16.1211 7.87894L17.1817 6.81828ZM8.23339 11.9152L5.75866 14.39L6.81932 15.4506L9.29405 12.9759L8.23339 11.9152ZM9.61003 18.2413L10.437 17.4144L9.37631 16.3537L8.54937 17.1807L9.61003 18.2413ZM10.437 17.4144L12.0849 15.7664L11.0243 14.7058L9.37631 16.3537L10.437 17.4144ZM5.26063 15.8098L5.41534 17.2021L6.90616 17.0365L6.75145 15.6441L5.26063 15.8098ZM6.79785 18.5847L8.19024 18.7394L8.35589 17.2485L6.9635 17.0938L6.79785 18.5847ZM5.81905 18.0421L5.95786 18.1809L7.01853 17.1203L6.87972 16.9815L5.81905 18.0421ZM6.9635 17.0938C6.98431 17.0961 7.00371 17.1055 7.01853 17.1203L5.95787 18.1809C6.18391 18.407 6.48015 18.5494 6.79785 18.5847L6.9635 17.0938ZM5.41534 17.2022C5.45064 17.5199 5.59302 17.8161 5.81906 18.0421L6.87972 16.9815C6.89453 16.9963 6.90385 17.0157 6.90616 17.0365L5.41534 17.2022ZM8.54937 17.1807C8.49854 17.2315 8.42735 17.2565 8.35589 17.2485L8.19024 18.7394C8.71459 18.7976 9.23698 18.6144 9.61003 18.2413L8.54937 17.1807ZM5.75866 14.39C5.38562 14.763 5.20237 15.2854 5.26063 15.8098L6.75145 15.6441C6.74351 15.5727 6.76849 15.5015 6.81932 15.4506L5.75866 14.39ZM9.66264 11.5675C9.33425 10.2888 9.67123 8.87777 10.6701 7.87894L9.60941 6.81828C8.22032 8.20737 7.75495 10.1695 8.20978 11.9406L9.66264 11.5675ZM16.1211 13.3299C15.1222 14.3288 13.7112 14.6657 12.4325 14.3374L12.0594 15.7902C13.8305 16.2451 15.7926 15.7797 17.1817 14.3906L16.1211 13.3299ZM12.0849 15.7664C12.0779 15.7735 12.0703 15.779 12.0633 15.7827C12.0566 15.7864 12.0516 15.7879 12.0493 15.7885C12.0456 15.7894 12.0497 15.7877 12.0594 15.7902L12.4325 14.3374C11.9675 14.218 11.4174 14.3127 11.0243 14.7058L12.0849 15.7664ZM9.29405 12.9759C9.68693 12.583 9.78213 12.0328 9.66264 11.5675L8.20978 11.9406C8.2122 11.95 8.21063 11.9541 8.21149 11.9506C8.21203 11.9483 8.21351 11.9434 8.21715 11.9367C8.2209 11.9299 8.22632 11.9223 8.23339 11.9152L9.29405 12.9759ZM10.434 16.3508L9.26801 15.1976L8.2132 16.264L9.37923 17.4173L10.434 16.3508Z" fill="currentColor"/><path d="M22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C21.5093 4.43821 21.8356 5.80655 21.9449 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
?>

<div class="tutor-fs-4 tutor-fw-medium tutor-mb-24"><?php esc_html_e( 'Settings', 'tutor' ); ?></div>

<div class="tutor-dashboard-content-inner">
	<div class="tutor-mb-32">
		<?php tutor_load_template( 'dashboard.settings.nav-bar', array( 'active_setting_nav' => 'passkeys' ) ); ?>
	</div>

	<div id="slr-passkey-manager">
		<div class="slr-pk-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
			<div>
				<h3 style="margin:0 0 4px;font-size:18px;font-weight:600;"><?php esc_html_e( 'Your Passkeys', 'smart-login-registration' ); ?></h3>
				<p style="margin:0;color:#6b7280;font-size:14px;"><?php esc_html_e( 'Manage the passkeys linked to your account.', 'smart-login-registration' ); ?></p>
			</div>
			<button type="button" id="slr-pk-add" class="tutor-btn tutor-btn-primary tutor-btn-sm">
				<?php esc_html_e( 'Add passkey', 'smart-login-registration' ); ?>
			</button>
		</div>

		<div id="slr-pk-list" style="min-height:60px;">
			<div class="slr-pk-loading" style="text-align:center;padding:24px;color:#9ca3af;">
				<?php esc_html_e( 'Loading…', 'smart-login-registration' ); ?>
			</div>
		</div>

		<div id="slr-pk-empty" style="display:none;text-align:center;padding:48px 16px;border:2px dashed #e5e7eb;border-radius:12px;">
			<div class="slr-pk-empty-icon" aria-hidden="true">
				<?php echo $pk_icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG markup. ?>
			</div>
			<p style="margin:0 0 4px;font-weight:600;font-size:15px;"><?php esc_html_e( 'No passkeys yet', 'smart-login-registration' ); ?></p>
			<p style="margin:0;color:#6b7280;font-size:14px;"><?php esc_html_e( 'Add a passkey to sign in faster next time.', 'smart-login-registration' ); ?></p>
		</div>

		<div id="slr-pk-toast" style="display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);padding:10px 20px;border-radius:8px;font-size:14px;font-weight:500;z-index:99999;box-shadow:0 4px 12px rgba(0,0,0,.15);"></div>
	</div>
</div>

<style>
.slr-pk-card{display:flex;align-items:center;justify-content:space-between;padding:16px;border:1px solid #e5e7eb;border-radius:10px;margin-bottom:10px;background:#fff;transition:box-shadow .15s}
.slr-pk-card:hover{box-shadow:0 2px 8px rgba(0,0,0,.06)}
.slr-pk-card-info{display:flex;align-items:center;gap:14px}
.slr-pk-icon{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,rgba(214,51,108,.12) 0%,rgba(214,51,108,.06) 100%);color:#d6336c;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.slr-pk-icon svg{width:22px;height:22px;display:block}
.slr-pk-empty-icon{width:72px;height:72px;margin:0 auto 16px;border-radius:20px;background:linear-gradient(135deg,rgba(214,51,108,.12) 0%,rgba(214,51,108,.06) 100%);color:#d6336c;display:flex;align-items:center;justify-content:center}
.slr-pk-empty-icon svg{width:32px;height:32px;display:block}
.slr-pk-meta span{display:block;font-size:13px;color:#6b7280}
.slr-pk-meta strong{font-size:15px;font-weight:600}
.slr-pk-del{background:none;border:1px solid #fca5a5;color:#dc2626;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;transition:all .15s}
.slr-pk-del:hover{background:#fef2f2;border-color:#dc2626;color:#c41e3a}
#slr-pk-add{display:inline-flex;align-items:center;gap:6px}
</style>

<script>
(function(){
	var apiUrl  = <?php echo wp_json_encode( $api_url ); ?>;
	var nonce   = <?php echo wp_json_encode( $nonce ); ?>;
	var listEl  = document.getElementById('slr-pk-list');
	var emptyEl = document.getElementById('slr-pk-empty');
	var addBtn  = document.getElementById('slr-pk-add');
	var toastEl = document.getElementById('slr-pk-toast');
	var pkIcon  = <?php echo wp_json_encode( $pk_icon_svg ); ?>;

	function toast(msg, ok){
		toastEl.textContent = msg;
		toastEl.style.background = ok ? '#065f46' : '#991b1b';
		toastEl.style.color = '#fff';
		toastEl.style.display = 'block';
		setTimeout(function(){ toastEl.style.display='none'; }, 3000);
	}

	function fmtDate(d){
		if(!d) return '—';
		var dt = new Date(d + 'Z');
		return dt.toLocaleDateString(undefined, {year:'numeric',month:'short',day:'numeric'});
	}

	function render(list){
		if(!list.length){
			listEl.innerHTML='';
			emptyEl.style.display='block';
			return;
		}
		emptyEl.style.display='none';
		listEl.innerHTML = list.map(function(pk, i){
			return '<div class="slr-pk-card" data-id="'+pk.id+'">'
				+'<div class="slr-pk-card-info">'
				+'<div class="slr-pk-icon">'+pkIcon+'</div>'
				+'<div class="slr-pk-meta">'
				+'<strong><?php echo esc_js( __( 'Passkey', 'smart-login-registration' ) ); ?> '+(i+1)+'</strong>'
				+'<span><?php echo esc_js( __( 'Added', 'smart-login-registration' ) ); ?>: '+fmtDate(pk.created_at)+'</span>'
				+(pk.last_used_at ? '<span><?php echo esc_js( __( 'Last used', 'smart-login-registration' ) ); ?>: '+fmtDate(pk.last_used_at)+'</span>' : '')
				+'</div></div>'
				+'<button type="button" class="slr-pk-del" data-id="'+pk.id+'"><?php echo esc_js( __( 'Remove', 'smart-login-registration' ) ); ?></button>'
				+'</div>';
		}).join('');
	}

	function parseJson(r){
		return r.json().then(function(d){
			if(!r.ok){
				var msg = (d && d.message) ? d.message : ('HTTP '+r.status);
				var err = new Error(msg); err.data = d; throw err;
			}
			return d;
		});
	}

	function load(){
		listEl.innerHTML='<div style="text-align:center;padding:24px;color:#9ca3af"><?php echo esc_js( __( 'Loading…', 'smart-login-registration' ) ); ?></div>';
		fetch(apiUrl+'webauthn/credentials',{headers:{'X-WP-Nonce':nonce}})
			.then(parseJson)
			.then(function(d){render(Array.isArray(d) ? d : []);})
			.catch(function(err){
				listEl.innerHTML='<p style="color:#dc2626;padding:16px;border:1px solid #fecaca;border-radius:8px;background:#fef2f2">'
					+'<?php echo esc_js( __( 'Failed to load passkeys:', 'smart-login-registration' ) ); ?> '
					+(err.message||'error')+'</p>';
			});
	}

	listEl.addEventListener('click',function(e){
		var btn = e.target.closest('.slr-pk-del');
		if(!btn) return;
		if(!confirm('<?php echo esc_js( __( 'Remove this passkey? You won\'t be able to sign in with it anymore.', 'smart-login-registration' ) ); ?>')) return;
		var id = btn.getAttribute('data-id');
		btn.disabled=true;
		btn.textContent='…';
		fetch(apiUrl+'webauthn/credentials/'+id,{method:'DELETE',headers:{'X-WP-Nonce':nonce}})
			.then(parseJson)
			.then(function(d){
				if(d.success){ toast('<?php echo esc_js( __( 'Passkey removed.', 'smart-login-registration' ) ); ?>',true); }
				else { toast(d.message||'<?php echo esc_js( __( 'Could not remove passkey.', 'smart-login-registration' ) ); ?>',false); }
				load();
			})
			.catch(function(err){ toast(err.message||'<?php echo esc_js( __( 'Network error.', 'smart-login-registration' ) ); ?>',false); load(); });
	});

	function b64urlToUint8(str){
		str = str.replace(/-/g,'+').replace(/_/g,'/');
		var pad = str.length % 4; if(pad) str += '===='.slice(pad);
		var raw = atob(str), arr = new Uint8Array(raw.length);
		for(var i=0;i<raw.length;i++) arr[i]=raw.charCodeAt(i);
		return arr;
	}
	function uint8ToB64url(buf){
		var str=''; var bytes=new Uint8Array(buf);
		for(var i=0;i<bytes.byteLength;i++) str+=String.fromCharCode(bytes[i]);
		return btoa(str).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
	}

	addBtn.addEventListener('click', function(){
		if(!window.PublicKeyCredential){
			toast('<?php echo esc_js( __( 'Your browser does not support passkeys.', 'smart-login-registration' ) ); ?>',false);
			return;
		}
		addBtn.disabled = true;
		addBtn.textContent = '<?php echo esc_js( __( 'Registering…', 'smart-login-registration' ) ); ?>';

		fetch(apiUrl+'webauthn/register/options',{method:'POST',headers:{'X-WP-Nonce':nonce,'Content-Type':'application/json'}})
			.then(parseJson)
			.then(function(opts){
				if(!opts || !opts.challenge){
					throw new Error('<?php echo esc_js( __( 'Invalid registration options received.', 'smart-login-registration' ) ); ?>');
				}
				var publicKey = {
					challenge: b64urlToUint8(opts.challenge),
					rp: opts.rp,
					user: {
						id: b64urlToUint8(opts.user.id),
						name: opts.user.name,
						displayName: opts.user.displayName
					},
					pubKeyCredParams: opts.pubKeyCredParams,
					timeout: opts.timeout || 60000,
					attestation: opts.attestation || 'none',
					authenticatorSelection: opts.authenticatorSelection || {
						authenticatorAttachment: 'platform',
						requireResidentKey: true,
						residentKey: 'required',
						userVerification: 'preferred'
					}
				};
				if(opts.excludeCredentials){
					publicKey.excludeCredentials = opts.excludeCredentials.map(function(c){
						return {type:c.type, id:b64urlToUint8(c.id), transports:c.transports};
					});
				}
				return navigator.credentials.create({publicKey:publicKey});
			})
			.then(function(cred){
				var body = {
					id: cred.id,
					rawId: uint8ToB64url(cred.rawId),
					type: cred.type,
					response: {
						attestationObject: uint8ToB64url(cred.response.attestationObject),
						clientDataJSON: uint8ToB64url(cred.response.clientDataJSON)
					}
				};
				if(cred.response.getTransports){
					body.response.transports = cred.response.getTransports();
				}
				return fetch(apiUrl+'webauthn/register/verify',{
					method:'POST',
					headers:{'X-WP-Nonce':nonce,'Content-Type':'application/json'},
					body:JSON.stringify(body)
				}).then(parseJson);
			})
			.then(function(d){
				if(d.success){ toast('<?php echo esc_js( __( 'Passkey added!', 'smart-login-registration' ) ); ?>',true); load(); }
				else { toast(d.message||'<?php echo esc_js( __( 'Could not add passkey.', 'smart-login-registration' ) ); ?>',false); }
			})
			.catch(function(err){
				if(err.name !== 'NotAllowedError'){
					toast(err.message||'<?php echo esc_js( __( 'Could not add passkey.', 'smart-login-registration' ) ); ?>',false);
				}
			})
			.finally(function(){
				addBtn.disabled = false;
				addBtn.textContent = '<?php echo esc_js( __( 'Add passkey', 'smart-login-registration' ) ); ?>';
			});
	});

	load();
})();
</script>
