<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Service;

use Magento\Framework\Exception\LocalizedException;

class BoxDataValidator
{
    public function validate(array $data): array
    {
        $name = $data['description_ua'] ?? null;
        if (!is_string($name) || trim($name) === '' || mb_strlen(trim($name)) > 255) {
            throw new LocalizedException(__('Enter a box name of up to 255 characters.'));
        }
        $result = ['description_ua' => trim($name)];
        foreach (['length', 'width', 'height', 'max_weight'] as $field) {
            $result[$field] = $this->number($data[$field] ?? null, $field, true);
        }
        foreach (['length', 'width', 'height'] as $dimension) {
            $field = 'inner_' . $dimension;
            $value = $data[$field] ?? null;
            $result[$field] = $value === null || $value === '' ? null : $this->number($value, $field, true);
            if ($result[$field] !== null && $result[$field] > $result[$dimension]) {
                throw new LocalizedException(__('Inner dimensions must not exceed outer dimensions.'));
            }
        }
        $result['empty_weight'] = $this->number(($data['empty_weight'] ?? '') === '' ? 0 : $data['empty_weight'], 'empty_weight', false);
        if ($result['empty_weight'] >= $result['max_weight']) {
            throw new LocalizedException(__('Empty box weight must be less than maximum weight.'));
        }
        $enabled = $data['boxpacker_enabled'] ?? 1;
        if (!in_array($enabled, [0, 1, '0', '1', false, true], true)) {
            throw new LocalizedException(__('Invalid BoxPacker enabled value.'));
        }
        $result['boxpacker_enabled'] = (int)$enabled;
        return $result;
    }

    private function number(mixed $value, string $field, bool $positive): float
    {
        $label = __(match ($field) {
            'length' => 'Length (mm)', 'width' => 'Width (mm)', 'height' => 'Height (mm)',
            'max_weight' => 'Maximum Weight (kg)', 'empty_weight' => 'Empty Box Weight (kg)',
            'inner_length' => 'Inner Length (mm)', 'inner_width' => 'Inner Width (mm)',
            'inner_height' => 'Inner Height (mm)',
        });
        if ((!is_string($value) && !is_int($value) && !is_float($value)) || !is_numeric($value)) {
            throw new LocalizedException(__('Enter a valid numeric value for %1.', $label));
        }
        $number = (float)$value;
        if (!is_finite($number) || $number > 999999999.999 || $number < 0 || ($positive && $number < 0.001)) {
            throw new LocalizedException(__('Enter a valid numeric value for %1.', $label));
        }
        return round($number, 3);
    }
}
