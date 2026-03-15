import { appConfig } from '@/src/lib/config/app-config';

type QueryValue = string | number | boolean | undefined | null;
type HttpRequestOptions = {
  csrf?: boolean;
  tokenId?: string;
};

let csrfTokenState: { token: string; tokenId: string } | null = null;

export function clearCsrfTokenCache() {
  csrfTokenState = null;
}

export class HttpClientError extends Error {
  constructor(
    message: string,
    public readonly status?: number,
    public readonly code?: string,
    public readonly details?: unknown
  ) {
    super(message);
    this.name = 'HttpClientError';
  }
}

function isFormDataBody(body: RequestInit['body']) {
  return typeof FormData !== 'undefined' && body instanceof FormData;
}

function buildUrl(path: string, query?: Record<string, QueryValue>) {
  const url = new URL(path.replace(/^\//, ''), appConfig.apiBaseUrl);

  if (query) {
    Object.entries(query).forEach(([key, value]) => {
      if (value === undefined || value === null || value === '') {
        return;
      }

      url.searchParams.set(key, String(value));
    });
  }

  return url.toString();
}

function rememberCsrfToken(payload: unknown) {
  if (!payload || typeof payload !== 'object') {
    return;
  }

  let nextToken: string | null = null;

  if ('data' in payload && payload.data && typeof payload.data === 'object') {
    const data = payload.data as { csrf_token?: unknown };
    if (typeof data.csrf_token === 'string' && data.csrf_token) {
      nextToken = data.csrf_token;
    }
  }

  if (!nextToken && 'csrf_token' in payload && typeof payload.csrf_token === 'string') {
    nextToken = payload.csrf_token;
  }

  if (nextToken) {
    csrfTokenState = {
      token: nextToken,
      tokenId: 'default',
    };
  }
}

async function parseJson(response: Response) {
  try {
    return await response.json();
  } catch {
    return null;
  }
}

async function getCsrfToken(tokenId = 'default') {
  if (csrfTokenState && csrfTokenState.tokenId === tokenId) {
    return csrfTokenState.token;
  }

  const data = await request<{ token: string }>(
    'api/csrf/refresh',
    {
      method: 'POST',
      body: JSON.stringify({ token_id: tokenId }),
    },
    undefined,
    {}
  );

  if (!data?.token) {
    throw new HttpClientError('Unable to refresh CSRF token.', 0, 'CSRF_REFRESH_FAILED');
  }

  csrfTokenState = {
    token: data.token,
    tokenId,
  };

  return data.token;
}

async function buildHeaders(init?: RequestInit, options?: HttpRequestOptions) {
  const headers = new Headers(init?.headers);

  headers.set('Accept', 'application/json');
  headers.set('X-Requested-With', 'XMLHttpRequest');

  if (init?.body && !headers.has('Content-Type') && !isFormDataBody(init.body)) {
    headers.set('Content-Type', 'application/json');
  }

  if (options?.csrf) {
    headers.set('X-CSRF-Token', await getCsrfToken(options.tokenId));
  }

  return headers;
}

async function request<T>(
  path: string,
  init?: RequestInit,
  query?: Record<string, QueryValue>,
  options?: HttpRequestOptions
) {
  if (!appConfig.apiBaseUrl) {
    throw new HttpClientError('API base URL not configured.', 0, 'NO_BASE_URL');
  }

  const response = await fetch(buildUrl(path, query), {
    ...init,
    headers: await buildHeaders(init, options),
    credentials: 'include',
  });

  const payload = await parseJson(response);
  rememberCsrfToken(payload);

  if (!response.ok) {
    throw new HttpClientError(
      payload?.message ?? `Request failed with status ${response.status}.`,
      response.status,
      undefined,
      payload?.errors
    );
  }

  if (payload?.success === false) {
    throw new HttpClientError(
      payload?.message ?? 'Request failed.',
      response.status,
      undefined,
      payload?.errors
    );
  }

  if (payload && typeof payload === 'object' && 'data' in payload) {
    return payload.data as T;
  }

  return payload as T;
}

export const httpClient = {
  get<T>(path: string, query?: Record<string, QueryValue>) {
    return request<T>(path, { method: 'GET' }, query);
  },
  post<T>(
    path: string,
    body?: unknown,
    query?: Record<string, QueryValue>,
    options?: HttpRequestOptions
  ) {
    return request<T>(
      path,
      {
        method: 'POST',
        body: body ? JSON.stringify(body) : undefined,
      },
      query,
      options
    );
  },
  postForm<T>(
    path: string,
    formData: FormData,
    query?: Record<string, QueryValue>,
    options?: HttpRequestOptions
  ) {
    return request<T>(
      path,
      {
        method: 'POST',
        body: formData,
      },
      query,
      options
    );
  },
};
