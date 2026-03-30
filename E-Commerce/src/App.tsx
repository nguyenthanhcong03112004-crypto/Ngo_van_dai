import React, { useState } from 'react';
import { UserOrderHistory } from './modules/user/orders/UserOrderHistory';
import { UserProfile } from './modules/user/profile/UserProfile';
import { AdminProductManager } from './modules/admin/products/AdminProductManager';
import { AdminOrderManager } from './modules/admin/orders/AdminOrderManager';
import { AdminAnalytics } from './modules/admin/analytics/AdminAnalytics';
import { AdminCustomerManager } from './modules/admin/customers/AdminCustomerManager';
import { LandingPage } from './modules/user/LandingPage';
import { ProductsPage } from './modules/user/ProductsPage';
import { PromotionsPage } from './modules/user/PromotionsPage';
import { CheckoutPage } from './modules/user/checkout/CheckoutPage';
import { WishlistPage } from './modules/user/WishlistPage';
import { Header, Footer } from './components/common/HeaderFooter';
import { LoginModal } from './components/auth/LoginModal';
import { RegisterModal } from './components/auth/RegisterModal';
import { WishlistProvider } from './context/WishlistContext';
import ErrorBoundary from './components/ErrorBoundary';
import { ToastProvider } from './context/ToastContext';
import { installFetchInterceptor } from './utils/logger';
import { 
  LayoutDashboard, 
  User as UserIcon, 
  Settings, 
  LogOut, 
  ShoppingCart, 
  Package, 
  Users, 
  BarChart3,
  Home,
  ShieldCheck,
  Heart
} from 'lucide-react';

// Install global Fetch interceptor — logs every API request/response automatically
installFetchInterceptor();

type Role = 'guest' | 'user' | 'admin';

export default function App() {
  return (
    <WishlistProvider>
      <ToastProvider>
        <AppContent />
      </ToastProvider>
    </WishlistProvider>
  );
}

