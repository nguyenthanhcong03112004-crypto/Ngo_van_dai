import React, { createContext, useContext, useState, useCallback } from 'react';

type ToastType = 'success' | 'error' | 'info';

interface Toast {
  id: string;
  message: string;
  type: ToastType;
}

interface ToastContextType {
  showToast: (message: string, type?: ToastType) => void;
}

const ToastContext = createContext<ToastContextType | undefined>(undefined);

export const ToastProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [toasts, setToasts] = useState<Toast[]>([]);

  const showToast = useCallback((message: string, type: ToastType = 'info') => {
    const id = Math.random().toString(36).substr(2, 9);
    setToasts((prev) => [...prev, { id, message, type }]);

    // Auto remove after 5 seconds for debug visibility
    setTimeout(() => {
      setToasts((prev) => prev.filter((t) => t.id !== id));
    }, 5000);
  }, []);

  return (
    <ToastContext.Provider value={{ showToast }}>
      {children}
      <div className="fixed bottom-6 right-6 z-[200] flex flex-col gap-3 max-w-md w-full pointer-events-none">
        {toasts.map((toast) => (
          <div 
            key={toast.id}
            className={`pointer-events-auto p-4 rounded-2xl shadow-2xl border flex items-center gap-3 animate-in slide-in-from-right duration-300 ${
              toast.type === 'success' ? 'bg-emerald-50 border-emerald-100 text-emerald-700' :
              toast.type === 'error' ? 'bg-red-50 border-red-100 text-red-700' :
              'bg-blue-50 border-blue-100 text-blue-700'
            }`}
          >
            <div className={`w-2 h-2 rounded-full shrink-0 ${
              toast.type === 'success' ? 'bg-emerald-500' :
              toast.type === 'error' ? 'bg-red-500' :
              'bg-blue-500'
            }`} />
            <p className="text-sm font-bold flex-1 leading-tight">{toast.message}</p>
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  );
};

export const useToast = () => {
  const context = useContext(ToastContext);
  if (!context) {
    throw new Error('useToast must be used within a ToastProvider');
  }
  return context;
};
