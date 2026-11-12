<?php
// Reads values from the "settings" table (for example: due_soon_days)
class Setting extends Model
{
    public function get($key, $default)
    {
        $value = $this->getValue('SELECT setting_value FROM settings WHERE setting_key = ?', [$key]);
        return ($value === false) ? $default : $value;
    }
}
