export interface Product {
  id: number;
  name: string;
  price: number;
  category: string;
  stock: number;
  description: string;
  image: string;
  rating: number;
}

export const MOCK_PRODUCTS: Product[] = [
  { id: 1, name: 'iPhone 15 Pro Max', price: 32000000, category: 'Smartphone', stock: 15, description: 'Flagship mới nhất từ Apple với chip A17 Pro.', image: 'https://picsum.photos/seed/iphone15/400/300', rating: 4.8 },
  { id: 2, name: 'MacBook Pro M3', price: 45000000, category: 'Laptop', stock: 8, description: 'Sức mạnh vượt trội cho đồ họa và lập trình.', image: 'https://picsum.photos/seed/macbookm3/400/300', rating: 4.9 },
  { id: 3, name: 'Samsung Galaxy S24 Ultra', price: 28000000, category: 'Smartphone', stock: 12, description: 'Camera 200MP và tính năng AI thông minh.', image: 'https://picsum.photos/seed/s24ultra/400/300', rating: 4.7 },
  { id: 4, name: 'Sony WH-1000XM5', price: 8500000, category: 'Phụ kiện', stock: 25, description: 'Chống ồn đỉnh cao, âm thanh chi tiết.', image: 'https://picsum.photos/seed/sonyxm5/400/300', rating: 4.8 },
  { id: 5, name: 'iPad Pro M2', price: 21000000, category: 'Tablet', stock: 10, description: 'Màn hình Liquid Retina XDR siêu đẹp.', image: 'https://picsum.photos/seed/ipadpro/400/300', rating: 4.7 },
  { id: 6, name: 'Dell XPS 15', price: 42000000, category: 'Laptop', stock: 5, description: 'Laptop Windows hoàn hảo cho sáng tạo.', image: 'https://picsum.photos/seed/dellxps/400/300', rating: 4.6 },
  { id: 7, name: 'Apple Watch Series 9', price: 10500000, category: 'Phụ kiện', stock: 30, description: 'Theo dõi sức khỏe và tập luyện chuyên nghiệp.', image: 'https://picsum.photos/seed/applewatch/400/300', rating: 4.5 },
  { id: 8, name: 'AirPods Pro 2', price: 5500000, category: 'Phụ kiện', stock: 50, description: 'Âm thanh không gian và khử tiếng ồn chủ động.', image: 'https://picsum.photos/seed/airpods/400/300', rating: 4.9 },
  { id: 9, name: 'Logitech MX Master 3S', price: 2500000, category: 'Phụ kiện', stock: 40, description: 'Chuột không dây tốt nhất cho công việc.', image: 'https://picsum.photos/seed/mxmaster/400/300', rating: 4.8 },
  { id: 10, name: 'Keychron K2 V2', price: 1800000, category: 'Phụ kiện', stock: 20, description: 'Bàn phím cơ không dây nhỏ gọn.', image: 'https://picsum.photos/seed/keychron/400/300', rating: 4.7 },
  { id: 11, name: 'Google Pixel 8 Pro', price: 24000000, category: 'Smartphone', stock: 7, description: 'Trải nghiệm Android thuần khiết nhất.', image: 'https://picsum.photos/seed/pixel8/400/300', rating: 4.6 },
  { id: 12, name: 'Nintendo Switch OLED', price: 8000000, category: 'Gaming', stock: 18, description: 'Máy chơi game cầm tay màn hình OLED rực rỡ.', image: 'https://picsum.photos/seed/nintendo/400/300', rating: 4.8 },
  { id: 13, name: 'ASUS ROG Zephyrus G14', price: 38000000, category: 'Laptop', stock: 4, description: 'Laptop gaming nhỏ gọn mạnh mẽ nhất.', image: 'https://picsum.photos/seed/rog/400/300', rating: 4.7 },
  { id: 14, name: 'Kindle Paperwhite 5', price: 3500000, category: 'Tablet', stock: 22, description: 'Máy đọc sách tốt nhất hiện nay.', image: 'https://picsum.photos/seed/kindle/400/300', rating: 4.9 },
  { id: 15, name: 'GoPro Hero 12', price: 11000000, category: 'Phụ kiện', stock: 14, description: 'Action camera chuyên nghiệp cho mọi hành trình.', image: 'https://picsum.photos/seed/gopro/400/300', rating: 4.6 },
];
