export type LoginField = 'email' | 'password';
export type RegisterField = 'fullName' | 'email' | 'phone' | 'password' | 'confirmPassword' | 'username';

export type FieldErrors<T extends string> = Partial<Record<T, boolean>>;

export interface LoginValidationOptions {
  allowEmail?: boolean;
  allowPhone?: boolean;
  allowUsername?: boolean;
}

export function mapApiErrorToLoginFields(message: string): FieldErrors<LoginField> {
  const lower = message.toLowerCase();
  const fields: FieldErrors<LoginField> = {};

  if ((lower.includes('email') || lower.includes('phone') || lower.includes('username')) && lower.includes('password')) {
    fields.email = true;
    fields.password = true;
  } else if (lower.includes('email')) {
    fields.email = true;
  } else if (lower.includes('phone')) {
    fields.email = true;
  } else if (lower.includes('username')) {
    fields.email = true;
  } else if (lower.includes('password')) {
    fields.password = true;
  }

  return fields;
}

export function mapApiErrorToRegisterFields(message: string): FieldErrors<RegisterField> {
  const lower = message.toLowerCase();
  const fields: FieldErrors<RegisterField> = {};

  if (lower.includes('all required') || lower.includes('fill in')) {
    return { fullName: true, email: true, phone: true, password: true, confirmPassword: true, username: true };
  }
  if (lower.includes('phone')) fields.phone = true;
  if (lower.includes('email')) fields.email = true;
  if (lower.includes('username')) fields.username = true;
  if (lower.includes('password')) {
    fields.password = true;
    if (lower.includes('match')) fields.confirmPassword = true;
  }
  if (lower.includes('name')) fields.fullName = true;

  return fields;
}

export function validateLoginFields(
  identifier: string,
  password: string,
  messages: { required: string; email: string; phone: string },
  options: LoginValidationOptions = {}
): { message: string; fields: FieldErrors<LoginField> } | null {
  const fields: FieldErrors<LoginField> = {};
  const value = identifier.trim();
  const allowEmail = options.allowEmail !== false;
  const allowPhone = options.allowPhone !== false;
  const allowUsername = options.allowUsername === true;

  if (!value) fields.email = true;
  if (!password) fields.password = true;

  if (Object.keys(fields).length) {
    return { message: messages.required, fields };
  }

  if (value.includes('@')) {
    if (allowEmail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
      return { message: messages.email, fields: { email: true } };
    }
    if (!allowEmail) {
      return { message: messages.email, fields: { email: true } };
    }
    return null;
  }

  const digitsOnly = value.replace(/\D/g, '');
  const looksLikePhone = digitsOnly.length >= 6 && digitsOnly.length === value.replace(/[\s()+-]/g, '').length;

  if (looksLikePhone) {
    if (!allowPhone) {
      return { message: messages.phone, fields: { email: true } };
    }
    return null;
  }

  if (allowUsername) {
    return null;
  }

  if (allowPhone && digitsOnly.length < 6) {
    return { message: messages.phone, fields: { email: true } };
  }

  if (allowEmail) {
    return { message: messages.email, fields: { email: true } };
  }

  return null;
}

export function validateRegisterFields(
  data: {
    fullName: string;
    email: string;
    phone: string;
    password: string;
    confirmPassword: string;
    requirePhone: boolean;
    username?: string;
    showUsername?: boolean;
  },
  messages: {
    required: string;
    email: string;
    phone: string;
    passwordMin: string;
    mismatch: string;
    username?: string;
  }
): { message: string; fields: FieldErrors<RegisterField> } | null {
  const fields: FieldErrors<RegisterField> = {};

  if (!data.fullName.trim()) fields.fullName = true;
  if (!data.email.trim()) fields.email = true;
  if (data.requirePhone && !data.phone.trim()) fields.phone = true;
  if (data.showUsername && !data.username?.trim()) fields.username = true;
  if (!data.password) fields.password = true;
  if (!data.confirmPassword) fields.confirmPassword = true;

  if (Object.keys(fields).length) {
    return { message: messages.required, fields };
  }

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email.trim())) {
    return { message: messages.email, fields: { email: true } };
  }

  if (data.requirePhone && data.phone.replace(/\D/g, '').length < 6) {
    return { message: messages.phone, fields: { phone: true } };
  }

  if (data.password.length < 8) {
    return { message: messages.passwordMin, fields: { password: true } };
  }

  if (data.password !== data.confirmPassword) {
    return { message: messages.mismatch, fields: { password: true, confirmPassword: true } };
  }

  return null;
}
