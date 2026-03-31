<?php
declare(strict_types=1);

namespace Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

class EmailService
{
    private static ?EmailService $instance = null;
    private PHPMailer $mailer;
    private Logger $logger;

    private function __construct()
    {
        $this->logger = Logger::getInstance();
        $this->mailer = new PHPMailer(true);

        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host       = $_ENV['SMTP_HOST'] ?? 'localhost';
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = $_ENV['SMTP_USER'] ?? '';
            $this->mailer->Password   = $_ENV['SMTP_PASS'] ?? '';
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port       = (int)($_ENV['SMTP_PORT'] ?? 587);
            $this->mailer->CharSet    = 'UTF-8';

            // From
            $fromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? 'from@example.com';
            $fromName = $_ENV['SMTP_FROM_NAME'] ?? 'ElectroHub';
            $this->mailer->setFrom($fromEmail, $fromName);

        } catch (Exception $e) {
            $this->logger->critical("PHPMailer could not be configured.", ['error' => $this->mailer->ErrorInfo]);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function sendWelcomeEmail(string $toEmail, string $toName): void
    {
        try {
            $this->mailer->addAddress($toEmail, $toName);

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Chào mừng bạn đến với ElectroHub!';
            $this->mailer->Body    = "<h1>Chào mừng, {$toName}!</h1><p>Cảm ơn bạn đã đăng ký tài khoản tại ElectroHub. Hãy bắt đầu khám phá hàng ngàn sản phẩm công nghệ ngay hôm nay.</p>";
            $this->mailer->AltBody = "Chào mừng, {$toName}! Cảm ơn bạn đã đăng ký tài khoản tại ElectroHub.";

            $this->mailer->send();
            $this->logger->info('Welcome email sent successfully', ['recipient' => $toEmail]);
        } catch (Exception $e) {
            $this->logger->error("Welcome email could not be sent.", ['recipient' => $toEmail, 'error' => $this->mailer->ErrorInfo]);
        }
    }

    public function sendOrderConfirmation(string $toEmail, string $toName, string $orderId, int $totalAmount): void
    {
        try {
            $this->mailer->clearAllRecipients(); // Xóa các người nhận cũ
            $this->mailer->addAddress($toEmail, $toName);

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = "Xac nhan don hang #{$orderId} tai ElectroHub";
            $formattedTotal = number_format($totalAmount, 0, ',', '.') . ' VND';
            $this->mailer->Body    = "<h1>Cảm ơn bạn đã đặt hàng, {$toName}!</h1><p>Đơn hàng <strong>#{$orderId}</strong> của bạn đã được ghi nhận trên hệ thống.</p><p>Tổng thanh toán: <strong style='color: #2563eb;'>{$formattedTotal}</strong></p><p>Chúng tôi sẽ sớm liên hệ lại với bạn để xác nhận giao hàng. Bạn có thể theo dõi trạng thái đơn hàng tại mục Lịch sử đơn hàng trên website.</p>";
            $this->mailer->AltBody = "Cảm ơn bạn đã đặt hàng, {$toName}! Đơn hàng #{$orderId} của bạn đã được ghi nhận. Tổng thanh toán: {$formattedTotal}.";

            $this->mailer->send();
            $this->logger->info('Order confirmation email sent successfully', ['recipient' => $toEmail, 'order_id' => $orderId]);
        } catch (Exception $e) {
            $this->logger->error("Order confirmation email could not be sent.", ['recipient' => $toEmail, 'error' => $this->mailer->ErrorInfo]);
        }
    }

    public function sendPasswordResetEmail(string $toEmail, string $resetLink): void
    {
        try {
            $this->mailer->clearAllRecipients();
            $this->mailer->addAddress($toEmail);

            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Yeu cau khoi phuc mat khau - ElectroHub';
            $this->mailer->Body    = "<h2>Khôi phục mật khẩu</h2><p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p><p>Vui lòng click vào đường link dưới đây để thiết lập mật khẩu mới (Link này chỉ có hiệu lực trong 15 phút):</p><p><a href='{$resetLink}' style='padding: 10px 20px; background-color: #2563eb; color: white; text-decoration: none; border-radius: 5px;'>Đặt lại mật khẩu</a></p><p>Nếu nút bấm không hoạt động, hãy copy đường link sau dán vào trình duyệt: <br>{$resetLink}</p><p>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email.</p>";

            $this->mailer->send();
            $this->logger->info('Password reset email sent successfully', ['recipient' => $toEmail]);
        } catch (Exception $e) {
            $this->logger->error("Password reset email could not be sent.", ['recipient' => $toEmail, 'error' => $this->mailer->ErrorInfo]);
        }
    }

    public function sendInvoicePdf(string $toEmail, string $toName, array $order): void
    {
        try {
            // 1. Tạo nội dung HTML cho Hóa đơn PDF
            $date = date('d/m/Y', strtotime($order['created_at']));
            $totalStr = number_format((float)$order['total_amount'], 0, ',', '.') . ' VND';
            
            $html = "<h1 style='color: #2563eb; font-family: sans-serif;'>Hoa don #ORD-{$order['id']}</h1>";
            $html .= "<p><strong>Khach hang:</strong> {$toName}</p>";
            $html .= "<p><strong>Ngay lap:</strong> {$date}</p>";
            $html .= "<table border='1' width='100%' cellpadding='8' style='border-collapse: collapse; font-family: sans-serif;'>";
            $html .= "<tr style='background-color: #f1f5f9;'><th>San pham</th><th>SL</th><th>Don gia</th></tr>";
            
            foreach ($order['items'] as $item) {
                $priceStr = number_format((float)$item['product_price'], 0, ',', '.');
                $html .= "<tr><td>{$item['product_name']}</td><td align='center'>{$item['quantity']}</td><td align='right'>{$priceStr}</td></tr>";
            }
            $html .= "</table>";
            $html .= "<h3 style='text-align: right; font-family: sans-serif;'>Tong thanh toan: {$totalStr}</h3>";

            // 2. Chuyển đổi HTML sang PDF
            $options = new Options();
            $options->set('defaultFont', 'Helvetica'); // Font mặc định hỗ trợ tốt
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfContent = $dompdf->output();

            // 3. Gửi Email kèm file đính kèm
            $this->mailer->clearAllRecipients();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($toEmail, $toName);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = "Hoa don dien tu cho don hang #ORD-{$order['id']}";
            $this->mailer->Body = "<p>Chào {$toName},</p><p>Cảm ơn bạn đã mua sắm. Vui lòng kiểm tra file PDF hóa đơn đính kèm.</p>";
            $this->mailer->addStringAttachment($pdfContent, "Hoa_Don_ORD_{$order['id']}.pdf");

            $this->mailer->send();
        } catch (\Throwable $e) {
            $this->logger->error("Invoice PDF email failed.", ['order_id' => $order['id'], 'error' => $e->getMessage()]);
        }
    }
}