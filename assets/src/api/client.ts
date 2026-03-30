export class ApiError extends Error {
  constructor(
    public readonly status: number,
    message: string,
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

class ApiClient {
  private get base(): string {
    return window.wpAiAgent.root;
  }

  private get nonce(): string {
    return window.wpAiAgent.nonce;
  }

  async post<T>(endpoint: string, body: unknown): Promise<T> {
    const res = await fetch(`${this.base}${endpoint}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      },
      body: JSON.stringify(body),
    });

    if (!res.ok) {
      const err = await res.json().catch(() => ({ message: 'Unknown error' }));
      throw new ApiError(res.status, err.message ?? err.error ?? 'Unknown error');
    }

    return res.json() as Promise<T>;
  }

  async get<T>(endpoint: string): Promise<T> {
    const res = await fetch(`${this.base}${endpoint}`, {
      headers: { 'X-WP-Nonce': this.nonce },
    });

    if (!res.ok) {
      const err = await res.json().catch(() => ({ message: 'Unknown error' }));
      throw new ApiError(res.status, err.message ?? err.error ?? 'Unknown error');
    }

    return res.json() as Promise<T>;
  }
}

export const api = new ApiClient();
