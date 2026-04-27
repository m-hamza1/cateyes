<?php
/**
 * Invoice service
 * Generates and manages printable PDF invoices for orders.
 */

class Invoice {
    private $db;
    private $lastError = '';

    public function __construct($db) {
        $this->db = $db;
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function getInfoForOrder($orderId) {
        $this->ensureInfrastructure();

        $row = $this->db->fetchOne(
            "SELECT id, order_number, invoice_number, invoice_pdf_path, invoice_generated_at
             FROM orders
             WHERE id = :id",
            ['id' => $orderId]
        );

        if (!$row) {
            return null;
        }

        $absolutePath = $this->toAbsolutePath($row['invoice_pdf_path'] ?? '');
        $exists = !empty($absolutePath) && file_exists($absolutePath);

        return [
            'order_id' => (int)$row['id'],
            'order_number' => $row['order_number'],
            'invoice_number' => $row['invoice_number'] ?? '',
            'invoice_pdf_path' => $row['invoice_pdf_path'] ?? '',
            'invoice_generated_at' => $row['invoice_generated_at'] ?? '',
            'exists' => $exists,
            'url' => $exists ? $this->toPublicUrl($row['invoice_pdf_path']) : ''
        ];
    }

    public function ensureGenerated($orderId, $forceRegenerate = false) {
        $this->lastError = '';
        $this->ensureInfrastructure();

        $order = $this->db->fetchOne(
            "SELECT *
             FROM orders
             WHERE id = :id",
            ['id' => $orderId]
        );

        if (!$order) {
            return $this->fail('Order not found.');
        }

        $missingParts = $this->getMissingShippingFields($order);
        if (!empty($missingParts)) {
            return $this->fail(
                'Invoice could not be generated because the shipping address is incomplete: ' .
                implode(', ', $missingParts) . '.'
            );
        }

        $relativePath = $order['invoice_pdf_path'] ?? '';
        $absolutePath = $this->toAbsolutePath($relativePath);
        if (
            !$forceRegenerate &&
            !empty($order['invoice_number']) &&
            !empty($order['invoice_generated_at']) &&
            !empty($absolutePath) &&
            file_exists($absolutePath)
        ) {
            return [
                'success' => true,
                'invoice_number' => $order['invoice_number'],
                'path' => $relativePath,
                'absolute_path' => $absolutePath,
                'url' => $this->toPublicUrl($relativePath),
                'generated_at' => $order['invoice_generated_at'],
                'already_exists' => true
            ];
        }

        $orderItems = $this->db->fetchAll(
            "SELECT product_name, product_sku, quantity, unit_price, total_price
             FROM order_items
             WHERE order_id = :order_id
             ORDER BY id ASC",
            ['order_id' => $orderId]
        );

        $invoiceNumber = !empty($order['invoice_number'])
            ? $order['invoice_number']
            : $this->generateInvoiceNumber($order);

        if (empty($relativePath)) {
            $safeOrderNumber = preg_replace('/[^A-Za-z0-9_-]/', '-', (string)$order['order_number']);
            $relativePath = 'assets/uploads/invoices/' . $safeOrderNumber . '.pdf';
            $absolutePath = $this->toAbsolutePath($relativePath);
        }

        $directory = dirname($absolutePath);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            return $this->fail('Unable to create the invoice storage directory.');
        }

        $pdf = $this->buildPdfDocument($order, $orderItems, $invoiceNumber);

        if (file_put_contents($absolutePath, $pdf, LOCK_EX) === false) {
            return $this->fail('Unable to write the invoice PDF file.');
        }

        $generatedAt = date('Y-m-d H:i:s');
        $updated = $this->db->update(
            'orders',
            [
                'invoice_number' => $invoiceNumber,
                'invoice_pdf_path' => $relativePath,
                'invoice_generated_at' => $generatedAt
            ],
            'id = :id',
            ['id' => $orderId]
        );

        if ($updated === false) {
            return $this->fail('Invoice PDF was created, but its database record could not be saved.');
        }

        return [
            'success' => true,
            'invoice_number' => $invoiceNumber,
            'path' => $relativePath,
            'absolute_path' => $absolutePath,
            'url' => $this->toPublicUrl($relativePath),
            'generated_at' => $generatedAt,
            'already_exists' => false
        ];
    }

