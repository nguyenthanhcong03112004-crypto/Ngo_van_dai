export interface Customer {
  id: string;
  name: string;
  email: string;
  phone: string;
  joinDate: string;
  status: 'active' | 'inactive' | 'blocked';
  totalOrders: number;
  totalSpent: number;
  avatar: string;
}

export const MOCK_CUSTOMERS: Customer[] = [
  {
    id: 'CUST-001',
    name: 'Nguyễn Văn A',
    email: 'nguyenvana@gmail.com',
    phone: '0901234567',
    joinDate: '2025-01-15',
    status: 'active',
    totalOrders: 12,
    totalSpent: 45000000,
    avatar: 'https://picsum.photos/seed/cust1/100'
  },
  {
    id: 'CUST-002',
    name: 'Trần Thị B',
    email: 'tranthib@gmail.com',
    phone: '0912345678',
    joinDate: '2025-02-10',
    status: 'active',
    totalOrders: 5,
    totalSpent: 12500000,
    avatar: 'https://picsum.photos/seed/cust2/100'
  },
  {
    id: 'CUST-003',
    name: 'Lê Văn C',
    email: 'levanc@gmail.com',
    phone: '0923456789',
    joinDate: '2025-03-05',
    status: 'inactive',
    totalOrders: 0,
    totalSpent: 0,
    avatar: 'https://picsum.photos/seed/cust3/100'
  },
  {
    id: 'CUST-004',
    name: 'Phạm Thị D',
    email: 'phamthid@gmail.com',
    phone: '0934567890',
    joinDate: '2024-12-20',
    status: 'blocked',
    totalOrders: 2,
    totalSpent: 3200000,
    avatar: 'https://picsum.photos/seed/cust4/100'
  },
  {
    id: 'CUST-005',
    name: 'Hoàng Văn E',
    email: 'hoangvane@gmail.com',
    phone: '0945678901',
    joinDate: '2025-01-25',
    status: 'active',
    totalOrders: 8,
    totalSpent: 28900000,
    avatar: 'https://picsum.photos/seed/cust5/100'
  },
  {
    id: 'CUST-006',
    name: 'Đặng Thị F',
    email: 'dangthif@gmail.com',
    phone: '0956789012',
    joinDate: '2025-02-15',
    status: 'active',
    totalOrders: 3,
    totalSpent: 7800000,
    avatar: 'https://picsum.photos/seed/cust6/100'
  },
  {
    id: 'CUST-007',
    name: 'Bùi Văn G',
    email: 'buivang@gmail.com',
    phone: '0967890123',
    joinDate: '2025-03-12',
    status: 'active',
    totalOrders: 1,
    totalSpent: 1500000,
    avatar: 'https://picsum.photos/seed/cust7/100'
  },
  {
    id: 'CUST-008',
    name: 'Ngô Thị H',
    email: 'ngothih@gmail.com',
    phone: '0978901234',
    joinDate: '2025-01-05',
    status: 'inactive',
    totalOrders: 0,
    totalSpent: 0,
    avatar: 'https://picsum.photos/seed/cust8/100'
  }
];
