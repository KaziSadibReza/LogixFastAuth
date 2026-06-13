export async function apiRequest<T>(
  baseUrl: string,
  nonce: string,
  path: string,
  options: RequestInit = {}
): Promise<T> {
  const response = await fetch(`${baseUrl}${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': nonce,
      ...(options.headers || {}),
    },
    credentials: 'same-origin',
  });

  const data = await response.json();

  if (!response.ok) {
    const message = data?.message || data?.code || 'Request failed';
    throw new Error(message);
  }

  return data as T;
}