    private function ensureInfrastructure() {
        static $bootstrapped = false;

        if ($bootstrapped) {
            return;
        }

        $columns = [
            'invoice_number' => "ALTER TABLE orders ADD COLUMN invoice_number VARCHAR(50) NULL AFTER tracking_number",
            'invoice_pdf_path' => "ALTER TABLE orders ADD COLUMN invoice_pdf_path VARCHAR(255) NULL AFTER invoice_number",
            'invoice_generated_at' => "ALTER TABLE orders ADD COLUMN invoice_generated_at DATETIME NULL AFTER invoice_pdf_path"
        ];

        foreach ($columns as $column => $sql) {
            $exists = $this->db->fetchOne("SHOW COLUMNS FROM orders LIKE :column", ['column' => $column]);
            if (!$exists) {
                $this->db->query($sql);
            }
        }

        $directory = BASE_PATH . 'assets/uploads/invoices';
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $bootstrapped = true;
    }

    private function buildInvoiceData(array $order, array $items, $invoiceNumber) {
        global $currency;

        $formatMoney = function ($amount) use ($currency) {
            if (isset($currency) && is_object($currency) && method_exists($currency, 'format')) {
                return trim(strip_tags((string)$currency->format((float)$amount)));
            }

            $code = $order['currency_code'] ?? $order['currency'] ?? 'USD';
            return $code . ' ' . number_format((float)$amount, 2);
        };

        $shippingAddress = array_filter([
            trim(($order['shipping_first_name'] ?? '') . ' ' . ($order['shipping_last_name'] ?? '')),
            trim((string)($order['shipping_company'] ?? '')),
            trim((string)($order['shipping_address_line1'] ?? '')),
            trim((string)($order['shipping_address_line2'] ?? '')),
            trim(implode(', ', array_filter([
                $order['shipping_city'] ?? '',
                $order['shipping_state'] ?? '',
                $order['shipping_postal_code'] ?? ''
            ]))),
            trim((string)($order['shipping_country'] ?? '')),
            trim((string)($order['shipping_phone'] ?? ''))
        ]);

        $billingAddress = array_filter([
            trim(($order['billing_first_name'] ?? '') . ' ' . ($order['billing_last_name'] ?? '')),
            trim((string)($order['billing_company'] ?? '')),
            trim((string)($order['billing_address_line1'] ?? '')),
            trim((string)($order['billing_address_line2'] ?? '')),
            trim(implode(', ', array_filter([
                $order['billing_city'] ?? '',
                $order['billing_state'] ?? '',
                $order['billing_postal_code'] ?? ''
            ]))),
            trim((string)($order['billing_country'] ?? '')),
            trim((string)($order['billing_phone'] ?? '')),
            trim((string)($order['billing_email'] ?? ''))
        ]);

        $storeProfile = $this->db->fetchAll(
            "SELECT `key`, `value`
             FROM site_settings
             WHERE `key` IN ('site_name', 'store_phone', 'store_email', 'store_address', 'store_city', 'store_country')"
        );

        $store = [];
        foreach ($storeProfile as $row) {
            $store[$row['key']] = trim((string)$row['value']);
        }

        $storeName = $store['site_name'] ?? SITE_NAME;
        $storeAddressParts = array_filter([
            $store['store_address'] ?? '',
            $store['store_city'] ?? '',
            $store['store_country'] ?? ''
        ]);

        $normalizedItems = [];
        foreach ($items as $item) {
            $normalizedItems[] = [
                'name' => $item['product_name'],
                'sku' => $item['product_sku'] ?? '',
                'quantity' => (int)$item['quantity'],
                'unit_price' => $formatMoney($item['unit_price'] ?? 0),
                'line_total' => $formatMoney($item['total_price'] ?? 0),
            ];
        }

        $totals = [
            ['label' => 'Subtotal', 'value' => $formatMoney($order['subtotal'] ?? 0)],
            ['label' => 'Tax', 'value' => $formatMoney($order['tax_amount'] ?? 0)],
            ['label' => 'Shipping', 'value' => $formatMoney($order['shipping_cost'] ?? 0)],
        ];

        if ((float)($order['discount_amount'] ?? 0) > 0) {
            $totals[] = ['label' => 'Discount', 'value' => '-' . $formatMoney($order['discount_amount'] ?? 0)];
        }

        $totals[] = ['label' => 'Total', 'value' => $formatMoney($order['total_amount'] ?? 0), 'highlight' => true];

        return [
            'store_name' => $storeName,
            'store_lines' => array_values(array_filter([
                implode(', ', $storeAddressParts),
                $store['store_phone'] ?? '',
                $store['store_email'] ?? ''
            ])),
            'invoice_number' => $invoiceNumber,
            'invoice_date' => date('Y-m-d H:i'),
            'order_number' => $order['order_number'] ?? '',
            'order_date' => !empty($order['created_at']) ? date('Y-m-d H:i', strtotime($order['created_at'])) : '',
            'payment_method' => ucwords(str_replace('_', ' ', (string)($order['payment_method'] ?? ''))),
            'payment_status' => ucfirst((string)($order['payment_status'] ?? 'pending')),
            'order_status' => ucfirst((string)($order['order_status'] ?? 'pending')),
            'tracking_number' => trim((string)($order['tracking_number'] ?? '')),
            'billing_lines' => array_values($billingAddress),
            'shipping_lines' => array_values($shippingAddress),
            'items' => $normalizedItems,
            'totals' => $totals
        ];
    }

