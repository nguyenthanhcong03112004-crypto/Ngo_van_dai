import React, { Component, ErrorInfo, ReactNode } from 'react';
import { logger } from '../utils/logger';

interface ErrorBoundaryProps {
  children: ReactNode;
  fallback?: ReactNode;
  onError?: (error: Error, errorInfo: ErrorInfo) => void;
}

interface ErrorBoundaryState {
  hasError:   boolean;
  error:      Error | null;
  errorInfo:  ErrorInfo | null;
}

class ErrorBoundary extends React.Component<ErrorBoundaryProps, ErrorBoundaryState> {
  state: ErrorBoundaryState = { hasError: false, error: null, errorInfo: null };

  constructor(props: ErrorBoundaryProps) {
    super(props);
  }

  // Invoked when a descendant component throws during rendering
  static getDerivedStateFromError(error: Error): Partial<ErrorBoundaryState> {
    return { hasError: true, error };
  }

  // Invoked after the error is caught — best place for logging
  componentDidCatch(error: Error, errorInfo: ErrorInfo): void {
    // Log to the structured frontend logger
    logger.error('💥 React Error Boundary caught a crash', {
      message:       error.message,
      name:          error.name,
      stack:         error.stack?.split('\n').slice(0, 8).join('\n'),
      componentStack: errorInfo.componentStack?.split('\n').slice(0, 10).join('\n'),
      location:      window.location.href,
      timestamp:     new Date().toISOString(),
    });

    // Call optional parent callback (can be used to send to Sentry etc.)
    this.props.onError?.(error, errorInfo);
    this.setState({ errorInfo });
  }

  private handleReload = (): void => {
    window.location.reload();
  };

  private handleReset = (): void => {
    this.setState({ hasError: false, error: null, errorInfo: null });
  };

  render(): ReactNode {
    if (!this.state.hasError) {
      return this.props.children;
    }

    // If a custom fallback is provided, render it
    if (this.props.fallback) {
      return this.props.fallback;
    }

    // ── Default Friendly Fallback UI ──────────────────────────────────────────
    return (
      <div style={{
        minHeight:      '100vh',
        display:        'flex',
        flexDirection:  'column',
        alignItems:     'center',
        justifyContent: 'center',
        background:     'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)',
        color:          '#e2e8f0',
        fontFamily:     "'Inter', -apple-system, sans-serif",
        padding:        '2rem',
        textAlign:      'center',
      }}>
        {/* Error icon */}
        <div style={{ fontSize: '4rem', marginBottom: '1rem' }}>🔧</div>

        <h1 style={{ fontSize: '1.75rem', fontWeight: 700, color: '#f8fafc', marginBottom: '0.5rem' }}>
          Ứng dụng gặp sự cố
        </h1>

        <p style={{ color: '#94a3b8', maxWidth: '420px', lineHeight: 1.6, marginBottom: '1.5rem' }}>
          Đã xảy ra lỗi không mong muốn. Đội ngũ kỹ thuật của chúng tôi đã được thông báo.
          Bạn có thể thử tải lại trang hoặc quay về trang chủ.
        </p>

        {/* Error detail (dev-only) */}
        {(window.location.hostname === 'localhost') && this.state.error && (
          <details style={{
            background:   'rgba(255,99,71,0.1)',
            border:       '1px solid rgba(255,99,71,0.3)',
            borderRadius: '8px',
            padding:      '1rem',
            marginBottom: '1.5rem',
            maxWidth:     '600px',
            textAlign:    'left',
            cursor:       'pointer',
          }}>
            <summary style={{ fontWeight: 600, color: '#ff7b72', marginBottom: '0.5rem' }}>
              🐛 Chi tiết lỗi (chỉ hiển thị trong môi trường phát triển)
            </summary>
            <pre style={{
              fontSize:   '0.75rem',
              color:      '#fca5a5',
              overflow:   'auto',
              whiteSpace: 'pre-wrap',
              wordBreak:  'break-word',
            }}>
              {this.state.error.name}: {this.state.error.message}
              {'\n\n'}
              {this.state.error.stack?.split('\n').slice(0, 10).join('\n')}
            </pre>
          </details>
        )}

        {/* Action buttons */}
        <div style={{ display: 'flex', gap: '1rem', flexWrap: 'wrap', justifyContent: 'center' }}>
          <button
            onClick={this.handleReload}
            style={{
              padding:       '0.65rem 1.5rem',
              background:    'linear-gradient(135deg, #3b82f6, #2563eb)',
              color:         '#fff',
              border:        'none',
              borderRadius:  '8px',
              fontSize:      '0.9rem',
              fontWeight:    600,
              cursor:        'pointer',
              transition:    'opacity 0.2s',
            }}
            onMouseOver={(e) => (e.currentTarget.style.opacity = '0.85')}
            onMouseOut={(e)  => (e.currentTarget.style.opacity = '1')}
          >
            🔄 Tải lại trang
          </button>

          <button
            onClick={this.handleReset}
            style={{
              padding:       '0.65rem 1.5rem',
              background:    'rgba(255,255,255,0.08)',
              color:         '#e2e8f0',
              border:        '1px solid rgba(255,255,255,0.15)',
              borderRadius:  '8px',
              fontSize:      '0.9rem',
              fontWeight:    600,
              cursor:        'pointer',
              transition:    'background 0.2s',
            }}
            onMouseOver={(e) => (e.currentTarget.style.background = 'rgba(255,255,255,0.15)')}
            onMouseOut={(e)  => (e.currentTarget.style.background = 'rgba(255,255,255,0.08)')}
          >
            ↩ Thử lại
          </button>
        </div>
      </div>
    );
  }
}

export default ErrorBoundary;
