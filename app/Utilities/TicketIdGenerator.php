<?php

namespace App\Utilities;

class TicketIdGenerator
{
    /**
     * Generate a random numeric string of specified length.
     */
    public static function generateRandomDigits(int $length): string
    {
        $digits = '';
        for ($i = 0; $i < $length; $i++) {
            $digits .= random_int(0, 9);
        }
        return $digits;
    }

    /**
     * Extract initials from holder name (first letter of each word).
     * 
     * Example: "John Michael Doe" -> "JMD"
     * Example: "Khensani Lekota" -> "KL"
     */
    public static function extractInitials(string $holderName): string
    {
        $words = preg_split('/\s+/', trim($holderName));
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        
        return $initials;
    }

    /**
     * Generate a payment code in the format: P-{INITIALS}-{RANDOM}
     * 
     * Example: P-KL-8592
     * Example: P-JMD-1234
     */
    public static function generatePaymentCode(string $holderName): string
    {
        $initials = self::extractInitials($holderName);
        $random = self::generateRandomDigits(4);
        
        return 'P-' . $initials . '-' . $random;
    }

    /**
     * Generate a secure ticket ID in the format: TYPE-R3DDR2MMR2YYR2C-I
     * 
     * Format breakdown:
     * - TYPE: Ticket type (VIP, FULL, D4, D5, D6)
     * - R3DD: 3 random digits + 2 digit day
     * - R2MM: 2 random digits + 2 digit month
     * - R2YY: 2 random digits + 2 digit year
     * - R2C: 2 random check digits
     * - I: Initials (first letter of each word)
     * 
     * Example: VIP-15415112501391-JMD
     */
    public static function generateSecureId(string $ticketType, string $dob, string $holderName): string
    {
        // Extract initials from holder name
        $initials = self::extractInitials($holderName);
        
        // Parse DOB (format: YYYY-MM-DD)
        $dateParts = explode('-', $dob);
        $year = (int) $dateParts[0];
        $month = (int) $dateParts[1];
        $day = (int) $dateParts[2];
        
        // Extract last 2 digits of year
        $year2 = str_pad($year % 100, 2, '0', STR_PAD_LEFT);
        
        // Format day and month as 2 digits
        $day2 = str_pad($day, 2, '0', STR_PAD_LEFT);
        $month2 = str_pad($month, 2, '0', STR_PAD_LEFT);
        
        // Generate random blocks
        $r3 = self::generateRandomDigits(3);  // 3 random digits
        $r2a = self::generateRandomDigits(2); // 2 random digits for month block
        $r2b = self::generateRandomDigits(2); // 2 random digits for year block
        $r2c = self::generateRandomDigits(2); // 2 random check digits
        
        // Assemble the random block: R3DD + R2MM + R2YY + R2C
        $randomBlock = $r3 . $day2 . $r2a . $month2 . $r2b . $year2 . $r2c;
        
        // Assemble final ID: TYPE-RBLOCK-I
        return strtoupper($ticketType) . '-' . $randomBlock . '-' . $initials;
    }
}