function AppContent() {
  const [role, setRole] = useState<Role>('guest');
  const [activeTab, setActiveTab] = useState('home');
  const [isLoginModalOpen, setIsLoginModalOpen] = useState(false);
  const [isRegisterModalOpen, setIsRegisterModalOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  const handleLogin = (newRole: 'user' | 'admin') => {
    setRole(newRole);
    setIsLoginModalOpen(false);
    setIsRegisterModalOpen(false);
    setActiveTab(newRole === 'admin' ? 'analytics' : 'home');
  };

  const handleRegisterSuccess = () => {
    setIsRegisterModalOpen(false);
    setIsLoginModalOpen(true);
  };

  const handleLogout = () => {
    setRole('guest');
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');
    setActiveTab('home');
  };

  const handleGuestAction = () => {
    if (role === 'guest') {
      setIsLoginModalOpen(true);
    } else {
      setActiveTab('checkout');
    }
  };

  // Render Admin Sidebar
  const renderAdminSidebar = () => (
    <aside className="w-64 bg-slate-900 text-slate-300 flex flex-col shrink-0 sticky top-0 h-screen">
      <div className="p-6 flex items-center gap-3 border-b border-slate-800">
        <div className="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white">
          <ShieldCheck size={24} />
        </div>
        <span className="font-bold text-xl text-white tracking-tight">Admin Panel</span>
      </div>

      <nav className="flex-1 p-4 space-y-2">
        <div className="pb-2 px-2 text-[10px] font-bold uppercase text-slate-500 tracking-widest">Menu chính</div>
        <button 
          onClick={() => setActiveTab('analytics')}
          className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all ${activeTab === 'analytics' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'hover:bg-slate-800'}`}
        >
          <BarChart3 size={20} /> Thống kê
        </button>
        <button 
          onClick={() => setActiveTab('products')}
          className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all ${activeTab === 'products' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'hover:bg-slate-800'}`}
        >
          <LayoutDashboard size={20} /> Sản phẩm
        </button>
        <button 
          onClick={() => setActiveTab('admin-orders')}
          className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all ${activeTab === 'admin-orders' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'hover:bg-slate-800'}`}
        >
          <ShoppingCart size={20} /> Đơn hàng
        </button>
        <button 
          onClick={() => setActiveTab('customers')}
          className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all ${activeTab === 'customers' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'hover:bg-slate-800'}`}
        >
          <Users size={20} /> Khách hàng
        </button>
      </nav>

      <div className="p-4 border-t border-slate-800">
        <button 
          onClick={handleLogout}
          className="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-red-900/20 text-red-400 transition-all text-sm font-medium"
        >
          <LogOut size={18} /> Đăng xuất
        </button>
      </div>
    </aside>
  );

  // Render User/Guest Content
  const renderStorefront = () => (
    <div className="flex flex-col min-h-screen">
      <Header 
        onLoginClick={() => setIsLoginModalOpen(true)} 
        onCartClick={() => setActiveTab('checkout')}
        onTabChange={setActiveTab}
        activeTab={activeTab}
        isLoggedIn={role !== 'guest'} 
        role={role}
        onLogout={handleLogout}
        searchQuery={searchQuery}
        onSearchChange={setSearchQuery}
      />
      
      <main className="flex-1">
        {activeTab === 'home' && (
          <LandingPage 
            onActionClick={handleGuestAction} 
            onTabChange={setActiveTab}
          />
        )}
        {activeTab === 'products' && (
          <ProductsPage 
            onActionClick={handleGuestAction}
            searchQuery={searchQuery}
          />
        )}
        {activeTab === 'promotions' && <PromotionsPage />}
        {activeTab === 'profile' && <div className="py-12 px-4"><UserProfile /></div>}
        {activeTab === 'orders' && <div className="py-12 px-4 max-w-7xl mx-auto"><UserOrderHistory /></div>}
        {activeTab === 'checkout' && <CheckoutPage />}
        {activeTab === 'wishlist' && <WishlistPage />}
      </main>

      <Footer />

      {/* User Quick Nav (Only when logged in) */}
      {role === 'user' && (
        <div className="fixed bottom-8 left-1/2 -translate-x-1/2 bg-white/80 backdrop-blur-xl border border-slate-200 px-6 py-3 rounded-full shadow-2xl flex items-center gap-8 z-50">
          <button onClick={() => setActiveTab('home')} className={`p-2 rounded-full transition-all ${activeTab === 'home' ? 'text-blue-600 bg-blue-50' : 'text-slate-400 hover:text-slate-600'}`}>
            <Home size={24} />
          </button>
          <button onClick={() => setActiveTab('wishlist')} className={`p-2 rounded-full transition-all ${activeTab === 'wishlist' ? 'text-blue-600 bg-blue-50' : 'text-slate-400 hover:text-slate-600'}`}>
            <Heart size={24} />
          </button>
          <button onClick={() => setActiveTab('orders')} className={`p-2 rounded-full transition-all ${activeTab === 'orders' ? 'text-blue-600 bg-blue-50' : 'text-slate-400 hover:text-slate-600'}`}>
            <Package size={24} />
          </button>
          <button onClick={() => setActiveTab('profile')} className={`p-2 rounded-full transition-all ${activeTab === 'profile' ? 'text-blue-600 bg-blue-50' : 'text-slate-400 hover:text-slate-600'}`}>
            <UserIcon size={24} />
          </button>
        </div>
      )}
    </div>
  );

  return (
    <div className="min-h-screen bg-slate-50">
      {role === 'admin' ? (
        <div className="flex">
          {renderAdminSidebar()}
          <main className="flex-1 overflow-y-auto">
            <header className="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-8 sticky top-0 z-10">
              <div className="text-slate-500 font-medium uppercase text-xs tracking-widest font-black">
                {activeTab === 'analytics' && 'Thống kê hệ thống'}
                {activeTab === 'products' && 'Quản lý sản phẩm'}
                {activeTab === 'admin-orders' && 'Quản lý đơn hàng'}
                {activeTab === 'customers' && 'Quản lý khách hàng'}
              </div>
              <div className="flex items-center gap-4">
                <div className="text-right">
                  <div className="text-sm font-bold text-slate-800">Admin Manager</div>
                  <div className="text-[10px] text-blue-600 uppercase font-black tracking-widest">Administrator</div>
                </div>
                <img src="https://picsum.photos/seed/admin/40" alt="Admin" className="w-10 h-10 rounded-full border border-slate-200" referrerPolicy="no-referrer" />
              </div>
            </header>
            <div className="p-8 max-w-7xl mx-auto">
              {activeTab === 'analytics' && <AdminAnalytics />}
              {activeTab === 'products' && <AdminProductManager />}
              {activeTab === 'admin-orders' && <AdminOrderManager />}
              {activeTab === 'customers' && <AdminCustomerManager />}
            </div>
          </main>
        </div>
      ) : (
        renderStorefront()
      )}

      <LoginModal 
        isOpen={isLoginModalOpen} 
        onClose={() => setIsLoginModalOpen(false)} 
        onLogin={handleLogin}
        onSwitchToRegister={() => {
          setIsLoginModalOpen(false);
          setIsRegisterModalOpen(true);
        }}
      />

      <RegisterModal
        isOpen={isRegisterModalOpen}
        onClose={() => setIsRegisterModalOpen(false)}
        onSwitchToLogin={() => {
          setIsRegisterModalOpen(false);
          setIsLoginModalOpen(true);
        }}
        onSuccess={handleRegisterSuccess}
      />
    </div>
  );
}
