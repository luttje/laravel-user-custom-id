<?php

namespace Luttje\UserCustomId\Tests\Unit\FormatChunks;

use Carbon\Carbon;

final class TimeTest extends FormatChunkTestCase
{
    public function test_year(): void
    {
        $chunk = $this->getChunk('year', []);

        $this->travelTo(now()->year(2023)->month(1)->day(1));

        $this->assertEquals('2023', $this->getNextValue($chunk));
    }

    public function test_month(): void
    {
        $chunk = $this->getChunk('month', []);

        $this->travelTo(now()->year(2023)->month(3)->day(1));

        $this->assertEquals('3', $this->getNextValue($chunk));
    }

    public function test_month_format_attribute(): void
    {
        Carbon::setLocale('en_US');
        $this->travelTo(now()->year(2023)->month(3)->day(1));

        $chunk = $this->getChunk('month', ['F']);

        $this->assertEquals('March', $this->getNextValue($chunk));

        $chunk = $this->getChunk('month', ['M']);

        $this->assertEquals('Mar', $this->getNextValue($chunk));

        $chunk = $this->getChunk('month', ['m']);

        $this->assertEquals('03', $this->getNextValue($chunk));

        $chunk = $this->getChunk('month', ['n']);

        $this->assertEquals('3', $this->getNextValue($chunk));

        $chunk = $this->getChunk('month', ['t']);

        $this->assertEquals('31', $this->getNextValue($chunk));
    }

    public function test_month_format_attribute_dutch(): void
    {
        Carbon::setLocale('nl_NL');
        $this->travelTo(now()->year(2023)->month(3)->day(1));

        $chunk = $this->getChunk('month', ['F']);

        $this->assertEquals('maart', $this->getNextValue($chunk));

        $chunk = $this->getChunk('month', ['M']);

        $this->assertEquals('mrt.', $this->getNextValue($chunk));

        $chunk = $this->getChunk('month', ['m']);

        $this->assertEquals('03', $this->getNextValue($chunk));

        $chunk = $this->getChunk('month', ['n']);

        $this->assertEquals('3', $this->getNextValue($chunk));

        $chunk = $this->getChunk('month', ['t']);

        $this->assertEquals('31', $this->getNextValue($chunk));
    }

    public function test_day(): void
    {
        $chunk = $this->getChunk('day', []);

        $this->travelTo(now()->year(2023)->month(3)->day(5));

        $this->assertEquals('5', $this->getNextValue($chunk));
    }

    public function test_day_of_week(): void
    {
        $chunk = $this->getChunk('weekday', []);

        Carbon::setLocale('en_US');

        $this->travelTo(now()->year(2023)->month(3)->day(5));

        $this->assertEquals('Sunday', $this->getNextValue($chunk));

        Carbon::setLocale('nl_NL');

        $this->assertEquals('zondag', $this->getNextValue($chunk));
    }

    public function test_hour(): void
    {
        $chunk = $this->getChunk('hour', []);

        $this->travelTo(now()->year(2023)->month(3)->day(5)->hour(12));

        $this->assertEquals('12', $this->getNextValue($chunk));
    }

    public function test_minute(): void
    {
        $chunk = $this->getChunk('minute', []);

        $this->travelTo(now()->year(2023)->month(3)->day(5)->hour(12)->minute(34));

        $this->assertEquals('34', $this->getNextValue($chunk));
    }

    public function test_second(): void
    {
        $chunk = $this->getChunk('second', []);

        $this->travelTo(now()->year(2023)->month(3)->day(5)->hour(12)->minute(34)->second(56));

        $this->assertEquals('56', $this->getNextValue($chunk));
    }

    public function test_millisecond(): void
    {
        $chunk = $this->getChunk('millisecond', []);

        $this->travelTo(now()->year(2023)->month(3)->day(5)->hour(12)->minute(34)->second(56)->millisecond(789));

        $this->assertEquals('789', $this->getNextValue($chunk));
    }
}
