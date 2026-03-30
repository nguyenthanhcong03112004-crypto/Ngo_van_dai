const API_BASE_URL = '/api'; // Using the Vite proxy we added

export const apiFetch = async (endpoint: string, options: RequestInit = {}) => {
  const token = localStorage.getItem('auth_token');
  
  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    ...(options.headers || {}),
  };

  if (token) {
    (headers as any)['Authorization'] = `Bearer ${token}`;
  }

  const response = await fetch(`${API_BASE_URL}${endpoint}`, {
    ...options,
    headers,
  });

  const text = await response.text();
  let data;
  try {
    data = text ? JSON.parse(text) : {};
  } catch (e) {
    data = { message: text || 'Invalid JSON response from server' };
  }

  if (!response.ok) {
    throw new Error(data.message || `HTTP Error ${response.status}: ${response.statusText}`);
  }

  return data;
};

export const productsApi = {
  getAll: (search?: string, categoryId?: number) => {
    let url = '/user/products';
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (categoryId) params.append('category_id', categoryId.toString());
    
    const queryString = params.toString();
    if (queryString) url += `?${queryString}`;
    
    return apiFetch(url);
  },
  getById: (id: number) => apiFetch(`/user/products/${id}`),
};

export const authApi = {
  login: (credentials: any) => apiFetch('/auth/login', {
    method: 'POST',
    body: JSON.stringify(credentials),
  }),
  register: (data: any) => apiFetch('/auth/register', {
    method: 'POST',
    body: JSON.stringify(data),
  }),
};

export const ordersApi = {
  checkout: (data: any) => apiFetch('/user/checkout', {
    method: 'POST',
    body: JSON.stringify(data),
  }),
  getHistory: () => apiFetch('/user/orders'),
  uploadReceipt: (orderId: number, file: File) => {
    const formData = new FormData();
    formData.append('receipt', file);
    
    const token = localStorage.getItem('auth_token');
    return fetch(`${API_BASE_URL}/user/orders/${orderId}/upload-receipt`, {
      method: 'POST',
      body: formData,
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    }).then(r => r.json());
  },
};
