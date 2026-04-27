<?php
/**
 * Newsletter Management Class
 * Handles subscription, unsubscription, campaign sending, and campaign history.
 * Uses the Email class for professional SMTP delivery.
 */

require_once INCLUDES_PATH . 'Email.php';

class Newsletter {
    private $db;
    private $email;

    public function __construct($database) {
        $this->db = $database;
        $this->email = new Email($database);
        $this->ensureCampaignTable();
    }

    private function ensureCampaignTable() {
        try {
            $this->db->query("CREATE TABLE IF NOT EXISTS `newsletter_campaigns` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
                `sent_count` int(11) DEFAULT 0,
                `failed_count` int(11) DEFAULT 0,
                `total_subscribers` int(11) DEFAULT 0,
                `status` enum('draft','sending','sent','failed') DEFAULT 'draft',
                `sent_by` int(11) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `sent_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        } catch (Exception $e) {
            // Table may already exist – ignore
        }
    }

    // ──────────────── Subscribe / Unsubscribe ────────────────

    public function subscribe($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please enter a valid email address'];
        }

        $existing = $this->db->fetchOne(
            "SELECT id, status FROM newsletter_subscribers WHERE email = :email",
            ['email' => $email]
        );

        if ($existing) {
            if ($existing['status'] === 'subscribed') {
                return ['success' => false, 'message' => 'This email is already subscribed to our newsletter'];
            }
            $this->db->query(
                "UPDATE newsletter_subscribers SET status = 'subscribed', subscribed_at = CURRENT_TIMESTAMP, unsubscribed_at = NULL WHERE email = :email",
                ['email' => $email]
            );
            $this->syncUserNewsletterFlag($email, true);
            try { $this->email->newsletterWelcome($email); } catch (Exception $e) {}
            return ['success' => true, 'message' => 'Welcome back! You have been re-subscribed to our newsletter'];
        }

        try {
            $this->db->query(
                "INSERT INTO newsletter_subscribers (email, status) VALUES (:email, 'subscribed')",
                ['email' => $email]
            );
            $this->syncUserNewsletterFlag($email, true);
            try { $this->email->newsletterWelcome($email); } catch (Exception $e) {}
            return ['success' => true, 'message' => 'Thank you for subscribing to our newsletter!'];
        } catch (Exception $e) {
            error_log("Newsletter subscription error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred. Please try again later.'];
        }
    }

    public function unsubscribe($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please enter a valid email address'];
        }

        $existing = $this->db->fetchOne(
            "SELECT id, status FROM newsletter_subscribers WHERE email = :email",
            ['email' => $email]
        );

        if (!$existing) {
            return ['success' => false, 'message' => 'This email is not subscribed to our newsletter'];
        }
        if ($existing['status'] === 'unsubscribed') {
            return ['success' => false, 'message' => 'This email is already unsubscribed'];
        }

        try {
            $this->db->query(
                "UPDATE newsletter_subscribers SET status = 'unsubscribed', unsubscribed_at = CURRENT_TIMESTAMP WHERE email = :email",
                ['email' => $email]
            );
            $this->syncUserNewsletterFlag($email, false);
            try { $this->email->newsletterGoodbye($email); } catch (Exception $e) {}
            return ['success' => true, 'message' => 'You have been successfully unsubscribed from our newsletter'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'An error occurred. Please try again later.'];
        }
    }

    /**
     * Keep users.newsletter_subscribed in sync when they sub/unsub.
     */
    private function syncUserNewsletterFlag($email, $subscribed) {
        try {
            $this->db->query(
                "UPDATE users SET newsletter_subscribed = :flag WHERE email = :email",
                ['flag' => $subscribed ? 1 : 0, 'email' => $email]
            );
        } catch (Exception $e) { /* user may not exist */ }
    }

    // ──────────────── Subscribers ────────────────

    /**
     * Get all unique subscriber emails (newsletter_subscribers + users with newsletter_subscribed=1).
     */
    public function getAllActiveEmails() {
        $rows = $this->db->fetchAll("
            SELECT email FROM newsletter_subscribers WHERE status = 'subscribed'
            UNION
            SELECT email FROM users WHERE newsletter_subscribed = 1 AND is_active = 1
        ");
        return array_unique(array_column($rows ?: [], 'email'));
    }

    public function getSubscribers($status = null, $limit = null, $offset = 0) {
        $sql = "SELECT * FROM newsletter_subscribers";
        $params = [];

        if ($status && in_array($status, ['subscribed', 'unsubscribed'])) {
            $sql .= " WHERE status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY subscribed_at DESC";

        if ($limit) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        return $this->db->fetchAll($sql, $params) ?: [];
    }

    public function getStatistics() {
        $total = $this->db->fetchOne("SELECT COUNT(*) as c FROM newsletter_subscribers")['c'] ?? 0;
        $subscribed = $this->db->fetchOne("SELECT COUNT(*) as c FROM newsletter_subscribers WHERE status = 'subscribed'")['c'] ?? 0;
        $unsubscribed = $this->db->fetchOne("SELECT COUNT(*) as c FROM newsletter_subscribers WHERE status = 'unsubscribed'")['c'] ?? 0;
        $newMonth = $this->db->fetchOne("SELECT COUNT(*) as c FROM newsletter_subscribers WHERE status = 'subscribed' AND subscribed_at >= DATE_FORMAT(NOW(), '%Y-%m-01')")['c'] ?? 0;

        $userSubs = $this->db->fetchOne("SELECT COUNT(*) as c FROM users WHERE newsletter_subscribed = 1 AND is_active = 1")['c'] ?? 0;

        $allActive = count($this->getAllActiveEmails());

        return [
            'total' => $total,
            'subscribed' => $subscribed,
            'unsubscribed' => $unsubscribed,
            'new_this_month' => $newMonth,
            'user_subscribers' => $userSubs,
            'all_active' => $allActive,
        ];
    }

    // ──────────────── Campaign Sending ────────────────

    public function sendCampaign($subject, $content, $sentBy = null) {
        $emails = $this->getAllActiveEmails();

        if (empty($emails)) {
            return ['success' => false, 'message' => 'No active subscribers found', 'sent_count' => 0, 'failed_count' => 0, 'campaign_id' => null];
        }

        $campaignId = $this->db->insert('newsletter_campaigns', [
            'subject' => $subject,
            'content' => $content,
            'total_subscribers' => count($emails),
            'status' => 'sending',
            'sent_by' => $sentBy,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $sent = 0;
        $failed = 0;

        foreach ($emails as $email) {
            try {
                $ok = $this->email->newsletterCampaign($email, $subject, $content);
                if ($ok) { $sent++; } else { $failed++; }
            } catch (Exception $e) {
                $failed++;
                error_log("Newsletter send failed to {$email}: " . $e->getMessage());
            }
            usleep(100000); // 0.1s throttle
        }

        $this->db->update('newsletter_campaigns', [
            'sent_count' => $sent,
            'failed_count' => $failed,
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $campaignId]);

        return [
            'success' => true,
            'message' => "Campaign sent to {$sent} subscribers" . ($failed ? " ({$failed} failed)" : ''),
            'sent_count' => $sent,
            'failed_count' => $failed,
            'total_subscribers' => count($emails),
            'campaign_id' => $campaignId,
        ];
    }

    // ──────────────── Campaign History ────────────────

    public function getCampaigns($limit = 20, $offset = 0) {
        return $this->db->fetchAll("
            SELECT nc.*, CONCAT(u.first_name, ' ', u.last_name) as sent_by_name
            FROM newsletter_campaigns nc
            LEFT JOIN users u ON nc.sent_by = u.id
            ORDER BY nc.created_at DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset
        ) ?: [];
    }

    public function getCampaignCount() {
        return $this->db->fetchOne("SELECT COUNT(*) as c FROM newsletter_campaigns")['c'] ?? 0;
    }

    // ──────────────── Admin Utilities ────────────────

    public function deleteSubscriber($id) {
        try {
            $sub = $this->db->fetchOne("SELECT email FROM newsletter_subscribers WHERE id = :id", ['id' => $id]);
            $this->db->query("DELETE FROM newsletter_subscribers WHERE id = :id", ['id' => $id]);
            if ($sub) { $this->syncUserNewsletterFlag($sub['email'], false); }
            return true;
        } catch (Exception $e) { return false; }
    }

    public function updateStatus($id, $status) {
        if (!in_array($status, ['subscribed', 'unsubscribed'])) return false;
        try {
            $sql = "UPDATE newsletter_subscribers SET status = :status";
            $params = ['status' => $status, 'id' => $id];
            if ($status === 'subscribed') {
                $sql .= ", subscribed_at = CURRENT_TIMESTAMP, unsubscribed_at = NULL";
            } else {
                $sql .= ", unsubscribed_at = CURRENT_TIMESTAMP";
            }
            $sql .= " WHERE id = :id";
            $this->db->query($sql, $params);

            $sub = $this->db->fetchOne("SELECT email FROM newsletter_subscribers WHERE id = :id", ['id' => $id]);
            if ($sub) { $this->syncUserNewsletterFlag($sub['email'], $status === 'subscribed'); }
            return true;
        } catch (Exception $e) { return false; }
    }

    public function exportToCSV($status = null) {
        $subscribers = $this->getSubscribers($status);
        $csv = "Email,Status,Subscribed At,Unsubscribed At\n";
        foreach ($subscribers as $s) {
            $csv .= '"' . $s['email'] . '","' . $s['status'] . '","' . $s['subscribed_at'] . '","' . ($s['unsubscribed_at'] ?: '') . '"' . "\n";
        }
        return $csv;
    }

    public function getSubscriberByEmail($email) {
        return $this->db->fetchOne("SELECT * FROM newsletter_subscribers WHERE email = :email", ['email' => $email]);
    }
}
