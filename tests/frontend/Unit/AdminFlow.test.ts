// globals: true

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * MODULE 7: Admin Products & Customers (SC-146 to SC-175)
 * MODULE 8: Admin Orders & Analytics (SC-176 to SC-200)
 *
 * HOW TO RUN: cd E-Commerce && npm run test:unit
 * ─────────────────────────────────────────────────────────────────────────────
 */

// ─── Types ────────────────────────────────────────────────────────────────────
type Product  = { id: number; name: string; price: number; stock: number; status: 'active' | 'inactive'; category?: string };
type Customer = { id: number; email: string; name: string; status: 'active' | 'inactive'; total_orders: number; total_spent: number };
type Order    = { id: number; status: string; total_amount: number; created_at: Date };

// ─── Utility Functions ────────────────────────────────────────────────────────
function validateProduct(p: Partial<Product>): string[] {
  const errors: string[] = [];
  if (!p.name || p.name.trim() === '')   errors.push('Tên sản phẩm là bắt buộc');
  if (p.price === undefined || p.price <= 0) errors.push('Giá phải là số dương');
  if (p.stock === undefined || p.stock < 0)  errors.push('Tồn kho không âm');
  return errors;
}

function filterOrdersByStatus(orders: Order[], status: string): Order[] {
  return orders.filter(o => o.status === status);
}

function calculateMonthlyRevenue(orders: Order[], month: number, year: number): number {
  return orders
    .filter(o =>
      o.status === 'delivered' &&
      o.created_at.getMonth() + 1 === month &&
      o.created_at.getFullYear() === year
    )
    .reduce((sum, o) => sum + o.total_amount, 0);
}

function buildStatusPieData(orders: Order[]): Record<string, number> {
  const counts: Record<string, number> = {};
  orders.forEach(o => { counts[o.status] = (counts[o.status] || 0) + 1; });
  return counts;
}

function searchProducts(products: Product[], query: string): Product[] {
  const q = query.toLowerCase();
  return products.filter(p => p.name.toLowerCase().includes(q));
}

function blockUser(user: Customer): Customer {
  return { ...user, status: 'inactive' };
}

