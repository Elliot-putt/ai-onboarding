<?php

namespace ElliotPutt\LaravelAiOnboarding\Traits;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ElliotPutt\LaravelAiOnboarding\DTOs\ValidationResult;

trait LaravelValidation
{
    /**
     * Validate user input against Laravel validation rules
     */
    public function validateWithLaravel(string $fieldName, string $value, array $rules): ValidationResult
    {
        try {
            $validator = Validator::make(
                [$fieldName => $value],
                [$fieldName => $rules]
            );

            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                $errorMessage = implode(', ', $errors);
                
                return ValidationResult::failure(
                    $errors,
                    null,
                    "Validation failed: {$errorMessage}"
                );
            }

            return ValidationResult::success();

        } catch (ValidationException $e) {
            return ValidationResult::failure(
                [$e->getMessage()],
                null,
                "Validation exception: {$e->getMessage()}"
            );
        } catch (\Exception $e) {
            return ValidationResult::failure(
                [$e->getMessage()],
                null,
                "Validation error: {$e->getMessage()}"
            );
        }
    }

    /**
     * Get validation rules for a specific field
     */
    protected function getFieldValidationRules(string $fieldName, array $fieldDefinitions): array
    {
        foreach ($fieldDefinitions as $field) {
            if ($field instanceof \ElliotPutt\LaravelAiOnboarding\DTOs\FieldDefinition) {
                if ($field->name === $fieldName) {
                    return $field->validationRules;
                }
            } elseif (is_string($field) && $field === $fieldName) {
                // For backward compatibility with simple field names
                return [];
            }
        }

        return [];
    }

    /**
     * Check if field has validation rules
     */
    protected function fieldHasValidationRules(string $fieldName, array $fieldDefinitions): bool
    {
        $rules = $this->getFieldValidationRules($fieldName, $fieldDefinitions);
        return !empty($rules);
    }
}
