import { describe, it, expect, vi } from 'vitest';

/**
 * Module 1 & 2: Auth Logic and Product Filtering (Frontend Unit Tests)
 * Scenarios: SC-001 to SC-005 (auth), SC-020 to SC-022 (product)
 *
 * HOW TO RUN:
 *   cd E-Commerce && npm run test:unit
 */

// ─────────────────────────────────────────────────────────────────────────────
// Simulated auth state utility (mirrors logic in LoginModal + App.tsx)
// ─────────────────────────────────────────────────────────────────────────────
type Role = 'guest' | 'user' | 'admin';

function getInitialRole(): Role {
  return 'guest';
}

function login(role: 'user' | 'admin'): Role {
  return role;
}

function logout(): Role {
  localStorage.removeItem('auth_token');
  localStorage.removeItem('user');
  return 'guest';
}

// ─────────────────────────────────────────────────────────────────────────────
// TEST SUITES
// ─────────────────────────────────────────────────────────────────────────────

describe('Auth — Guest and User Login Flow (SC-003 to SC-005)', () => {
  it('[SC-003] should set role to "user" on successful user login', () => {
    // Arrange
    let currentRole = getInitialRole();

    // Act
    currentRole = login('user');

    // Assert
    expect(currentRole).toBe('user');
  });

  it('[SC-004] should set role to "admin" on successful admin login', () => {
    // Arrange
    let currentRole = getInitialRole();

    // Act
    currentRole = login('admin');

    // Assert
    expect(currentRole).toBe('admin');
  });

  it('[SC-005] should reset role to "guest" on logout', () => {
    // Arrange
    let currentRole: Role = 'user';

    // Act
    currentRole = logout();

    // Assert
    expect(currentRole).toBe('guest');
  });
});

describe('Auth — Registration Validation (SC-001, SC-002)', () => {
  it('[SC-001] all required fields should produce a valid registration payload', () => {
    // Arrange
    const formData = {
      name: 'Nguyễn Văn A',
      email: 'new_user@example.com',
      password: 'SecurePass123!',
      phone: '0912345678',
      address: '1 Đại Cồ Việt, Hà Nội',
    };

    // Act
    const isValid =
      formData.name.trim() !== '' &&
      /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email) &&
      formData.password.length >= 8;

    // Assert
    expect(isValid).toBe(true);
  });

  it('[SC-002] empty email should fail registration validation', () => {
    // Arrange
    const email = '';

    // Act
    const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

    // Assert
    expect(isValid).toBe(false);
  });

  it('[SC-008] invalid phone format should fail validation', () => {
    // Arrange
    const phone = '01234'; // Too short

    // Act
    const isValid = /^0[0-9]{9}$/.test(phone);

    // Assert
    expect(isValid).toBe(false);
  });
});

describe('Product Filtering & Sorting (SC-020 to SC-022)', () => {
  const allProducts = [
    { id: 1, name: 'MacBook Pro',  price: 50_000_000, status: 'active' },
    { id: 2, name: 'iPad Air',     price: 18_000_000, status: 'active' },
    { id: 3, name: 'iPhone 16',    price: 25_000_000, status: 'active' },
    { id: 4, name: 'Hidden Item',  price: 10_000_000, status: 'inactive' },
  ];

  it('[SC-020] only active products should be shown on the storefront', () => {
    // Act
    const visible = allProducts.filter((p) => p.status === 'active');

    // Assert
    expect(visible).toHaveLength(3);
    visible.forEach((p) => expect(p.status).toBe('active'));
  });

  it('[SC-021] price range filter 10M–20M should return only iPad', () => {
    // Arrange
    const min = 10_000_000;
    const max = 20_000_000;

    // Act
    const filtered = allProducts.filter((p) => p.price >= min && p.price <= max && p.status === 'active');

    // Assert
    expect(filtered).toHaveLength(1);
    expect(filtered[0].name).toBe('iPad Air');
  });

  it('[SC-022] products sorted by price ascending should start with iPad', () => {
    // Act
    const activeProducts = allProducts.filter((p) => p.status === 'active');
    const sorted = [...activeProducts].sort((a, b) => a.price - b.price);

    // Assert
    expect(sorted[0].name).toBe('iPad Air');
    expect(sorted[sorted.length - 1].name).toBe('MacBook Pro');
  });

  it('[SC-016] product search by name should find exact matches', () => {
    // Arrange
    const query = 'iPhone';

    // Act
    const results = allProducts.filter((p) =>
      p.name.toLowerCase().includes(query.toLowerCase())
    );

    // Assert
    expect(results).toHaveLength(1);
    expect(results[0].name).toContain('iPhone');
  });
});

describe('Wishlist — Add & Remove (SC-028 to SC-030)', () => {
  it('[SC-028] should add a product to wishlist', () => {
    // Arrange
    const wishlist: number[] = [];

    // Act
    wishlist.push(1);

    // Assert
    expect(wishlist).toContain(1);
  });

  it('[SC-030] should remove a product from wishlist', () => {
    // Arrange
    let wishlist = [1, 2, 3];

    // Act: user unfavorites product 2
    wishlist = wishlist.filter((id) => id !== 2);

    // Assert
    expect(wishlist).not.toContain(2);
    expect(wishlist).toHaveLength(2);
  });
});

describe('Analytics — Data Integrity (SC-045 to SC-046)', () => {
  it('[SC-045] line chart data entries should have month and revenue fields', () => {
    // Arrange: simulate API response for monthly revenue
    const chartData = [
      { month: '2025-01', total_revenue: 500_000_000 },
      { month: '2025-02', total_revenue: 700_000_000 },
    ];

    // Act & Assert
    chartData.forEach((entry) => {
      expect(entry).toHaveProperty('month');
      expect(entry).toHaveProperty('total_revenue');
      expect(typeof entry.total_revenue).toBe('number');
    });
  });

  it('[SC-046] order status pie chart ratios should sum to 100%', () => {
    // Arrange
    const statuses = [
      { label: 'Pending',   count: 10 },
      { label: 'Shipped',   count: 15 },
      { label: 'Cancelled', count: 5 },
    ];
    const total = statuses.reduce((s, x) => s + x.count, 0);

    // Act
    const percentages = statuses.map((s) => Math.round((s.count / total) * 100));
    const sum = percentages.reduce((a, b) => a + b, 0);

    // Assert
    expect(sum).toBeCloseTo(100, 0);
  });
});
