<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit\Core;

use Doctrine\DBAL\Connection;

final class GpsrManufacturerDataCleaner
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function removeStoredValues(): void
    {
        $address = '$.' . GpsrManufacturerFields::ADDRESS;
        $contact = '$.' . GpsrManufacturerFields::CONTACT;

        $this->connection->executeStatement(
            'UPDATE product_manufacturer_translation
            SET custom_fields = CASE
                WHEN JSON_LENGTH(JSON_REMOVE(custom_fields, :address, :contact)) = 0 THEN NULL
                ELSE JSON_REMOVE(custom_fields, :addressAgain, :contactAgain)
            END
            WHERE custom_fields IS NOT NULL
              AND (
                JSON_CONTAINS_PATH(custom_fields, \'one\', :addressPath)
                OR JSON_CONTAINS_PATH(custom_fields, \'one\', :contactPath)
              )',
            [
                'address' => $address,
                'contact' => $contact,
                'addressAgain' => $address,
                'contactAgain' => $contact,
                'addressPath' => $address,
                'contactPath' => $contact,
            ],
        );
    }
}
