/**
 * ─────────────────────────────────────────────────────────────────────────────
 * ElectroHub Frontend Logger
 * src/utils/logger.ts
 *
 * Features:
 * - Structured log levels: debug, info, warn, error
 * - Timestamp & colored console output for DevTools
 * - Fetch API interceptor for automatic request/response logging
 * - Sensitive field masking (password, token, etc.)
 * - Production-aware (silent in prod, verbose in dev)
 * ─────────────────────────────────────────────────────────────────────────────
 */

type LogLevel = 'DEBUG' | 'INFO' | 'WARN' | 'ERROR';

interface LogEntry {
  timestamp: string;
  level: LogLevel;
  message: string;
  context?: unknown;
}

// ─── Config ──────────────────────────────────────────────────────────────────
// Use hostname-based check as fallback if import.meta.env is not available
const IS_DEV: boolean = (() => {
  try { return (import.meta as { env?: { DEV?: boolean } }).env?.DEV ?? false; }
  catch { return window.location.hostname === 'localhost'; }
})();

/** Fields that must never appear in logs in plain text */
const MASKED_FIELDS = ['password', 'password_confirmation', 'token', 'accessToken', 'secret'];

// ─── ANSI-style colors for DevTools console ───────────────────────────────────
const COLORS: Record<LogLevel, string> = {
  DEBUG: 'color: #8b949e; font-weight: normal',
  INFO:  'color: #3fb950; font-weight: bold',
  WARN:  'color: #d29922; font-weight: bold',
  ERROR: 'color: #ff7b72; font-weight: bold',
};

// ─── Core writer ──────────────────────────────────────────────────────────────
function writeLog(level: LogLevel, message: string, context?: unknown): void {
  if (!IS_DEV && level === 'DEBUG') return; // Suppress debug logs in production

  const entry: LogEntry = {
    timestamp: new Date().toISOString(),
    level,
    message,
    context: context ? maskSensitiveFields(context) : undefined,
  };

  const prefix = `%c[${entry.timestamp}] [${level}]`;
  const style  = COLORS[level];

  switch (level) {
    case 'ERROR':
      console.error(prefix, style, message, context ?? '');
      break;
    case 'WARN':
      console.warn(prefix, style, message, context ?? '');
      break;
    default:
      console.log(prefix, style, message, context ?? '');
  }
}

// ─── Public Logger API ────────────────────────────────────────────────────────
export const logger = {
  debug: (message: string, context?: unknown) => writeLog('DEBUG', message, context),
  info:  (message: string, context?: unknown) => writeLog('INFO',  message, context),
  warn:  (message: string, context?: unknown) => writeLog('WARN',  message, context),
  error: (message: string, context?: unknown) => writeLog('ERROR', message, context),
};

// ─── Sensitive field masker ───────────────────────────────────────────────────
function maskSensitiveFields(obj: unknown): unknown {
  if (typeof obj !== 'object' || obj === null) return obj;

  const result: Record<string, unknown> = { ...(obj as Record<string, unknown>) };
  for (const key of Object.keys(result)) {
    if (MASKED_FIELDS.includes(key)) {
      result[key] = '***MASKED***';
    } else if (typeof result[key] === 'object') {
      result[key] = maskSensitiveFields(result[key]);
    }
  }
  return result;
}

// ─────────────────────────────────────────────────────────────────────────────
// FETCH INTERCEPTOR
// Patches the global fetch() to automatically log all API calls.
// Call installFetchInterceptor() once in main.tsx.
// ─────────────────────────────────────────────────────────────────────────────
export function installFetchInterceptor(): void {
  const originalFetch = window.fetch.bind(window);

  window.fetch = async (input: RequestInfo | URL, init?: RequestInit): Promise<Response> => {
    const url    = input instanceof Request ? input.url : String(input);
    const method = (init?.method ?? 'GET').toUpperCase();

    // ── Log outgoing request ──────────────────────────────────────────────────
    let parsedBody: unknown = undefined;
    if (init?.body) {
      try { parsedBody = JSON.parse(init.body as string); } catch { parsedBody = '[non-JSON body]'; }
    }

    logger.info(`→ ${method} ${url}`, {
      headers: init?.headers,
      body:    parsedBody ? maskSensitiveFields(parsedBody) : undefined,
    });

    const startTime = performance.now();

    try {
      const response   = await originalFetch(input, init);
      const durationMs = Math.round(performance.now() - startTime);
      const level: LogLevel = response.ok ? 'INFO' : response.status >= 500 ? 'ERROR' : 'WARN';

      writeLog(level, `← ${response.status} ${method} ${url} (${durationMs}ms)`, {
        status: response.status,
        ok:     response.ok,
      });

      return response;
    } catch (err) {
      const durationMs = Math.round(performance.now() - startTime);
      logger.error(`✗ NETWORK ERROR ${method} ${url} (${durationMs}ms)`, {
        error: (err as Error).message,
      });
      throw err;
    }
  };

  logger.info('Fetch interceptor installed ✅');
}

export default logger;
