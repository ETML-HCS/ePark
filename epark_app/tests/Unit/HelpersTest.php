<?php

namespace Tests\Unit;

use Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_format_chf_default(): void
    {
        $this->assertEquals('CHF 4.50', format_chf(4.50));
    }

    public function test_format_chf_zero(): void
    {
        $this->assertEquals('CHF 0.00', format_chf(0));
    }

    public function test_format_chf_large_amount(): void
    {
        $this->assertEquals('CHF 1 234.50', format_chf(1234.50));
    }

    public function test_format_chf_custom_decimals(): void
    {
        $this->assertEquals('CHF 4.5', format_chf(4.50, 1));
    }

    public function test_format_chf_no_decimals(): void
    {
        $this->assertEquals('CHF 5', format_chf(4.50, 0));
    }

    public function test_format_chf_negative(): void
    {
        $this->assertEquals('CHF -10.00', format_chf(-10));
    }
}
