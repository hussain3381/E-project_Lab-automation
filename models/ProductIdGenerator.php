<?php
// Compose the approved ten-digit Product ID without truncating or guessing components.

declare(strict_types=1);

final class ProductIdGenerator
{
    /**
     * Build product-code(2) + revision(2) + manufacturing-sequence(6).
     */
    public static function generate(string $numericProductCode, string $revisionCode, string $manufacturingNumber): string
    {
        if (!preg_match('/^\d{2}$/', $numericProductCode)) {
            throw new InvalidArgumentException('The registered product-code number must be exactly two digits.');
        }

        if (!preg_match('/^\d{2}$/', $revisionCode)) {
            throw new InvalidArgumentException('Revision must be a two-digit code, such as 01 or 02.');
        }

        if (!preg_match('/^\d{1,6}$/', $manufacturingNumber)) {
            throw new InvalidArgumentException('Manufacturing number must contain one to six digits.');
        }

        $manufacturingSequence = str_pad($manufacturingNumber, 6, '0', STR_PAD_LEFT);
        $productId = $numericProductCode . $revisionCode . $manufacturingSequence;

        if (!preg_match('/^\d{10}$/', $productId)) {
            throw new LogicException('Generated Product ID did not meet the ten-digit requirement.');
        }

        return $productId;
    }
}
