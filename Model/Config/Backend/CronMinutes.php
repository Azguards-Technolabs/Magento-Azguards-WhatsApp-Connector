<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Config\Backend;

use Magento\Framework\App\Config\Value;

/**
 * Backend model for admin Cron schedule fields stored as "every N minutes".
 *
 * Converts the admin-facing integer (minutes) into a valid 5-part cron expression
 * before persisting to core_config_data, so that crontab.xml <config_path> gets
 * a real schedule such as "* * * * *" or "* /5 * * * *".
 *
 * On load, the stored expression is converted back to a readable minutes integer
 * for the admin field display.
 */
class CronMinutes extends Value
{
    /**
     * Convert the admin integer (minutes) to a cron expression before saving.
     *
     * @return $this
     */
    public function beforeSave(): self
    {
        $minutes = (int) $this->getValue();

        if ($minutes <= 0) {
            $minutes = 1;
        }

        // Every minute → "* * * * *"
        // Every N minutes → "*/N * * * *"
        $cronExpression = $minutes === 1 ? '* * * * *' : '*/' . $minutes . ' * * * *';
        $this->setValue($cronExpression);

        return parent::beforeSave();
    }

    /**
     * Convert the stored cron expression back to a minutes integer for admin display.
     *
     * @return $this
     */
    protected function _afterLoad(): self
    {
        $value = (string) $this->getValue();

        if ($value === '* * * * *') {
            // "Every minute" → display as 1
            $this->setValue(1);
        } elseif (preg_match('/^\*\/(\d+) \* \* \* \*$/', $value, $matches)) {
            // "Every N minutes" → display the N
            $this->setValue((int) $matches[1]);
        } else {
            // Unrecognised expression (e.g. manually edited): default to 1 for display
            $this->setValue(1);
        }

        return parent::_afterLoad();
    }
}
