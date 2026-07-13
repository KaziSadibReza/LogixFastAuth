import type { LogixFastAuthSettings } from '@shared/types';

export type AuthPlaceholderKey = keyof LogixFastAuthSettings['auth']['placeholders'];

function joinList(items: string[]): string {
  if (items.length <= 1) return items[0] ?? '';
  if (items.length === 2) return `${items[0]} or ${items[1]}`;
  return `${items.slice(0, -1).join(', ')}, or ${items[items.length - 1]}`;
}

export function getLoginMethods(auth: LogixFastAuthSettings['auth']): Array<'email' | 'phone' | 'username'> {
  const methods: Array<'email' | 'phone' | 'username'> = [];
  if (auth.login_allow_email !== false) methods.push('email');
  if (auth.login_allow_phone !== false) methods.push('phone');
  if (auth.login_allow_username) methods.push('username');
  return methods.length ? methods : ['email'];
}

export function buildLoginIdentifierLabel(auth: LogixFastAuthSettings['auth']): string {
  const labels: string[] = [];
  for (const method of getLoginMethods(auth)) {
    if (method === 'email') labels.push('Email');
    if (method === 'phone') labels.push('Phone');
    if (method === 'username') labels.push('Username');
  }
  return joinList(labels);
}

export function buildLoginIdentifierPlaceholderDefault(auth: LogixFastAuthSettings['auth']): string {
  const methods = getLoginMethods(auth);

  if (methods.length > 1) {
    return buildLoginIdentifierLabel(auth);
  }

  const method = methods[0] ?? 'email';
  if (method === 'email') return 'you@example.com';
  if (method === 'phone') return 'Phone number';
  return 'Username';
}

export function getPlaceholderDefaults(auth: LogixFastAuthSettings['auth']) {
  return {
    login_identifier: buildLoginIdentifierPlaceholderDefault(auth),
    register_username: 'Username',
    register_full_name: 'Jane Doe',
    register_email: 'you@example.com',
    register_phone: 'Phone number',
    register_password: 'Min 8 characters',
    login_password: 'Enter your password',
  };
}

export function resolvePlaceholders(auth: LogixFastAuthSettings['auth']) {
  const defaults = getPlaceholderDefaults(auth);

  if (!auth.use_custom_placeholders) {
    return {
      loginIdentifier: defaults.login_identifier,
      registerUsername: defaults.register_username,
      registerFullName: defaults.register_full_name,
      registerEmail: defaults.register_email,
      registerPhone: defaults.register_phone,
      registerPassword: defaults.register_password,
      loginPassword: defaults.login_password,
    };
  }

  const overrides = auth.placeholders ?? defaults;
  return {
    loginIdentifier: overrides.login_identifier?.trim() || defaults.login_identifier,
    registerUsername: overrides.register_username?.trim() || defaults.register_username,
    registerFullName: overrides.register_full_name?.trim() || defaults.register_full_name,
    registerEmail: overrides.register_email?.trim() || defaults.register_email,
    registerPhone: overrides.register_phone?.trim() || defaults.register_phone,
    registerPassword: overrides.register_password?.trim() || defaults.register_password,
    loginPassword: overrides.login_password?.trim() || defaults.login_password,
  };
}

export function countEnabledLoginMethods(auth: LogixFastAuthSettings['auth']): number {
  return getLoginMethods(auth).length;
}

export const loginPlaceholderFields: { key: AuthPlaceholderKey; label: string }[] = [
  { key: 'login_identifier', label: 'Login identifier' },
  { key: 'login_password', label: 'Login password' },
];

export const registerPlaceholderFields: { key: AuthPlaceholderKey; label: string }[] = [
  { key: 'register_full_name', label: 'Full name' },
  { key: 'register_email', label: 'Email' },
  { key: 'register_username', label: 'Username' },
  { key: 'register_phone', label: 'Phone' },
  { key: 'register_password', label: 'Password' },
];