    private function buildPdfDocument(array $order, array $items, $invoiceNumber) {
        $data = $this->buildInvoiceData($order, $items, $invoiceNumber);
        $pages = [];
        $page = $this->createInvoicePageCanvas();
        $currentY = $this->drawInvoiceHeader($page, $data, true);
        $currentY = $this->drawAddressBlocks($page, $data, $currentY - 18);
        $currentY -= 18;
        $currentY = $this->drawItemsTableHeader($page, $currentY);

        foreach ($data['items'] as $item) {
            $descriptionLines = $this->wrapTextForPdf(
                trim($item['name'] . (!empty($item['sku']) ? ' (SKU: ' . $item['sku'] . ')' : '')),
                34
            );
            $rowHeight = max(22, count($descriptionLines) * 11 + 8);

            if ($currentY - $rowHeight < 140) {
                $this->drawPageFooter($page, $data);
                $pages[] = $page;
                $page = $this->createInvoicePageCanvas();
                $currentY = $this->drawInvoiceHeader($page, $data, false);
                $currentY = $this->drawItemsTableHeader($page, $currentY - 4);
            }

            $this->drawItemsTableRow($page, $item, $descriptionLines, $currentY, $rowHeight);
            $currentY -= $rowHeight;
        }

        if ($currentY - 120 < 60) {
            $this->drawPageFooter($page, $data);
            $pages[] = $page;
            $page = $this->createInvoicePageCanvas();
            $currentY = $this->drawInvoiceHeader($page, $data, false);
        }

        $this->drawTotalsBox($page, $data, $currentY - 16);
        $this->drawPageFooter($page, $data);
        $pages[] = $page;

        $objects = [];
        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";

        $pageObjectNumbers = [];
        $contentObjectNumbers = [];
        $fontRegular = 3;
        $fontBold = 4;

        $pageCount = count($pages);
        for ($i = 0; $i < $pageCount; $i++) {
            $pageObjectNumbers[] = 5 + ($i * 2);
            $contentObjectNumbers[] = 6 + ($i * 2);
        }

        $kids = implode(' ', array_map(fn($num) => $num . ' 0 R', $pageObjectNumbers));
        $objects[] = "<< /Type /Pages /Kids [ $kids ] /Count $pageCount >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";

        foreach ($pages as $pageIndex => $pageLines) {
            $pageObject = $pageObjectNumbers[$pageIndex];
            $contentObject = $contentObjectNumbers[$pageIndex];

            $objects[$pageObject - 1] =
                "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] " .
                "/Resources << /Font << /F1 {$fontRegular} 0 R /F2 {$fontBold} 0 R >> >> " .
                "/Contents {$contentObject} 0 R >>";

            $stream = implode("\n", $pageLines);

            $objects[$contentObject - 1] =
                "<< /Length " . strlen($stream) . " >>\nstream\n" .
                $stream . "\nendstream";
        }

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $body) {
            $objectNumber = $index + 1;
            $offsets[$objectNumber] = strlen($pdf);
            $pdf .= $objectNumber . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n";
        $pdf .= "<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n";
        $pdf .= $xrefOffset . "\n";
        $pdf .= "%%EOF";

        return $pdf;
    }