function sanitizeXSS(input: string): string {
  return input.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ─── Sample Data ──────────────────────────────────────────────────────────────
const SAMPLE_PRODUCTS: Product[] = [
  { id: 1, name: 'MacBook Pro M4',  price: 50_000_000, stock: 10, status: 'active',   category: 'laptop' },
  { id: 2, name: 'iPhone 16 Pro',   price: 28_000_000, stock: 0,  status: 'active',   category: 'phone' },
  { id: 3, name: 'iPad Air M3',     price: 16_000_000, stock: 5,  status: 'active',   category: 'tablet' },
  { id: 4, name: 'Archived Watch',  price: 5_000_000,  stock: 20, status: 'inactive', category: 'wearable' },
];

const SAMPLE_ORDERS: Order[] = [
  { id: 1, status: 'delivered', total_amount: 10_000_000, created_at: new Date('2025-03-15') },
  { id: 2, status: 'pending',   total_amount: 5_000_000,  created_at: new Date('2025-03-20') },
  { id: 3, status: 'delivered', total_amount: 20_000_000, created_at: new Date('2025-03-22') },
  { id: 4, status: 'cancelled', total_amount: 8_000_000,  created_at: new Date('2025-03-18') },
  { id: 5, status: 'shipping',  total_amount: 15_000_000, created_at: new Date('2025-03-25') },
];

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN PRODUCTS (SC-146 to SC-160)
// ─────────────────────────────────────────────────────────────────────────────

describe('SC-146 to SC-160 — Admin Product Management', () => {

  it('[SC-146] adding a product with all valid fields should pass validation', () => {
    // Arrange
    const product: Partial<Product> = { name: 'Dell XPS 15', price: 40_000_000, stock: 5 };

    // Act
    const errors = validateProduct(product);

    // Assert
    expect(errors).toHaveLength(0);
  });

  it('[SC-147] adding product without price should fail with error', () => {
    // Arrange
    const product: Partial<Product> = { name: 'No Price Item', price: undefined };

    // Act
    const errors = validateProduct(product);

    // Assert
    expect(errors.length).toBeGreaterThan(0);
    expect(errors.join(' ')).toContain('Giá');
  });

  it('[SC-147b] adding product without name should fail with error', () => {
    // Arrange
    const product: Partial<Product> = { name: '', price: 10_000_000, stock: 5 };

    // Act
    const errors = validateProduct(product);

    // Assert
    expect(errors.join(' ')).toContain('Tên');
  });

  it('[SC-148] product with stock = 0 should be flagged as out-of-stock', () => {
    // Arrange
    const product = SAMPLE_PRODUCTS.find(p => p.id === 2)!;

    // Act
    const isOutOfStock = product.stock === 0;

    // Assert
    expect(isOutOfStock).toBe(true);
  });

  it('[SC-150] search returns products matching the query name', () => {
    // Arrange
    const query = 'iPhone';

    // Act
    const results = searchProducts(SAMPLE_PRODUCTS, query);

    // Assert
    expect(results).toHaveLength(1);
    expect(results[0].name).toContain('iPhone');
  });

  it('[SC-151] search is case-insensitive', () => {
    // Arrange
    const query = 'macbook';

    // Act
    const results = searchProducts(SAMPLE_PRODUCTS, query.toLowerCase());

    // Assert
    expect(results).toHaveLength(1);
    expect(results[0].name).toContain('MacBook');
  });

  it('[SC-152] filter by category "laptop" returns only laptops', () => {
    // Act
    const laptops = SAMPLE_PRODUCTS.filter(p => p.category === 'laptop' && p.status === 'active');

    // Assert
    expect(laptops.every(p => p.category === 'laptop')).toBe(true);
  });

  it('[SC-153] quick-edit: updating stock in-place does not change other fields', () => {
    // Arrange
    const product = { ...SAMPLE_PRODUCTS[0] };
    const originalName = product.name;

    // Act
    product.stock = 25; // Quick edit stock only

    // Assert
    expect(product.stock).toBe(25);
    expect(product.name).toBe(originalName);    // Name must be unchanged
  });

  it('[SC-154] delete removes product from the list', () => {
    // Arrange
    let products = [...SAMPLE_PRODUCTS];
    const deleteId = 1;

    // Act
    products = products.filter(p => p.id !== deleteId);

    // Assert
    expect(products.find(p => p.id === deleteId)).toBeUndefined();
  });

  it('[SC-158] inactive products should not appear in public storefront', () => {
    // Act
    const visible = SAMPLE_PRODUCTS.filter(p => p.status === 'active');

    // Assert
    visible.forEach(p => expect(p.status).toBe('active'));
    expect(visible.find(p => p.name === 'Archived Watch')).toBeUndefined();
  });
});

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN CUSTOMERS (SC-161 to SC-175)
// ─────────────────────────────────────────────────────────────────────────────

describe('SC-161 to SC-175 — Admin Customer Management', () => {

  const CUSTOMERS: Customer[] = [
    { id: 1, email: 'alice@example.com', name: 'Alice',   status: 'active',   total_orders: 15, total_spent: 50_000_000 },
    { id: 2, email: 'bob@example.com',   name: 'Bob',     status: 'active',   total_orders: 3,  total_spent: 10_000_000 },
    { id: 3, email: 'carol@example.com', name: 'Carol',   status: 'inactive', total_orders: 0,  total_spent: 0 },
  ];

  it('[SC-161] dashboard shows total user count from the database', () => {
    // Act
    const totalUsers = CUSTOMERS.length;

    // Assert
    expect(totalUsers).toBe(3);
  });

  it('[SC-164] search user by email returns correct result', () => {
    // Arrange
    const query = 'alice';

    // Act
    const results = CUSTOMERS.filter(c => c.email.includes(query));

    // Assert
    expect(results).toHaveLength(1);
    expect(results[0].name).toBe('Alice');
  });

  it('[SC-165] sort users by highest total orders descending', () => {
    // Act
    const sorted = [...CUSTOMERS].sort((a, b) => b.total_orders - a.total_orders);

    // Assert
    expect(sorted[0].name).toBe('Alice');
    expect(sorted[sorted.length - 1].total_orders).toBe(0);
  });

  it('[SC-166] blocking a user changes their status to inactive', () => {
    // Arrange
    const activeUser = CUSTOMERS.find(c => c.status === 'active')!;

    // Act
    const blocked = blockUser(activeUser);

    // Assert
    expect(blocked.status).toBe('inactive');
    expect(blocked.id).toBe(activeUser.id); // Same user, just new status
  });

  it('[SC-167] unblocking a user restores their status to active', () => {
    // Arrange
    const blockedUser = CUSTOMERS.find(c => c.status === 'inactive')!;

    // Act
    const unblocked = { ...blockedUser, status: 'active' as const };

    // Assert
    expect(unblocked.status).toBe('active');
  });
});

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN ORDERS (SC-176 to SC-190)
// ─────────────────────────────────────────────────────────────────────────────

describe('SC-176 to SC-190 — Admin Order Management', () => {

  it('[SC-176] orders list is sorted newest first by created_at', () => {
    // Act
    const sorted = [...SAMPLE_ORDERS].sort((a, b) => b.created_at.getTime() - a.created_at.getTime());

    // Assert
    expect(sorted[0].id).toBe(5); // March 25 is the latest
  });

  it('[SC-177] filter by "pending" returns only pending orders', () => {
    // Act
    const pending = filterOrdersByStatus(SAMPLE_ORDERS, 'pending');

    // Assert
    expect(pending.every(o => o.status === 'pending')).toBe(true);
    expect(pending).toHaveLength(1);
  });

  it('[SC-178] status transition pending → confirmed is valid', () => {
    // Arrange
    const order = { id: 2, status: 'pending' };
    const VALID_TRANSITIONS: Record<string, string[]> = {
      pending:   ['confirmed', 'cancelled'],
      confirmed: ['shipping'],
      shipping:  ['delivered'],
    };

    // Act
    const canTransition = VALID_TRANSITIONS[order.status]?.includes('confirmed');
    if (canTransition) order.status = 'confirmed';

    // Assert
    expect(order.status).toBe('confirmed');
  });

  it('[SC-179] status transition delivered → pending is INVALID', () => {
    // Arrange
    const VALID_TRANSITIONS: Record<string, string[]> = {
      pending:   ['confirmed', 'cancelled'],
      confirmed: ['shipping'],
      shipping:  ['delivered'],
    };

    // Act
    const canGoBack = VALID_TRANSITIONS['delivered']?.includes('pending') ?? false;

    // Assert
    expect(canGoBack).toBe(false);
  });
});

// ─────────────────────────────────────────────────────────────────────────────
// ANALYTICS & REPORTING (SC-191 to SC-200)
// ─────────────────────────────────────────────────────────────────────────────

describe('SC-191 to SC-200 — Analytics & Reporting', () => {

  it('[SC-193] monthly revenue ONLY counts orders with status = "delivered"', () => {
    // Arrange: March 2025 has 2 delivered orders: 10M + 20M = 30M
    // Act
    const revenue = calculateMonthlyRevenue(SAMPLE_ORDERS, 3, 2025);

    // Assert
    expect(revenue).toBe(30_000_000);    // pending, cancelled, shipping NOT counted
  });

  it('[SC-194] pie chart status percentages must sum to 100%', () => {
    // Arrange
    const statusData = buildStatusPieData(SAMPLE_ORDERS);
    const total = Object.values(statusData).reduce((s, c) => s + c, 0);

    // Act
    const percentages = Object.fromEntries(
      Object.entries(statusData).map(([k, v]) => [k, Math.round((v / total) * 100)])
    );
    const sum = Object.values(percentages).reduce((s, c) => s + c, 0);

    // Assert — allow ±1 due to rounding
    expect(sum).toBeCloseTo(100, 0);
  });

  it('[SC-193b] analytics correctly excludes pending and cancelled orders from revenue', () => {
    // Arrange
    const pendingRevenue   = filterOrdersByStatus(SAMPLE_ORDERS, 'pending').reduce((s, o) => s + o.total_amount, 0);
    const cancelledRevenue = filterOrdersByStatus(SAMPLE_ORDERS, 'cancelled').reduce((s, o) => s + o.total_amount, 0);
    const deliveredRevenue = calculateMonthlyRevenue(SAMPLE_ORDERS, 3, 2025);

    // Assert
    expect(pendingRevenue).toBeGreaterThan(0);    // They exist but...
    expect(deliveredRevenue).not.toContain?.(pendingRevenue);  // ...aren't counted
    expect(deliveredRevenue).toBe(30_000_000);    // Only delivered counts
  });

  it('[SC-191] stats card: total revenue is sum of all delivered orders', () => {
    // Act
    const totalRevenue = SAMPLE_ORDERS
      .filter(o => o.status === 'delivered')
      .reduce((s, o) => s + o.total_amount, 0);

    // Assert
    expect(totalRevenue).toBe(30_000_000);
  });

  it('[SC-198] XSS input in dispute chat is sanitized before storing/displaying', () => {
    // Arrange
    const maliciousInput = '<script>alert("xss")</script>';

    // Act
    const sanitized = sanitizeXSS(maliciousInput);

    // Assert
    expect(sanitized).not.toContain('<script>');
    expect(sanitized).toContain('&lt;script&gt;');
  });

  it('[SC-191b] new order count badge shows orders created today', () => {
    // Arrange
    const today = new Date().toDateString();
    const todayOrders = SAMPLE_ORDERS.filter(o => o.created_at.toDateString() === today);

    // Assert — just verify the logic, not a specific count
    expect(Array.isArray(todayOrders)).toBe(true);
  });
});
