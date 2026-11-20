<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit\Test\Unit;

use EschSoftware\PflichtDatenCockpit\Core\Rule\GpsrManufacturerRule;
use EschSoftware\PflichtDatenCockpit\Core\Rule\ProductCheckInput;
use EschSoftware\PflichtDatenCockpit\Core\Rule\RuleFinding;
use PHPUnit\Framework\TestCase;

final class GpsrManufacturerRuleTest extends TestCase
{
    private GpsrManufacturerRule $rule;

    protected function setUp(): void
    {
        $this->rule = new GpsrManufacturerRule();
    }

    public function testMissingManufacturerIsTheOnlyGap(): void
    {
        $finding = $this->rule->evaluate(new ProductCheckInput(false, null, null, null));

        self::assertSame(RuleFinding::STATUS_OPEN, $finding->status);
        self::assertSame([GpsrManufacturerRule::MISSING_MANUFACTURER], $finding->missing);
        self::assertTrue($finding->blocksCheckout());
    }

    public function testCompleteManufacturerIsOk(): void
    {
        $finding = $this->rule->evaluate(new ProductCheckInput(
            true,
            'Vollständig GmbH',
            "Musterweg 12\n50667 Köln",
            'kontakt@pdc-example.test',
        ));

        self::assertSame(RuleFinding::STATUS_OK, $finding->status);
        self::assertSame([], $finding->missing);
        self::assertFalse($finding->blocksCheckout());
    }

    public function testBlankNameAddressAndContactAreReportedTogether(): void
    {
        $finding = $this->rule->evaluate(new ProductCheckInput(true, '  ', " \n", 'keine-mail'));

        self::assertSame(
            [
                GpsrManufacturerRule::MISSING_NAME,
                GpsrManufacturerRule::MISSING_ADDRESS,
                GpsrManufacturerRule::MISSING_CONTACT,
            ],
            $finding->missing,
        );
    }

    public function testWhitespaceAddressIsMissing(): void
    {
        $finding = $this->rule->evaluate(new ProductCheckInput(
            true,
            'Lampe GmbH',
            '   ',
            'lampe@pdc-example.test',
        ));

        self::assertSame([GpsrManufacturerRule::MISSING_ADDRESS], $finding->missing);
    }

    public function testInvalidEmailIsMissingContact(): void
    {
        $finding = $this->rule->evaluate(new ProductCheckInput(
            true,
            'Kabel GmbH',
            'Hafenstraße 1, 20457 Hamburg',
            'kontakt-ohne-at',
        ));

        self::assertSame([GpsrManufacturerRule::MISSING_CONTACT], $finding->missing);
    }
}