    private function createInvoicePageCanvas() {
        return [];
    }

    private function drawInvoiceHeader(array &$page, array $data, $fullHeader = true) {
        // Professional Modern Header
        $headerTopY = 740;
        
        // Store Name (Logo replacement)
        $this->pushText($page, 36, $headerTopY + 15, $data['store_name'], 22, 'F2', [17, 24, 39]);
        
        // "INVOICE" Title
        $this->pushText($page, 460, $headerTopY + 15, 'INVOICE', 22, 'F2', [17, 24, 39]);
        
        // Decorative Accent Line
        $this->pushFillRect($page, 36, $headerTopY, 540, 2, [59, 130, 246]); // Modern Blue accent
        
        // Info Section
        $contactX = 36;
        $contactY = 660;
        $this->pushText($page, $contactX, $contactY + 36, 'FROM', 8, 'F2', [107, 114, 128]);
        $this->pushText($page, $contactX, $contactY + 20, $data['store_name'], 12, 'F2', [17, 24, 39]);
        
        $lineY = $contactY + 8;
        foreach (array_slice($data['store_lines'], 0, 3) as $line) {
            $this->pushText($page, $contactX, $lineY, $line, 9, 'F1', [75, 85, 99]);
            $lineY -= 12;
        }

        // Details Section
        $metaX = 380;
        $metaY = 660;
        $metaRows = [
            ['Invoice Number', $data['invoice_number']],
            ['Invoice Date', $data['invoice_date']],
            ['Order Number', $data['order_number']],
        ];

        $rowY = $metaY + 36;
        foreach ($metaRows as $metaRow) {
            $this->pushText($page, $metaX, $rowY, $metaRow[0], 9, 'F2', [107, 114, 128]);
            $this->pushText($page, $metaX + 100, $rowY, $metaRow[1], 9, 'F1', [17, 24, 39]);
            $rowY -= 14;
        }

        if (!$fullHeader) {
            return 610;
        }

        // Status Cards
        $summaryY = 575;
        $cardW = 175;
        $cardH = 45;
        $cards = [
            ['Payment Method', $data['payment_method']],
            ['Payment Status', $data['payment_status']],
            ['Order Status', $data['order_status']]
        ];

        $cardX = 36;
        foreach ($cards as $card) {
            $this->pushFillRect($page, $cardX, $summaryY, $cardW, $cardH, [249, 250, 251]);
            $this->pushStrokeRect($page, $cardX, $summaryY, $cardW, $cardH, [229, 231, 235], 0.5);
            $this->pushText($page, $cardX + 12, $summaryY + 28, $card[0], 8, 'F2', [107, 114, 128]);
            
            // Status Color Coding
            $statusColor = [17, 24, 39];
            $status = strtolower($card[1]);
            if (strpos($status, 'paid') !== false || strpos($status, 'completed') !== false || strpos($status, 'delivered') !== false) {
                $statusColor = [21, 128, 61]; // Green
            } elseif (strpos($status, 'pending') !== false || strpos($status, 'processing') !== false) {
                $statusColor = [180, 83, 9]; // Amber
            } elseif (strpos($status, 'cancelled') !== false || strpos($status, 'failed') !== false) {
                $statusColor = [185, 28, 28]; // Red
            }
            
            $this->pushText($page, $cardX + 12, $summaryY + 12, $card[1], 10, 'F2', $statusColor);
            $cardX += 182;
        }

        return 545;
    }


