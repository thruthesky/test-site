<?php

declare(strict_types=1);

namespace App\Validation;

final class Validator
{
    public static function validate(array $input, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $input[$field] ?? null;
            foreach ($fieldRules as $rule) {
                if ($rule === 'required' && ($value === null || $value === '')) {
                    $errors[$field][] = 'This field is required.';
                }

                if ($rule === 'email' && $value !== null && $value !== '' && !filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = 'Invalid email address.';
                }

                if (str_starts_with($rule, 'min:') && is_string($value)) {
                    $min = (int) substr($rule, 4);
                    if (mb_strlen($value) < $min) {
                        $errors[$field][] = sprintf('Minimum length is %d.', $min);
                    }
                }

                if (str_starts_with($rule, 'max:') && is_string($value)) {
                    $max = (int) substr($rule, 4);
                    if (mb_strlen($value) > $max) {
                        $errors[$field][] = sprintf('Maximum length is %d.', $max);
                    }
                }
            }
        }

        return $errors;
    }
}

