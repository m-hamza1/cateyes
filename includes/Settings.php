<?php
class Settings {
    private $db;
    private $cache = [];

    public function __construct($db) {
        $this->db = $db;
        $this->loadAll();
    }

    private function loadAll() {
        $settings = $this->db->fetchAll("SELECT `key`, `value`, `group` FROM settings");
        foreach ($settings as $setting) {
            $this->cache[$setting['key']] = $setting['value'];
        }
    }

    public function get($key, $default = null) {
        return $this->cache[$key] ?? $default;
    }

    /**
     * Set a single setting. Preserves existing group or sets default.
     */
    public function set($key, $value, $group = null) {
        $existing = $this->db->fetchOne("SELECT id, `group` FROM settings WHERE `key` = :key", ['key' => $key]);

        if ($existing) {
            $updateData = ['value' => $value];
            // Only update group if explicitly provided; don't overwrite existing group
            if ($group !== null) {
                $updateData['group'] = $group;
            }
            $this->db->update('settings', $updateData, '`key` = :key', ['key' => $key]);
        } else {
            $insertData = ['key' => $key, 'value' => $value];
            if ($group !== null) {
                $insertData['group'] = $group;
            }
            $this->db->insert('settings', $insertData);
        }

        $this->cache[$key] = $value;
        return true;
    }

    public function getByGroup($group) {
        return $this->db->fetchAll(
            "SELECT * FROM settings WHERE `group` = :group ORDER BY `key`",
            ['group' => $group]
        );
    }

    /**
     * Update multiple settings with optional group mapping.
     * Keys in $groupMap will be assigned the specified group.
     */
    public function updateMultiple($settings, $groupMap = []) {
        // Auto-detect groups for known appearance/general settings
        $knownAppearanceKeys = [
            'primary_color', 'secondary_color', 'success_color', 'danger_color',
            'warning_color', 'info_color', 'font_family', 'font_size', 'line_height',
            'heading_font', 'paragraph_font',
            'footer_bg_color', 'footer_text_color', 'footer_link_color'
        ];
        $knownGeneralKeys = [
            'site_name', 'site_url', 'admin_email', 'phone', 'address',
            'meta_description', 'meta_keywords', 'logo', 'favicon'
        ];

        foreach ($settings as $key => $value) {
            $group = $groupMap[$key] ?? null;

            // Auto-assign group if not specified
            if ($group === null) {
                if (in_array($key, $knownAppearanceKeys)) {
                    $group = 'appearance';
                } elseif (in_array($key, $knownGeneralKeys)) {
                    $group = 'general';
                }
            }
            $this->set($key, $value, $group);
        }
        return true;
    }
}