    private function drawAddressBlocks(array &$page, array $data, $startY) {
        $billingWrapped = $this->prepareWrappedBlockLines($data['billing_lines'], 40);
        $shippingWrapped = $this->prepareWrappedBlockLines($data['shipping_lines'], 40);
        $maxLineCount = max(count($billingWrapped), count($shippingWrapped), 4);
        $boxH = max(80, 20 + ($maxLineCount * 12) + 10);
        $boxY = $startY - $boxH;
        $leftX = 36;
        $rightX = 312;

        foreach ([[$leftX, 'BILL TO', $billingWrapped], [$rightX, 'SHIP TO', $shippingWrapped]] as $block) {
            [$x, $title, $lines] = $block;
            $this->pushText($page, $x, $boxY + $boxH + 5, $title, 8, 'F2', [107, 114, 128]);
            
            $textY = $boxY + $boxH - 12;
            foreach ($lines as $index => $wrapped) {
                $font = $index === 0 ? 'F2' : 'F1';
                $size = $index === 0 ? 10 : 9;
                $color = $index === 0 ? [17, 24, 39] : [55, 65, 81];
                $this->pushText($page, $x, $textY, $wrapped, $size, $font, $color);
                $textY -= 12;
            }
        }

        return $boxY - 20;
    }


    private function drawItemsTableHeader(array &$page, $y) {
        $this->pushFillRect($page, 36, $y - 20, 540, 20, [243, 244, 246]);
        $this->pushText($page, 46, $y - 12, 'DESCRIPTION', 8, 'F2', [107, 114, 128]);
        $this->pushText($page, 365, $y - 12, 'QTY', 8, 'F2', [107, 114, 128]);
        $this->pushText($page, 420, $y - 12, 'UNIT PRICE', 8, 'F2', [107, 114, 128]);
        $this->pushText($page, 505, $y - 12, 'TOTAL', 8, 'F2', [107, 114, 128]);
        $this->pushLine($page, 36, $y - 20, 576, $y - 20, [229, 231, 235], 0.5);
        return $y - 20;
    }


    private function drawItemsTableRow(array &$page, array $item, array $descriptionLines, $y, $height) {
        $textY = $y - 14;
        foreach ($descriptionLines as $index => $line) {
            $font = $index === 0 ? 'F2' : 'F1';
            $size = $index === 0 ? 10 : 9;
            $this->pushText($page, 46, $textY, $line, $size, $font, [17, 24, 39]);
            $textY -= 11;
        }

        $this->pushText($page, 365, $y - 14, (string)$item['quantity'], 9, 'F1', [55, 65, 81]);
        $this->pushText($page, 420, $y - 14, $item['unit_price'], 9, 'F1', [55, 65, 81]);
        $this->pushText($page, 505, $y - 14, $item['line_total'], 10, 'F2', [17, 24, 39]);
        
        $this->pushLine($page, 36, $y - $height, 576, $y - $height, [243, 244, 246], 0.5);
    }


    private function drawTotalsBox(array &$page, array $data, $startY) {
        $boxX = 360;
        
        $y = $startY;
        foreach ($data['totals'] as $total) {
            if (!empty($total['highlight'])) {
                $y -= 8;
                $this->pushLine($page, $boxX, $y + 4, 576, $y + 4, [229, 231, 235], 0.8);
                $this->pushText($page, $boxX, $y - 10, $total['label'], 12, 'F2', [17, 24, 39]);
                $this->pushText($page, 480, $y - 10, $total['value'], 13, 'F2', [59, 130, 246]);
                $y -= 25;
                continue;
            }

            $this->pushText($page, $boxX, $y, $total['label'], 10, 'F1', [107, 114, 128]);
            $this->pushText($page, 480, $y, $total['value'], 10, 'F1', [17, 24, 39]);
            $y -= 18;
        }
    }


    private function drawPageFooter(array &$page, array $data) {
        $this->pushLine($page, 36, 60, 576, 60, [229, 231, 235], 0.5);
        $footerText = 'Thank you for shopping with us. For any inquiries, please contact us.';
        $this->pushText($page, 36, 45, $footerText, 9, 'F1', [107, 114, 128]);

        if (!empty($data['tracking_number'])) {
            $this->pushText($page, 36, 30, 'Tracking Number: ' . $data['tracking_number'], 9, 'F2', [75, 85, 99]);
        }
    }


