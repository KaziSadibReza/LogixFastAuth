import { apiRequest } from '@shared/api';
import type { SlrSettings } from '@shared/types';

function getAdmin() {
  return window.SLR_ADMIN!;
}

export type PhoneSyncTarget = 'woocommerce' | 'tutor' | 'all';

export interface PhoneSyncResult {
  updated_users: number;
  updated_fields: number;
  preview: NonNullable<SlrSettings['phone_sync_preview']>;
}

export function syncPhoneFields(target: PhoneSyncTarget): Promise<PhoneSyncResult> {
  const admin = getAdmin();
  return apiRequest(admin.apiUrl, admin.nonce, '/settings/phone-sync', {
    method: 'POST',
    body: JSON.stringify({ target }),
  });
}
