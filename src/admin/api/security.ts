import { apiRequest } from '@shared/api';

function getAdmin() {
  return window.SLR_ADMIN!;
}

export interface RateBlock {
  id: string;
  action: string;
  key: string;
  ip: string;
  blocked_at: number;
  expires_at: number;
}

export function fetchRateBlocks(): Promise<{ blocks: RateBlock[] }> {
  const admin = getAdmin();
  return apiRequest(admin.apiUrl, admin.nonce, '/security/blocks');
}

export function unblockRateLimit(blockId: string): Promise<{ unblocked: boolean }> {
  const admin = getAdmin();
  return apiRequest(admin.apiUrl, admin.nonce, `/security/blocks/${blockId}/unblock`, {
    method: 'POST',
  });
}