    private function prepareWrappedBlockLines(array $lines, $maxChars) {
        $wrappedLines = [];
        foreach ($lines as $index => $line) {
            $wrapped = $this->wrapTextForPdf($line, $maxChars);
            foreach ($wrapped as $wrappedLine) {
                $wrappedLines[] = $wrappedLine;
            }
            if ($index === 0 && count($wrapped) === 0) {
                $wrappedLines[] = '';
            }
        }
        return $wrappedLines;
    }

    private function wrapTextForPdf($text, $maxChars) {
        $text = trim((string)$text);
        if ($text === '') {
            return [''];
        }

        return preg_split('/\r\n|\r|\n/', wordwrap($text, $maxChars, "\n", true));
    }

    private function pushText(array &$page, $x, $y, $text, $size = 10, $font = 'F1', array $color = [0, 0, 0]) {
        $page[] = 'BT';
        $page[] = sprintf('%.3F %.3F %.3F rg', $color[0] / 255, $color[1] / 255, $color[2] / 255);
        $page[] = sprintf('/%s %.2F Tf', $font, $size);
        $page[] = sprintf('1 0 0 1 %.2F %.2F Tm', $x, $y);
        $page[] = '(' . $this->escapePdfText($text) . ') Tj';
        $page[] = 'ET';
    }

    private function pushFillRect(array &$page, $x, $y, $w, $h, array $color) {
        $page[] = sprintf('%.3F %.3F %.3F rg', $color[0] / 255, $color[1] / 255, $color[2] / 255);
        $page[] = sprintf('%.2F %.2F %.2F %.2F re f', $x, $y, $w, $h);
    }

    private function pushStrokeRect(array &$page, $x, $y, $w, $h, array $color, $lineWidth = 1) {
        $page[] = sprintf('%.2F w', $lineWidth);
        $page[] = sprintf('%.3F %.3F %.3F RG', $color[0] / 255, $color[1] / 255, $color[2] / 255);
        $page[] = sprintf('%.2F %.2F %.2F %.2F re S', $x, $y, $w, $h);
    }

    private function pushLine(array &$page, $x1, $y1, $x2, $y2, array $color, $lineWidth = 1) {
        $page[] = sprintf('%.2F w', $lineWidth);
        $page[] = sprintf('%.3F %.3F %.3F RG', $color[0] / 255, $color[1] / 255, $color[2] / 255);
        $page[] = sprintf('%.2F %.2F m %.2F %.2F l S', $x1, $y1, $x2, $y2);
    }

    private function escapePdfText($text) {
        $text = str_replace('\\', '\\\\', (string)$text);
        $text = str_replace('(', '\\(', $text);
        $text = str_replace(')', '\\)', $text);
        return preg_replace('/[^\x20-\x7E]/', '?', $text);
    }

    private function generateInvoiceNumber(array $order) {
        $stamp = !empty($order['created_at']) ? strtotime($order['created_at']) : time();
        return 'INV-' . date('Ymd', $stamp) . '-' . str_pad((string)$order['id'], 6, '0', STR_PAD_LEFT);
    }

    private function getMissingShippingFields(array $order) {
        $required = [
            'shipping_first_name' => 'first name',
            'shipping_last_name' => 'last name',
            'shipping_address_line1' => 'address line 1',
            'shipping_city' => 'city',
            'shipping_country' => 'country'
        ];

        $missing = [];
        foreach ($required as $field => $label) {
            if (trim((string)($order[$field] ?? '')) === '') {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    private function toAbsolutePath($relativePath) {
        $relativePath = trim((string)$relativePath);
        if ($relativePath === '') {
            return '';
        }

        return BASE_PATH . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    }

    private function toPublicUrl($relativePath) {
        $relativePath = ltrim(str_replace('\\', '/', (string)$relativePath), '/');
        return BASE_URL . $relativePath;
    }

    private function fail($message) {
        $this->lastError = $message;
        return ['success' => false, 'message' => $message];
    }
}
